<?php

namespace App\Http\Controllers;

use App\Models\InSppm;
use App\Models\InDetail;
use App\Models\InLog;
use App\Models\InStock;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Stock;
use App\Models\OutStock; // Ditambahkan untuk pengecekan relasi Outbound
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
    private $inboundMode = 'mode-1'; 

    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        $category_id = $request->input('category_id');
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');
        $perPage = $limit === 'all' ? 999999 : $limit;

        $sppms = InSppm::with([
                'category', 'warehouse', 'details.material', 
                'logs' => function($q) { $q->orderBy('batch_number', 'asc'); }, 
                'logs.stocks', 'updater', 'creator'  
            ])
            ->when($search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('sppm_no', 'like', '%' . $search . '%')
                      ->orWhereHas('details.material', function($q2) use ($search) {
                          $q2->where('name', 'like', '%' . $search . '%')
                             ->orWhere('code', 'like', '%' . $search . '%');
                      })
                      ->orWhereHas('details', function($q3) use ($search) {
                          // Karena di DB tersimpan murni tanpa titik, kita hapus titik pada kata kunci pencarian
                          $cleanSearch = str_replace('.', '', $search);
                          $q3->where('sppm_serial_start', 'like', '%' . $cleanSearch . '%')
                             ->orWhere('sppm_serial_end', 'like', '%' . $cleanSearch . '%')
                             ->orWhere('sppm_serial_prefix', 'like', '%' . $search . '%');
                      });
                });
            })
            ->when($category_id, function ($query, $category_id) {
                return $query->where('material_category_id', $category_id);
            })
            ->when($bulan, function ($query, $bulan) {
                return $query->whereMonth('sppm_date', $bulan);
            })
            ->when($tahun, function ($query, $tahun) {
                return $query->whereYear('sppm_date', $tahun);
            })
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage)
            ->withQueryString();

        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $availableYears = InSppm::selectRaw('YEAR(sppm_date) as year')->distinct()->orderBy('year', 'desc')->pluck('year');

        return view('inbound.index', compact('sppms', 'categories', 'search', 'limit', 'category_id', 'bulan', 'tahun', 'availableYears'));
    }

    public function create()
    {
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $warehouses = Warehouse::orderBy('id', 'asc')->get(); 
        $inboundMode = $this->inboundMode;
        // Di dalam method create/edit InboundController
        $inboundSetting = \App\Models\Setting::where('key', 'inbound_mode')->value('value') ?? 'mode-1';
        $maxBatchSetting = \App\Models\Setting::where('key', 'max_batch')->value('value') ?? 5;

        return view('inbound.form', compact('categories', 'warehouses', 'inboundMode', 'inboundSetting', 'maxBatchSetting'))->with('inbound', null);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sppm_no'              => 'required|string|max:255|unique:in_sppms,sppm_no',
            'sppm_date'            => 'required|date',
            'material_category_id' => 'required|exists:material_categories,id',
            'warehouse_id'         => 'required|exists:warehouses,id',
            'inbound_mode'         => 'required|string|in:mode-1,mode-2',
            'items'                => 'required|array',
            'items.*.material_id'  => 'required|exists:materials,id',
            'items.*.target_qty'   => 'nullable|numeric|min:0', 
        ]);

        $currentMode = $request->input('inbound_mode');
        $batchDate = $currentMode === 'mode-2' ? $request->input('batch_date') : $request->sppm_date;

        // 1. VALIDASI OVERLAP NOMOR SERI
        foreach ($request->items as $item) {
            $prefix = $item['sppm_serial_prefix'] ?? null;
            $startStr = $item['sppm_serial_start'] ?? null;
            $endStr = $item['sppm_serial_end'] ?? null;

            if ($startStr && $endStr) {
                $start = (int) str_replace('.', '', $startStr);
                $end = (int) str_replace('.', '', $endStr);

                $isOverlap = Stock::where('material_id', $item['material_id'])
                    ->where('prefix', $prefix)
                    ->where('seri_awal', '<=', $end)
                    ->where('seri_akhir', '>=', $start)
                    ->exists();

                if ($isOverlap) {
                    return back()->withInput()->with('error', "GAGAL! Terdapat rentang Nomor Seri yang tumpang tindih (duplikat) pada Master Stock untuk prefix {$prefix}.");
                }
            }
        }

        // 2. SIMPAN DATA
        DB::transaction(function () use ($request, $currentMode, $batchDate) {
            $sppm = InSppm::create([
                'sppm_no'              => $request->sppm_no,
                'sppm_date'            => $request->sppm_date,
                'material_category_id' => $request->material_category_id,
                'warehouse_id'         => $request->warehouse_id,
                'notes'                => $request->notes_manifes,
                'status'               => $currentMode === 'mode-1' ? 'completed' : 'pending',
                'created_by'           => auth()->id(),
                'updated_by'           => auth()->id()
            ]);

            $log = InLog::create([
                'in_sppm_id'   => $sppm->id,
                'batch_number' => 1,
                'receive_date' => $batchDate,
                'receiver_name'=> auth()->user()->name ?? 'Admin Gudang',
                'notes'        => $request->batch_notes ?? 'Penerimaan awal Tahap 1.'
            ]);

            $isAllCompleted = true;

            foreach ($request->items as $item) {
                if (isset($item['target_qty']) && $item['target_qty'] > 0) {
                    
                    // Filter String menjadi Integer
                    $sppmPrefix = $item['sppm_serial_prefix'] ?? null;
                    $sppmStart = isset($item['sppm_serial_start']) ? (int) str_replace('.', '', $item['sppm_serial_start']) : null;
                    $sppmEnd = isset($item['sppm_serial_end']) ? (int) str_replace('.', '', $item['sppm_serial_end']) : null;

                    $realPrefix = $item['serial_prefix'] ?? null;
                    $realStart = isset($item['serial_start']) ? (int) str_replace('.', '', $item['serial_start']) : null;
                    $realEnd = isset($item['serial_end']) ? (int) str_replace('.', '', $item['serial_end']) : null;

                    InDetail::create([
                        'in_sppm_id'        => $sppm->id,
                        'material_id'       => $item['material_id'],
                        'target_qty'        => $item['target_qty'],
                        'qty_huruf'         => $item['qty_huruf'] ?? null,
                        'harga_satuan'      => $item['harga_satuan'] ?? 0,
                        'harga_total'       => $item['harga_total'] ?? 0,
                        'sppm_serial_prefix'=> $sppmPrefix,
                        'sppm_serial_start' => $sppmStart,
                        'sppm_serial_end'   => $sppmEnd,
                    ]);

                    $qtyReceived = $currentMode === 'mode-1' ? $item['target_qty'] : ($item['qty_received'] ?? 0);
                    
                    if ($qtyReceived < $item['target_qty']) {
                        $isAllCompleted = false;
                    }

                    if ($qtyReceived > 0) {
                        InStock::create([
                            'in_log_id'    => $log->id,
                            'material_id'  => $item['material_id'],
                            'qty_received' => $qtyReceived,
                            'serial_prefix'=> $currentMode === 'mode-2' ? $realPrefix : $sppmPrefix,
                            'serial_start' => $currentMode === 'mode-2' ? $realStart : $sppmStart,
                            'serial_end'   => $currentMode === 'mode-2' ? $realEnd : $sppmEnd,
                        ]);

                        Stock::create([
                            'no_surat_masuk' => $sppm->sppm_no,
                            'tgl_masuk'      => $batchDate,
                            'material_id'    => $item['material_id'],
                            'warehouse_id'   => $request->warehouse_id,
                            'prefix'         => $currentMode === 'mode-2' ? $realPrefix : $sppmPrefix,
                            'seri_awal'      => $currentMode === 'mode-2' ? $realStart : $sppmStart,
                            'seri_akhir'     => $currentMode === 'mode-2' ? $realEnd : $sppmEnd,
                            'qty'            => $qtyReceived,
                            'harga_satuan'   => $item['harga_satuan'] ?? 0,
                            'total_harga'    => ($item['harga_satuan'] ?? 0) * $qtyReceived,
                            'status'         => '-',
                            'keterangan'     => $request->batch_notes ?? 'Penerimaan Tahap 1',
                        ]);
                    }
                }
            }

            if ($currentMode === 'mode-2') {
                $sppm->update(['status' => $isAllCompleted ? 'completed' : 'partial']);
            }
        });

        $redirect = $request->input('submit_action') === 'save_new' ? route('inbound.create') : route('inbound.index');
        return redirect($redirect)->with('success', 'Data berhasil diverifikasi dan disimpan.');
    }

    public function edit($id)
    {
        $inbound = InSppm::with(['details.material', 'logs' => function($q){
            $q->orderBy('batch_number', 'asc');
        }, 'logs.stocks'])->findOrFail($id);
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $warehouses = Warehouse::orderBy('id', 'asc')->get();
        $inboundMode = $this->inboundMode;

        return view('inbound.form', compact('inbound', 'categories', 'warehouses', 'inboundMode'));
    }

    public function update(Request $request, $id)
    {
        $sppm = InSppm::with('details', 'logs.stocks')->findOrFail($id);
        $currentMode = $request->input('inbound_mode');
        $oldSppmNo = $sppm->sppm_no;

        // 1. VALIDASI OVERLAP NOMOR SERI SAAT UPDATE
        foreach ($request->items as $item) {
            $prefix = $item['sppm_serial_prefix'] ?? null;
            $startStr = $item['sppm_serial_start'] ?? null;
            $endStr = $item['sppm_serial_end'] ?? null;

            if ($startStr && $endStr) {
                $start = (int) str_replace('.', '', $startStr);
                $end = (int) str_replace('.', '', $endStr);

                $isOverlap = Stock::where('material_id', $item['material_id'])
                    ->where('no_surat_masuk', '!=', $sppm->sppm_no) // Kecualikan dokumen saat ini
                    ->where('prefix', $prefix)
                    ->where('seri_awal', '<=', $end)
                    ->where('seri_akhir', '>=', $start)
                    ->exists();

                if ($isOverlap) {
                    return back()->withInput()->with('error', "GAGAL UPDATE! Rentang Seri duplikat dengan dokumen surat masuk lain pada prefix {$prefix}.");
                }
            }
        }

        if ($currentMode === 'mode-2') {
            $request->validate([
                'batch_date'          => 'required|date',
                'items'               => 'required|array',
                'items.*.material_id' => 'required|exists:materials,id',
                'items.*.qty_received'=> 'nullable|numeric|min:0'
            ]);

            DB::transaction(function () use ($request, $sppm) {
                $nextBatch = $sppm->logs()->max('batch_number') + 1;

                $log = InLog::create([
                    'in_sppm_id'   => $sppm->id,
                    'batch_number' => $nextBatch,
                    'receive_date' => $request->batch_date,
                    'receiver_name'=> auth()->user()->name ?? 'Admin Gudang',
                    'notes'        => $request->batch_notes ?? "Penerimaan fisik parsial Tahap {$nextBatch}."
                ]);

                $isAllCompleted = true;

                foreach ($request->items as $item) {
                    $qtyReceived = $item['qty_received'] ?? 0;

                    if ($qtyReceived > 0) {
                        $realPrefix = $item['serial_prefix'] ?? null;
                        $realStart = isset($item['serial_start']) ? (int) str_replace('.', '', $item['serial_start']) : null;
                        $realEnd = isset($item['serial_end']) ? (int) str_replace('.', '', $item['serial_end']) : null;

                        InStock::create([
                            'in_log_id'    => $log->id,
                            'material_id'  => $item['material_id'],
                            'qty_received' => $qtyReceived,
                            'serial_prefix'=> $realPrefix,
                            'serial_start' => $realStart,
                            'serial_end'   => $realEnd,
                        ]);

                        Stock::create([
                            'no_surat_masuk' => $sppm->sppm_no,
                            'tgl_masuk'      => $request->batch_date,
                            'material_id'    => $item['material_id'],
                            'warehouse_id'   => $sppm->warehouse_id,
                            'prefix'         => $realPrefix,
                            'seri_awal'      => $realStart,
                            'seri_akhir'     => $realEnd,
                            'qty'            => $qtyReceived,
                            'harga_satuan'   => $item['harga_satuan'] ?? 0,
                            'total_harga'    => ($item['harga_satuan'] ?? 0) * $qtyReceived,
                            'status'         => '-',
                            'keterangan'     => $request->batch_notes ?? "Penerimaan Tahap {$nextBatch}."
                        ]);
                    }

                    $detail = $sppm->details->where('material_id', $item['material_id'])->first();
                    $target = $detail ? $detail->target_qty : 0;
                    
                    $pastReceived = 0;
                    foreach ($sppm->logs as $oldLog) {
                        $st = $oldLog->stocks->where('material_id', $item['material_id'])->first();
                        $pastReceived += $st ? $st->qty_received : 0;
                    }

                    if (($pastReceived + $qtyReceived) < $target) {
                        $isAllCompleted = false;
                    }
                }

                $sppm->update([
                    'status'     => $isAllCompleted ? 'completed' : 'partial',
                    'updated_by' => auth()->id()
                ]);
            });

            return redirect()->route('inbound.index')->with('success', 'Penerimaan fisik Tahap Baru berhasil dicatat.');
        }

        $request->validate([
            'sppm_no'      => 'required|string|max:255|unique:in_sppms,sppm_no,' . $sppm->id,
            'sppm_date'    => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items'        => 'required|array',
        ]);

        DB::transaction(function () use ($request, $sppm, $oldSppmNo) {
            $sppm->update([
                'sppm_no'      => $request->sppm_no,
                'sppm_date'    => $request->sppm_date,
                'warehouse_id' => $request->warehouse_id,
                'notes'        => $request->notes_manifes,
                'updated_by'   => auth()->id()
            ]);

            if ($oldSppmNo !== $request->sppm_no) {
                Stock::where('no_surat_masuk', $oldSppmNo)->update(['no_surat_masuk' => $request->sppm_no]);
            }

            foreach ($request->items as $item) {
                if (isset($item['target_qty'])) {
                    
                    $sppmPrefix = $item['sppm_serial_prefix'] ?? null;
                    $sppmStart = isset($item['sppm_serial_start']) ? (int) str_replace('.', '', $item['sppm_serial_start']) : null;
                    $sppmEnd = isset($item['sppm_serial_end']) ? (int) str_replace('.', '', $item['sppm_serial_end']) : null;

                    InDetail::where('in_sppm_id', $sppm->id)
                        ->where('material_id', $item['material_id'])
                        ->update([
                            'target_qty'        => $item['target_qty'],
                            'qty_huruf'         => $item['qty_huruf'] ?? null,
                            'harga_satuan'      => $item['harga_satuan'] ?? 0,
                            'harga_total'       => $item['harga_total'] ?? 0,
                            'sppm_serial_prefix'=> $sppmPrefix,
                            'sppm_serial_start' => $sppmStart,
                            'sppm_serial_end'   => $sppmEnd,
                        ]);

                    $firstLog = $sppm->logs()->where('batch_number', 1)->first();
                    if ($firstLog) {
                        if ($item['target_qty'] > 0) {
                            InStock::updateOrCreate(
                                ['in_log_id' => $firstLog->id, 'material_id' => $item['material_id']],
                                [
                                    'qty_received' => $item['target_qty'],
                                    'serial_prefix'=> $sppmPrefix,
                                    'serial_start' => $sppmStart,
                                    'serial_end'   => $sppmEnd
                                ]
                            );

                            Stock::updateOrCreate(
                                [
                                    'no_surat_masuk' => $request->sppm_no,
                                    'material_id'    => $item['material_id']
                                ],
                                [
                                    'tgl_masuk'    => $request->sppm_date,
                                    'warehouse_id' => $request->warehouse_id,
                                    'prefix'       => $sppmPrefix,
                                    'seri_awal'    => $sppmStart,
                                    'seri_akhir'   => $sppmEnd,
                                    'qty'          => $item['target_qty'],
                                    'harga_satuan' => $item['harga_satuan'] ?? 0,
                                    'total_harga'  => ($item['harga_satuan'] ?? 0) * $item['target_qty'],
                                    'status'       => '-',
                                    'keterangan'   => $request->notes_manifes
                                ]
                            );
                        } else {
                            InStock::where('in_log_id', $firstLog->id)->where('material_id', $item['material_id'])->delete();
                            Stock::where('no_surat_masuk', $request->sppm_no)->where('material_id', $item['material_id'])->delete();
                        }
                    }
                }
            }
        });

        return redirect()->route('inbound.index')->with('success', 'Pembaruan Dokumen & Seri berhasil disimpan.');
    }

    public function destroy($id)
    {
        $sppm = InSppm::with('logs')->findOrFail($id);

        // PENGECEKAN: Cari ID stock yang berkaitan dengan SPPM ini di tabel stocks
        $stockIds = Stock::where('no_surat_masuk', $sppm->sppm_no)->pluck('id');
        
        // Cek apakah ada satupun ID stock tersebut yang sudah masuk ke tabel out_stocks
        $isUsedInOutbound = OutStock::whereIn('stock_id', $stockIds)->exists();

        // Jika sudah dipakai di outbound, kembalikan ke halaman sebelumnya dengan pesan error merah
        if ($isUsedInOutbound) {
            return redirect()->back()->with('error', 'GAGAL MENGHAPUS! Data Inbound ini tidak dapat dihapus karena barang di dalamnya sudah didistribusikan di menu Outbound. Silakan hapus/batalkan data Outbound terkait terlebih dahulu.');
        }

        DB::transaction(function () use ($sppm) {
            Stock::where('no_surat_masuk', $sppm->sppm_no)->delete();
            foreach ($sppm->logs as $log) {
                InStock::where('in_log_id', $log->id)->delete();
            }
            $sppm->logs()->delete();
            $sppm->details()->delete();
            $sppm->delete();
        });

        return redirect()->route('inbound.index')->with('success', 'Dokumen dan Master Stock terkait berhasil dihapus.');
    }

    public function getMaterialsByCategory($category_id)
    {
        $materials = Material::with(['children' => function($q) { $q->orderBy('nomor_urut', 'asc'); }])
            ->where('material_category_id', $category_id)->whereNull('parent_id')->orderBy('nomor_urut', 'asc')->get();
        return response()->json($materials);
    }

    /// Fungsi untuk memproses pembuatan Gudang Baru secara AJAX
    public function storeWarehouseAjax(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255|unique:warehouses,name',
            'code'       => 'nullable|string|max:255',
            'lokasi'     => 'nullable|string|max:255',
            'keterangan' => 'nullable|string'
        ]);

        // Auto-generate nomor_urut berdasarkan nilai tertinggi saat ini
        $lastUrut = Warehouse::max('nomor_urut');
        $nextUrut = $lastUrut ? $lastUrut + 1 : 1;

        $warehouse = Warehouse::create([
            'nomor_urut' => $nextUrut,
            'name'       => $request->name,
            'code'       => $request->code,
            'lokasi'     => $request->lokasi,
            'keterangan' => $request->keterangan,
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Gudang berhasil ditambahkan',
            'warehouse' => $warehouse
        ]);
    }

    // --- FUNGSI DOWNLOAD TEMPLATE EXCEL (Sesuai Nomor Urut Parent & Child) ---
    public function downloadTemplate(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:material_categories,id']);
        
        $categoryId = $request->input('category_id');
        $category = MaterialCategory::findOrFail($categoryId);

        // 1. Ambil Material Induk (Parent) berurutan, berserta anak-anaknya yang juga berurutan
        $topLevelMaterials = Material::with(['children' => function($q) {
                $q->orderBy('nomor_urut', 'asc');
            }])
            ->where('material_category_id', $categoryId)
            ->whereNull('parent_id')
            ->orderBy('nomor_urut', 'asc')
            ->get();

        if ($topLevelMaterials->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak bisa mengunduh template: Kategori ini belum memiliki data Master Barang.');
        }

        // 2. Susun kolom data agar lurus/rata secara hierarki untuk pemetaan data
        $flatMaterials = collect();
        $hasChildren = false; // Penanda apakah kita butuh 3 baris header atau 2
        
        foreach ($topLevelMaterials as $parent) {
            if ($parent->children->count() > 0) {
                $hasChildren = true;
                foreach ($parent->children as $child) {
                    $flatMaterials->push($child);
                }
            } else {
                $flatMaterials->push($parent);
            }
        }

        $fileName = 'Template_Import_' . str_replace(' ', '_', strtoupper($category->name)) . '_' . date('Ymd') . '.xls';

        $headers = [
            "Content-type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($topLevelMaterials, $flatMaterials, $category, $hasChildren) {
            echo '<table border="1" style="font-family: Arial; font-size: 10px; text-align: center;">';
            
            $headerRows = $hasChildren ? 3 : 2;

            // --- BARIS 1: HEADER UTAMA (STATIK) ---
            echo '<tr style="font-weight: bold; background-color: #f8f9fa;">';
            echo '<th rowspan="'.$headerRows.'" style="width: 40px;">NO</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 120px;">TGL PENERIMAAN<br>(YYYY-MM-DD)</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 120px;">TGL SPPM<br>(YYYY-MM-DD)</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 180px;">NO. SPPM KORLANTAS</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 180px;">NOMOR SERI</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">NO. BAPPM</th>';
            
            // Header Dinamis Barang (Menyatu di atas)
            echo '<th colspan="'.$flatMaterials->count().'" style="background-color: #d1e7dd;">RINCIAN BARANG KATEGORI: '.strtoupper($category->name).'</th>';
            echo '</tr>';

            // --- BARIS 2: NAMA INDUK / MANDIRI ---
            echo '<tr style="font-weight: bold; background-color: #d1e7dd;">';
            foreach ($topLevelMaterials as $mat) {
                if ($mat->children->count() > 0) {
                    // Jika Parent, jangkau sejumlah anaknya ke samping
                    echo '<th colspan="'.$mat->children->count().'" style="background-color: #badce3;">'.strtoupper($mat->name).'</th>';
                } else {
                    // Jika Mandiri, tembus kebawah (rowspan) jika tabel punya baris ketiga
                    $rs = $hasChildren ? 2 : 1;
                    if ($rs > 1) {
                        echo '<th rowspan="'.$rs.'">'.strtoupper($mat->name).'</th>';
                    } else {
                        echo '<th>'.strtoupper($mat->name).'</th>';
                    }
                }
            }
            echo '</tr>';

            // --- BARIS 3: NAMA ANAK (Jika Ada) ---
            if ($hasChildren) {
                echo '<tr style="font-weight: bold; background-color: #e2e3e5;">';
                foreach ($topLevelMaterials as $mat) {
                    if ($mat->children->count() > 0) {
                        foreach ($mat->children as $child) {
                            echo '<th>'.$child->name.'</th>';
                        }
                    }
                }
                echo '</tr>';
            }

            // --- BARIS 4: CONTOH DATA ---
            echo '<tr>';
            echo '<td>1</td>';
            echo '<td>'.date('Y-m-d').'</td>';
            echo '<td>'.date('Y-m-d').'</td>';
            echo '<td>SPPM/001/VII/'.date('Y').'</td>';
            echo '<td>H. 01.300.001 - 01.400.000</td>';
            echo '<td>BAPPM-001</td>';
            foreach ($flatMaterials as $mat) {
                echo '<td>500</td>'; // Contoh qty
            }
            echo '</tr>';
            
            echo '</table>';
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- FUNGSI HANDLE UPLOAD EXCEL (Sinkron Kolom Baru, Auto-Delimiter & Bersihkan Prefix) ---
    public function importExcel(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:material_categories,id',
            'excel_file'  => 'required|file|mimes:csv,txt'
        ]);

        $categoryId = $request->input('category_id');
        $file = $request->file('excel_file');

        // 1. Ambil Material Induk (Parent) berurutan, berserta anak-anaknya yang juga berurutan
        $topLevelMaterials = Material::with(['children' => function($q) {
                $q->orderBy('nomor_urut', 'asc');
            }])
            ->where('material_category_id', $categoryId)
            ->whereNull('parent_id')
            ->orderBy('nomor_urut', 'asc')
            ->get();

        if ($topLevelMaterials->isEmpty()) {
            return redirect()->back()->with('error', 'Kategori ini tidak memiliki daftar material.');
        }

        // 2. Ratakan daftar material untuk mencocokkan Index Kolom Excel
        $flatMaterials = collect();
        $hasChildren = false;
        
        foreach ($topLevelMaterials as $parent) {
            if ($parent->children->count() > 0) {
                $hasChildren = true;
                foreach ($parent->children as $child) {
                    $flatMaterials->push($child);
                }
            } else {
                $flatMaterials->push($parent);
            }
        }

        // Jika Excel memiliki baris anak (3 baris header), sistem wajib melompati 3 baris
        $headerRowsToSkip = $hasChildren ? 3 : 2;

        // Fungsi Pemisah Nomor Seri dengan Pembersih Prefix
        $parseSerial = function($string) {
            $result = ['prefix' => null, 'start' => null, 'end' => null];
            if (empty(trim($string)) || trim($string) === '-') return $result;

            $parts = explode('-', $string);
            if (count($parts) == 2) {
                $left = trim($parts[0]);
                if (preg_match('/^([a-zA-Z\.\s]+)?([\d\.]+)$/', $left, $matches)) {
                    // Membersihkan prefix dari titik, spasi, atau karakter selain alfabet
                    if (isset($matches[1])) {
                        $cleanPrefix = preg_replace('/[^a-zA-Z]/', '', $matches[1]);
                        $result['prefix'] = $cleanPrefix !== '' ? strtoupper($cleanPrefix) : null;
                    } else {
                        $result['prefix'] = null;
                    }
                    
                    $result['start'] = (int) str_replace('.', '', $matches[2]);
                }
                $result['end'] = (int) str_replace('.', '', trim($parts[1]));
            }
            return $result;
        };

        // Otomatis mendeteksi End of Line (berguna jika file dari Mac/Linux)
        ini_set('auto_detect_line_endings', true);
        
        $handle = fopen($file->getPathname(), "r");
        
        // Deteksi Otomatis Delimiter (Koma atau Titik Koma)
        $firstLine = fgets($handle);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($handle); // Kembalikan pointer baca ke baris pertama

        $rowCounter = 0;
        $insertedDataCount = 0;

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
                $rowCounter++;
                if ($rowCounter <= $headerRowsToSkip) continue; 

                // Lewati jika baris kosong
                if (count($data) < 6) continue;

                // --- POSISI KOLOM STATIK (0 s/d 5) ---
                $tglPenerimaanStr = $data[1] ?? null;
                $tglSppmStr       = $data[2] ?? null;
                $noSppm           = trim($data[3] ?? '');
                $nomorSeriStr     = trim($data[4] ?? '');
                $noBappm          = trim($data[5] ?? '');

                if (empty($noSppm) || empty(trim($tglPenerimaanStr))) continue; 

                $tglPenerimaan = date('Y-m-d', strtotime($tglPenerimaanStr));
                $tglSppm = !empty(trim($tglSppmStr)) ? date('Y-m-d', strtotime($tglSppmStr)) : $tglPenerimaan;

                $defaultWarehouse = Warehouse::first();
                $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

                $existingSppm = InSppm::where('sppm_no', $noSppm)->first();
                if ($existingSppm) {
                    throw new \Exception("Ditemukan duplikat SPPM di dalam database untuk nomor: {$noSppm}");
                }

                $sppm = InSppm::create([
                    'sppm_no'              => $noSppm,
                    'sppm_date'            => $tglSppm,
                    'no_bappm'             => $noBappm, 
                    'material_category_id' => $categoryId,
                    'warehouse_id'         => $warehouseId,
                    'notes'                => 'Import otomatis via CSV',
                    'status'               => 'completed',
                    'created_by'           => auth()->id(),
                    'updated_by'           => auth()->id()
                ]);

                $log = InLog::create([
                    'in_sppm_id'   => $sppm->id,
                    'batch_number' => 1,
                    'receive_date' => $tglPenerimaan,
                    'receiver_name'=> auth()->user()->name ?? 'Admin Gudang',
                    'notes'        => 'Import otomatis via CSV'
                ]);

                $serialParsed = $parseSerial($nomorSeriStr);

                // --- POSISI KOLOM DINAMIS BARANG (Mulai Indeks 6 ke Kanan) ---
                foreach ($flatMaterials as $idx => $material) {
                    $colIndex = 6 + $idx; // 6 adalah jumlah fix kolom statik di sebelah kiri
                    $qty = isset($data[$colIndex]) ? (int) str_replace(['.', ','], '', $data[$colIndex]) : 0;

                    if ($qty > 0) {
                        InDetail::create([
                            'in_sppm_id'        => $sppm->id,
                            'material_id'       => $material->id,
                            'target_qty'        => $qty,
                            'qty_huruf'         => null,
                            'harga_satuan'      => 0,
                            'harga_total'       => 0,
                            'sppm_serial_prefix'=> $serialParsed['prefix'],
                            'sppm_serial_start' => $serialParsed['start'],
                            'sppm_serial_end'   => $serialParsed['end'],
                        ]);

                        InStock::create([
                            'in_log_id'    => $log->id,
                            'material_id'  => $material->id,
                            'qty_received' => $qty,
                            'serial_prefix'=> $serialParsed['prefix'],
                            'serial_start' => $serialParsed['start'],
                            'serial_end'   => $serialParsed['end'],
                        ]);

                        Stock::create([
                            'no_surat_masuk' => $sppm->sppm_no,
                            'tgl_masuk'      => $tglPenerimaan,
                            'material_id'    => $material->id,
                            'warehouse_id'   => $warehouseId,
                            'prefix'         => $serialParsed['prefix'],
                            'seri_awal'      => $serialParsed['start'],
                            'seri_akhir'     => $serialParsed['end'],
                            'qty'            => $qty,
                            'harga_satuan'   => 0,
                            'total_harga'    => 0,
                            'status'         => '-',
                            'keterangan'     => 'Import otomatis via CSV',
                        ]);
                    }
                }
                
                $insertedDataCount++;
            }
            fclose($handle);
            
            if ($insertedDataCount === 0) {
                throw new \Exception("Sistem membaca file, tetapi tidak ada baris data yang valid. Pastikan TGL PENERIMAAN dan NO. SPPM terisi, serta Anda tidak mengubah/menghapus susunan kolom dari template asli.");
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            return redirect()->back()->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }

        return redirect()->route('inbound.index')->with('success', "Data Inbound berhasil diimport ($insertedDataCount baris dokumen SPPM).");
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\InSppm;
use App\Models\InDetail;
use App\Models\InLog;
use App\Models\InStock;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Stock;
use App\Models\OutStock;
use App\Models\Warehouse;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
    private $inboundMode = 'mode-1'; 

    /**
     * Fungsi Helper Privat untuk Mencatat Log Sistem
     */
    private function recordLog($action, $tableName, $recordId, $oldValues, $newValues)
    {
        SystemLog::create([
            'user_id'    => auth()->id(),
            'username'   => auth()->user()->name ?? 'Sistem',
            'action'     => strtoupper($action),
            'table_name' => strtoupper($tableName),
            'record_id'  => (string) $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

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

            // --- CATAT LOG SISTEM (Format User Friendly) ---
            $this->recordLog('CREATED', 'DOKUMEN SPPM', $sppm->id, null, [
                'Nomor SPPM'        => $sppm->sppm_no,
                'Tanggal SPPM'      => $sppm->sppm_date,
                'Kategori Material' => $sppm->category->name ?? $sppm->material_category_id,
                'Status'            => $sppm->status,
                'Keterangan'        => $sppm->notes
            ]);
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
        $sppm = InSppm::with('details.material', 'logs.stocks')->findOrFail($id);
        $currentMode = $request->input('inbound_mode');
        $oldSppmNo = $sppm->sppm_no;
        $oldDate = $sppm->sppm_date;

        foreach ($request->items as $item) {
            $prefix = $item['sppm_serial_prefix'] ?? null;
            $startStr = $item['sppm_serial_start'] ?? null;
            $endStr = $item['sppm_serial_end'] ?? null;

            if ($startStr && $endStr) {
                $start = (int) str_replace('.', '', $startStr);
                $end = (int) str_replace('.', '', $endStr);

                $isOverlap = Stock::where('material_id', $item['material_id'])
                    ->where('no_surat_masuk', '!=', $sppm->sppm_no) 
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
                $receivedItemsLog = [];

                foreach ($request->items as $item) {
                    $qtyReceived = $item['qty_received'] ?? 0;

                    if ($qtyReceived > 0) {
                        $matName = Material::find($item['material_id'])->name ?? 'Barang';
                        $receivedItemsLog["Masuk: {$matName}"] = $qtyReceived;

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

                // --- CATAT LOG SISTEM (Mode 2) ---
                $newValuesLog = array_merge([
                    'Nomor SPPM' => $sppm->sppm_no,
                    'Aktivitas'  => "Penerimaan Fisik Gelombang ke-{$nextBatch}",
                    'Tgl Terima' => $request->batch_date,
                    'Status Baru'=> $sppm->status
                ], $receivedItemsLog);

                $this->recordLog('UPDATED', 'DOKUMEN SPPM', $sppm->id, [ 'Status Sebelumnya' => 'partial' ], $newValuesLog);
            });

            return redirect()->route('inbound.index')->with('success', 'Penerimaan fisik Tahap Baru berhasil dicatat.');
        }

        $request->validate([
            'sppm_no'      => 'required|string|max:255|unique:in_sppms,sppm_no,' . $sppm->id,
            'sppm_date'    => 'required|date',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items'        => 'required|array',
        ]);

        // PERSIAPAN DATA LOG (Pendeteksi Perubahan)
        $oldDetails = $sppm->details->keyBy('material_id');
        $oldChanges = [];
        $newChanges = [];

        if ($sppm->sppm_no != $request->sppm_no) {
            $oldChanges['Nomor SPPM'] = $sppm->sppm_no;
            $newChanges['Nomor SPPM'] = $request->sppm_no;
        }
        if ($sppm->sppm_date != $request->sppm_date) {
            $oldChanges['Tanggal SPPM'] = $sppm->sppm_date;
            $newChanges['Tanggal SPPM'] = $request->sppm_date;
        }
        
        foreach ($request->items as $item) {
            if (isset($item['target_qty'])) {
                $matId = $item['material_id'];
                $newQty = $item['target_qty'];
                $oldDetail = $oldDetails->get($matId);
                
                $matName = $oldDetail ? $oldDetail->material->name : Material::find($matId)->name;
                $oldQty = $oldDetail ? $oldDetail->target_qty : 0;
                
                if ($oldQty != $newQty) {
                    $oldChanges["Jml " . strtoupper($matName)] = $oldQty;
                    $newChanges["Jml " . strtoupper($matName)] = $newQty;
                }
            }
        }

        if (empty($oldChanges) && empty($newChanges)) {
             $newChanges['Keterangan'] = 'Update dilakukan namun tidak terdeteksi perubahan kuantitas/nomor SPPM.';
        }

        DB::transaction(function () use ($request, $sppm, $oldSppmNo, $oldChanges, $newChanges) {
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

            // --- CATAT LOG SISTEM ---
            $this->recordLog('UPDATED', 'DOKUMEN SPPM', $sppm->id, $oldChanges, $newChanges);
        });

        return redirect()->route('inbound.index')->with('success', 'Pembaruan Dokumen & Seri berhasil disimpan.');
    }

    public function destroy($id)
    {
        $sppm = InSppm::with('logs')->findOrFail($id);
        
        // Simpan data untuk kebutuhan log
        $deletedSppmNo = $sppm->sppm_no;
        $deletedSppmDate = $sppm->sppm_date;

        $stockIds = Stock::where('no_surat_masuk', $sppm->sppm_no)->pluck('id');
        $isUsedInOutbound = OutStock::whereIn('stock_id', $stockIds)->exists();

        if ($isUsedInOutbound) {
            return redirect()->back()->with('error', 'GAGAL MENGHAPUS! Data Inbound ini tidak dapat dihapus karena barang di dalamnya sudah didistribusikan di menu Outbound. Silakan hapus/batalkan data Outbound terkait terlebih dahulu.');
        }

        DB::transaction(function () use ($sppm, $deletedSppmNo, $deletedSppmDate) {
            Stock::where('no_surat_masuk', $sppm->sppm_no)->delete();
            foreach ($sppm->logs as $log) {
                InStock::where('in_log_id', $log->id)->delete();
            }
            $sppm->logs()->delete();
            $sppm->details()->delete();
            
            // --- CATAT LOG SISTEM SEBELUM MODEL DIHAPUS ---
            $this->recordLog('DELETED', 'DOKUMEN SPPM', $sppm->id, [
                'Nomor SPPM Dihapus' => $deletedSppmNo,
                'Tanggal SPPM'       => $deletedSppmDate
            ], null);

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

    public function storeWarehouseAjax(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255|unique:warehouses,name',
            'code'       => 'nullable|string|max:255',
            'lokasi'     => 'nullable|string|max:255',
            'keterangan' => 'nullable|string'
        ]);

        $lastUrut = Warehouse::max('nomor_urut');
        $nextUrut = $lastUrut ? $lastUrut + 1 : 1;

        $warehouse = Warehouse::create([
            'nomor_urut' => $nextUrut,
            'name'       => $request->name,
            'code'       => $request->code,
            'lokasi'     => $request->lokasi,
            'keterangan' => $request->keterangan,
        ]);

        // --- CATAT LOG SISTEM ---
        $this->recordLog('CREATED', 'GUDANG', $warehouse->id, null, [
            'Nama Gudang' => $warehouse->name,
            'Kode Gudang' => $warehouse->code,
            'Lokasi'      => $warehouse->lokasi
        ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Gudang berhasil ditambahkan',
            'warehouse' => $warehouse
        ]);
    }

    // --- FUNGSI DOWNLOAD TEMPLATE EXCEL ---
    public function downloadTemplate(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:material_categories,id']);
        
        $categoryId = $request->input('category_id');
        $category = MaterialCategory::findOrFail($categoryId);

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

            echo '<tr style="font-weight: bold; background-color: #f8f9fa;">';
            echo '<th rowspan="'.$headerRows.'" style="width: 40px;">NO</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 120px;">TGL PENERIMAAN<br>(YYYY-MM-DD)</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 120px;">TGL SPPM<br>(YYYY-MM-DD)</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 180px;">NO. SPPM KORLANTAS</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 180px;">NOMOR SERI</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">NO. BAPPM</th>';
            
            echo '<th colspan="'.$flatMaterials->count().'" style="background-color: #d1e7dd;">RINCIAN BARANG KATEGORI: '.strtoupper($category->name).'</th>';
            echo '</tr>';

            echo '<tr style="font-weight: bold; background-color: #d1e7dd;">';
            foreach ($topLevelMaterials as $mat) {
                if ($mat->children->count() > 0) {
                    echo '<th colspan="'.$mat->children->count().'" style="background-color: #badce3;">'.strtoupper($mat->name).'</th>';
                } else {
                    $rs = $hasChildren ? 2 : 1;
                    if ($rs > 1) {
                        echo '<th rowspan="'.$rs.'">'.strtoupper($mat->name).'</th>';
                    } else {
                        echo '<th>'.strtoupper($mat->name).'</th>';
                    }
                }
            }
            echo '</tr>';

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

            echo '<tr>';
            echo '<td>1</td>';
            echo '<td>'.date('Y-m-d').'</td>';
            echo '<td>'.date('Y-m-d').'</td>';
            echo '<td>SPPM/001/VII/'.date('Y').'</td>';
            echo '<td>H. 01.300.001 - 01.400.000</td>';
            echo '<td>BAPPM-001</td>';
            foreach ($flatMaterials as $mat) {
                echo '<td>500</td>'; 
            }
            echo '</tr>';
            
            echo '</table>';
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- FUNGSI HANDLE UPLOAD EXCEL ---
    public function importExcel(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:material_categories,id',
            'excel_file'  => 'required|file|mimes:csv,txt'
        ]);

        $categoryId = $request->input('category_id');
        $file = $request->file('excel_file');
        $originalFileName = $file->getClientOriginalName();

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

        $headerRowsToSkip = $hasChildren ? 3 : 2;

        $parseSerial = function($string) {
            $result = ['prefix' => null, 'start' => null, 'end' => null];
            if (empty(trim($string)) || trim($string) === '-') return $result;

            $parts = explode('-', $string);
            if (count($parts) == 2) {
                $left = trim($parts[0]);
                if (preg_match('/^([a-zA-Z\.\s]+)?([\d\.]+)$/', $left, $matches)) {
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

        ini_set('auto_detect_line_endings', true);
        
        $handle = fopen($file->getPathname(), "r");
        
        $firstLine = fgets($handle);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($handle); 

        $rowCounter = 0;
        $insertedDataCount = 0;
        $importedSppms = []; // Array untuk mencatat SPPM yang berhasil di-import ke LOG

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
                $rowCounter++;
                if ($rowCounter <= $headerRowsToSkip) continue; 

                if (count($data) < 6) continue;

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

                foreach ($flatMaterials as $idx => $material) {
                    $colIndex = 6 + $idx; 
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
                $importedSppms[] = $noSppm; // Catat nomor SPPM ke array log
            }
            fclose($handle);
            
            if ($insertedDataCount === 0) {
                throw new \Exception("Sistem membaca file, tetapi tidak ada baris data yang valid. Pastikan TGL PENERIMAAN dan NO. SPPM terisi, serta Anda tidak mengubah/menghapus susunan kolom dari template asli.");
            }

            DB::commit();

            // --- CATAT LOG SISTEM UNTUK IMPORT ---
            $this->recordLog('IMPORT', 'DOKUMEN SPPM', null, null, [
                'Nama File CSV'       => $originalFileName,
                'Total Baris Sukses'  => $insertedDataCount,
                'Daftar SPPM Masuk'   => implode(', ', $importedSppms)
            ]);

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
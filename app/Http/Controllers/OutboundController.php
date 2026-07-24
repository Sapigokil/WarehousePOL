<?php

namespace App\Http\Controllers;

use App\Models\OutSppm;
use App\Models\OutDetail;
use App\Models\OutLog;
use App\Models\OutStock;
use App\Models\Stock;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Destination;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OutboundController extends Controller
{
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

        $outbounds = OutSppm::with(['destination', 'details.material', 'logs.outStocks', 'updater'])
            ->when($search, function ($query, $search) {
                return $query->where('sppm_no', 'like', "%{$search}%")
                             ->orWhereHas('destination', function($q) use ($search) {
                                 $q->where('name', 'like', "%{$search}%");
                             });
            })
            ->orderBy('sppm_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($limit)
            ->withQueryString();

        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        return view('outbound.index', compact('outbounds', 'search', 'limit', 'categories'));
    }

    public function create()
    {
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $destinations = Destination::orderBy('nomor_urut', 'asc')->get();

        return view('outbound.form', compact('categories', 'destinations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sppm_no'        => 'required|string|unique:out_sppms,sppm_no',
            'sppm_date'      => 'required|date',
            'destination_id' => 'required|exists:destinations,id',
            'action_type'    => 'required|in:draft,final',
            'items'          => 'required|array',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.target_qty'  => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $action = $request->input('action_type');
            $destination = Destination::find($request->destination_id);
            
            $sppm = OutSppm::create([
                'sppm_no'        => $request->sppm_no,
                'sppm_date'      => $request->sppm_date,
                'destination_id' => $request->destination_id,
                'keterangan'     => $request->keterangan,
                'nama_bamat'     => $destination->nama ?? null,
                'pangkat'        => $destination->pangkat_nrp ?? null,
                'jabatan'        => $destination->jabatan ?? null,
                'status'         => $action === 'final' ? 'completed' : 'pending', 
                'created_by'     => Auth::id(),
                'updated_by'     => Auth::id(),
            ]);

            $hasItems = false;
            $itemsToProcess = [];

            foreach ($request->items as $item) {
                $qty = (int) ($item['target_qty'] ?? 0);
                if ($qty > 0) {
                    $hasItems = true;
                    
                    if ($action === 'final') {
                        $availableStock = Stock::where('material_id', $item['material_id'])->sum('qty');
                        if ($qty > $availableStock) {
                            $matName = Material::find($item['material_id'])->name ?? 'Barang';
                            throw new \Exception("GAGAL DISIMPAN: Jumlah barang keluar untuk [{$matName}] adalah {$qty}, sedangkan stok tersedia di gudang hanya {$availableStock}.");
                        }
                    }

                    OutDetail::create([
                        'out_sppm_id'  => $sppm->id,
                        'material_id'  => $item['material_id'],
                        'target_qty'   => $qty,
                        'harga_satuan' => $item['harga_satuan'] ?? 0,
                        'harga_total'  => $item['harga_total'] ?? 0,
                    ]);

                    $itemsToProcess[] = $item;
                }
            }

            if (!$hasItems) {
                throw new \Exception("SPPM harus memiliki minimal satu barang dengan target jumlah keluar lebih dari 0.");
            }

            if ($action === 'final') {
                $log = OutLog::create([
                    'out_sppm_id'  => $sppm->id,
                    'batch_number' => 1,
                    'tgl_keluar'   => $request->sppm_date,
                    'keterangan'   => 'Realisasi keluar otomatis.',
                ]);

                foreach ($itemsToProcess as $item) {
                    $sisaKebutuhan = (int) $item['target_qty'];
                    $availableStocks = Stock::where('material_id', $item['material_id'])
                        ->where('qty', '>', 0)
                        ->orderBy('tgl_masuk', 'asc')
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($availableStocks as $stock) {
                        if ($sisaKebutuhan <= 0) break;

                        $qtyAmbil = min($stock->qty, $sisaKebutuhan);
                        $outSeriAwal = null;
                        $outSeriAkhir = null;

                        if ($stock->seri_awal !== null) {
                            $outSeriAwal = $stock->seri_awal;
                            $outSeriAkhir = $stock->seri_awal + $qtyAmbil - 1;

                            if ($qtyAmbil < $stock->qty) {
                                $stock->seri_awal = $outSeriAkhir + 1;
                            } else {
                                $stock->seri_awal = null;
                                $stock->seri_akhir = null;
                            }
                        }

                        OutStock::create([
                            'out_log_id' => $log->id,
                            'stock_id'   => $stock->id,
                            'qty_keluar' => $qtyAmbil,
                            'prefix'     => $stock->prefix,
                            'seri_awal'  => $outSeriAwal,
                            'seri_akhir' => $outSeriAkhir,
                        ]);

                        $stock->qty -= $qtyAmbil;
                        $stock->save();
                        $sisaKebutuhan -= $qtyAmbil;
                    }
                }
            }

            // --- CATAT LOG SISTEM ---
            $this->recordLog('CREATED', 'DOKUMEN SPPM KELUAR', $sppm->id, null, [
                'Nomor SPPM' => $sppm->sppm_no,
                'Tanggal'    => $sppm->sppm_date,
                'Tujuan'     => $destination->name ?? 'Unknown',
                'Status'     => $sppm->status
            ]);

            DB::commit();
            $msg = $action === 'final' ? 'Dokumen berhasil disimpan dan stok gudang telah dipotong.' : 'Dokumen berhasil disimpan sebagai DRAFT.';
            return redirect()->route('outbounds.index')->with('success', $msg);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors($e->getMessage());
        }
    }

    public function edit($id)
    {
        $outbound = OutSppm::with('details')->findOrFail($id);
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $destinations = Destination::orderBy('nomor_urut', 'asc')->get();
        
        $firstDetail = $outbound->details->first();
        $selectedCategoryId = $firstDetail ? $firstDetail->material->material_category_id : null;

        return view('outbound.form', compact('categories', 'destinations', 'outbound', 'selectedCategoryId'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'sppm_no'        => 'required|string|unique:out_sppms,sppm_no,'.$id,
            'sppm_date'      => 'required|date',
            'destination_id' => 'required|exists:destinations,id',
            'action_type'    => 'required|in:draft,final',
            'items'          => 'required|array',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.target_qty'  => 'nullable|numeric|min:0',
        ]);

        $sppm = OutSppm::with('details.material')->findOrFail($id);

        if ($sppm->status === 'completed') {
            return back()->withErrors('Dokumen yang sudah Final / Selesai tidak dapat diubah kembali.');
        }

        $destination = Destination::find($request->destination_id);

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
        if ($sppm->destination_id != $request->destination_id) {
            $oldChanges['Tujuan'] = Destination::find($sppm->destination_id)->name ?? '-';
            $newChanges['Tujuan'] = $destination->name ?? '-';
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

        if (empty($oldChanges) && empty($newChanges) && $request->input('action_type') == 'final') {
             $newChanges['Status'] = 'Draft di-Finalisasi, stok fisik gudang dipotong.';
        }

        DB::beginTransaction();
        try {
            $action = $request->input('action_type');

            $sppm->update([
                'sppm_no'        => $request->sppm_no,
                'sppm_date'      => $request->sppm_date,
                'destination_id' => $request->destination_id,
                'keterangan'     => $request->keterangan,
                'nama_bamat'     => $destination->nama ?? null,
                'pangkat'        => $destination->pangkat_nrp ?? null,
                'jabatan'        => $destination->jabatan ?? null,
                'status'         => $action === 'final' ? 'completed' : 'pending',
                'updated_by'     => Auth::id(),
            ]);

            $sppm->details()->delete();

            $hasItems = false;
            $itemsToProcess = [];

            foreach ($request->items as $item) {
                $qty = (int) ($item['target_qty'] ?? 0);
                if ($qty > 0) {
                    $hasItems = true;

                    if ($action === 'final') {
                        $availableStock = Stock::where('material_id', $item['material_id'])->sum('qty');
                        if ($qty > $availableStock) {
                            $matName = Material::find($item['material_id'])->name ?? 'Barang';
                            throw new \Exception("GAGAL: Jumlah keluar untuk [{$matName}] adalah {$qty}, sedangkan stok tersedia hanya {$availableStock}.");
                        }
                    }

                    OutDetail::create([
                        'out_sppm_id'  => $sppm->id,
                        'material_id'  => $item['material_id'],
                        'target_qty'   => $qty,
                        'harga_satuan' => $item['harga_satuan'] ?? 0,
                        'harga_total'  => $item['harga_total'] ?? 0,
                    ]);

                    $itemsToProcess[] = $item;
                }
            }

            if (!$hasItems) {
                throw new \Exception("SPPM harus memiliki minimal satu barang dengan target jumlah keluar lebih dari 0.");
            }

            if ($action === 'final') {
                $log = OutLog::create([
                    'out_sppm_id'  => $sppm->id,
                    'batch_number' => 1,
                    'tgl_keluar'   => $request->sppm_date,
                    'keterangan'   => 'Realisasi keluar otomatis (Update dari Draft).',
                ]);

                foreach ($itemsToProcess as $item) {
                    $sisaKebutuhan = (int) $item['target_qty'];
                    $availableStocks = Stock::where('material_id', $item['material_id'])
                        ->where('qty', '>', 0)
                        ->orderBy('tgl_masuk', 'asc')
                        ->orderBy('id', 'asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($availableStocks as $stock) {
                        if ($sisaKebutuhan <= 0) break;

                        $qtyAmbil = min($stock->qty, $sisaKebutuhan);
                        $outSeriAwal = null;
                        $outSeriAkhir = null;

                        if ($stock->seri_awal !== null) {
                            $outSeriAwal = $stock->seri_awal;
                            $outSeriAkhir = $stock->seri_awal + $qtyAmbil - 1;

                            if ($qtyAmbil < $stock->qty) {
                                $stock->seri_awal = $outSeriAkhir + 1;
                            } else {
                                $stock->seri_awal = null;
                                $stock->seri_akhir = null;
                            }
                        }

                        OutStock::create([
                            'out_log_id' => $log->id,
                            'stock_id'   => $stock->id,
                            'qty_keluar' => $qtyAmbil,
                            'prefix'     => $stock->prefix,
                            'seri_awal'  => $outSeriAwal,
                            'seri_akhir' => $outSeriAkhir,
                        ]);

                        $stock->qty -= $qtyAmbil;
                        $stock->save();
                        $sisaKebutuhan -= $qtyAmbil;
                    }
                }
            }

            // --- CATAT LOG SISTEM ---
            $this->recordLog('UPDATED', 'DOKUMEN SPPM KELUAR', $sppm->id, $oldChanges, $newChanges);

            DB::commit();
            $msg = $action === 'final' ? 'Draft berhasil disimpan dan stok gudang telah dipotong.' : 'DRAFT berhasil diperbarui.';
            return redirect()->route('outbounds.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors($e->getMessage());
        }
    }

    public function destroy($id)
    {
        $sppm = OutSppm::with('logs.outStocks')->findOrFail($id);

        $deletedSppmNo = $sppm->sppm_no;
        $deletedSppmDate = $sppm->sppm_date;

        DB::beginTransaction();
        try {
            foreach ($sppm->logs()->orderBy('id', 'desc')->get() as $log) {
                foreach ($log->outStocks()->orderBy('id', 'desc')->get() as $outStock) {
                    $stock = Stock::find($outStock->stock_id);
                    if ($stock) {
                        $stock->qty += $outStock->qty_keluar;
                        if ($outStock->seri_awal !== null) {
                            $stock->seri_awal = $outStock->seri_awal;
                            if ($stock->seri_akhir === null) {
                                $stock->seri_akhir = $outStock->seri_akhir;
                            }
                        }
                        $stock->save();
                    }
                }
            }
            
            // --- CATAT LOG SISTEM ---
            $this->recordLog('DELETED', 'DOKUMEN SPPM KELUAR', $sppm->id, [
                'Nomor SPPM Dihapus' => $deletedSppmNo,
                'Tanggal SPPM'       => $deletedSppmDate
            ], null);

            $sppm->delete(); 
            DB::commit();
            return redirect()->route('outbounds.index')->with('success', 'Dokumen Keluar berhasil dihapus dan stok fisik telah dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }

    public function getMaterialsByCategory($category_id)
    {
        $materials = Material::with(['children' => function($q) {
            $q->orderBy('nomor_urut', 'asc');
        }])
        ->where('material_category_id', $category_id)
        ->whereNull('parent_id')
        ->orderBy('nomor_urut', 'asc')
        ->get();

        $materials->each(function($mat) {
            $availableStocks = Stock::where('material_id', $mat->id)
                                ->where('qty', '>', 0)
                                ->orderBy('tgl_masuk', 'asc')
                                ->orderBy('id', 'asc')
                                ->get();
                                
            $mat->current_stock = $availableStocks->sum('qty');
            
            $fifoQueue = [];
            foreach($availableStocks as $st) {
                $fifoQueue[] = [
                    'qty' => $st->qty,
                    'price' => $st->harga_satuan
                ];
            }
            $mat->fifo_queue = $fifoQueue;
            
            if ($mat->pakai_seri == 1) {
                $firstStock = $availableStocks->whereNotNull('seri_awal')->first();
                $mat->next_prefix = $firstStock ? $firstStock->prefix : null;
                $mat->next_seri = $firstStock ? $firstStock->seri_awal : null;
            }

            if ($mat->children) {
                $mat->children->each(function($child) {
                    $availableStocksChild = Stock::where('material_id', $child->id)
                                    ->where('qty', '>', 0)
                                    ->orderBy('tgl_masuk', 'asc')
                                    ->orderBy('id', 'asc')
                                    ->get();
                                    
                    $child->current_stock = $availableStocksChild->sum('qty');
                    
                    $fifoQueueChild = [];
                    foreach($availableStocksChild as $st) {
                        $fifoQueueChild[] = [
                            'qty' => $st->qty,
                            'price' => $st->harga_satuan
                        ];
                    }
                    $child->fifo_queue = $fifoQueueChild;
                    
                    if ($child->pakai_seri == 1) {
                        $firstStockChild = $availableStocksChild->whereNotNull('seri_awal')->first();
                        $child->next_prefix = $firstStockChild ? $firstStockChild->prefix : null;
                        $child->next_seri = $firstStockChild ? $firstStockChild->seri_awal : null;
                    }
                });
            }
        });

        return response()->json($materials);
    }

    public function print($id)
    {
        $sppm = OutSppm::with([
            'destination', 
            'details.material', 
            'logs.outStocks.stock', 
            'creator'
        ])->findOrFail($id);

        if ($sppm->status !== 'completed') {
            abort(403, 'Hanya dokumen yang sudah berstatus FINAL yang dapat dicetak.');
        }

        // --- CATAT LOG SISTEM ---
        $this->recordLog('PRINT', 'DOKUMEN SPPM KELUAR', $sppm->id, null, [
            'Aksi' => 'Mencetak dokumen fisik SPPM',
            'Nomor SPPM' => $sppm->sppm_no
        ]);

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $signatory = [
            'name'     => $settings['signatory_name'] ?? 'NAMA DIREKTUR',
            'nrp'      => $settings['signatory_nrp'] ?? 'NRP. 00000000',
            'position' => $settings['signatory_position'] ?? 'JABATAN',
        ];

        return view('outbound.print', compact('sppm', 'signatory'));
    }

    // --- FUNGSI DOWNLOAD TEMPLATE EXCEL OUTBOUND ---
    public function downloadTemplate(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:material_categories,id']);
        
        $categoryId = $request->input('category_id');
        $category = MaterialCategory::findOrFail($categoryId);

        // --- CATAT LOG SISTEM ---
        $this->recordLog('DOWNLOAD', 'TEMPLATE EXCEL KELUAR', null, null, [
            'Aksi' => 'Mengunduh template import excel',
            'Kategori' => $category->name
        ]);

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

        $fileName = 'Template_Keluar_' . str_replace(' ', '_', strtoupper($category->name)) . '_' . date('Ymd') . '.xls';

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

            // Header Statik (11 Kolom)
            echo '<tr style="font-weight: bold; background-color: #f8f9fa;">';
            echo '<th rowspan="'.$headerRows.'" style="width: 40px;">NO</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 120px;">TGL SPPM<br>(YYYY-MM-DD)</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 180px;">No. SPPM DITLANTAS</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 100px;">KODE<br>(PREFIX)</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">NO SERI AWAL</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">NO SERI AKHIR</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 200px;">TUJUAN PENGIRIMAN</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">NAMA BAMAT</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">PANGKAT/ NRP</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">JABATAN</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">KETERANGAN</th>';
            
            // Header Dinamis Material
            echo '<th colspan="'.$flatMaterials->count().'" style="background-color: #fecdd3;">BARANG KELUAR: '.strtoupper($category->name).'</th>';
            echo '</tr>';

            echo '<tr style="font-weight: bold; background-color: #fecdd3;">';
            foreach ($topLevelMaterials as $mat) {
                if ($mat->children->count() > 0) {
                    echo '<th colspan="'.$mat->children->count().'" style="background-color: #ffe4e6;">'.strtoupper($mat->name).'</th>';
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
                echo '<tr style="font-weight: bold; background-color: #fff1f2;">';
                foreach ($topLevelMaterials as $mat) {
                    if ($mat->children->count() > 0) {
                        foreach ($mat->children as $child) {
                            echo '<th>'.$child->name.'</th>';
                        }
                    }
                }
                echo '</tr>';
            }

            // Contoh Data Dummy
            echo '<tr>';
            echo '<td>1</td>';
            echo '<td>'.date('Y-m-d').'</td>';
            echo '<td>SPPM/001/VI/2026/DITLANTAS</td>';
            echo '<td>H</td>'; 
            echo '<td>1300001</td>';
            echo '<td>1400000</td>';
            echo '<td>POLRES DEMAK</td>';
            echo '<td>Budi Santoso</td>';
            echo '<td>IPDA / 12345678</td>';
            echo '<td>BAUR STNK</td>';
            echo '<td>Distribusi Rutin</td>';
            foreach ($flatMaterials as $mat) {
                echo '<td>50</td>'; 
            }
            echo '</tr>';
            
            echo '</table>';
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- FUNGSI HANDLE UPLOAD EXCEL OUTBOUND ---
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

        ini_set('auto_detect_line_endings', true);
        
        $handle = fopen($file->getPathname(), "r");
        
        $firstLine = fgets($handle);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($handle); 

        $rowCounter = 0;
        $insertedDataCount = 0;
        $importedSppms = []; 

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
                $rowCounter++;
                if ($rowCounter <= $headerRowsToSkip) continue; 

                if (count($data) < 11) continue; // Wajib minimal 11 kolom statik (Indeks 0 s/d 10)

                // Mapping Kolom Baru Sesuai Template (11 Kolom)
                $tglSppmStr   = $data[1] ?? null;
                $noSppm       = trim($data[2] ?? '');
                $prefixRaw    = trim($data[3] ?? '');
                $seriAwalRaw  = trim($data[4] ?? '');
                $seriAkhirRaw = trim($data[5] ?? '');
                $tujuanStr    = trim($data[6] ?? '');
                $namaBamat    = trim($data[7] ?? '');
                $pangkatNrp   = trim($data[8] ?? '');
                $jabatan      = trim($data[9] ?? '');
                $keterangan   = trim($data[10] ?? '');

                if (empty($noSppm) || empty($tujuanStr)) continue; 

                $tglSppm = date('Y-m-d', strtotime($tglSppmStr));

                // Pembersihan Prefix (Hanya Alfabet) dan Konversi Angka Seri
                $cleanPrefix = preg_replace('/[^a-zA-Z]/', '', $prefixRaw);
                $prefix = $cleanPrefix !== '' ? strtoupper($cleanPrefix) : null;
                $seriAwal = (!empty($seriAwalRaw) && $seriAwalRaw !== '-') ? (int) str_replace(['.', ','], '', $seriAwalRaw) : null;
                $seriAkhir = (!empty($seriAkhirRaw) && $seriAkhirRaw !== '-') ? (int) str_replace(['.', ','], '', $seriAkhirRaw) : null;

                // Cari Destination berdasarkan nama (Wajib Sama)
                $destination = Destination::where('name', 'like', $tujuanStr)->first();
                if (!$destination) {
                    throw new \Exception("GAGAL! Tujuan Pengiriman '{$tujuanStr}' pada SPPM '{$noSppm}' tidak ditemukan di Master Data Tujuan. Pastikan pengetikan nama di Excel sama persis.");
                }

                $existingSppm = OutSppm::where('sppm_no', $noSppm)->first();
                if ($existingSppm) {
                    throw new \Exception("Ditemukan duplikat Dokumen Keluar (SPPM) di database untuk nomor: {$noSppm}");
                }

                $sppm = OutSppm::create([
                    'sppm_no'        => $noSppm,
                    'sppm_date'      => $tglSppm,
                    'destination_id' => $destination->id,
                    'keterangan'     => $keterangan,
                    'nama_bamat'     => $namaBamat,
                    'pangkat'        => $pangkatNrp,
                    'jabatan'        => $jabatan,
                    'status'         => 'completed', 
                    'created_by'     => auth()->id(),
                    'updated_by'     => auth()->id()
                ]);

                $log = OutLog::create([
                    'out_sppm_id'  => $sppm->id,
                    'batch_number' => 1,
                    'tgl_keluar'   => $tglSppm,
                    'keterangan'   => 'Import & Realisasi otomatis via CSV',
                ]);

                // --- POSISI KOLOM DINAMIS BARANG (Mulai Indeks 11 ke Kanan) ---
                foreach ($flatMaterials as $idx => $material) {
                    $colIndex = 11 + $idx; // Digeser ke indeks 11 karena ada 11 kolom statik di kiri
                    $qty = isset($data[$colIndex]) ? (int) str_replace(['.', ','], '', $data[$colIndex]) : 0;

                    if ($qty > 0) {
                        // VALIDASI STOK SEBELUM DISIMPAN
                        $availableStock = Stock::where('material_id', $material->id)->sum('qty');
                        if ($qty > $availableStock) {
                            throw new \Exception("GAGAL IMPORT! Stok gudang tidak mencukupi untuk [{$material->name}] pada SPPM {$noSppm}. Diminta: {$qty}, Tersedia: {$availableStock}");
                        }

                        OutDetail::create([
                            'out_sppm_id'  => $sppm->id,
                            'material_id'  => $material->id,
                            'target_qty'   => $qty,
                            'harga_satuan' => 0,
                            'harga_total'  => 0,
                        ]);

                        // LOGIKA PEMOTONGAN STOK (PRIORITAS NOMOR SERI INPUTAN -> FALLBACK FIFO)
                        $sisaKebutuhan = $qty;
                        $queryStock = Stock::where('material_id', $material->id)->where('qty', '>', 0);

                        // Jika Prefix dan Range Seri diisi, prioritaskan memotong stok yang sesuai terlebih dahulu
                        if ($prefix) {
                            $queryStock->orderByRaw("prefix = '{$prefix}' DESC");
                        }
                        if ($seriAwal && $seriAkhir) {
                            $queryStock->orderByRaw("seri_awal <= {$seriAwal} AND seri_akhir >= {$seriAwal} DESC");
                        }

                        $availableStocks = $queryStock->orderBy('tgl_masuk', 'asc')->orderBy('id', 'asc')->lockForUpdate()->get();

                        foreach ($availableStocks as $stock) {
                            if ($sisaKebutuhan <= 0) break;

                            $qtyAmbil = min($stock->qty, $sisaKebutuhan);
                            $outSeriAwal = null;
                            $outSeriAkhir = null;

                            if ($stock->seri_awal !== null) {
                                $outSeriAwal = $stock->seri_awal;
                                $outSeriAkhir = $stock->seri_awal + $qtyAmbil - 1;

                                if ($qtyAmbil < $stock->qty) {
                                    $stock->seri_awal = $outSeriAkhir + 1;
                                } else {
                                    $stock->seri_awal = null;
                                    $stock->seri_akhir = null;
                                }
                            }

                            // MENYIMPAN PREFIX, SERI AWAL, DAN SERI AKHIR
                            OutStock::create([
                                'out_log_id' => $log->id,
                                'stock_id'   => $stock->id,
                                'qty_keluar' => $qtyAmbil,
                                'prefix'     => $stock->prefix,
                                'seri_awal'  => $outSeriAwal,
                                'seri_akhir' => $outSeriAkhir,
                            ]);

                            $stock->qty -= $qtyAmbil;
                            $stock->save();
                            
                            $sisaKebutuhan -= $qtyAmbil;
                        }
                    }
                }
                
                $insertedDataCount++;
                $importedSppms[] = $noSppm; 
            }
            fclose($handle);
            
            if ($insertedDataCount === 0) {
                throw new \Exception("Sistem membaca file, tetapi tidak ada baris data yang valid. Pastikan template sesuai (terdapat 3 kolom nomor seri baru) dan tidak diubah susunannya.");
            }

            // --- CATAT LOG SISTEM UNTUK IMPORT ---
            $this->recordLog('IMPORT', 'DOKUMEN SPPM KELUAR', null, null, [
                'Nama File CSV'       => $originalFileName,
                'Total Baris Sukses'  => $insertedDataCount,
                'Daftar SPPM Keluar'  => implode(', ', $importedSppms)
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            return redirect()->back()->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }

        return redirect()->route('outbounds.index')->with('success', "Data Barang Keluar berhasil diimport dan memotong stok ($insertedDataCount baris dokumen SPPM).");
    }
}
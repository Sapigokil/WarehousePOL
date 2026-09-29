<?php

namespace App\Http\Controllers;

use App\Models\OutSppm;
use App\Models\OutDetail;
use App\Models\OutLog;
use App\Models\OutStock;
use App\Models\InStock; // Ditambahkan untuk kalkulasi Ledger
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

    /**
     * Fungsi Helper Privat untuk Kalkulasi Set Difference (Ledger)
     */
    private function subtractRanges($ranges, $subtract)
    {
        $result = [];
        foreach ($ranges as $r) {
            if ($subtract['end'] < $r['start'] || $subtract['start'] > $r['end']) {
                $result[] = $r;
            } else if ($subtract['start'] <= $r['start'] && $subtract['end'] >= $r['end']) {
                continue;
            } else if ($subtract['start'] > $r['start'] && $subtract['end'] < $r['end']) {
                $result[] = ['start' => $r['start'], 'end' => $subtract['start'] - 1];
                $result[] = ['start' => $subtract['end'] + 1, 'end' => $r['end']];
            } else if ($subtract['start'] <= $r['start'] && $subtract['end'] >= $r['start']) {
                $result[] = ['start' => $subtract['end'] + 1, 'end' => $r['end']];
            } else if ($subtract['start'] <= $r['end'] && $subtract['end'] >= $r['end']) {
                $result[] = ['start' => $r['start'], 'end' => $subtract['start'] - 1];
            }
        }
        return $result;
    }

    /**
     * Fungsi Helper Privat untuk Mendapatkan Antrean Stok Tersedia (Untuk Wizard Frontend)
     */
    private function calculateMaterialStock($mat)
    {
        $inStocks = InStock::where('material_id', $mat->id)->get();
        $outStocks = OutStock::where('material_id', $mat->id)->get();
        
        $mat->current_stock = $inStocks->sum('qty_received') - $outStocks->sum('qty_keluar');
        $fifoQueue = [];

        if ($mat->current_stock > 0) {
            if ($mat->pakai_seri == 1) {
                $prefixes = $inStocks->pluck('serial_prefix')->merge($outStocks->pluck('prefix'))->unique()->filter();
                foreach($prefixes as $prefix) {
                    $inForPrefix = $inStocks->where('serial_prefix', $prefix);
                    $outForPrefix = $outStocks->where('prefix', $prefix)->map(function($o) {
                        return ['start' => $o->seri_awal, 'end' => $o->seri_akhir];
                    })->toArray();
                    
                    foreach($inForPrefix as $in) {
                        $availRanges = [['start' => $in->serial_start, 'end' => $in->serial_end]];
                        foreach($outForPrefix as $out) {
                            $availRanges = $this->subtractRanges($availRanges, $out);
                        }
                        foreach($availRanges as $r) {
                            $fifoQueue[] = [
                                'id'         => $in->id,
                                'qty'        => $r['end'] - $r['start'] + 1,
                                'price'      => 0,
                                'prefix'     => $prefix,
                                'seri_awal'  => $r['start'],
                                'seri_akhir' => $r['end']
                            ];
                        }
                    }
                }
            } else {
                $fifoQueue[] = [
                    'id'         => 1,
                    'qty'        => $mat->current_stock,
                    'price'      => 0,
                    'prefix'     => null,
                    'seri_awal'  => null,
                    'seri_akhir' => null
                ];
            }
        }

        $mat->fifo_queue = $fifoQueue;
        
        if ($mat->pakai_seri == 1 && count($fifoQueue) > 0) {
            $mat->next_prefix = $fifoQueue[0]['prefix'];
            $mat->next_seri = $fifoQueue[0]['seri_awal'];
        } else {
            $mat->next_prefix = null;
            $mat->next_seri = null;
        }
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        
        $categoryId = $request->input('category_id');
        $destinationId = $request->input('destination_id');
        $yearFilter = $request->input('year', date('Y')); 

        $sortBy = $request->input('sort_by', 'sppm_no');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSortColumns = ['sppm_no', 'sppm_date', 'created_at', 'destination_name']; 
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'sppm_no';
        }
        
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $modelTable = (new OutSppm)->getTable();
        // HAPUS RELASI .stock YANG BIKIN ERROR
        $query = OutSppm::with(['destination', 'details.material', 'logs.outStocks', 'updater'])
                    ->select($modelTable . '.*');

        if ($search) {
            $query->where(function ($q) use ($search, $modelTable) {
                $q->where($modelTable . '.sppm_no', 'like', "%{$search}%")
                  ->orWhereHas('destination', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($categoryId) {
            $query->whereHas('details.material', function($q) use ($categoryId) {
                $q->where('material_category_id', $categoryId);
            });
        }

        if ($destinationId) {
            $query->where($modelTable . '.destination_id', $destinationId);
        }

        if ($yearFilter) {
            $query->whereYear($modelTable . '.sppm_date', $yearFilter);
        }

        if ($sortBy === 'destination_name') {
            $query->leftJoin('destinations', $modelTable . '.destination_id', '=', 'destinations.id')
                  ->orderBy('destinations.name', $sortDir);
        } elseif ($sortBy === 'sppm_no') {
            $query->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(" . $modelTable . ".sppm_no, '/', 2), '/', -1) AS UNSIGNED) $sortDir");
        } else {
            $query->orderBy($modelTable . '.' . $sortBy, $sortDir);
        }

        $outbounds = $query->paginate($limit)->withQueryString();

        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $destinations = Destination::orderBy('nomor_urut', 'asc')->get();

        $years = OutSppm::selectRaw('YEAR(sppm_date) as year')
                    ->distinct()
                    ->orderBy('year', 'desc')
                    ->pluck('year');

        $currentYear = (int) date('Y');
        if (!$years->contains($currentYear)) {
            $years->prepend($currentYear);
        }

        return view('outbound.index', compact('outbounds', 'search', 'limit', 'categories', 'destinations', 'sortBy', 'sortDir', 'categoryId', 'destinationId', 'years', 'yearFilter'));
    }

    public function create()
    {
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $destinations = Destination::orderBy('nomor_urut', 'asc')->get();

        $currentYear = date('Y');
        $currentMonth = date('n');

        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romanMonths[$currentMonth];

        $latestSppm = OutSppm::where('sppm_no', 'like', "SPPM/%/%/{$currentYear}/DITLANTAS")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1; 

        if ($latestSppm) {
            $parts = explode('/', $latestSppm->sppm_no);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $nextNumber = (int)$parts[1] + 1;
            }
        }

        $generatedSppm = "SPPM/{$nextNumber}/{$romanMonth}/{$currentYear}/DITLANTAS";
        $isLocked = false;
        
        return view('outbound.form', compact('categories', 'destinations', 'generatedSppm', 'isLocked'));
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

        $allowMinusStock = \App\Models\Setting::where('key', 'allow_minus_stock')->value('value') == '1';

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

            $log = null;
            if ($action === 'final') {
                $log = OutLog::create([
                    'out_sppm_id'  => $sppm->id,
                    'batch_number' => 1,
                    'tgl_keluar'   => $request->sppm_date,
                    'keterangan'   => 'Realisasi keluar manual.',
                ]);
            }

            $hasItems = false;

            foreach ($request->items as $item) {
                $qty = (int) ($item['target_qty'] ?? 0);
                if ($qty <= 0) continue;
                
                $hasItems = true;
                $material = Material::find($item['material_id']);
                
                $seriesList = [];
                $isSerialized = false;
                
                if ($material->pakai_seri == 1 && !empty($item['serials'])) {
                    $isSerialized = true;
                    $totalSeriQty = 0;
                    
                    foreach ($item['serials'] as $seriReq) {
                        $pfx = strtoupper(preg_replace('/[^a-zA-Z]/', '', $seriReq['prefix'] ?? ''));
                        $sAw = preg_replace('/[^0-9]/', '', $seriReq['start'] ?? '');
                        $sAk = preg_replace('/[^0-9]/', '', $seriReq['end'] ?? '');
                        
                        if ($sAw !== '' && $sAk !== '') {
                            $sqty = ((int)$sAk - (int)$sAw) + 1;
                            $seriesList[] = [
                                'prefix' => $pfx, 
                                'awal'   => (int)$sAw, 
                                'akhir'  => (int)$sAk, 
                                'qty'    => $sqty
                            ];
                            $totalSeriQty += $sqty;
                        }
                    }
                    
                    if ($totalSeriQty > 0) {
                        $qty = $totalSeriQty;
                    }
                }

                $hargaSatuan = $item['harga_satuan'] ?? 0;
                $hargaTotal = $qty * $hargaSatuan;

                OutDetail::create([
                    'out_sppm_id'  => $sppm->id,
                    'material_id'  => $material->id,
                    'target_qty'   => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'harga_total'  => $hargaTotal,
                ]);

                // LOGIKA LEDGER: Langsung insert log pengeluaran, tidak perlu loop baris stok!
                if ($action === 'final') {
                    $inQty = InStock::where('material_id', $material->id)->sum('qty_received');
                    $outQty = OutStock::where('material_id', $material->id)->sum('qty_keluar');
                    $availableStock = $inQty - $outQty;
                    
                    if (!$allowMinusStock && $qty > $availableStock) {
                        throw new \Exception("GAGAL DISIMPAN: Jumlah keluar [{$material->name}] adalah {$qty}, sedangkan stok tersedia hanya {$availableStock}. Mode Transaksi Stok Minus Dinonaktifkan.");
                    }

                    if ($isSerialized && !empty($seriesList)) {
                        foreach ($seriesList as $seri) {
                            OutStock::create([
                                'out_log_id'  => $log->id,
                                'material_id' => $material->id,
                                'qty_keluar'  => $seri['qty'],
                                'prefix'      => $seri['prefix'],
                                'seri_awal'   => $seri['awal'],
                                'seri_akhir'  => $seri['akhir'],
                            ]);
                        }
                    } else {
                        OutStock::create([
                            'out_log_id'  => $log->id,
                            'material_id' => $material->id,
                            'qty_keluar'  => $qty,
                            'prefix'      => null,
                            'seri_awal'   => null,
                            'seri_akhir'  => null,
                        ]);
                    } 
                }
            }

            if (!$hasItems) {
                throw new \Exception("SPPM harus memiliki minimal satu barang dengan target jumlah keluar lebih dari 0.");
            }

            if (method_exists($this, 'recordLog')) {
                $this->recordLog('CREATED', 'DOKUMEN SPPM KELUAR', $sppm->id, null, [
                    'Nomor SPPM' => $sppm->sppm_no,
                    'Tanggal'    => $sppm->sppm_date,
                    'Tujuan'     => $destination->name ?? 'Unknown',
                    'Status'     => $sppm->status
                ]);
            }

            DB::commit();
            $msg = $action === 'final' ? 'Dokumen berhasil disimpan dan dibukukan ke Ledger.' : 'Dokumen berhasil disimpan sebagai DRAFT.';
            return redirect()->route('outbounds.index')->with('success', $msg);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors($e->getMessage());
        }
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

        $allowMinusStock = \App\Models\Setting::where('key', 'allow_minus_stock')->value('value') == '1';
        $destination = Destination::find($request->destination_id);

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
             $newChanges['Status'] = 'Draft di-Finalisasi, tercatat di Ledger.';
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

            $log = null;
            if ($action === 'final') {
                $log = OutLog::create([
                    'out_sppm_id'  => $sppm->id,
                    'batch_number' => 1,
                    'tgl_keluar'   => $request->sppm_date,
                    'keterangan'   => 'Realisasi keluar otomatis (Update dari Draft).',
                ]);
            }

            $hasItems = false;

            foreach ($request->items as $item) {
                $qty = (int) ($item['target_qty'] ?? 0);
                if ($qty <= 0) continue;
                
                $hasItems = true;
                $material = Material::find($item['material_id']);
                
                $seriesList = [];
                $isSerialized = false;
                
                if ($material->pakai_seri == 1 && !empty($item['serials'])) {
                    $isSerialized = true;
                    $totalSeriQty = 0;
                    
                    foreach ($item['serials'] as $seriReq) {
                        $pfx = strtoupper(preg_replace('/[^a-zA-Z]/', '', $seriReq['prefix'] ?? ''));
                        $sAw = preg_replace('/[^0-9]/', '', $seriReq['start'] ?? '');
                        $sAk = preg_replace('/[^0-9]/', '', $seriReq['end'] ?? '');
                        
                        if ($sAw !== '' && $sAk !== '') {
                            $sqty = ((int)$sAk - (int)$sAw) + 1;
                            $seriesList[] = [
                                'prefix' => $pfx, 
                                'awal'   => (int)$sAw, 
                                'akhir'  => (int)$sAk, 
                                'qty'    => $sqty
                            ];
                            $totalSeriQty += $sqty;
                        }
                    }
                    
                    if ($totalSeriQty > 0) {
                        $qty = $totalSeriQty;
                    }
                }

                $hargaSatuan = $item['harga_satuan'] ?? 0;
                $hargaTotal = $qty * $hargaSatuan;

                OutDetail::create([
                    'out_sppm_id'  => $sppm->id,
                    'material_id'  => $material->id,
                    'target_qty'   => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'harga_total'  => $hargaTotal,
                ]);

                if ($action === 'final') {
                    $inQty = InStock::where('material_id', $material->id)->sum('qty_received');
                    $outQty = OutStock::where('material_id', $material->id)->sum('qty_keluar');
                    $availableStock = $inQty - $outQty;
                    
                    if (!$allowMinusStock && $qty > $availableStock) {
                        throw new \Exception("GAGAL DISIMPAN: Jumlah keluar [{$material->name}] adalah {$qty}, sedangkan stok tersedia hanya {$availableStock}.");
                    }

                    if ($isSerialized && !empty($seriesList)) {
                        foreach ($seriesList as $seri) {
                            OutStock::create([
                                'out_log_id'  => $log->id,
                                'material_id' => $material->id,
                                'qty_keluar'  => $seri['qty'],
                                'prefix'      => $seri['prefix'],
                                'seri_awal'   => $seri['awal'],
                                'seri_akhir'  => $seri['akhir'],
                            ]);
                        }
                    } else {
                        OutStock::create([
                            'out_log_id'  => $log->id,
                            'material_id' => $material->id,
                            'qty_keluar'  => $qty,
                            'prefix'      => null,
                            'seri_awal'   => null,
                            'seri_akhir'  => null,
                        ]);
                    } 
                }
            }

            if (!$hasItems) {
                throw new \Exception("SPPM harus memiliki minimal satu barang dengan target jumlah keluar lebih dari 0.");
            }

            if (method_exists($this, 'recordLog')) {
                $this->recordLog('UPDATED', 'DOKUMEN SPPM KELUAR', $sppm->id, $oldChanges, $newChanges);
            }

            DB::commit();
            $msg = $action === 'final' ? 'Draft berhasil di-Finalisasi dan tercatat di Ledger.' : 'DRAFT berhasil diperbarui.';
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

    public function destroy($id)
    {
        $sppm = OutSppm::with('logs.outStocks')->findOrFail($id);

        $deletedSppmNo = $sppm->sppm_no;
        $deletedSppmDate = $sppm->sppm_date;

        DB::beginTransaction();
        try {
            // LEDGER: Hapus Log & OutStock cukup untuk mengembalikan nilai stok In-Out
            foreach ($sppm->logs as $log) {
                $log->outStocks()->delete();
                $log->delete();
            }
            
            $this->recordLog('DELETED', 'DOKUMEN SPPM KELUAR', $sppm->id, [
                'Nomor SPPM Dihapus' => $deletedSppmNo,
                'Tanggal SPPM'       => $deletedSppmDate
            ], null);

            $sppm->details()->delete();
            $sppm->delete(); 
            
            DB::commit();
            return redirect()->route('outbounds.index')->with('success', 'Dokumen Keluar berhasil dihapus dan stok dikembalikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }

    public function massDestroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids) || !is_array($ids)) {
            return redirect()->back()->with('error', 'Tidak ada data SPPM yang dipilih untuk dihapus.');
        }

        DB::beginTransaction();
        try {
            $deletedCount = 0;
            $deletedDocs = [];

            foreach ($ids as $id) {
                $sppm = OutSppm::with('logs.outStocks')->find($id);
                if (!$sppm) continue;

                $deletedSppmNo = $sppm->sppm_no;
                $deletedDocs[] = $deletedSppmNo;

                foreach ($sppm->logs as $log) {
                    $log->outStocks()->delete();
                    $log->delete();
                }
                
                if (method_exists($this, 'recordLog')) {
                    $this->recordLog('DELETED_MASS', 'DOKUMEN SPPM KELUAR', $sppm->id, [
                        'Nomor SPPM Dihapus' => $deletedSppmNo,
                    ], null);
                }

                $sppm->details()->delete();
                $sppm->delete(); 
                $deletedCount++;
            }

            DB::commit();
            return redirect()->route('outbounds.index')->with('success', "Sebanyak $deletedCount Dokumen SPPM Keluar berhasil dihapus massal.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal melakukan penghapusan massal: ' . $e->getMessage());
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
            $this->calculateMaterialStock($mat);
            
            if ($mat->children) {
                $mat->children->each(function($child) {
                    $this->calculateMaterialStock($child);
                });
            }
        });

        return response()->json($materials);
    }

    public function print($id)
    {
        // HAPUS RELASI .stock YANG BIKIN ERROR
        $sppm = OutSppm::with([
            'destination', 
            'details.material', 
            'logs.outStocks', 
            'creator'
        ])->findOrFail($id);

        if ($sppm->status !== 'completed') {
            abort(403, 'Hanya dokumen yang sudah berstatus FINAL yang dapat dicetak.');
        }

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

    public function downloadTemplate(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:material_categories,id']);
        
        $categoryId = $request->input('category_id');
        $category = MaterialCategory::findOrFail($categoryId);

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

                if (count($data) < 11) continue; 

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

                $cleanPrefix = preg_replace('/[^a-zA-Z]/', '', $prefixRaw);
                $prefix = $cleanPrefix !== '' ? strtoupper($cleanPrefix) : null;
                $seriAwal = (!empty($seriAwalRaw) && $seriAwalRaw !== '-') ? (int) str_replace(['.', ','], '', $seriAwalRaw) : null;
                $seriAkhir = (!empty($seriAkhirRaw) && $seriAkhirRaw !== '-') ? (int) str_replace(['.', ','], '', $seriAkhirRaw) : null;

                $destination = Destination::where('name', 'like', $tujuanStr)->first();
                if (!$destination) {
                    throw new \Exception("GAGAL! Tujuan Pengiriman '{$tujuanStr}' pada SPPM '{$noSppm}' tidak ditemukan di Master Data Tujuan.");
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
                    'keterangan'   => 'Import otomatis via CSV',
                ]);

                foreach ($flatMaterials as $idx => $material) {
                    $colIndex = 11 + $idx; 
                    $qty = isset($data[$colIndex]) ? (int) str_replace(['.', ','], '', $data[$colIndex]) : 0;

                    if ($qty > 0) {
                        $inQty = InStock::where('material_id', $material->id)->sum('qty_received');
                        $outQty = OutStock::where('material_id', $material->id)->sum('qty_keluar');
                        $availableStock = $inQty - $outQty;

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

                        // LOGIKA LEDGER: Cukup simpan 1 baris
                        OutStock::create([
                            'out_log_id'  => $log->id,
                            'material_id' => $material->id,
                            'qty_keluar'  => $qty,
                            'prefix'      => $prefix,
                            'seri_awal'   => $seriAwal,
                            'seri_akhir'  => $seriAkhir,
                        ]);
                    }
                }
                
                $insertedDataCount++;
                $importedSppms[] = $noSppm; 
            }
            fclose($handle);
            
            if ($insertedDataCount === 0) {
                throw new \Exception("Sistem membaca file, tetapi tidak ada baris data yang valid.");
            }

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

        return redirect()->route('outbounds.index')->with('success', "Data Barang Keluar berhasil diimport ($insertedDataCount baris dokumen SPPM).");
    }

    public function fixOldDataOutbound()
    {
        if (!auth()->user()->can('Setting Menu')) {
            abort(403, 'Anda tidak memiliki otorisasi untuk mengeksekusi script ini.');
        }

        DB::beginTransaction();
        try {
            $sppms = OutSppm::with('details.material')->get();
            $insertedCount = 0;

            foreach ($sppms as $sppm) {
                $firstDetail = $sppm->details->first();
                
                if ($firstDetail && $firstDetail->material) {
                    $categoryId = $firstDetail->material->material_category_id;
                    $materials = Material::where('material_category_id', $categoryId)->get();
                    $existingMaterialIds = $sppm->details->pluck('material_id')->toArray();

                    foreach ($materials as $material) {
                        if (!in_array($material->id, $existingMaterialIds)) {
                            OutDetail::create([
                                'out_sppm_id'  => $sppm->id,
                                'material_id'  => $material->id,
                                'target_qty'   => 0,
                                'harga_satuan' => 0,
                                'harga_total'  => 0,
                            ]);
                            $insertedCount++;
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('outbounds.index')->with('success', "Proses Auto-Fix Outbound Selesai! Sebanyak {$insertedCount} baris materiil (QTY 0) berhasil disuntikkan.");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('outbounds.index')->with('error', "Gagal melakukan Auto-Fix: " . $e->getMessage());
        }
    }

    public function show($id)
    {
        $outbound = OutSppm::with('details')->findOrFail($id);
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $destinations = Destination::orderBy('nomor_urut', 'asc')->get();
        
        $firstDetail = $outbound->details->first();
        $selectedCategoryId = $firstDetail ? $firstDetail->material->material_category_id : null;

        $isReadonly = true;

        return view('outbound.form', compact('categories', 'destinations', 'outbound', 'selectedCategoryId', 'isReadonly'));
    }
}
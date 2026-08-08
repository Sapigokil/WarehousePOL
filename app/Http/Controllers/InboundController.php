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
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InboundImport;

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
        
        // Default tahun ke tahun saat ini
        $tahun = $request->input('tahun', date('Y'));
        $perPage = $limit === 'all' ? 999999 : $limit;

        // Default sort ke sppm_no (terbesar)
        $sortBy = $request->input('sort_by', 'sppm_no');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSortColumns = ['sppm_no', 'sppm_date', 'created_at']; 
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'sppm_no';
        }
        
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query = InSppm::with([
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
            });

        // Logika Sorting Algoritma Matematika khusus untuk Kolom SPPM NO
        if ($sortBy === 'sppm_no') {
            $query->orderByRaw("CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(sppm_no, '/', 2), '/', -1) AS UNSIGNED) $sortDir");
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $sppms = $query->paginate($perPage)->withQueryString();

        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        
        // Mengambil daftar tahun yang tersedia dari data sppm_date
        $availableYears = InSppm::selectRaw('YEAR(sppm_date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // Memastikan tahun berjalan tetap ada di dropdown opsi pilihan filter
        $currentYear = (int) date('Y');
        if (!$availableYears->contains($currentYear)) {
            $availableYears->prepend($currentYear);
        }

        return view('inbound.index', compact(
            'sppms', 'categories', 'search', 'limit', 
            'category_id', 'bulan', 'tahun', 'availableYears', 
            'sortBy', 'sortDir'
        ));
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
            'file_lampiran'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'items'                => 'required|array',
            'items.*.material_id'  => 'required|exists:materials,id',
            'items.*.target_qty'   => 'nullable|numeric|min:0', 
        ]);

        $currentMode = $request->input('inbound_mode');
        $batchDate = $currentMode === 'mode-2' ? $request->input('batch_date') : $request->sppm_date;

        $lampiranPath = null;
        if ($request->hasFile('file_lampiran')) {
            $file = $request->file('file_lampiran');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-]/', '_', $file->getClientOriginalName());
            $lampiranPath = $file->storeAs('inbound_attachments', $filename, 'public');
        }

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
                    if ($lampiranPath && Storage::disk('public')->exists($lampiranPath)) {
                        Storage::disk('public')->delete($lampiranPath); 
                    }
                    return back()->withInput()->with('error', "GAGAL! Terdapat rentang Nomor Seri yang tumpang tindih (duplikat) pada Master Stock untuk prefix {$prefix}.");
                }
            }
        }

        DB::transaction(function () use ($request, $currentMode, $batchDate, $lampiranPath) {
            $sppm = InSppm::create([
                'sppm_no'              => $request->sppm_no,
                'sppm_date'            => $request->sppm_date,
                'material_category_id' => $request->material_category_id,
                'warehouse_id'         => $request->warehouse_id,
                'notes'                => $request->notes_manifes,
                'file_lampiran'        => $lampiranPath, 
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
                $targetQty = (int) ($item['target_qty'] ?? 0);
                $qtyReceived = $currentMode === 'mode-1' ? $targetQty : (int) ($item['qty_received'] ?? 0);
                
                $sppmPrefix = $item['sppm_serial_prefix'] ?? null;
                $sppmStart = isset($item['sppm_serial_start']) ? (int) str_replace('.', '', $item['sppm_serial_start']) : null;
                $sppmEnd = isset($item['sppm_serial_end']) ? (int) str_replace('.', '', $item['sppm_serial_end']) : null;

                $realPrefix = $item['serial_prefix'] ?? null;
                $realStart = isset($item['serial_start']) ? (int) str_replace('.', '', $item['serial_start']) : null;
                $realEnd = isset($item['serial_end']) ? (int) str_replace('.', '', $item['serial_end']) : null;

                // --- PERBAIKAN: Selalu simpan Detail untuk menampilkan semua list material di view ---
                InDetail::create([
                    'in_sppm_id'        => $sppm->id,
                    'material_id'       => $item['material_id'],
                    'target_qty'        => $targetQty,
                    'qty_huruf'         => $item['qty_huruf'] ?? null,
                    'harga_satuan'      => $item['harga_satuan'] ?? 0,
                    'harga_total'       => $item['harga_total'] ?? 0,
                    'sppm_serial_prefix'=> $sppmPrefix,
                    'sppm_serial_start' => $sppmStart,
                    'sppm_serial_end'   => $sppmEnd,
                ]);

                if ($qtyReceived < $targetQty) {
                    $isAllCompleted = false;
                }

                // Proses fisik stok gudang HANYA jika masuk
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

            if ($currentMode === 'mode-2') {
                $sppm->update(['status' => $isAllCompleted ? 'completed' : 'partial']);
            }

            $this->recordLog('CREATED', 'DOKUMEN SPPM', $sppm->id, null, [
                'Nomor SPPM'        => $sppm->sppm_no,
                'Tanggal SPPM'      => $sppm->sppm_date,
                'Kategori Material' => $sppm->category->name ?? $sppm->material_category_id,
                'Lampiran'          => $lampiranPath ? 'Ada Lampiran' : 'Kosong',
                'Status'            => $sppm->status,
                'Keterangan'        => $sppm->notes
            ]);
        });

        $redirect = $request->input('submit_action') === 'save_new' ? route('inbound.create') : route('inbound.index');
        return redirect($redirect)->with('success', 'Data berhasil diverifikasi dan disimpan.');
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
            'file_lampiran'=> 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'items'        => 'required|array',
        ]);

        $lampiranPath = $sppm->file_lampiran;
        if ($request->hasFile('file_lampiran')) {
            if ($sppm->file_lampiran && Storage::disk('public')->exists($sppm->file_lampiran)) {
                Storage::disk('public')->delete($sppm->file_lampiran);
            }
            $file = $request->file('file_lampiran');
            $filename = time() . '_' . preg_replace('/[^A-Za-z0-9\.\-]/', '_', $file->getClientOriginalName());
            $lampiranPath = $file->storeAs('inbound_attachments', $filename, 'public');
        }

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
        if ($sppm->file_lampiran != $lampiranPath) {
            $oldChanges['Lampiran'] = $sppm->file_lampiran ? 'Ada Lampiran' : 'Kosong';
            $newChanges['Lampiran'] = $lampiranPath ? 'Lampiran Baru Diunggah' : 'Kosong';
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

        DB::transaction(function () use ($request, $sppm, $oldSppmNo, $oldChanges, $newChanges, $lampiranPath) {
            $sppm->update([
                'sppm_no'      => $request->sppm_no,
                'sppm_date'    => $request->sppm_date,
                'warehouse_id' => $request->warehouse_id,
                'notes'        => $request->notes_manifes,
                'file_lampiran'=> $lampiranPath, 
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

                    // --- PERBAIKAN: Selalu perbarui/masukkan Detail agar utuh di Master View ---
                    InDetail::updateOrCreate(
                        ['in_sppm_id' => $sppm->id, 'material_id' => $item['material_id']],
                        [
                            'target_qty'        => $item['target_qty'],
                            'qty_huruf'         => $item['qty_huruf'] ?? null,
                            'harga_satuan'      => $item['harga_satuan'] ?? 0,
                            'harga_total'       => $item['harga_total'] ?? 0,
                            'sppm_serial_prefix'=> $sppmPrefix,
                            'sppm_serial_start' => $sppmStart,
                            'sppm_serial_end'   => $sppmEnd,
                        ]
                    );

                    $firstLog = $sppm->logs()->where('batch_number', 1)->first();
                    if ($firstLog) {
                        // Proses update ke tabel Stock HANYA jika qty diketik > 0
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

            $this->recordLog('UPDATED', 'DOKUMEN SPPM', $sppm->id, $oldChanges, $newChanges);
        });

        return redirect()->route('inbound.index')->with('success', 'Pembaruan Dokumen & Seri berhasil disimpan.');
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
            
            // Hapus File Fisik jika ada saat menghapus data
            if ($sppm->file_lampiran && Storage::disk('public')->exists($sppm->file_lampiran)) {
                Storage::disk('public')->delete($sppm->file_lampiran);
            }

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

    // --- FUNGSI DOWNLOAD TEMPLATE EXCEL INBOUND - NATIVE XLSX ---
    public function downloadTemplate(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:material_categories,id']);
        
        $categoryId = $request->input('category_id');
        $category = \App\Models\MaterialCategory::findOrFail($categoryId);

        $topLevelMaterials = \App\Models\Material::with(['children' => function($q) {
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

        // Inisiasi PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Styling Default
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Helper untuk mengubah index angka menjadi huruf kolom (1=A, 2=B, dst)
        $colString = function($c) {
            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
        };

        $headerRows = $hasChildren ? 3 : 2;

        // =================================================================
        // BARIS 1: HEADER UTAMA
        // =================================================================
        $headersAwal = [
            'NO', 
            "TGL PENERIMAAN\n(YYYY-MM-DD)", 
            "TGL SPPM\n(YYYY-MM-DD)", 
            'NO. SPPM KORLANTAS', 
            'NOMOR SERI', 
            'NO. BAPPM'
        ];
        
        $col = 1;
        foreach ($headersAwal as $head) {
            $sheet->setCellValue($colString($col) . '1', $head);
            $sheet->mergeCells($colString($col) . '1:' . $colString($col) . $headerRows);
            $sheet->getStyle($colString($col) . '1')->getFont()->setBold(true);
            $sheet->getStyle($colString($col) . "1:" . $colString($col) . $headerRows)
                  ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                  ->getStartColor()->setARGB('FFF8F9FA'); // Background light gray
            $col++;
        }

        // Blok Rincian Barang
        $startColBarang = $col;
        $endColBarang = $col + $flatMaterials->count() - 1;
        
        $sheet->setCellValue($colString($startColBarang) . '1', 'RINCIAN BARANG KATEGORI: ' . strtoupper($category->name) . ' (QTY)');
        $sheet->mergeCells($colString($startColBarang) . '1:' . $colString($endColBarang) . '1');
        $sheet->getStyle($colString($startColBarang) . '1')->getFont()->setBold(true);
        $sheet->getStyle($colString($startColBarang) . '1')
              ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFD1E7DD'); // Background light green

        // Kolom Harga Satuan di Ujung Kanan
        $colHarga = $endColBarang + 1;
        $sheet->setCellValue($colString($colHarga) . '1', "HARGA SATUAN\n(Rp)");
        $sheet->mergeCells($colString($colHarga) . '1:' . $colString($colHarga) . $headerRows);
        $sheet->getStyle($colString($colHarga) . '1')->getFont()->setBold(true);
        $sheet->getStyle($colString($colHarga) . "1:" . $colString($colHarga) . $headerRows)
              ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
              ->getStartColor()->setARGB('FFF8F9FA');

        // =================================================================
        // BARIS 2 (DAN BARIS 3 JIKA ADA ANAK): SUB-HEADER BARANG
        // =================================================================
        $subCol = $startColBarang;
        
        foreach ($topLevelMaterials as $mat) {
            $anakCount = $mat->children->count();
            
            if ($anakCount > 0) {
                // Parent memiliki Anak
                $sheet->setCellValue($colString($subCol) . '2', strtoupper($mat->name));
                $sheet->mergeCells($colString($subCol) . '2:' . $colString($subCol + $anakCount - 1) . '2');
                $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
                $sheet->getStyle($colString($subCol) . '2:' . $colString($subCol + $anakCount - 1) . '2')
                      ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->getStartColor()->setARGB('FFBADCE3'); 

                // Tulis nama Anak-anaknya di Baris 3
                $childCol = $subCol;
                foreach ($mat->children as $child) {
                    $sheet->setCellValue($colString($childCol) . '3', $child->name);
                    $sheet->getStyle($colString($childCol) . '3')->getFont()->setBold(true);
                    $sheet->getStyle($colString($childCol) . '3')
                          ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                          ->getStartColor()->setARGB('FFE2E3E5');
                    $childCol++;
                }
                $subCol += $anakCount;

            } else {
                // Parent Tunggal (Tanpa Anak)
                $sheet->setCellValue($colString($subCol) . '2', strtoupper($mat->name));
                
                if ($hasChildren) {
                    // Merge sampai baris 3 jika sheet ini memiliki baris ke-3
                    $sheet->mergeCells($colString($subCol) . '2:' . $colString($subCol) . '3');
                }
                
                $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
                $sheet->getStyle($colString($subCol) . '2:' . $colString($subCol) . ($hasChildren ? '3' : '2'))
                      ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                      ->getStartColor()->setARGB('FFD1E7DD'); 
                $subCol++;
            }
        }

        // =================================================================
        // BARIS DUMMY DATA
        // =================================================================
        $rowDummy = $headerRows + 1;

        $sheet->setCellValue('A' . $rowDummy, '1');
        $sheet->setCellValue('B' . $rowDummy, date('Y-m-d'));
        $sheet->setCellValue('C' . $rowDummy, date('Y-m-d'));
        $sheet->setCellValue('D' . $rowDummy, 'SPPM/001/VII/' . date('Y'));
        $sheet->setCellValue('E' . $rowDummy, 'H. 01.300.001 - 01.400.000');
        $sheet->setCellValue('F' . $rowDummy, 'BAPPM-001');

        $colData = 7; // Dimulai dari Kolom G
        foreach ($flatMaterials as $mat) {
            $sheet->setCellValue($colString($colData) . $rowDummy, '500'); 
            $colData++;
        }
        
        // Harga Dummy
        $sheet->setCellValue($colString($colData) . $rowDummy, '150000'); 

        $maxColAlpha = $colString($colHarga);

        // =================================================================
        // FORMATTING (AUTO SIZE & BORDERS)
        // =================================================================
        // Borders
        $sheet->getStyle("A1:{$maxColAlpha}{$rowDummy}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        // AutoSize
        foreach (range(1, $colHarga) as $columnID) {
            $sheet->getColumnDimension($colString($columnID))->setAutoSize(true);
        }
        
        // Wrap Text Header Surat (Kolom B dan C) dan Kolom Harga
        $sheet->getStyle("B1:C1")->getAlignment()->setWrapText(true);
        $sheet->getStyle("{$maxColAlpha}1")->getAlignment()->setWrapText(true);

        // =================================================================
        // OUTPUT FILE MURNI XLSX
        // =================================================================
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'Template_Inbound_' . str_replace(' ', '_', strtoupper($category->name)) . '_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:material_categories,id',
            'excel_file'  => 'required|file|mimes:xls,xlsx,csv'
        ]);

        $categoryId = $request->input('category_id');
        $file = $request->file('excel_file');
        
        try {
            Excel::import(new InboundImport($categoryId), $file);
            return redirect()->route('inbound.index')->with('success', "Data Inbound berhasil diimport. Sistem juga telah melakukan Auto-Reconciliation pada utang stok jika ada.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }
    }

    /**
     * SCRIPT AUTO-FIX (ONE-TIME RUN)
     * Untuk menyuntikkan baris materiil ber-qty 0 ke dokumen SPPM Inbound lama.
     */
    public function fixOldDataInbound()
    {
        // Proteksi agar hanya role tertentu yang bisa mengakses script ini
        if (!auth()->user()->can('Setting Menu')) {
            abort(403, 'Anda tidak memiliki otorisasi untuk mengeksekusi script ini.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Ambil semua dokumen SPPM beserta detailnya
            $sppms = \App\Models\InSppm::with('details')->get();
            $insertedCount = 0;

            foreach ($sppms as $sppm) {
                $categoryId = $sppm->material_category_id;

                // Ambil semua materiil (induk dan anak) yang berada di bawah kategori dokumen ini
                $materials = \App\Models\Material::where('material_category_id', $categoryId)->get();
                
                // Kumpulkan ID materiil yang sudah ada di dokumen ini
                $existingMaterialIds = $sppm->details->pluck('material_id')->toArray();

                foreach ($materials as $material) {
                    // Jika materiil dari Master belum ada di dokumen SPPM lama ini, suntikkan!
                    if (!in_array($material->id, $existingMaterialIds)) {
                        \App\Models\InDetail::create([
                            'in_sppm_id'         => $sppm->id,
                            'material_id'        => $material->id,
                            'target_qty'         => 0,
                            'qty_huruf'          => null,
                            'harga_satuan'       => 0,
                            'harga_total'        => 0,
                            'sppm_serial_prefix' => null,
                            'sppm_serial_start'  => null,
                            'sppm_serial_end'    => null,
                        ]);
                        $insertedCount++;
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('inbound.index')->with('success', "Proses Auto-Fix Inbound Selesai! Sebanyak {$insertedCount} baris materiil (QTY 0) berhasil disuntikkan ke dalam dokumen lama.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->route('inbound.index')->with('error', "Gagal melakukan Auto-Fix: " . $e->getMessage());
        }
    }

    public function show($id)
    {
        $inbound = InSppm::with([
            'category', 
            'warehouse', 
            'details.material', 
            'logs' => function($q) {
                $q->orderBy('batch_number', 'asc');
            }, 
            'logs.stocks', 
            'creator', 
            'updater'
        ])->findOrFail($id);

        return view('inbound.show', compact('inbound'));
    }
}
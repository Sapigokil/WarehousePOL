<?php

namespace App\Http\Controllers;

use App\Models\InSppm;
use App\Models\InDetail;
use App\Models\InLog;
use App\Models\InStock;
use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
    // Mode Default Sistem: 'mode-1' (SPPM Mutlak) atau 'mode-2' (Realita / Parsial)
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
                'category', 
                'details.material', 
                'logs' => function($q) {
                    $q->orderBy('batch_number', 'asc');
                }, 
                'logs.stocks',
                'updater', 
                'creator'  
            ])
            ->when($search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('sppm_no', 'like', '%' . $search . '%')
                      ->orWhereHas('details.material', function($q2) use ($search) {
                          $q2->where('name', 'like', '%' . $search . '%')
                             ->orWhere('code', 'like', '%' . $search . '%');
                      })
                      ->orWhereHas('details', function($q3) use ($search) {
                          $q3->where('sppm_serial_start', 'like', '%' . $search . '%')
                             ->orWhere('sppm_serial_end', 'like', '%' . $search . '%');
                      })
                      ->orWhereHas('logs.stocks', function($q4) use ($search) {
                          $q4->where('serial_start', 'like', '%' . $search . '%')
                             ->orWhere('serial_end', 'like', '%' . $search . '%');
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
        $inboundMode = $this->inboundMode;

        return view('inbound.form', compact('categories', 'inboundMode'))->with('inbound', null);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sppm_no'              => 'required|string|max:255|unique:in_sppms,sppm_no',
            'sppm_date'            => 'required|date',
            'material_category_id' => 'required|exists:material_categories,id',
            'inbound_mode'         => 'required|string|in:mode-1,mode-2',
            'items'                => 'required|array',
            'items.*.material_id'  => 'required|exists:materials,id',
            'items.*.target_qty'   => 'nullable|numeric|min:0', 
        ]);

        $currentMode = $request->input('inbound_mode');
        $batchDate = $currentMode === 'mode-2' ? $request->input('batch_date') : $request->sppm_date;

        DB::transaction(function () use ($request, $currentMode, $batchDate) {
            // 1. Catat Main Manifest SPPM & Log User Pembuat
            $sppm = InSppm::create([
                'sppm_no'              => $request->sppm_no,
                'sppm_date'            => $request->sppm_date,
                'material_category_id' => $request->material_category_id,
                'notes'                => $request->notes_manifes,
                'status'               => $currentMode === 'mode-1' ? 'completed' : 'pending',
                'created_by'           => auth()->id(),
                'updated_by'           => auth()->id()
            ]);

            // 2. Registrasi System Log Kedatangan Fisik (Tahap 1)
            $log = InLog::create([
                'in_sppm_id'   => $sppm->id,
                'batch_number' => 1,
                'receive_date' => $batchDate,
                'receiver_name'=> auth()->user()->name ?? 'Admin Gudang',
                'notes'        => $request->batch_notes ?? 'Penerimaan awal berkas komoditas SPPM Tahap 1.'
            ]);

            $isAllCompleted = true;

            foreach ($request->items as $item) {
                if (isset($item['target_qty']) && $item['target_qty'] > 0) {
                    
                    // 3. Simpan rincian data manifest dokumen ke in_details
                    InDetail::create([
                        'in_sppm_id'        => $sppm->id,
                        'material_id'       => $item['material_id'],
                        'target_qty'        => $item['target_qty'],
                        'qty_huruf'         => $item['qty_huruf'] ?? null,
                        'harga_satuan'      => $item['harga_satuan'] ?? 0,
                        'harga_total'       => $item['harga_total'] ?? 0,
                        'sppm_serial_start' => $item['sppm_serial_start'] ?? null,
                        'sppm_serial_end'   => $item['sppm_serial_end'] ?? null,
                    ]);

                    // Otomatisasi jumlah stok masuk berdasarkan mode operasional yang aktif
                    $qtyReceived = $currentMode === 'mode-1' ? $item['target_qty'] : ($item['qty_received'] ?? 0);
                    
                    if ($qtyReceived < $item['target_qty']) {
                        $isAllCompleted = false;
                    }

                    // 4. Update dan isi data ke dalam table stock (Ledger Masuk) jika kuantitas > 0
                    if ($qtyReceived > 0) {
                        InStock::create([
                            'in_log_id'    => $log->id,
                            'material_id'  => $item['material_id'],
                            'qty_received' => $qtyReceived,
                            'serial_start' => $currentMode === 'mode-2' ? ($item['serial_start'] ?? null) : null,
                            'serial_end'   => $currentMode === 'mode-2' ? ($item['serial_end'] ?? null) : null,
                        ]);
                    }
                }
            }

            if ($currentMode === 'mode-2') {
                $sppm->update(['status' => $isAllCompleted ? 'completed' : 'partial']);
            }
        });

        if ($request->input('submit_action') === 'save_new') {
            return redirect()->route('inbound.create')->with('success', 'Data SPPM, System Log, dan Sinkronisasi Stok berhasil disimpan.');
        }

        return redirect()->route('inbound.index')->with('success', 'Data SPPM, System Log, dan Sinkronisasi Stok berhasil disimpan.');
    }

    public function edit($id)
    {
        $inbound = InSppm::with(['details.material', 'logs' => function($q){
            $q->orderBy('batch_number', 'asc');
        }, 'logs.stocks'])->findOrFail($id);
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $inboundMode = $this->inboundMode;

        return view('inbound.form', compact('inbound', 'categories', 'inboundMode'));
    }

    public function update(Request $request, $id)
    {
        $sppm = InSppm::with('details', 'logs.stocks')->findOrFail($id);
        $currentMode = $request->input('inbound_mode');

        if ($currentMode === 'mode-2') {
            $request->validate([
                'batch_date'          => 'required|date',
                'items'               => 'required|array',
                'items.*.material_id' => 'required|exists:materials,id',
                'items.*.qty_received'=> 'nullable|numeric|min:0'
            ]);

            DB::transaction(function () use ($request, $sppm) {
                $nextBatch = $sppm->logs()->max('batch_number') + 1;

                // 1. Tambah baris baru pada System Log Kedatangan Fisik (Tahap Lanjutan)
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

                    // 2. Tambah data mutasi masuk baru ke table stock (in_stocks)
                    if ($qtyReceived > 0) {
                        InStock::create([
                            'in_log_id'    => $log->id,
                            'material_id'  => $item['material_id'],
                            'qty_received' => $qtyReceived,
                            'serial_start' => $item['serial_start'] ?? null,
                            'serial_end'   => $item['serial_end'] ?? null,
                        ]);
                    }

                    // Re-kalkulasi keutuhan data untuk menentukan status dokumen
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

            return redirect()->route('inbound.index')->with('success', 'Penerimaan fisik Tahap Baru berhasil dicatat ke System Log dan Table Stock.');
        }

        // KONDISI MODE-1: Update murni mengubah manifes kertas & memperbarui stock Tahap 1 agar tetap sinkron
        $request->validate([
            'sppm_no'   => 'required|string|max:255|unique:in_sppms,sppm_no,' . $sppm->id,
            'sppm_date' => 'required|date',
            'items'     => 'required|array',
        ]);

        DB::transaction(function () use ($request, $sppm) {
            $sppm->update([
                'sppm_no'    => $request->sppm_no,
                'sppm_date'  => $request->sppm_date,
                'notes'      => $request->notes_manifes,
                'updated_by' => auth()->id()
            ]);

            foreach ($request->items as $item) {
                if (isset($item['target_qty'])) {
                    // 1. Update data manifes di table in_details
                    InDetail::where('in_sppm_id', $sppm->id)
                        ->where('material_id', $item['material_id'])
                        ->update([
                            'target_qty'        => $item['target_qty'],
                            'qty_huruf'         => $item['qty_huruf'] ?? null,
                            'harga_satuan'      => $item['harga_satuan'] ?? 0,
                            'harga_total'       => $item['harga_total'] ?? 0,
                            'sppm_serial_start' => $item['sppm_serial_start'] ?? null,
                            'sppm_serial_end'   => $item['sppm_serial_end'] ?? null,
                        ]);

                    // 2. Ambil System Log Tahap 1 untuk menyinkronkan ulang isi Table Stock
                    $firstLog = $sppm->logs()->where('batch_number', 1)->first();
                    if ($firstLog) {
                        if ($item['target_qty'] > 0) {
                            InStock::updateOrCreate(
                                ['in_log_id' => $firstLog->id, 'material_id' => $item['material_id']],
                                [
                                    'qty_received' => $item['target_qty'],
                                    'serial_start' => $item['serial_start'] ?? null,
                                    'serial_end'   => $item['serial_end'] ?? null
                                ]
                            );
                        } else {
                            // Hapus dari table stock jika kuantitas diubah menjadi 0
                            InStock::where('in_log_id', $firstLog->id)
                                ->where('material_id', $item['material_id'])
                                ->delete();
                        }
                    }
                }
            }
        });

        return redirect()->route('inbound.index')->with('success', 'Manifes SPPM, System Log, dan Table Stock Berhasil Diperbarui.');
    }

    public function destroy($id)
    {
        // KONDISI DESTROY: Menghapus total seluruh keterkaitan data agar table stock bersih dari transaksi ini
        DB::transaction(function () use ($id) {
            $sppm = InSppm::with('logs')->findOrFail($id);

            // 1. Bersihkan Table Stock berdasarkan ID log/tahap yang beraliansi dengan SPPM ini
            foreach ($sppm->logs as $log) {
                InStock::where('in_log_id', $log->id)->delete();
            }

            // 2. Hapus Log Tahap Kedatangan (System Log)
            $sppm->logs()->delete();

            // 3. Hapus Rincian Target Manifes (InDetail)
            $sppm->details()->delete();

            // 4. Hapus Dokumen Utama (InSppm)
            $sppm->delete();
        });

        return redirect()->route('inbound.index')->with('success', 'Dokumen SPPM, Riwayat System Log, dan Seluruh Table Stock terkait berhasil dibersihkan.');
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

        return response()->json($materials);
    }
}
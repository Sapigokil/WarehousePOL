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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OutboundController extends Controller
{
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

        return view('outbound.index', compact('outbounds', 'search', 'limit'));
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
            
            $sppm = OutSppm::create([
                'sppm_no'        => $request->sppm_no,
                'sppm_date'      => $request->sppm_date,
                'destination_id' => $request->destination_id,
                'keterangan'     => $request->keterangan,
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
                    
                    // PENGAMAN: Cek apakah jumlah melebihi ketersediaan stok fisik gudang
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

            // PROSES REALISASI OTOMATIS: Potong Stok & Nomor Seri jika status Final
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

                        // Perhitungan pecahan nomor seri
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
        
        // Ambil kategori dari item pertama untuk men-*trigger* JS otomatis me-load daftar barang
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

        $sppm = OutSppm::findOrFail($id);

        if ($sppm->status === 'completed') {
            return back()->withErrors('Dokumen yang sudah Final / Selesai tidak dapat diubah kembali.');
        }

        DB::beginTransaction();
        try {
            $action = $request->input('action_type');

            $sppm->update([
                'sppm_no'        => $request->sppm_no,
                'sppm_date'      => $request->sppm_date,
                'destination_id' => $request->destination_id,
                'keterangan'     => $request->keterangan,
                'status'         => $action === 'final' ? 'completed' : 'pending',
                'updated_by'     => Auth::id(),
            ]);

            // Hapus detail lama, ganti dengan yang baru
            $sppm->details()->delete();

            $hasItems = false;
            $itemsToProcess = [];

            foreach ($request->items as $item) {
                $qty = (int) ($item['target_qty'] ?? 0);
                if ($qty > 0) {
                    $hasItems = true;

                    // PENGAMAN STOK
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

            // PROSES REALISASI OTOMATIS JIKA FINAL
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
            
            // Susun antrean FIFO untuk dikirim ke JS
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

        return view('outbound.print', compact('sppm'));
    }
}
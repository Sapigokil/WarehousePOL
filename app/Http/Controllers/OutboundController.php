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
            'items'          => 'required|array',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.target_qty'  => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $sppm = OutSppm::create([
                'sppm_no'        => $request->sppm_no,
                'sppm_date'      => $request->sppm_date,
                'destination_id' => $request->destination_id,
                'keterangan'     => $request->keterangan,
                'status'         => 'pending', 
                'created_by'     => Auth::id(),
                'updated_by'     => Auth::id(),
            ]);

            $hasItems = false;

            foreach ($request->items as $item) {
                if (isset($item['target_qty']) && $item['target_qty'] > 0) {
                    $hasItems = true;
                    OutDetail::create([
                        'out_sppm_id'  => $sppm->id,
                        'material_id'  => $item['material_id'],
                        'target_qty'   => $item['target_qty'],
                        'harga_satuan' => $item['harga_satuan'] ?? 0,
                        'harga_total'  => $item['harga_total'] ?? 0,
                    ]);
                }
            }

            if (!$hasItems) {
                throw new \Exception("SPPM harus memiliki minimal satu barang dengan target jumlah lebih dari 0.");
            }

            DB::commit();
            return redirect()->route('outbounds.index')->with('success', 'Dokumen SPPM Keluar berhasil diregistrasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $outbound = OutSppm::with(['destination', 'details.material', 'logs.outStocks'])->findOrFail($id);
        
        $stockTotals = Stock::selectRaw('material_id, SUM(qty) as total_qty')
            ->whereIn('material_id', $outbound->details->pluck('material_id'))
            ->groupBy('material_id')
            ->pluck('total_qty', 'material_id')
            ->toArray();

        return view('outbound.form_realization', compact('outbound', 'stockTotals'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tgl_keluar' => 'required|date',
            'items'      => 'required|array',
            'items.*.qty_keluar' => 'nullable|integer|min:0',
        ]);

        $sppm = OutSppm::with('details.material')->findOrFail($id);

        DB::beginTransaction();
        try {
            $batchNumber = $sppm->logs()->count() + 1;
            $hasRealisasi = false;

            $log = OutLog::create([
                'out_sppm_id'  => $sppm->id,
                'batch_number' => $batchNumber,
                'tgl_keluar'   => $request->tgl_keluar,
                'keterangan'   => $request->keterangan_log,
            ]);

            foreach ($sppm->details as $detail) {
                $qtyKeluar = (int) ($request->items[$detail->id]['qty_keluar'] ?? 0);
                
                if ($qtyKeluar > 0) {
                    $hasRealisasi = true;
                    $sisaKebutuhan = $qtyKeluar;

                    $availableStocks = Stock::where('material_id', $detail->material_id)
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

                        if ($detail->material->pakai_seri == 1 && $stock->seri_awal !== null) {
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

                    if ($sisaKebutuhan > 0) {
                        throw new \Exception("Stok fisik untuk " . $detail->material->name . " tidak mencukupi. Kurang: " . $sisaKebutuhan);
                    }
                }
            }

            if (!$hasRealisasi) {
                throw new \Exception("Tidak ada jumlah barang keluar yang diinputkan.");
            }

            $allCompleted = true;
            $hasPartial = false;

            foreach ($sppm->details as $detail) {
                $totalKeluar = OutStock::whereHas('outLog', function($q) use ($sppm) {
                    $q->where('out_sppm_id', $sppm->id);
                })->whereHas('stock', function($q) use ($detail) {
                    $q->where('material_id', $detail->material_id);
                })->sum('qty_keluar');

                if ($totalKeluar > 0) $hasPartial = true;
                if ($totalKeluar < $detail->target_qty) $allCompleted = false;
            }

            $sppm->status = $allCompleted ? 'completed' : ($hasPartial ? 'partial' : 'pending');
            $sppm->updated_by = Auth::id();
            $sppm->save();

            DB::commit();
            return redirect()->route('outbounds.index')->with('success', 'Realisasi barang keluar berhasil disimpan.');

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
}
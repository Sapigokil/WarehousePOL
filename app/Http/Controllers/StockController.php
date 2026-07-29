<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Material;
use App\Models\Warehouse;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category_filter = $request->input('category_id');

        $categories = MaterialCategory::with(['materials' => function($q) use ($search) {
            $q->whereNull('parent_id')
              ->when($search, function($query) use ($search) {
                  
                  $stockSearchQuery = function($sub) use ($search) {
                      $cleanNum = preg_replace('/[^0-9]/', '', $search);
                      $cleanNum = $cleanNum !== '' ? (int)$cleanNum : null;
                      
                      $prefixStr = trim(preg_replace('/[0-9.\-]/', '', $search));

                      $sub->select('material_id')
                          ->from('stocks')
                          ->where('no_surat_masuk', 'like', "%{$search}%")
                          ->orWhere('prefix', 'like', "%{$search}%");

                      if ($cleanNum !== null) {
                          $sub->orWhere(function($q) use ($cleanNum, $prefixStr) {
                              $q->where('seri_awal', '<=', $cleanNum)
                                ->where('seri_akhir', '>=', $cleanNum);
                              
                              if (!empty($prefixStr)) {
                                  $q->where('prefix', 'like', "%{$prefixStr}%");
                              }
                          });
                      }
                  };

                  $query->where(function($q2) use ($search, $stockSearchQuery) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%")
                         ->orWhereIn('id', $stockSearchQuery)
                         ->orWhereHas('children', function($q3) use ($search, $stockSearchQuery) {
                             $q3->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhereIn('id', $stockSearchQuery);
                         });
                  });
              })
              ->with(['children' => function($q2) {
                  $q2->orderBy('nomor_urut', 'asc');
              }])
              ->orderBy('nomor_urut', 'asc');
        }])
        ->when($category_filter, function($q) use ($category_filter) {
            return $q->where('id', $category_filter);
        })
        ->orderBy('nomor_urut', 'asc')->get();

        $stockTotals = Stock::join('materials', 'stocks.material_id', '=', 'materials.id')
            ->selectRaw('stocks.material_id, SUM(CASE WHEN materials.pakai_seri = 1 AND stocks.qty < 0 THEN 0 ELSE stocks.qty END) as total_qty')
            ->groupBy('stocks.material_id')
            ->pluck('total_qty', 'stocks.material_id')
            ->toArray();

        $allCategories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        return view('stocks.stock_index', compact('categories', 'stockTotals', 'search', 'category_filter', 'allCategories'));
    }

    public function show(Request $request, $id)
    {
        $material = Material::with('category')->findOrFail($id);
        
        $search = $request->input('search');
        $sortBy = $request->input('sort', 'tgl_masuk'); // default sort
        $sortOrder = $request->input('order', 'desc'); // default order

        $query = Stock::with('warehouse')
            ->where('material_id', $id)
            ->where('qty', '!=', 0);

        // Filter Pencarian
        if (!empty($search) && $material->pakai_seri == 1) {
            $query->where(function($q) use ($search) {
                $cleanNum = preg_replace('/[^0-9]/', '', $search);
                $cleanNum = $cleanNum !== '' ? (int)$cleanNum : null;
                $prefixStr = trim(preg_replace('/[0-9.\-]/', '', $search));

                $q->where('no_surat_masuk', 'like', "%{$search}%")
                  ->orWhere('prefix', 'like', "%{$search}%");

                if ($cleanNum !== null) {
                    $q->orWhere(function($q2) use ($cleanNum, $prefixStr) {
                        $q2->where('seri_awal', '<=', $cleanNum)
                           ->where('seri_akhir', '>=', $cleanNum);
                        
                        if (!empty($prefixStr)) {
                            $q2->where('prefix', 'like', "%{$prefixStr}%");
                        }
                    });
                }
            });
        }

        // Sorting Logika
        $allowedSorts = ['no_surat_masuk', 'tgl_masuk', 'warehouse_id', 'seri_awal', 'qty'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('tgl_masuk', 'desc')->orderBy('created_at', 'desc');
        }

        $allStockDetails = $query->get();

        $normalStocks = $allStockDetails->where('qty', '>', 0)->values();
        $minusStocks  = $allStockDetails->where('qty', '<', 0)->values();

        if ($material->pakai_seri == 1) {
            $totalStock = $normalStocks->sum('qty');
            
            // --- REVISI: GROUPING BERDASARKAN PREFIX & TAHUN ---
            if ($normalStocks->isNotEmpty()) {
                $normalStocks = $normalStocks->groupBy(function($item) {
                    $prefix = $item->prefix ?: 'TANPA PREFIX';
                    $tahun = date('Y', strtotime($item->tgl_masuk));
                    return $prefix . ' - TAHUN ' . $tahun;
                });
            } else {
                $normalStocks = collect();
            }

        } else {
            $totalStock = $allStockDetails->sum('qty');
            
            if ($normalStocks->isNotEmpty()) {
                $groupedNormal = collect();
                foreach ($normalStocks->groupBy('warehouse_id') as $wId => $stocks) {
                    $firstStock = $stocks->first();
                    $mergedStock = new \stdClass();
                    $mergedStock->no_surat_masuk = strtoupper($firstStock->warehouse->name ?? 'GUDANG UTAMA');
                    $mergedStock->tgl_masuk = $stocks->max('tgl_masuk');
                    $mergedStock->warehouse = (object)['name' => $firstStock->warehouse->name ?? '-'];
                    $mergedStock->prefix = null;
                    $mergedStock->seri_awal = null;
                    $mergedStock->seri_akhir = null;
                    $mergedStock->qty = $stocks->sum('qty');
                    $mergedStock->keterangan = 'Akumulasi Total Fisik di Gudang';
                    
                    $groupedNormal->push($mergedStock);
                }
                $normalStocks = $groupedNormal;
                
                // Urutkan ulang array virtual (karena stdClass) jika disort selain tanggal
                if ($sortBy == 'qty') {
                    $normalStocks = $sortOrder == 'asc' ? $normalStocks->sortBy('qty') : $normalStocks->sortByDesc('qty');
                } elseif ($sortBy == 'no_surat_masuk') {
                    $normalStocks = $sortOrder == 'asc' ? $normalStocks->sortBy('no_surat_masuk') : $normalStocks->sortByDesc('no_surat_masuk');
                }
            }
        }

        // Penggabungan Stok Minus sesuai Kesepakatan (Merge Contiguous)
        $totalMinusQty = 0;
        $mergedMinusRanges = [];

        if ($minusStocks->isNotEmpty()) {
            $totalMinusQty = $minusStocks->sum('qty');

            if ($material->pakai_seri == 1) {
                $groupedByPrefix = $minusStocks->groupBy('prefix');

                foreach ($groupedByPrefix as $prefix => $stocks) {
                    $ranges = $stocks->map(function($item) {
                        return [
                            'awal'  => $item->seri_awal,
                            'akhir' => $item->seri_akhir,
                        ];
                    })->sortBy('awal')->values()->toArray();

                    $merged = [];
                    foreach ($ranges as $range) {
                        if (empty($merged)) {
                            $merged[] = $range;
                        } else {
                            $lastIndex = count($merged) - 1;
                            if ($range['awal'] <= $merged[$lastIndex]['akhir'] + 1) {
                                $merged[$lastIndex]['akhir'] = max($merged[$lastIndex]['akhir'], $range['akhir']);
                            } else {
                                $merged[] = $range;
                            }
                        }
                    }

                    foreach ($merged as $m) {
                        $mergedMinusRanges[] = [
                            'prefix' => $prefix,
                            'awal'   => $m['awal'],
                            'akhir'  => $m['akhir']
                        ];
                    }
                }
            }
        }

        return view('stocks.stock_detail', compact(
            'material', 'normalStocks', 'totalStock', 'totalMinusQty', 'mergedMinusRanges', 'sortBy', 'sortOrder'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_surat_masuk' => 'required|string|max:100',
            'tgl_masuk'      => 'required|date',
            'material_id'    => 'required|integer',
            'warehouse_id'   => 'required|integer',
            'qty'            => 'required|numeric|min:1',
            'harga_satuan'   => 'nullable|numeric|min:0',
            'seri_awal'      => 'nullable|string',
            'seri_akhir'     => 'nullable|string',
            'keterangan'     => 'nullable|string',
        ]);

        $data = $request->all();
        $hargaSatuan = $request->input('harga_satuan', 0);
        $data['total_harga'] = $request->qty * $hargaSatuan;
        $data['status'] = 'Tersedia';

        Stock::create($data);

        return redirect()->route('stocks.index')->with('success', 'Penyesuaian stok manual berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $stock = Stock::findOrFail($id);

        $request->validate([
            'no_surat_masuk' => 'required|string|max:100',
            'tgl_masuk'      => 'required|date',
            'material_id'    => 'required|integer',
            'warehouse_id'   => 'required|integer',
            'qty'            => 'required|numeric|min:0',
            'harga_satuan'   => 'nullable|numeric|min:0',
            'seri_awal'      => 'nullable|string',
            'seri_akhir'     => 'nullable|string',
            'status'         => 'required|string',
            'keterangan'     => 'nullable|string',
        ]);

        $data = $request->all();
        $hargaSatuan = $request->input('harga_satuan', 0);
        $data['total_harga'] = $request->qty * $hargaSatuan;

        $stock->update($data);

        return redirect()->route('stocks.index')->with('success', 'Data stok berhasil diubah/dikoreksi.');
    }

    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();

        return redirect()->route('stocks.index')->with('success', 'Data stok berhasil dihapus.');
    }
}
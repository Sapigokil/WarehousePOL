<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\OutStock;
use App\Models\InStock;
use App\Models\MaterialCategory;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrackingController extends Controller
{
    public function index()
    {
        $categories = MaterialCategory::all();
        return view('tracking.index', compact('categories'));
    }

    public function search(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:material_categories,id',
            'search_type' => 'required|in:single,range',
            'prefix'      => 'nullable|string|max:50',
            'seri_awal'   => 'required|numeric',
            'seri_akhir'  => 'nullable|required_if:search_type,range|numeric',
        ]);

        $searchData = $request->all();
        
        $categoryId = $searchData['category_id'] ?? null;
        
        // Membersihkan nilai prefix
        $rawPrefix = $searchData['prefix'] ?? null;
        $prefix = trim((string)$rawPrefix) === '' ? null : trim((string)$rawPrefix);

        $serialStart = (int) $searchData['seri_awal'];
        $serialEnd = (int) ($searchData['seri_akhir'] ?? $serialStart);

        $singleResult = null;
        $rangeResults = [];

        // Deteksi SoftDeletes
        $applyWithTrashed = function($q) {
            if (in_array(SoftDeletes::class, class_uses_recursive($q->getModel()))) {
                $q->withTrashed();
            }
        };

        // Aturan Pencarian Prefix
        $applyPrefixFilter = function($q) use ($prefix) {
            if ($prefix !== null) {
                $q->where('prefix', $prefix);
            }
        };

        // Aturan Pencarian Kategori
        $applyCategoryToStock = function($q) use ($categoryId) {
            if ($categoryId) {
                $q->whereHas('material', function($mQ) use ($categoryId) {
                    $mQ->where('material_category_id', $categoryId);
                });
            }
        };

        $categories = MaterialCategory::all(); 

        if ($searchData['search_type'] === 'single') {
            
            // ==========================================
            // TAHAP 1: CARI RIWAYAT INBOUND (MASUK)
            // ==========================================
            $inStockQuery = InStock::with(['log.sppm', 'material']);
            if ($prefix !== null) {
                $inStockQuery->where('serial_prefix', $prefix);
            }
            $applyCategoryToStock($inStockQuery);

            $inStock = $inStockQuery->where('serial_start', '<=', $serialStart)
                                    ->where('serial_end', '>=', $serialStart)
                                    ->first();

            // ==========================================
            // TAHAP 2: CARI DI LINI OUTBOUND (KELUAR)
            // ==========================================
            $outStockQuery = OutStock::with(['outLog.outSppm.destination', 'stock' => function($q) use ($applyWithTrashed) {
                $applyWithTrashed($q);
                $q->with(['material', 'warehouse']);
            }]);

            $outStockQuery->whereHas('stock', function($q) use ($applyPrefixFilter, $applyWithTrashed, $applyCategoryToStock) {
                $applyWithTrashed($q);
                $applyPrefixFilter($q);
                $applyCategoryToStock($q);
            });

            $outStock = $outStockQuery->where('seri_awal', '<=', $serialStart)
                                      ->where('seri_akhir', '>=', $serialStart)
                                      ->first();

            if ($outStock) {
                $singleResult = [
                    'stock' => $outStock->stock,
                    'inStock' => $inStock,
                    'outStock' => $outStock,
                    'status' => 'distributed'
                ];
            } else {
                // ==========================================
                // TAHAP 3: CARI DI GUDANG (STOCK)
                // ==========================================
                $stockQuery = Stock::with(['material', 'warehouse']);
                $applyWithTrashed($stockQuery);
                $applyPrefixFilter($stockQuery);
                $applyCategoryToStock($stockQuery);
                
                $stock = $stockQuery->where('seri_awal', '<=', $serialStart)
                                    ->where('seri_akhir', '>=', $serialStart)
                                    ->first();

                if ($stock) {
                    $singleResult = [
                        'stock' => $stock,
                        'inStock' => $inStock,
                        'outStock' => null,
                        'status' => 'available'
                    ];
                }
            }

        } else {
            // ==========================================
            // PENCARIAN RENTANG (RANGE) / MATRIX
            // ==========================================
            
            // A. Ambil data Outbound 
            $outStockQuery = OutStock::with(['outLog.outSppm.destination', 'stock' => function($q) use ($applyWithTrashed) {
                $applyWithTrashed($q);
                $q->with('warehouse');
            }]);

            $outStockQuery->whereHas('stock', function($q) use ($applyPrefixFilter, $applyWithTrashed, $applyCategoryToStock) {
                $applyWithTrashed($q);
                $applyPrefixFilter($q);
                $applyCategoryToStock($q);
            });

            $outStocks = $outStockQuery->where('seri_awal', '<=', $serialEnd)
                                       ->where('seri_akhir', '>=', $serialStart)
                                       ->get();

            foreach ($outStocks as $os) {
                $start = max($serialStart, $os->seri_awal);
                $end = min($serialEnd, $os->seri_akhir);
                
                $rangeResults[] = [
                    'start' => $start,
                    'end' => $end,
                    'qty' => ($end - $start) + 1,
                    'status' => 'distributed',
                    'warehouse' => $os->stock->warehouse->name ?? 'Tidak Diketahui',
                    'destination' => $os->outLog->outSppm->destination->name ?? 'Tidak Diketahui',
                    'sppm_no' => $os->outLog->outSppm->sppm_no ?? 'Tidak Diketahui',
                    // Menambahkan SPPM ID untuk tautan klik
                    'sppm_id' => $os->outLog->outSppm->id ?? null,
                ];
            }

            // B. Ambil data Stock
            $stockQuery = Stock::with(['warehouse']);
            $applyWithTrashed($stockQuery);
            $applyPrefixFilter($stockQuery);
            $applyCategoryToStock($stockQuery);
            
            $stocks = $stockQuery->where('seri_awal', '<=', $serialEnd)
                                 ->where('seri_akhir', '>=', $serialStart)
                                 ->get();

            foreach ($stocks as $st) {
                $start = max($serialStart, $st->seri_awal);
                $end = min($serialEnd, $st->seri_akhir);
                
                $rangeResults[] = [
                    'start' => $start,
                    'end' => $end,
                    'qty' => ($end - $start) + 1,
                    'status' => 'available',
                    'warehouse' => $st->warehouse->name ?? 'Tidak Diketahui',
                    'destination' => '-',
                    'sppm_no' => '-',
                    'sppm_id' => null,
                ];
            }

            // C. Urutkan hasil matriks
            usort($rangeResults, function($a, $b) {
                return $a['start'] <=> $b['start'];
            });
        }

        return view('tracking.index', compact('searchData', 'singleResult', 'rangeResults', 'categories'));
    }
}
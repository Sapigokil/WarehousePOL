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
        // Hanya tampilkan kategori yang memiliki material utama (ismain=1) dan berseri (pakai_seri=1)
        $categories = MaterialCategory::whereHas('materials', function($q) {
            $q->where('pakai_seri', 1)->where('ismain', 1);
        })->get();
        
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

        $singleResults = [];
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

        // REVISI: Aturan Pencarian Filter Material (Kategori + IsMain + Pakai Seri)
        $applyMaterialFilters = function($q) use ($categoryId) {
            $q->whereHas('material', function($mQ) use ($categoryId) {
                if ($categoryId) {
                    $mQ->where('material_category_id', $categoryId);
                }
                // Wajib material utama dan berseri
                $mQ->where('pakai_seri', 1)->where('ismain', 1);
            });
        };

        // Filter dropdown Kategori (Sama seperti di index)
        $categories = MaterialCategory::whereHas('materials', function($q) {
            $q->where('pakai_seri', 1)->where('ismain', 1);
        })->get();

        if ($searchData['search_type'] === 'single') {
            
            // ==========================================
            // TAHAP 1: CARI DI GUDANG (STOCK - AVAILABLE)
            // ==========================================
            $stockQuery = Stock::with(['material', 'warehouse']);
            $applyWithTrashed($stockQuery);
            $applyPrefixFilter($stockQuery);
            $applyMaterialFilters($stockQuery); // Gunakan filter material baru
            
            $stocks = $stockQuery->where('seri_awal', '<=', $serialStart)
                                 ->where('seri_akhir', '>=', $serialStart)
                                 ->get();

            foreach ($stocks as $stock) {
                // Cari Inbound (Masuk) terkait
                $inStock = InStock::with(['log.sppm', 'material'])
                                  ->where('material_id', $stock->material_id)
                                  ->where('serial_prefix', $stock->prefix)
                                  ->where('serial_start', '<=', $serialStart)
                                  ->where('serial_end', '>=', $serialStart)
                                  ->first();

                $singleResults[] = [
                    'stock' => $stock,
                    'inStock' => $inStock,
                    'outStock' => null,
                    'status' => 'available',
                    'prefix' => $stock->prefix
                ];
            }

            // ==========================================
            // TAHAP 2: CARI DI LINI OUTBOUND (DISTRIBUTED)
            // ==========================================
            $outStockQuery = OutStock::with(['outLog.outSppm.destination', 'stock' => function($q) use ($applyWithTrashed) {
                $applyWithTrashed($q);
                $q->with(['material', 'warehouse']);
            }]);

            $outStockQuery->whereHas('stock', function($q) use ($applyPrefixFilter, $applyWithTrashed, $applyMaterialFilters) {
                $applyWithTrashed($q);
                $applyPrefixFilter($q);
                $applyMaterialFilters($q); // Gunakan filter material baru
            });

            $outStocks = $outStockQuery->where('seri_awal', '<=', $serialStart)
                                       ->where('seri_akhir', '>=', $serialStart)
                                       ->get();

            foreach ($outStocks as $outStock) {
                $stock = $outStock->stock;
                
                // Cari Inbound (Masuk) terkait
                $inStock = null;
                if ($stock) {
                    $inStock = InStock::with(['log.sppm', 'material'])
                                      ->where('material_id', $stock->material_id)
                                      ->where('serial_prefix', $stock->prefix)
                                      ->where('serial_start', '<=', $serialStart)
                                      ->where('serial_end', '>=', $serialStart)
                                      ->first();
                }

                $singleResults[] = [
                    'stock' => $stock,
                    'inStock' => $inStock,
                    'outStock' => $outStock,
                    'status' => 'distributed',
                    'prefix' => $stock ? $stock->prefix : null
                ];
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

            $outStockQuery->whereHas('stock', function($q) use ($applyPrefixFilter, $applyWithTrashed, $applyMaterialFilters) {
                $applyWithTrashed($q);
                $applyPrefixFilter($q);
                $applyMaterialFilters($q); // Gunakan filter material baru
            });

            $outStocks = $outStockQuery->where('seri_awal', '<=', $serialEnd)
                                       ->where('seri_akhir', '>=', $serialStart)
                                       ->get();

            foreach ($outStocks as $os) {
                $start = max($serialStart, $os->seri_awal);
                $end = min($serialEnd, $os->seri_akhir);
                
                $rangeResults[] = [
                    'prefix'=> $os->prefix,
                    'start' => $start,
                    'end'   => $end,
                    'qty'   => ($end - $start) + 1,
                    'status'=> 'distributed',
                    'warehouse' => $os->stock->warehouse->name ?? 'Tidak Diketahui',
                    'destination' => $os->outLog->outSppm->destination->name ?? 'Tidak Diketahui',
                    'sppm_no' => $os->outLog->outSppm->sppm_no ?? 'Tidak Diketahui',
                    'sppm_id' => $os->outLog->outSppm->id ?? null,
                ];
            }

            // B. Ambil data Stock
            $stockQuery = Stock::with(['warehouse']);
            $applyWithTrashed($stockQuery);
            $applyPrefixFilter($stockQuery);
            $applyMaterialFilters($stockQuery); // Gunakan filter material baru
            
            $stocks = $stockQuery->where('seri_awal', '<=', $serialEnd)
                                 ->where('seri_akhir', '>=', $serialStart)
                                 ->get();

            foreach ($stocks as $st) {
                $start = max($serialStart, $st->seri_awal);
                $end = min($serialEnd, $st->seri_akhir);
                
                $rangeResults[] = [
                    'prefix'=> $st->prefix,
                    'start' => $start,
                    'end'   => $end,
                    'qty'   => ($end - $start) + 1,
                    'status'=> 'available',
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

        return view('tracking.index', compact('searchData', 'singleResults', 'rangeResults', 'categories'));
    }
}
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

        // Aturan Pencarian Prefix untuk Stock
        $applyPrefixFilter = function($q) use ($prefix) {
            if ($prefix !== null) {
                $q->where('prefix', $prefix);
            }
        };

        // Aturan Pencarian Prefix untuk OutStock
        $applyPrefixFilterOut = function($q) use ($prefix) {
            if ($prefix !== null) {
                $q->where('prefix', $prefix);
            }
        };

        // Aturan Pencarian Filter Material untuk Stock
        $applyMaterialFilters = function($q) use ($categoryId) {
            $q->whereHas('material', function($mQ) use ($categoryId) {
                if ($categoryId) {
                    $mQ->where('material_category_id', $categoryId);
                }
                $mQ->where('pakai_seri', 1)->where('ismain', 1);
            });
        };

        // Aturan Pencarian Filter Material untuk OutStock
        $applyMaterialFiltersOut = function($q) use ($categoryId) {
            $q->whereHas('material', function($mQ) use ($categoryId) {
                if ($categoryId) {
                    $mQ->where('material_category_id', $categoryId);
                }
                $mQ->where('pakai_seri', 1)->where('ismain', 1);
            });
        };

        // Filter dropdown Kategori
        $categories = MaterialCategory::whereHas('materials', function($q) {
            $q->where('pakai_seri', 1)->where('ismain', 1);
        })->get();

        if ($searchData['search_type'] === 'single') {
            
            // 1. Cari data Inbound secara mandiri
            $inboundMatches = InStock::with(['log.sppm.warehouse', 'material'])
                ->whereHas('material', function($q) use ($categoryId) {
                    if ($categoryId) {
                        $q->where('material_category_id', $categoryId);
                    }
                    $q->where('pakai_seri', 1)->where('ismain', 1);
                })
                ->when($prefix !== null, function($q) use ($prefix) {
                    $q->where('serial_prefix', $prefix);
                })
                ->where('serial_start', '<=', $serialStart)
                ->where('serial_end', '>=', $serialStart)
                ->get();

            // 2. Cari data Outbound secara mandiri
            $outboundMatches = OutStock::with(['outLog.outSppm.destination', 'material'])
                ->whereHas('material', function($q) use ($categoryId) {
                    if ($categoryId) {
                        $q->where('material_category_id', $categoryId);
                    }
                    $q->where('pakai_seri', 1)->where('ismain', 1);
                })
                ->when($prefix !== null, function($q) use ($prefix) {
                    $q->where('prefix', $prefix);
                })
                ->where('seri_awal', '<=', $serialStart)
                ->where('seri_akhir', '>=', $serialStart)
                ->get();

            // Gabungkan hasilnya secara independen ke singleResults
            // Ambil semua material_id unik yang ditemukan dari kedua sisi
            $materialIds = $inboundMatches->pluck('material_id')
                ->merge($outboundMatches->pluck('material_id'))
                ->unique();

            foreach ($materialIds as $matId) {
                $inStock = $inboundMatches->where('material_id', $matId)->first();
                $outStock = $outboundMatches->where('material_id', $matId)->first();
                
                // Tentukan referensi material & prefix dari mana saja yang ada
                $matRef = $inStock->material ?? optional($outStock)->material;
                $prefRef = $inStock->serial_prefix ?? optional($outStock)->prefix;

                // Cari data stok fisik gudang jika diperlukan untuk status
                $stock = Stock::with('warehouse')
                    ->where('material_id', $matId)
                    ->where('prefix', $prefRef)
                    ->where('seri_awal', '<=', $serialStart)
                    ->where('seri_akhir', '>=', $serialStart)
                    ->first();

                $status = $outStock ? 'distributed' : 'available';

                $singleResults[] = [
                    'stock'    => $stock,
                    'inStock'  => $inStock,  // Bisa null jika inbound tidak ada
                    'outStock' => $outStock, // Bisa null jika outbound tidak ada
                    'status'   => $status,
                    'prefix'   => $prefRef
                ];
            }
        } else {
            // ==========================================
            // PENCARIAN RENTANG (RANGE) / MATRIX
            // ==========================================
            
            // A. Ambil data Outbound 
            $outStockQuery = OutStock::with(['outLog.outSppm.destination', 'material']);
            $applyPrefixFilterOut($outStockQuery);
            $applyMaterialFiltersOut($outStockQuery);

            $outStocks = $outStockQuery->where('seri_awal', '<=', $serialEnd)
                                       ->where('seri_akhir', '>=', $serialStart)
                                       ->get();

            foreach ($outStocks as $os) {
                $start = max($serialStart, $os->seri_awal);
                $end = min($serialEnd, $os->seri_akhir);

                $stockRef = Stock::with('warehouse')
                                 ->where('material_id', $os->material_id)
                                 ->where('prefix', $os->prefix)
                                 ->first();
                
                $rangeResults[] = [
                    'prefix'=> $os->prefix,
                    'start' => $start,
                    'end'   => $end,
                    'qty'   => ($end - $start) + 1,
                    'status'=> 'distributed',
                    'warehouse' => $stockRef->warehouse->name ?? 'Tidak Diketahui',
                    'destination' => $os->outLog->outSppm->destination->name ?? 'Tidak Diketahui',
                    'sppm_no' => $os->outLog->outSppm->sppm_no ?? 'Tidak Diketahui',
                    'sppm_id' => $os->outLog->outSppm->id ?? null,
                ];
            }

            // B. Ambil data Stock
            $stockQuery = Stock::with(['warehouse'])->where('qty', '>', 0);
            $applyPrefixFilter($stockQuery);
            $applyMaterialFilters($stockQuery); 
            
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
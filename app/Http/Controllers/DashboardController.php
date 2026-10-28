<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\InSppm;
use App\Models\OutSppm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. HITUNG STOK REALISED PER MATERIAL (in_details - out_details completed)
        $inTotals = DB::table('in_details')
            ->selectRaw('material_id, SUM(target_qty) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->toArray();

        $outTotals = DB::table('out_details')
            ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
            ->where('out_sppms.status', 'completed')
            ->selectRaw('material_id, SUM(target_qty) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->toArray();

        // 2. KETAHUI MATERIAL HANYA YANG MEMILIKI rate_usage >= 0 (EXCLUDE NULL & KELOMPOK INDUK)
        $materials = Material::with(['category', 'parent'])
            ->whereNotNull('rate_usage')
            ->where('satuan', '!=', '-')
            ->get();

        $totalMaterialCount = $materials->count();
        $lowStockCount = 0;
        $criticalDepletionCount = 0;

        $lowStockItems = collect();
        $depletionItems = collect();

        foreach ($materials as $mat) {
            $in = $inTotals[$mat->id] ?? 0;
            $out = $outTotals[$mat->id] ?? 0;
            $currentStock = max(0, $in - $out);
            $mat->current_stock = $currentStock;

            // A. Warning Stok Minimal (Hanya material rate_usage >= 0)
            if ($currentStock <= $mat->minimal_stok) {
                $lowStockCount++;
                $lowStockItems->push($mat);
            }

            // B. Depleksi Stok (Kalkulasi Daya Tahan)
            if ($currentStock <= 0) {
                $mat->months_left = 0;
                $criticalDepletionCount++;
                $depletionItems->push($mat);
            } else {
                if ($mat->rate_usage > 0) {
                    $months = round($currentStock / $mat->rate_usage, 1);
                    $mat->months_left = $months;
                } else {
                    $mat->months_left = 9999; // Tanpa batas jika rate 0
                }
                
                if ($mat->months_left < 3) {
                    $criticalDepletionCount++;
                }
                
                $depletionItems->push($mat);
            }
        }

        // Urutkan Depleksi Kritis dari daya tahan yang paling terdekat (rendah)
        $depletionItems = $depletionItems->sortBy('months_left')->take(5);
        $lowStockItems = $lowStockItems->sortBy('current_stock')->take(5);

        // 3. STATISTIK DOKUMEN & TRANSAKSI HARI INI
        $pendingInboundCount = InSppm::where('status', 'draft')->count();

        // 4. DATA GRAFIK 1 TAHUN TERAKHIR (12 BULAN)
        $chartLabels = [];
        $chartInbound = [];
        $chartOutbound = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthYear = $date->format('M Y');
            $chartLabels[] = $monthYear;

            $inQty = DB::table('in_details')
                ->join('in_sppms', 'in_details.in_sppm_id', '=', 'in_sppms.id')
                ->whereYear('in_sppms.created_at', $date->year)
                ->whereMonth('in_sppms.created_at', $date->month)
                ->sum('target_qty');

            $outQty = DB::table('out_details')
                ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
                ->where('out_sppms.status', 'completed')
                ->whereYear('out_sppms.created_at', $date->year)
                ->whereMonth('out_sppms.created_at', $date->month)
                ->sum('target_qty');

            $chartInbound[] = (int) $inQty;
            $chartOutbound[] = (int) $outQty;
        }

        // 5. DOKUMEN RECENT ACTIVITY (MIX INBOUND & OUTBOUND DENGAN KATEGORI & TOTAL QTY)
        $recentInbound = InSppm::with(['warehouse', 'details.material.category'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($item) {
                $item->type = 'SPPM Masuk';
                $item->total_qty = $item->details->sum('target_qty');
                
                // Ambil daftar kategori unik dari detail barang di dokumen ini
                $categories = $item->details->map(function($d) {
                    return $d->material->category->name ?? null;
                })->filter()->unique()->values();

                $item->category_summary = $categories->count() > 0 ? $categories->implode(', ') : '-';
                return $item;
            });

        $recentOutbound = OutSppm::with(['destination', 'details.material.category'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($item) {
                $item->type = 'SPPM Keluar';
                $item->total_qty = $item->details->sum('target_qty');
                
                // Ambil daftar kategori unik dari detail barang di dokumen ini
                $categories = $item->details->map(function($d) {
                    return $d->material->category->name ?? null;
                })->filter()->unique()->values();

                $item->category_summary = $categories->count() > 0 ? $categories->implode(', ') : '-';
                return $item;
            });

        $recentActivities = $recentInbound->concat($recentOutbound)->sortByDesc('created_at')->take(6);
        
        return view('dashboard', compact(
            'totalMaterialCount',
            'lowStockCount',
            'criticalDepletionCount',
            'pendingInboundCount',
            'lowStockItems',
            'depletionItems',
            'chartLabels',
            'chartInbound',
            'chartOutbound',
            'recentActivities'
        ));
    }
}
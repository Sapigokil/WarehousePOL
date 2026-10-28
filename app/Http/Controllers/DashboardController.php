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

        // 2. KETAHUI SELURUH MATERIAL TUNGGAL & VARIAN (ECLUDED KELOMPOK INDUK)
        $materials = Material::with(['category', 'parent'])
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

            // A. Cek Warning Stok Minimal (Low Stock)
            if ($currentStock <= $mat->minimal_stok) {
                $lowStockCount++;
                $lowStockItems->push($mat);
            }

            // B. Cek Depleksi Kritis (< 3 Bulan atau Stok Habis)
            if (!is_null($mat->rate_usage) && $mat->rate_usage >= 0) {
                if ($currentStock <= 0) {
                    $mat->months_left = 0;
                    $criticalDepletionCount++;
                    $depletionItems->push($mat);
                } elseif ($mat->rate_usage > 0) {
                    $months = round($currentStock / $mat->rate_usage, 1);
                    $mat->months_left = $months;
                    if ($months < 3) {
                        $criticalDepletionCount++;
                        $depletionItems->push($mat);
                    }
                }
            }
        }

        // Urutkan Depleksi dari yang paling terancam habis (terrendah)
        $depletionItems = $depletionItems->sortBy('months_left')->take(5);
        $lowStockItems = $lowStockItems->sortBy('current_stock')->take(5);

        // 3. STATISTIK DOKUMEN & TRANSAKSI HARI INI
        $todayOutboundCount = DB::table('out_details')
            ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
            ->where('out_sppms.status', 'completed')
            ->whereDate('out_sppms.created_at', Carbon::today())
            ->sum('out_details.target_qty');

        $pendingInboundCount = InSppm::where('status', 'draft')->count();

        // 4. DATA GRAFIK 6 BULAN TERAKHIR (INBOUND VS OUTBOUND)
        $chartLabels = [];
        $chartInbound = [];
        $chartOutbound = [];

        for ($i = 5; $i >= 0; $i--) {
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

        // 5. DOKUMEN RECENT ACTIVITY (MIX INBOUND & OUTBOUND)
        $recentInbound = InSppm::with('warehouse')->latest()->take(5)->get()->map(function($item) {
            $item->type = 'INBOUND';
            return $item;
        });

        $recentOutbound = OutSppm::with('destination')->latest()->take(5)->get()->map(function($item) {
            $item->type = 'OUTBOUND';
            return $item;
        });

        $recentActivities = $recentInbound->concat($recentOutbound)->sortByDesc('created_at')->take(6);

        return view('dashboard', compact(
            'totalMaterialCount',
            'lowStockCount',
            'criticalDepletionCount',
            'todayOutboundCount',
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
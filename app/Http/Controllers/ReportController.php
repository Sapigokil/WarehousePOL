<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\InStock;
use App\Models\OutStock;
use App\Models\MaterialCategory;
use App\Models\Material;

class ReportController extends Controller
{
    /**
     * Fungsi Helper untuk menghitung mutasi per materiil (Dipakai di view dan export)
     */
    private function getMaterialMutationData($material, $isChild = false)
    {
        $totalIn = InStock::where('material_id', $material->id)->sum('qty_received');
        
        $totalOut = OutStock::whereHas('stock', function($q) use ($material) {
            $q->where('material_id', $material->id);
        })->sum('qty_keluar');

        $currentStock = Stock::where('material_id', $material->id)->sum('qty');

        return [
            'material_name' => $material->name,
            'is_child'      => $isChild,
            'total_in'      => $totalIn,
            'total_out'     => $totalOut,
            'saldo_akhir'   => $currentStock,
        ];
    }

    // Sub Menu 1: Mutasi Stock
    public function mutation(Request $request)
    {
        $categoryId = $request->input('category_id');
        
        // Ambil kategori untuk dropdown filter
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        $groupedMutations = [];

        // Query Kategori (Sesuai Filter jika ada)
        $catQuery = MaterialCategory::orderBy('nomor_urut', 'asc');
        if ($categoryId) {
            $catQuery->where('id', $categoryId);
        }
        $filteredCategories = $catQuery->get();

        foreach ($filteredCategories as $cat) {
            $categoryData = [
                'category_name' => $cat->name,
                'items' => []
            ];

            // 1. Ambil parent materials (yang tidak punya parent_id)
            $parents = Material::where('material_category_id', $cat->id)
                               ->whereNull('parent_id')
                               ->orderBy('nomor_urut', 'asc')
                               ->get();

            foreach ($parents as $parent) {
                // Masukkan data parent
                $categoryData['items'][] = $this->getMaterialMutationData($parent, false);

                // 2. Ambil children dari parent ini
                $children = Material::where('parent_id', $parent->id)
                                    ->orderBy('nomor_urut', 'asc')
                                    ->get();

                foreach ($children as $child) {
                    // Masukkan data child
                    $categoryData['items'][] = $this->getMaterialMutationData($child, true);
                }
            }

            // Hanya tampilkan kategori di tabel jika ada materiil di dalamnya
            if (count($categoryData['items']) > 0) {
                $groupedMutations[] = $categoryData;
            }
        }

        return view('reports.mutation', compact('groupedMutations', 'categories', 'categoryId'));
    }

    // Sub Menu 2: Riwayat Penerimaan (Inbound)
    public function inbound(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $query = InStock::with(['log.sppm.destination', 'material.category', 'log.sppm.warehouse'])
            ->orderBy('created_at', 'desc');

        if ($startDate && $endDate) {
            $query->whereHas('log', function($q) use ($startDate, $endDate) {
                $q->whereBetween('receive_date', [$startDate, $endDate]);
            });
        }

        $inbounds = $query->paginate(20)->withQueryString();

        return view('reports.inbound', compact('inbounds', 'startDate', 'endDate'));
    }

    // Sub Menu 3: Riwayat Distribusi (Outbound)
    public function outbound(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = OutStock::with(['outLog.outSppm.destination', 'stock.material.category', 'stock.warehouse'])
            ->orderBy('created_at', 'desc');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $outbounds = $query->paginate(20)->withQueryString();

        return view('reports.outbound', compact('outbounds', 'startDate', 'endDate'));
    }

    // --- FITUR NATIVE EXPORT EXCEL ---

    public function exportMutation(Request $request)
    {
        $fileName = 'Laporan_Mutasi_Stock_' . date('Y-m-d') . '.xls';
        $categoryId = $request->input('category_id');

        $catQuery = MaterialCategory::orderBy('nomor_urut', 'asc');
        if ($categoryId) {
            $catQuery->where('id', $categoryId);
        }
        $filteredCategories = $catQuery->get();

        $headers = [
            "Content-type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($filteredCategories) {
            $file = fopen('php://output', 'w');
            
            // Header Tabel Excel
            fputcsv($file, ['Kategori', 'Nama Materiil', 'Tipe', 'Total Masuk', 'Total Keluar', 'Saldo Akhir'], "\t");

            foreach ($filteredCategories as $cat) {
                $parents = Material::where('material_category_id', $cat->id)
                                   ->whereNull('parent_id')
                                   ->orderBy('nomor_urut', 'asc')
                                   ->get();

                foreach ($parents as $parent) {
                    $pData = $this->getMaterialMutationData($parent, false);
                    fputcsv($file, [
                        $cat->name,
                        strtoupper($pData['material_name']),
                        'Induk',
                        $pData['total_in'],
                        $pData['total_out'],
                        $pData['saldo_akhir']
                    ], "\t");

                    $children = Material::where('parent_id', $parent->id)
                                        ->orderBy('nomor_urut', 'asc')
                                        ->get();

                    foreach ($children as $child) {
                        $cData = $this->getMaterialMutationData($child, true);
                        fputcsv($file, [
                            $cat->name,
                            '   -> ' . strtoupper($cData['material_name']),
                            'Turunan',
                            $cData['total_in'],
                            $cData['total_out'],
                            $cData['saldo_akhir']
                        ], "\t");
                    }
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportInbound(Request $request)
    {
        $fileName = 'Laporan_Riwayat_Penerimaan_' . date('Y-m-d') . '.xls';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = InStock::with(['log.sppm.destination', 'material.category', 'log.sppm.warehouse'])
            ->orderBy('created_at', 'desc');

        if ($startDate && $endDate) {
            $query->whereHas('log', function($q) use ($startDate, $endDate) {
                $q->whereBetween('receive_date', [$startDate, $endDate]);
            });
        }
        $inbounds = $query->get();

        $headers = [
            "Content-type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($inbounds) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal Terima', 'No. SPPM Masuk', 'Materiil', 'Batch/Tahap', 'Rentang Seri Awal', 'Rentang Seri Akhir', 'Qty Diterima'], "\t");

            foreach ($inbounds as $row) {
                fputcsv($file, [
                    optional($row->log)->receive_date ? \Carbon\Carbon::parse($row->log->receive_date)->format('Y-m-d') : '-',
                    optional(optional($row->log)->sppm)->sppm_no ?? '-',
                    optional($row->material)->name ?? '-',
                    'Tahap ' . (optional($row->log)->batch_number ?? '-'),
                    ($row->serial_prefix ?? '') . str_pad($row->serial_start, 9, '0', STR_PAD_LEFT),
                    ($row->serial_prefix ?? '') . str_pad($row->serial_end, 9, '0', STR_PAD_LEFT),
                    $row->qty_received
                ], "\t");
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportOutbound(Request $request)
    {
        $fileName = 'Laporan_Riwayat_Distribusi_' . date('Y-m-d') . '.xls';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = OutStock::with(['outLog.outSppm.destination', 'stock.material.category', 'stock.warehouse'])
            ->orderBy('created_at', 'desc');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }
        $outbounds = $query->get();

        $headers = [
            "Content-type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($outbounds) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal Keluar', 'No. SPPM Keluar', 'Tujuan Distribusi', 'Materiil', 'Rentang Seri Awal', 'Rentang Seri Akhir', 'Qty Keluar'], "\t");

            foreach ($outbounds as $row) {
                fputcsv($file, [
                    \Carbon\Carbon::parse($row->created_at)->format('Y-m-d H:i:s'),
                    optional(optional(optional($row->outLog)->outSppm))->sppm_no ?? '-',
                    optional(optional(optional(optional($row->outLog)->outSppm)->destination))->name ?? '-',
                    optional(optional($row->stock)->material)->name ?? '-',
                    (optional($row->stock)->prefix ?? '') . str_pad($row->seri_awal, 9, '0', STR_PAD_LEFT),
                    (optional($row->stock)->prefix ?? '') . str_pad($row->seri_akhir, 9, '0', STR_PAD_LEFT),
                    $row->qty_keluar
                ], "\t");
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
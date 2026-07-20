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
    // Sub Menu 1: Mutasi Stock
    public function mutation(Request $request)
    {
        $categoryId = $request->input('category_id');
        $categories = MaterialCategory::all();

        $materialsQuery = Material::with('category');
        if ($categoryId) {
            $materialsQuery->where('material_category_id', $categoryId);
        }
        $materials = $materialsQuery->get();

        $mutations = [];
        foreach ($materials as $mat) {
            $totalIn = InStock::where('material_id', $mat->id)->sum('qty_received');
            
            $totalOut = OutStock::whereHas('stock', function($q) use ($mat) {
                $q->where('material_id', $mat->id);
            })->sum('qty_keluar');

            $currentStock = Stock::where('material_id', $mat->id)->sum('qty');

            $mutations[] = [
                'material_name' => $mat->name,
                'category_name' => $mat->category->name ?? '-',
                'total_in'      => $totalIn,
                'total_out'     => $totalOut,
                'saldo_akhir'   => $currentStock,
            ];
        }

        return view('reports.mutation', compact('mutations', 'categories', 'categoryId'));
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

        $materialsQuery = Material::with('category');
        if ($categoryId) {
            $materialsQuery->where('material_category_id', $categoryId);
        }
        $materials = $materialsQuery->get();

        $headers = [
            "Content-type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($materials) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nama Materiil', 'Kategori', 'Total Masuk (Inbound)', 'Total Keluar (Outbound)', 'Saldo Akhir (Gudang)'], "\t");

            foreach ($materials as $mat) {
                $totalIn = InStock::where('material_id', $mat->id)->sum('qty_received');
                $totalOut = OutStock::whereHas('stock', function($q) use ($mat) {
                    $q->where('material_id', $mat->id);
                })->sum('qty_keluar');
                $currentStock = Stock::where('material_id', $mat->id)->sum('qty');

                fputcsv($file, [
                    $mat->name,
                    $mat->category->name ?? '-',
                    $totalIn,
                    $totalOut,
                    $currentStock
                ], "\t");
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
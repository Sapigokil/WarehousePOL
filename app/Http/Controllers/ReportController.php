<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\InStock;
use App\Models\OutStock;
use App\Models\MaterialCategory;
use App\Models\Material;
use App\Models\SystemLog;

class ReportController extends Controller
{
    /**
     * Fungsi Helper Privat untuk Mencatat Log Sistem
     */
    private function recordLog($action, $tableName, $recordId, $oldValues, $newValues)
    {
        SystemLog::create([
            'user_id'    => auth()->id(),
            'username'   => auth()->user()->name ?? 'Sistem',
            'action'     => strtoupper($action),
            'table_name' => strtoupper($tableName),
            'record_id'  => (string) $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Fungsi Helper untuk menghitung mutasi per materiil
     */
    private function getMaterialMutationData($material, $isChild = false, $startDate = null, $endDate = null)
    {
        $inQuery = InStock::where('material_id', $material->id);
        $outQuery = OutStock::whereHas('stock', function($q) use ($material) {
            $q->where('material_id', $material->id);
        });

        if ($startDate && $endDate) {
            $inQuery->whereHas('log', function($q) use ($startDate, $endDate) {
                $q->whereBetween('receive_date', [$startDate, $endDate]);
            });
            $outQuery->whereHas('outLog', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tgl_keluar', [$startDate, $endDate]);
            });

            $totalInUpToDate = InStock::where('material_id', $material->id)
                ->whereHas('log', function($q) use ($endDate) {
                    $q->where('receive_date', '<=', $endDate);
                })->sum('qty_received');
                
            $totalOutUpToDate = OutStock::whereHas('stock', function($q) use ($material) {
                $q->where('material_id', $material->id);
            })->whereHas('outLog', function($q) use ($endDate) {
                $q->where('tgl_keluar', '<=', $endDate);
            })->sum('qty_keluar');

            $currentStock = $totalInUpToDate - $totalOutUpToDate;
        } else {
            $currentStock = Stock::where('material_id', $material->id)->sum('qty');
        }

        return [
            'material_name' => $material->name,
            'is_child'      => $isChild,
            'total_in'      => $inQuery->sum('qty_received'),
            'total_out'     => $outQuery->sum('qty_keluar'),
            'saldo_akhir'   => $currentStock,
        ];
    }

    /**
     * Fungsi Helper untuk menghitung histori transaksi INBOUND per materiil
     */
    private function getMaterialInboundData($material, $isChild = false, $hasChildren = false, $startDate = null, $endDate = null)
    {
        $query = InStock::with(['log.sppm.warehouse'])->where('material_id', $material->id);

        if ($startDate && $endDate) {
            $query->whereHas('log', function($q) use ($startDate, $endDate) {
                $q->whereBetween('receive_date', [$startDate, $endDate]);
            });
        }

        $transactions = $query->get()->sortByDesc(function($stock) {
            return $stock->log->receive_date ?? '';
        })->values();

        return [
            'material_id'   => $material->id,
            'material_name' => $material->name,
            'satuan'        => $material->satuan,
            'is_child'      => $isChild,
            'has_children'  => $hasChildren,
            'total_in'      => $transactions->sum('qty_received'),
            'transactions'  => $transactions,
        ];
    }

    /**
     * Fungsi Helper untuk menghitung histori transaksi OUTBOUND per materiil
     */
    private function getMaterialOutboundData($material, $isChild = false, $hasChildren = false, $startDate = null, $endDate = null)
    {
        $query = OutStock::with(['outLog.outSppm.destination', 'stock'])
            ->whereHas('stock', function($q) use ($material) {
                $q->where('material_id', $material->id);
            });

        if ($startDate && $endDate) {
            $query->whereHas('outLog', function($q) use ($startDate, $endDate) {
                $q->whereBetween('tgl_keluar', [$startDate, $endDate]);
            });
        }

        $transactions = $query->get()->sortByDesc(function($outStock) {
            return $outStock->outLog->tgl_keluar ?? '';
        })->values();

        return [
            'material_id'   => $material->id,
            'material_name' => $material->name,
            'satuan'        => $material->satuan,
            'is_child'      => $isChild,
            'has_children'  => $hasChildren,
            'total_out'     => $transactions->sum('qty_keluar'),
            'transactions'  => $transactions,
        ];
    }

    // Sub Menu 1: Mutasi Stock
    public function mutation(Request $request)
    {
        if (!$request->has('start_date') && !$request->has('end_date') && !$request->has('category_id')) {
            $startDate = date('Y-m-01'); 
            $endDate = date('Y-m-d');    
            $categoryId = null;
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $categoryId = $request->input('category_id');
        }
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $groupedMutations = [];

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

            $parents = Material::where('material_category_id', $cat->id)
                               ->whereNull('parent_id')
                               ->orderBy('nomor_urut', 'asc')
                               ->get();

            foreach ($parents as $parent) {
                $children = Material::where('parent_id', $parent->id)->orderBy('nomor_urut', 'asc')->get();
                $hasChildren = $children->count() > 0;

                $pData = $this->getMaterialMutationData($parent, false, $startDate, $endDate);
                $pData['has_children'] = $hasChildren;
                $categoryData['items'][] = $pData;

                foreach ($children as $child) {
                    $cData = $this->getMaterialMutationData($child, true, $startDate, $endDate);
                    $cData['has_children'] = false; 
                    $categoryData['items'][] = $cData;
                }
            }

            if (count($categoryData['items']) > 0) {
                $groupedMutations[] = $categoryData;
            }
        }

        return view('reports.mutation', compact('groupedMutations', 'categories', 'categoryId', 'startDate', 'endDate'));
    }

    // Sub Menu 2: Riwayat Penerimaan (Inbound)
    public function inbound(Request $request)
    {
        if (!$request->has('start_date') && !$request->has('end_date') && !$request->has('category_id')) {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');
            $categoryId = null;
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $categoryId = $request->input('category_id');
        }
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $groupedInbounds = [];

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

            $parents = Material::where('material_category_id', $cat->id)
                               ->whereNull('parent_id')
                               ->orderBy('nomor_urut', 'asc')
                               ->get();

            foreach ($parents as $parent) {
                $children = Material::where('parent_id', $parent->id)->orderBy('nomor_urut', 'asc')->get();
                $hasChildren = $children->count() > 0;

                $pData = $this->getMaterialInboundData($parent, false, $hasChildren, $startDate, $endDate);
                $categoryData['items'][] = $pData;

                foreach ($children as $child) {
                    $cData = $this->getMaterialInboundData($child, true, false, $startDate, $endDate);
                    $categoryData['items'][] = $cData;
                }
            }

            if (count($categoryData['items']) > 0) {
                $groupedInbounds[] = $categoryData;
            }
        }

        return view('reports.inbound', compact('groupedInbounds', 'categories', 'categoryId', 'startDate', 'endDate'));
    }

    // Sub Menu 3: Riwayat Distribusi (Outbound)
    public function outbound(Request $request)
    {
        if (!$request->has('start_date') && !$request->has('end_date') && !$request->has('category_id')) {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');
            $categoryId = null;
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $categoryId = $request->input('category_id');
        }
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $groupedOutbounds = [];

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

            $parents = Material::where('material_category_id', $cat->id)
                               ->whereNull('parent_id')
                               ->orderBy('nomor_urut', 'asc')
                               ->get();

            foreach ($parents as $parent) {
                $children = Material::where('parent_id', $parent->id)->orderBy('nomor_urut', 'asc')->get();
                $hasChildren = $children->count() > 0;

                $pData = $this->getMaterialOutboundData($parent, false, $hasChildren, $startDate, $endDate);
                $categoryData['items'][] = $pData;

                foreach ($children as $child) {
                    $cData = $this->getMaterialOutboundData($child, true, false, $startDate, $endDate);
                    $categoryData['items'][] = $cData;
                }
            }

            if (count($categoryData['items']) > 0) {
                $groupedOutbounds[] = $categoryData;
            }
        }

        return view('reports.outbound', compact('groupedOutbounds', 'categories', 'categoryId', 'startDate', 'endDate'));
    }

    // --- FUNGSI EXPORT (DILENGKAPI SYSTEM LOG) ---

    public function exportMutation(Request $request)
    {
        if (!$request->has('start_date') && !$request->has('end_date') && !$request->has('category_id')) {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');
            $categoryId = null;
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $categoryId = $request->input('category_id');
        }

        // --- CATAT LOG SISTEM ---
        $this->recordLog('EXPORT', 'LAPORAN MUTASI', null, null, [
            'Aksi' => 'Mengunduh Laporan Mutasi',
            'Periode' => ($startDate && $endDate) ? "$startDate s/d $endDate" : "Semua Data",
            'Filter Kategori ID' => $categoryId ?? 'Semua'
        ]);

        $fileName = 'Laporan_Mutasi_Stock_' . date('Y-m-d') . '.xls';

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

        $callback = function() use ($filteredCategories, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            $periode = ($startDate && $endDate) ? "$startDate s/d $endDate" : "Semua Data Berjalan";
            fputcsv($file, ["PERIODE LAPORAN:", $periode], "\t");
            fputcsv($file, [], "\t"); 

            fputcsv($file, ['Kategori', 'Nama Materiil', 'Tipe', 'Total Masuk', 'Total Keluar', 'Saldo Akhir'], "\t");

            foreach ($filteredCategories as $cat) {
                $parents = Material::where('material_category_id', $cat->id)
                                   ->whereNull('parent_id')
                                   ->orderBy('nomor_urut', 'asc')
                                   ->get();

                foreach ($parents as $parent) {
                    $children = Material::where('parent_id', $parent->id)->orderBy('nomor_urut', 'asc')->get();
                    $hasChildren = $children->count() > 0;

                    $pData = $this->getMaterialMutationData($parent, false, $startDate, $endDate);
                    
                    fputcsv($file, [
                        $cat->name,
                        strtoupper($pData['material_name']),
                        'Induk',
                        $hasChildren ? '-' : $pData['total_in'],
                        $hasChildren ? '-' : $pData['total_out'],
                        $hasChildren ? '-' : $pData['saldo_akhir']
                    ], "\t");

                    foreach ($children as $child) {
                        $cData = $this->getMaterialMutationData($child, true, $startDate, $endDate);
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
        if (!$request->has('start_date') && !$request->has('end_date') && !$request->has('category_id')) {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');
            $categoryId = null;
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $categoryId = $request->input('category_id');
        }

        // --- CATAT LOG SISTEM ---
        $this->recordLog('EXPORT', 'LAPORAN INBOUND', null, null, [
            'Aksi' => 'Mengunduh Laporan Riwayat Penerimaan',
            'Periode' => ($startDate && $endDate) ? "$startDate s/d $endDate" : "Semua Data",
            'Filter Kategori ID' => $categoryId ?? 'Semua'
        ]);

        $fileName = 'Laporan_Riwayat_Penerimaan_' . date('Y-m-d') . '.xls';

        $catQuery = \App\Models\MaterialCategory::orderBy('nomor_urut', 'asc');
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

        $callback = function() use ($filteredCategories, $startDate, $endDate) {
            echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
            echo '<table border="1" style="font-family: Arial, sans-serif; font-size: 11px; border-collapse: collapse;">';
            
            $periode = ($startDate && $endDate) ? "$startDate s/d $endDate" : "Semua Data Berjalan";
            
            echo '<tr><th colspan="7" style="text-align: left; font-size: 14px; background-color: #0284c7; color: #ffffff; padding: 10px;">LAPORAN RIWAYAT PENERIMAAN / INBOUND</th></tr>';
            echo '<tr><th colspan="7" style="text-align: left; background-color: #e0f2fe; padding: 5px;">PERIODE LAPORAN: ' . $periode . '</th></tr>';
            echo '<tr><th colspan="7"></th></tr>'; 

            echo '<tr style="background-color: #f8fafc; font-weight: bold; text-align: center;">';
            echo '<th style="width: 250px; padding: 5px;">Nama Materiil / Komoditas</th>';
            echo '<th style="width: 120px; padding: 5px;">Tanggal Terima Fisik</th>';
            echo '<th style="width: 180px; padding: 5px;">Nomor SPPM / BAPPM</th>';
            echo '<th style="width: 150px; padding: 5px;">Gudang Penempatan</th>';
            echo '<th style="width: 150px; padding: 5px;">Rentang Seri Awal</th>';
            echo '<th style="width: 150px; padding: 5px;">Rentang Seri Akhir</th>';
            echo '<th style="width: 100px; padding: 5px;">Qty Masuk</th>';
            echo '</tr>';

            foreach ($filteredCategories as $cat) {
                $categoryItems = [];
                $parents = \App\Models\Material::where('material_category_id', $cat->id)
                               ->whereNull('parent_id')
                               ->orderBy('nomor_urut', 'asc')
                               ->get();

                foreach ($parents as $parent) {
                    $children = \App\Models\Material::where('parent_id', $parent->id)->orderBy('nomor_urut', 'asc')->get();
                    $hasChildren = $children->count() > 0;

                    $pData = $this->getMaterialInboundData($parent, false, $hasChildren, $startDate, $endDate);
                    $categoryItems[] = $pData;

                    foreach ($children as $child) {
                        $cData = $this->getMaterialInboundData($child, true, false, $startDate, $endDate);
                        $categoryItems[] = $cData;
                    }
                }

                if (count($categoryItems) > 0) {
                    echo '<tr style="background-color: #bfdbfe; font-weight: bold;">';
                    echo '<td colspan="7" style="padding: 5px;">[KATEGORI: ' . strtoupper($cat->name) . ']</td>';
                    echo '</tr>';

                    foreach ($categoryItems as $row) {
                        $matName = $row['is_child'] ? '&nbsp;&nbsp;&nbsp;&nbsp;&#8627; ' . strtoupper($row['material_name']) : strtoupper($row['material_name']);
                        
                        if ($row['has_children']) {
                            echo '<tr style="background-color: #f1f5f9; font-weight: bold;">';
                            echo '<td style="padding: 5px;">' . $matName . '</td>';
                            echo '<td></td><td></td><td></td><td></td><td></td>';
                            echo '<td style="text-align: center; color: #94a3b8;">-</td>';
                            echo '</tr>';
                        } else {
                            echo '<tr style="background-color: #f1f5f9; font-weight: bold;">';
                            echo '<td style="padding: 5px;">' . $matName . '</td>';
                            echo '<td></td><td></td><td></td><td></td>';
                            echo '<td style="text-align: right; padding: 5px;">TOTAL MASUK:</td>';
                            echo '<td style="text-align: center; color: #16a34a; padding: 5px;">' . $row['total_in'] . '</td>';
                            echo '</tr>';
                            
                            if ($row['total_in'] > 0 && count($row['transactions']) > 0) {
                                foreach ($row['transactions'] as $trx) {
                                    $seriAwal = $trx->serial_start ? ($trx->serial_prefix ?? '') . str_pad($trx->serial_start, 9, '0', STR_PAD_LEFT) : '-';
                                    $seriAkhir = $trx->serial_end ? ($trx->serial_prefix ?? '') . str_pad($trx->serial_end, 9, '0', STR_PAD_LEFT) : '-';
                                    $tgl = \Carbon\Carbon::parse($trx->log->receive_date ?? $trx->created_at)->format('Y-m-d');
                                    $sppmNo = $trx->log->sppm->sppm_no ?? '-';
                                    $gudang = $trx->log->sppm->warehouse->name ?? 'Gudang Utama';

                                    echo '<tr>';
                                    echo '<td></td>';
                                    echo '<td style="text-align: center; padding: 3px;">' . $tgl . '</td>';
                                    echo '<td style="padding: 3px;">' . $sppmNo . '</td>';
                                    echo '<td style="padding: 3px;">' . $gudang . '</td>';
                                    echo '<td style="text-align: center; padding: 3px;">' . $seriAwal . '</td>';
                                    echo '<td style="text-align: center; padding: 3px;">' . $seriAkhir . '</td>';
                                    echo '<td style="text-align: center; color: #16a34a; padding: 3px;">+' . $trx->qty_received . '</td>';
                                    echo '</tr>';
                                }
                            }
                        }
                    }
                }
            }
            echo '</table></body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportOutbound(Request $request)
    {
        if (!$request->has('start_date') && !$request->has('end_date') && !$request->has('category_id')) {
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-d');
            $categoryId = null;
        } else {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $categoryId = $request->input('category_id');
        }

        // --- CATAT LOG SISTEM ---
        $this->recordLog('EXPORT', 'LAPORAN OUTBOUND', null, null, [
            'Aksi' => 'Mengunduh Laporan Riwayat Distribusi',
            'Periode' => ($startDate && $endDate) ? "$startDate s/d $endDate" : "Semua Data",
            'Filter Kategori ID' => $categoryId ?? 'Semua'
        ]);

        $fileName = 'Laporan_Riwayat_Distribusi_' . date('Y-m-d') . '.xls';

        $catQuery = \App\Models\MaterialCategory::orderBy('nomor_urut', 'asc');
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

        $callback = function() use ($filteredCategories, $startDate, $endDate) {
            echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
            echo '<table border="1" style="font-family: Arial, sans-serif; font-size: 11px; border-collapse: collapse;">';
            
            $periode = ($startDate && $endDate) ? "$startDate s/d $endDate" : "Semua Data Berjalan";
            
            echo '<tr><th colspan="7" style="text-align: left; font-size: 14px; background-color: #be123c; color: #ffffff; padding: 10px;">LAPORAN RIWAYAT DISTRIBUSI / OUTBOUND</th></tr>';
            echo '<tr><th colspan="7" style="text-align: left; background-color: #ffe4e6; padding: 5px;">PERIODE LAPORAN: ' . $periode . '</th></tr>';
            echo '<tr><th colspan="7"></th></tr>'; 

            echo '<tr style="background-color: #f8fafc; font-weight: bold; text-align: center;">';
            echo '<th style="width: 250px; padding: 5px;">Nama Materiil / Komoditas</th>';
            echo '<th style="width: 120px; padding: 5px;">Tanggal Keluar Fisik</th>';
            echo '<th style="width: 180px; padding: 5px;">Nomor SPPM</th>';
            echo '<th style="width: 200px; padding: 5px;">Tujuan Pengiriman</th>';
            echo '<th style="width: 150px; padding: 5px;">Rentang Seri Awal</th>';
            echo '<th style="width: 150px; padding: 5px;">Rentang Seri Akhir</th>';
            echo '<th style="width: 100px; padding: 5px;">Qty Keluar</th>';
            echo '</tr>';

            foreach ($filteredCategories as $cat) {
                $categoryItems = [];
                $parents = \App\Models\Material::where('material_category_id', $cat->id)
                               ->whereNull('parent_id')
                               ->orderBy('nomor_urut', 'asc')
                               ->get();

                foreach ($parents as $parent) {
                    $children = \App\Models\Material::where('parent_id', $parent->id)->orderBy('nomor_urut', 'asc')->get();
                    $hasChildren = $children->count() > 0;

                    $pData = $this->getMaterialOutboundData($parent, false, $hasChildren, $startDate, $endDate);
                    $categoryItems[] = $pData;

                    foreach ($children as $child) {
                        $cData = $this->getMaterialOutboundData($child, true, false, $startDate, $endDate);
                        $categoryItems[] = $cData;
                    }
                }

                if (count($categoryItems) > 0) {
                    echo '<tr style="background-color: #fecdd3; font-weight: bold;">';
                    echo '<td colspan="7" style="padding: 5px;">[KATEGORI: ' . strtoupper($cat->name) . ']</td>';
                    echo '</tr>';

                    foreach ($categoryItems as $row) {
                        $matName = $row['is_child'] ? '&nbsp;&nbsp;&nbsp;&nbsp;&#8627; ' . strtoupper($row['material_name']) : strtoupper($row['material_name']);
                        
                        if ($row['has_children']) {
                            echo '<tr style="background-color: #f1f5f9; font-weight: bold;">';
                            echo '<td style="padding: 5px;">' . $matName . '</td>';
                            echo '<td></td><td></td><td></td><td></td><td></td>';
                            echo '<td style="text-align: center; color: #94a3b8;">-</td>';
                            echo '</tr>';
                        } else {
                            echo '<tr style="background-color: #f1f5f9; font-weight: bold;">';
                            echo '<td style="padding: 5px;">' . $matName . '</td>';
                            echo '<td></td><td></td><td></td><td></td>';
                            echo '<td style="text-align: right; padding: 5px;">TOTAL KELUAR:</td>';
                            echo '<td style="text-align: center; color: #e11d48; padding: 5px;">' . $row['total_out'] . '</td>';
                            echo '</tr>';
                            
                            if ($row['total_out'] > 0 && count($row['transactions']) > 0) {
                                foreach ($row['transactions'] as $trx) {
                                    $seriAwal = $trx->seri_awal ? ($trx->prefix ?? '') . str_pad($trx->seri_awal, 9, '0', STR_PAD_LEFT) : '-';
                                    $seriAkhir = $trx->seri_akhir ? ($trx->prefix ?? '') . str_pad($trx->seri_akhir, 9, '0', STR_PAD_LEFT) : '-';
                                    $tgl = \Carbon\Carbon::parse($trx->outLog->tgl_keluar ?? $trx->created_at)->format('Y-m-d');
                                    $sppmNo = $trx->outLog->outSppm->sppm_no ?? '-';
                                    $tujuan = $trx->outLog->outSppm->destination->name ?? 'Tujuan Tidak Diketahui';

                                    echo '<tr>';
                                    echo '<td></td>';
                                    echo '<td style="text-align: center; padding: 3px;">' . $tgl . '</td>';
                                    echo '<td style="padding: 3px;">' . $sppmNo . '</td>';
                                    echo '<td style="padding: 3px;">' . $tujuan . '</td>';
                                    echo '<td style="text-align: center; padding: 3px;">' . $seriAwal . '</td>';
                                    echo '<td style="text-align: center; padding: 3px;">' . $seriAkhir . '</td>';
                                    echo '<td style="text-align: center; color: #e11d48; padding: 3px;">-' . $trx->qty_keluar . '</td>';
                                    echo '</tr>';
                                }
                            }
                        }
                    }
                }
            }
            echo '</table></body></html>';
        };

        return response()->stream($callback, 200, $headers);
    }

    // Sub Menu 4: SIMAK (Pendistribusian Materiel)
    public function simak(Request $request)
    {
        // 1. Tangkap Parameter Global & Tab Aktif
        $tab = $request->input('tab', '1'); // Default ke Tab 1 jika kosong
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        $selectedLabel = $request->input('simak_label');

        // 2. Data Master (Dibutuhkan oleh kedua Tab)
        $destinations = \App\Models\Destination::orderBy('nomor_urut', 'asc')->get();
        
        $simakMaterials = \App\Models\Material::where('is_simak', 1)
            ->whereNotNull('simak_label')
            ->orderBy('simak_urut', 'asc')
            ->get();
            
        $simakHeaders = [];
        foreach ($simakMaterials as $mat) {
            // Normalisasi huruf kapital dan hapus spasi berlebih
            $label = strtoupper(trim($mat->simak_label)); 
            if (!in_array($label, $simakHeaders)) {
                $simakHeaders[] = $label;
            }
        }

        // Jika Tab 2 aktif tapi label belum dipilih, set default ke label urutan pertama
        if ($tab == '2' && empty($selectedLabel) && count($simakHeaders) > 0) {
            $selectedLabel = $simakHeaders[0];
        }
        
        // Normalisasi label yang dipilih agar sama persis saat dibandingkan
        $selectedLabel = strtoupper(trim($selectedLabel));

        // 3. Inisialisasi Variabel Data (Kerangka Dasar)
        $simakDataTab1 = [];
        $simakDataTab2 = [];

        // Buat kerangka untuk Mode 1
        foreach ($destinations as $dest) {
            foreach ($simakHeaders as $label) {
                $simakDataTab1[$dest->id][$label] = 0;
            }
        }

        // Buat kerangka untuk Mode 2
        foreach ($destinations as $dest) {
            for ($m = 1; $m <= 12; $m++) {
                $simakDataTab2[$dest->id][$m] = 0;
            }
            $simakDataTab2[$dest->id]['jumlah'] = 0;
        }

        // ====================================================================
        // PROSES TAB 1: MODE BULANAN (KESELURUHAN MATERIIL)
        // ====================================================================
        if ($tab == '1') {
            // Hapus constraint di dalam with() untuk menghindari bug Laravel Eager Loading
            $outSppmsTab1 = \App\Models\OutSppm::with('details.material')
                ->where('status', 'completed')
                ->whereMonth('sppm_date', $month)
                ->whereYear('sppm_date', $year)
                ->get();

            foreach ($outSppmsTab1 as $sppm) {
                foreach ($sppm->details as $detail) {
                    // Validasi ketat di level PHP
                    if ($detail->material && $detail->material->is_simak == 1) {
                        $label = strtoupper(trim($detail->material->simak_label));
                        if (isset($simakDataTab1[$sppm->destination_id][$label])) {
                            $simakDataTab1[$sppm->destination_id][$label] += $detail->target_qty;
                        }
                    }
                }
            }
        }

        // ====================================================================
        // PROSES TAB 2: MODE TAHUNAN (PER MATERIIL SIMAK)
        // ====================================================================
        if ($tab == '2') {
            // Tarik seluruh SPPM di tahun tersebut beserta detail materialnya
            $outSppmsTab2 = \App\Models\OutSppm::with('details.material')
                ->where('status', 'completed')
                ->whereYear('sppm_date', $year)
                ->get();

            foreach ($outSppmsTab2 as $sppm) {
                // Gunakan Carbon untuk memastikan format bulan ditarik presisi sebagai integer (1-12)
                $sppmMonth = (int) \Carbon\Carbon::parse($sppm->sppm_date)->format('n');
                
                foreach ($sppm->details as $detail) {
                    // Pastikan detail material valid dan ter-mapping ke SIMAK
                    if ($detail->material && $detail->material->is_simak == 1) {
                        $detailLabel = strtoupper(trim($detail->material->simak_label));
                        
                        // Cek apakah label material ini persis sama dengan yang difilter user
                        if ($detailLabel === $selectedLabel) {
                            $simakDataTab2[$sppm->destination_id][$sppmMonth] += $detail->target_qty;
                            $simakDataTab2[$sppm->destination_id]['jumlah'] += $detail->target_qty;
                        }
                    }
                }
            }
        }

        return view('reports.simak', compact(
            'tab', 'month', 'year', 'selectedLabel', 
            'destinations', 'simakHeaders', 
            'simakDataTab1', 'simakDataTab2'
        ));
    }

    public function exportSimak(Request $request)
    {
        // 1. Tangkap Parameter Bulan dan Tahun (Default: Bulan dan Tahun saat ini)
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        // 2. Kumpulkan kembali data persis seperti fungsi simak()
        $simakMaterials = \App\Models\Material::where('is_simak', 1)
            ->whereNotNull('simak_label')
            ->orderBy('simak_urut', 'asc')
            ->get();
            
        $simakHeaders = [];
        foreach ($simakMaterials as $mat) {
            if (!in_array($mat->simak_label, $simakHeaders)) {
                $simakHeaders[] = $mat->simak_label;
            }
        }

        $destinations = \App\Models\Destination::orderBy('nomor_urut', 'asc')->get();

        $outSppms = \App\Models\OutSppm::with(['details' => function($q) {
                $q->whereHas('material', function($q2) {
                    $q2->where('is_simak', 1);
                });
            }, 'details.material'])
            ->where('status', 'completed')
            ->whereMonth('sppm_date', $month)
            ->whereYear('sppm_date', $year)
            ->get();

        $simakData = [];
        
        foreach ($destinations as $dest) {
            foreach ($simakHeaders as $label) {
                $simakData[$dest->id][$label] = 0;
            }
        }

        foreach ($outSppms as $sppm) {
            foreach ($sppm->details as $detail) {
                if ($detail->material && $detail->material->is_simak == 1) {
                    $label = $detail->material->simak_label;
                    if (isset($simakData[$sppm->destination_id][$label])) {
                        $simakData[$sppm->destination_id][$label] += $detail->target_qty;
                    }
                }
            }
        }

        // 3. Mapping Nama Bulan
        $months = [
            '01' => 'JANUARI', '02' => 'FEBRUARI', '03' => 'MARET', '04' => 'APRIL',
            '05' => 'MEI', '06' => 'JUNI', '07' => 'JULI', '08' => 'AGUSTUS',
            '09' => 'SEPTEMBER', '10' => 'OKTOBER', '11' => 'NOVEMBER', '12' => 'DESEMBER'
        ];
        $monthName = $months[str_pad($month, 2, '0', STR_PAD_LEFT)];

        // 4. Proses Download Excel
        $fileName = 'Laporan_SIMAK_' . $monthName . '_' . $year . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\SimakExport($month, $year, $simakHeaders, $destinations, $simakData, $monthName),
            $fileName
        );
    }

    // Sub Menu: Export Excel SIMAK (MODE 2 - TAHUNAN)
    public function exportSimak2(Request $request)
    {
        // 1. Tangkap Parameter
        $year = $request->input('year', date('Y'));
        $selectedLabel = strtoupper(trim($request->input('simak_label')));

        if (empty($selectedLabel)) {
            return back()->with('error', 'Label SIMAK belum dipilih.');
        }

        // 2. Siapkan Master Data
        $destinations = \App\Models\Destination::orderBy('nomor_urut', 'asc')->get();

        $simakDataTab2 = [];
        foreach ($destinations as $dest) {
            for ($m = 1; $m <= 12; $m++) {
                $simakDataTab2[$dest->id][$m] = 0;
            }
            $simakDataTab2[$dest->id]['jumlah'] = 0;
        }

        // 3. Tarik dan Kalkulasi Data
        $outSppmsTab2 = \App\Models\OutSppm::with('details.material')
            ->where('status', 'completed')
            ->whereYear('sppm_date', $year)
            ->get();

        foreach ($outSppmsTab2 as $sppm) {
            $sppmMonth = (int) \Carbon\Carbon::parse($sppm->sppm_date)->format('n');
            
            foreach ($sppm->details as $detail) {
                if ($detail->material && $detail->material->is_simak == 1) {
                    $detailLabel = strtoupper(trim($detail->material->simak_label));
                    
                    if ($detailLabel === $selectedLabel) {
                        $simakDataTab2[$sppm->destination_id][$sppmMonth] += $detail->target_qty;
                        $simakDataTab2[$sppm->destination_id]['jumlah'] += $detail->target_qty;
                    }
                }
            }
        }

        // 4. Proses Download Excel
        // Membersihkan string nama materiil untuk nama file excel
        $cleanLabel = preg_replace('/[^A-Za-z0-9\-]/', '_', $selectedLabel);
        $fileName = 'Laporan_SIMAK_' . $cleanLabel . '_Tahun_' . $year . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\Simak2Export($year, $selectedLabel, $destinations, $simakDataTab2),
            $fileName
        );
    }
}
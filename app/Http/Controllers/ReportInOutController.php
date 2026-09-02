<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Setting;
use App\Models\ReportAdjustment;
use Illuminate\Support\Facades\DB;

class ReportInOutController extends Controller
{
    // =========================================================================
    // FUNGSI PRIVATE: ENGINE PENGAMBIL DATA (DENGAN BATAS CUT-OFF DATE & INJEKSI PENYESUAIAN)
    // =========================================================================
    private function getReportData($year, $ttdMonth, $ttdDate)
    {
        $signatureKeys = ['Jabatan_tnkb_ttd', 'Nama_tnkb_ttd', 'pangkatnrp_tnkb_ttd'];
        $signatureSettings = Setting::whereIn('key', $signatureKeys)->pluck('value', 'key')->toArray();

        // --- LOGIKA CUT-OFF CERDAS UNTUK TAHUN LAMPAU ---
        $currentYear = date('Y');
        
        if ($year < $currentYear) {
            // Jika mereview data tahun lampau, paksa tampil full 1 tahun (sampai 31 Desember)
            $effectiveMonth = 12;
            $effectiveDate = 31;
        } else {
            // Jika tahun berjalan, gunakan input dari filter user
            $effectiveMonth = $ttdMonth;
            $effectiveDate = $ttdDate;
        }

        $cutoffDate = sprintf('%04d-%02d-%02d', $year, $effectiveMonth, $effectiveDate);

        $reportData = [
            'tnkb_non_ev' => ['R2' => ['sisa_awal_tahun' => 0, 'months' => []], 'R4' => ['sisa_awal_tahun' => 0, 'months' => []]],
            'tnkb_ev'     => ['R2' => ['sisa_awal_tahun' => 0, 'months' => []], 'R4' => ['sisa_awal_tahun' => 0, 'months' => []]],
            'tckb'        => ['R2' => ['sisa_awal_tahun' => 0, 'months' => []], 'R4' => ['sisa_awal_tahun' => 0, 'months' => []]],
        ];

        for ($m = 1; $m <= 12; $m++) {
            foreach (['tnkb_non_ev', 'tnkb_ev', 'tckb'] as $type) {
                foreach (['R2', 'R4'] as $r) {
                    $reportData[$type][$r]['months'][$m] = [
                        'in' => 0, 
                        'out' => 0, 
                        'adj_sisa_awal' => 0, 
                        'adj_sisa_gudang' => 0
                    ];
                }
            }
        }

        $getTypeKey = function($rpt, $ev) {
            if ($rpt == 2) return 'tckb';
            if ($rpt == 1 && $ev == 1) return 'tnkb_ev';
            if ($rpt == 1 && $ev == 0) return 'tnkb_non_ev';
            return null;
        };

        // =========================================================
        // 1. PENGOLAHAN DATA TNKB & TCKB
        // =========================================================

        // Query Inbound TNKB
        $inboundQuery = DB::table('in_details')
            ->join('in_sppms', 'in_details.in_sppm_id', '=', 'in_sppms.id')
            ->join('materials', 'in_details.material_id', '=', 'materials.id')
            ->whereNotNull('materials.tnkb_rpt')
            ->where('materials.tnkb_rpt', '>', 0)
            ->whereDate('in_sppms.sppm_date', '<=', $cutoffDate)
            ->selectRaw('
                materials.tnkb_rpt, materials.tnkb_r, materials.tnkb_ev, 
                YEAR(in_sppms.sppm_date) as year, MONTH(in_sppms.sppm_date) as month, 
                SUM(in_details.target_qty) as total_qty
            ')
            ->groupBy('materials.tnkb_rpt', 'materials.tnkb_r', 'materials.tnkb_ev', 'year', 'month')
            ->get();

        foreach ($inboundQuery as $row) {
            $type = $getTypeKey($row->tnkb_rpt, $row->tnkb_ev);
            if (!$type || !in_array($row->tnkb_r, ['R2', 'R4'])) continue;

            if ($row->year < $year) {
                $reportData[$type][$row->tnkb_r]['sisa_awal_tahun'] += $row->total_qty;
            } elseif ($row->year == $year) {
                $reportData[$type][$row->tnkb_r]['months'][$row->month]['in'] += $row->total_qty;
            }
        }

        // Query Outbound TNKB
        $outboundQuery = DB::table('out_details')
            ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
            ->join('materials', 'out_details.material_id', '=', 'materials.id')
            ->whereNotNull('materials.tnkb_rpt')
            ->where('materials.tnkb_rpt', '>', 0)
            ->whereDate('out_sppms.sppm_date', '<=', $cutoffDate)
            ->selectRaw('
                materials.tnkb_rpt, materials.tnkb_r, materials.tnkb_ev, 
                YEAR(out_sppms.sppm_date) as year, MONTH(out_sppms.sppm_date) as month, 
                SUM(out_details.target_qty) as total_qty
            ')
            ->groupBy('materials.tnkb_rpt', 'materials.tnkb_r', 'materials.tnkb_ev', 'year', 'month')
            ->get();

        foreach ($outboundQuery as $row) {
            $type = $getTypeKey($row->tnkb_rpt, $row->tnkb_ev);
            if (!$type || !in_array($row->tnkb_r, ['R2', 'R4'])) continue;

            if ($row->year < $year) {
                $reportData[$type][$row->tnkb_r]['sisa_awal_tahun'] -= $row->total_qty;
            } elseif ($row->year == $year) {
                $reportData[$type][$row->tnkb_r]['months'][$row->month]['out'] += $row->total_qty;
            }
        }

        // --- INJEKSI PENYESUAIAN TNKB (TAHUN-TAHUN SEBELUMNYA) CARRY OVER ---
        $pastTnkbAdjustments = ReportAdjustment::where('year', '<', $year)->where('tab_type', 'tnkb')->get();
        foreach ($pastTnkbAdjustments as $adj) {
            $parts = explode('_', $adj->bucket_key);
            $r = array_pop($parts);
            $type = implode('_', $parts);
            
            if (isset($reportData[$type][$r])) {
                if ($adj->transaction_type === 'out') {
                    $reportData[$type][$r]['sisa_awal_tahun'] -= $adj->qty_adjustment;
                } else {
                    $reportData[$type][$r]['sisa_awal_tahun'] += $adj->qty_adjustment;
                }
            }
        }

        // --- INJEKSI PENYESUAIAN TNKB (TAHUN BERJALAN SAAT INI) ---
        $tnkbAdjustments = ReportAdjustment::where('year', $year)->where('tab_type', 'tnkb')->get();
        foreach ($tnkbAdjustments as $adj) {
            $parts = explode('_', $adj->bucket_key);
            $r = array_pop($parts);
            $type = implode('_', $parts);
            
            if (isset($reportData[$type][$r]['months'][$adj->month])) {
                // Spesial: Jika penyesuaian sisa awal ditaruh di bulan Januari, maka ubah sisa awal tahunnya agar header tabel ikut berubah
                if ($adj->transaction_type == 'sisa_awal' && $adj->month == 1) {
                    $reportData[$type][$r]['sisa_awal_tahun'] += $adj->qty_adjustment;
                } elseif (in_array($adj->transaction_type, ['in', 'out'])) {
                    $reportData[$type][$r]['months'][$adj->month][$adj->transaction_type] += $adj->qty_adjustment;
                } else {
                    $reportData[$type][$r]['months'][$adj->month]['adj_' . $adj->transaction_type] += $adj->qty_adjustment;
                }
            }
        }

        // Kalkulasi Sisa TNKB (Running Balance)
        foreach ($reportData as $type => $rTypes) {
            foreach (['R2', 'R4'] as $r) {
                $runningBalance = $reportData[$type][$r]['sisa_awal_tahun'];
                for ($m = 1; $m <= 12; $m++) {
                    $in = $reportData[$type][$r]['months'][$m]['in'];
                    $out = $reportData[$type][$r]['months'][$m]['out'];
                    
                    // Sisa awal dipengaruhi oleh keranjang adj_sisa_awal
                    $sisa_awal = $runningBalance + $reportData[$type][$r]['months'][$m]['adj_sisa_awal'];
                    
                    // Sisa gudang dipengaruhi oleh rumus awal + in - out, ditambah keranjang adj_sisa_gudang
                    $sisa_gudang = $sisa_awal + $in - $out + $reportData[$type][$r]['months'][$m]['adj_sisa_gudang'];
                    
                    $reportData[$type][$r]['months'][$m]['sisa_awal'] = $sisa_awal;
                    $reportData[$type][$r]['months'][$m]['sisa_gudang'] = $sisa_gudang;
                    
                    // Bawa sisa gudang bulan ini menjadi modal sisa awal bulan depannya
                    $runningBalance = $sisa_gudang;
                }
            }
        }

        // =========================================================
        // 2. PENGOLAHAN DATA SBST
        // =========================================================
        $sbstMaterials = Material::select('materials.*')
            ->join('material_categories', 'materials.material_category_id', '=', 'material_categories.id')
            ->whereNotNull('materials.sbst_judul')
            ->where('materials.sbst_judul', '!=', '')
            ->orderBy('material_categories.nomor_urut', 'asc')
            ->get();

        $sbstMaterialIds = $sbstMaterials->pluck('id')->toArray();

        $sbstData = [];
        foreach ($sbstMaterials as $mat) {
            $sbstData[$mat->id] = [
                'judul' => $mat->sbst_judul,
                'sisa_awal_tahun' => 0,
                'months' => []
            ];
            for ($m = 1; $m <= 12; $m++) {
                $sbstData[$mat->id]['months'][$m] = [
                    'in' => 0, 
                    'out' => 0,
                    'adj_sisa_awal' => 0,
                    'adj_sisa_gudang' => 0
                ];
            }
        }

        if (!empty($sbstMaterialIds)) {
            // Query Inbound SBST
            $sbstInQuery = DB::table('in_details')
                ->join('in_sppms', 'in_details.in_sppm_id', '=', 'in_sppms.id')
                ->whereIn('in_details.material_id', $sbstMaterialIds)
                ->whereDate('in_sppms.sppm_date', '<=', $cutoffDate)
                ->selectRaw('in_details.material_id, YEAR(in_sppms.sppm_date) as year, MONTH(in_sppms.sppm_date) as month, SUM(in_details.target_qty) as total_qty')
                ->groupBy('in_details.material_id', 'year', 'month')
                ->get();

            foreach ($sbstInQuery as $row) {
                if ($row->year < $year) {
                    $sbstData[$row->material_id]['sisa_awal_tahun'] += $row->total_qty;
                } elseif ($row->year == $year) {
                    $sbstData[$row->material_id]['months'][$row->month]['in'] += $row->total_qty;
                }
            }

            // Query Outbound SBST
            $sbstOutQuery = DB::table('out_details')
                ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
                ->whereIn('out_details.material_id', $sbstMaterialIds)
                ->whereDate('out_sppms.sppm_date', '<=', $cutoffDate)
                ->selectRaw('out_details.material_id, YEAR(out_sppms.sppm_date) as year, MONTH(out_sppms.sppm_date) as month, SUM(out_details.target_qty) as total_qty')
                ->groupBy('out_details.material_id', 'year', 'month')
                ->get();

            foreach ($sbstOutQuery as $row) {
                if ($row->year < $year) {
                    $sbstData[$row->material_id]['sisa_awal_tahun'] -= $row->total_qty;
                } elseif ($row->year == $year) {
                    $sbstData[$row->material_id]['months'][$row->month]['out'] += $row->total_qty;
                }
            }
        }

        // --- INJEKSI PENYESUAIAN SBST (TAHUN-TAHUN SEBELUMNYA) CARRY OVER ---
        $pastSbstAdjustments = ReportAdjustment::where('year', '<', $year)->where('tab_type', 'sbst')->get();
        foreach ($pastSbstAdjustments as $adj) {
            $matId = str_replace('sbst_', '', $adj->bucket_key);
            if (isset($sbstData[$matId])) {
                if ($adj->transaction_type === 'out') {
                    $sbstData[$matId]['sisa_awal_tahun'] -= $adj->qty_adjustment;
                } else {
                    $sbstData[$matId]['sisa_awal_tahun'] += $adj->qty_adjustment;
                }
            }
        }

        // --- INJEKSI PENYESUAIAN SBST (TAHUN BERJALAN SAAT INI) ---
        $sbstAdjustments = ReportAdjustment::where('year', $year)->where('tab_type', 'sbst')->get();
        foreach ($sbstAdjustments as $adj) {
            $matId = str_replace('sbst_', '', $adj->bucket_key);
            if (isset($sbstData[$matId]['months'][$adj->month])) {
                // Spesial: Jika penyesuaian sisa awal ditaruh di bulan Januari
                if ($adj->transaction_type == 'sisa_awal' && $adj->month == 1) {
                    $sbstData[$matId]['sisa_awal_tahun'] += $adj->qty_adjustment;
                } elseif (in_array($adj->transaction_type, ['in', 'out'])) {
                    $sbstData[$matId]['months'][$adj->month][$adj->transaction_type] += $adj->qty_adjustment;
                } else {
                    $sbstData[$matId]['months'][$adj->month]['adj_' . $adj->transaction_type] += $adj->qty_adjustment;
                }
            }
        }

        // Kalkulasi Sisa SBST (Running Balance)
        foreach ($sbstData as $matId => &$data) {
            $runningBalance = $data['sisa_awal_tahun'];
            for ($m = 1; $m <= 12; $m++) {
                $in = $data['months'][$m]['in'];
                $out = $data['months'][$m]['out'];
                
                $sisa_lalu = $runningBalance + $data['months'][$m]['adj_sisa_awal'];
                $jumlah = $sisa_lalu + $in;
                $sisa = $jumlah - $out + $data['months'][$m]['adj_sisa_gudang'];
                
                $data['months'][$m]['sisa_lalu'] = $sisa_lalu;
                $data['months'][$m]['jumlah'] = $jumlah;
                $data['months'][$m]['sisa'] = $sisa;
                
                $runningBalance = $sisa;
            }
        }
        unset($data);

        $monthsName = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS', 
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
        ];

        return compact('reportData', 'sbstData', 'signatureSettings', 'monthsName', 'year', 'ttdMonth', 'ttdDate');
    }

    public function index(Request $request)
    {
        $yearsIn = DB::table('in_sppms')->selectRaw('YEAR(sppm_date) as year')->distinct()->pluck('year')->toArray();
        $yearsOut = DB::table('out_sppms')->selectRaw('YEAR(sppm_date) as year')->distinct()->pluck('year')->toArray();
        $yearsAdj = ReportAdjustment::select('year')->distinct()->pluck('year')->toArray();
        
        $years = array_unique(array_merge($yearsIn, $yearsOut, $yearsAdj));
        rsort($years);
        if (empty($years)) $years = [date('Y')];

        $year = $request->input('year', $years[0] ?? date('Y'));
        $ttdMonth = $request->input('ttd_month', date('n'));
        $ttdDate = $request->input('ttd_date', date('j'));
        
        $data = $this->getReportData($year, $ttdMonth, $ttdDate);
        $data['years'] = $years;

        return view('reports.inout', $data);
    }

    public function export(Request $request, $type)
    {
        $year = $request->input('year', date('Y'));
        $ttdMonth = $request->input('ttd_month', date('n'));
        $ttdDate = $request->input('ttd_date', date('j'));
        
        $data = $this->getReportData($year, $ttdMonth, $ttdDate);

        if ($type == 'excel') {
            return response((string) view('reports.inout_export', $data))
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="Laporan_Terima_Keluar_'.$year.'.xls"');
        }

        if ($type == 'pdf') {
            if (!class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                return back()->with('error', 'Fitur cetak PDF membutuhkan library DOMPDF.');
            }
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.inout_export', $data)->setPaper('a4', 'landscape');
            return $pdf->download('Laporan_Terima_Keluar_'.$year.'.pdf');
        }

        return redirect()->back();
    }

    public function settings(Request $request)
    {
        $categoryId = $request->input('category_id');
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        $signatureKeys = ['Jabatan_tnkb_ttd', 'Nama_tnkb_ttd', 'pangkatnrp_tnkb_ttd'];
        $signatureSettings = Setting::whereIn('key', $signatureKeys)->pluck('value', 'key')->toArray();

        $queryCategories = MaterialCategory::orderBy('nomor_urut', 'asc');
        if ($categoryId) {
            $queryCategories->where('id', $categoryId);
        }
        $groupedCategories = $queryCategories->get();

        $allMaterials = Material::with(['parent'])
            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->where('material_category_id', $categoryId);
            })
            ->orderBy('nomor_urut', 'asc')
            ->get();

        $structuredData = [];
        foreach ($groupedCategories as $cat) {
            $catMaterials = $allMaterials->where('material_category_id', $cat->id);
            if ($catMaterials->isEmpty()) continue;

            $structuredData[$cat->name] = [
                'cat_id' => $cat->id,
                'items' => []
            ];

            $parents = $catMaterials->filter(function($item) {
                return empty($item->parent_id);
            });

            foreach ($parents as $parent) {
                $structuredData[$cat->name]['items'][] = [
                    'item' => $parent,
                    'is_child' => false
                ];

                $children = $catMaterials->filter(function($item) use ($parent) {
                    return $item->parent_id == $parent->id;
                });

                foreach ($children as $child) {
                    $structuredData[$cat->name]['items'][] = [
                        'item' => $child,
                        'is_child' => true
                    ];
                }
            }

            $caughtIds = collect($structuredData[$cat->name]['items'])->pluck('item.id')->toArray();
            $orphans = $catMaterials->whereNotIn('id', $caughtIds);
            foreach ($orphans as $orphan) {
                $structuredData[$cat->name]['items'][] = [
                    'item' => $orphan,
                    'is_child' => !empty($orphan->parent_id)
                ];
            }
        }

        return view('reports.settings.inout', compact('structuredData', 'categories', 'categoryId', 'signatureSettings'));
    }

    public function updateSignature(Request $request)
    {
        $keys = ['Jabatan_tnkb_ttd', 'Nama_tnkb_ttd', 'pangkatnrp_tnkb_ttd'];
        
        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }
        return redirect()->route('report.inout.settings')->with('success', 'Data Penandatangan Laporan berhasil diperbarui!');
    }

    public function updateSettings(Request $request)
    {
        $mappings = $request->input('mappings', []);

        foreach ($mappings as $id => $data) {
            $updateData = [];
            
            if (array_key_exists('tnkb_rpt', $data)) $updateData['tnkb_rpt'] = $data['tnkb_rpt'];
            if (array_key_exists('tnkb_r', $data)) $updateData['tnkb_r'] = $data['tnkb_r'];
            if (array_key_exists('tnkb_ev', $data)) $updateData['tnkb_ev'] = $data['tnkb_ev'];
            if (array_key_exists('sbst_judul', $data)) $updateData['sbst_judul'] = $data['sbst_judul'];

            if (!empty($updateData)) {
                Material::where('id', $id)->update($updateData);
            }
        }
        return redirect()->route('report.inout.settings')->with('success', 'Konfigurasi Mapping Laporan berhasil diperbarui!');
    }

    public function reorderCategories(Request $request)
    {
        $order = $request->input('order');
        if ($order && is_array($order)) {
            foreach ($order as $index => $id) {
                MaterialCategory::where('id', $id)->update(['nomor_urut' => $index + 1]);
            }
            return response()->json(['success' => true, 'message' => 'Urutan Kategori SBST berhasil diperbarui!']);
        }
        return response()->json(['success' => false], 400);
    }
}
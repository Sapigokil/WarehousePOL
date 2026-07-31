<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class ReportInOutController extends Controller
{
    // =========================================================================
    // FUNGSI PRIVATE: ENGINE PENGAMBIL DATA (AGAR TIDAK DUPLIKAT DI INDEX & EXPORT)
    // =========================================================================
    private function getReportData($year)
    {
        $signatureKeys = ['Jabatan_tnkb_ttd', 'Nama_tnkb_ttd', 'pangkatnrp_tnkb_ttd'];
        $signatureSettings = Setting::whereIn('key', $signatureKeys)->pluck('value', 'key')->toArray();

        // 1. Matriks Kosong TNKB & TCKB
        $reportData = [
            'tnkb_non_ev' => ['R2' => ['sisa_awal_tahun' => 0, 'months' => []], 'R4' => ['sisa_awal_tahun' => 0, 'months' => []]],
            'tnkb_ev'     => ['R2' => ['sisa_awal_tahun' => 0, 'months' => []], 'R4' => ['sisa_awal_tahun' => 0, 'months' => []]],
            'tckb'        => ['R2' => ['sisa_awal_tahun' => 0, 'months' => []], 'R4' => ['sisa_awal_tahun' => 0, 'months' => []]],
        ];

        for ($m = 1; $m <= 12; $m++) {
            foreach (['tnkb_non_ev', 'tnkb_ev', 'tckb'] as $type) {
                foreach (['R2', 'R4'] as $r) {
                    $reportData[$type][$r]['months'][$m] = ['in' => 0, 'out' => 0];
                }
            }
        }

        $getTypeKey = function($rpt, $ev) {
            if ($rpt == 2) return 'tckb';
            if ($rpt == 1 && $ev == 1) return 'tnkb_ev';
            if ($rpt == 1 && $ev == 0) return 'tnkb_non_ev';
            return null;
        };

        // Query Inbound TNKB
        $inboundQuery = DB::table('in_details')
            ->join('in_sppms', 'in_details.in_sppm_id', '=', 'in_sppms.id')
            ->join('materials', 'in_details.material_id', '=', 'materials.id')
            ->whereNotNull('materials.tnkb_rpt')
            ->where('materials.tnkb_rpt', '>', 0)
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

        // Kalkulasi Sisa TNKB
        foreach ($reportData as $type => $rTypes) {
            foreach (['R2', 'R4'] as $r) {
                $runningBalance = $reportData[$type][$r]['sisa_awal_tahun'];
                for ($m = 1; $m <= 12; $m++) {
                    $in = $reportData[$type][$r]['months'][$m]['in'];
                    $out = $reportData[$type][$r]['months'][$m]['out'];
                    $sisa_awal = $runningBalance;
                    $sisa_gudang = $sisa_awal + $in - $out;
                    $reportData[$type][$r]['months'][$m]['sisa_awal'] = $sisa_awal;
                    $reportData[$type][$r]['months'][$m]['sisa_gudang'] = $sisa_gudang;
                    $runningBalance = $sisa_gudang;
                }
            }
        }

        // 2. Matriks Data SBST
        $sbstMaterials = Material::whereNotNull('sbst_judul')->where('sbst_judul', '!=', '')->get();
        $sbstMaterialIds = $sbstMaterials->pluck('id')->toArray();

        $sbstData = [];
        foreach ($sbstMaterials as $mat) {
            $sbstData[$mat->id] = [
                'judul' => $mat->sbst_judul,
                'sisa_awal_tahun' => 0,
                'months' => []
            ];
            for ($m = 1; $m <= 12; $m++) {
                $sbstData[$mat->id]['months'][$m] = ['in' => 0, 'out' => 0];
            }
        }

        if (!empty($sbstMaterialIds)) {
            $sbstInQuery = DB::table('in_details')
                ->join('in_sppms', 'in_details.in_sppm_id', '=', 'in_sppms.id')
                ->whereIn('in_details.material_id', $sbstMaterialIds)
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

            $sbstOutQuery = DB::table('out_details')
                ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
                ->whereIn('out_details.material_id', $sbstMaterialIds)
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

        // Kalkulasi Sisa SBST
        foreach ($sbstData as $matId => &$data) {
            $runningBalance = $data['sisa_awal_tahun'];
            for ($m = 1; $m <= 12; $m++) {
                $in = $data['months'][$m]['in'];
                $out = $data['months'][$m]['out'];
                $sisa_lalu = $runningBalance;
                $jumlah = $sisa_lalu + $in;
                $sisa = $jumlah - $out;
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

        return compact('reportData', 'sbstData', 'signatureSettings', 'monthsName', 'year');
    }

    // =========================================================================
    // MENU B: HALAMAN LAPORAN (INDEX)
    // =========================================================================
    public function index(Request $request)
    {
        $yearsIn = DB::table('in_sppms')->selectRaw('YEAR(sppm_date) as year')->distinct()->pluck('year')->toArray();
        $yearsOut = DB::table('out_sppms')->selectRaw('YEAR(sppm_date) as year')->distinct()->pluck('year')->toArray();
        $years = array_unique(array_merge($yearsIn, $yearsOut));
        rsort($years);
        if (empty($years)) $years = [date('Y')];

        $year = $request->input('year', $years[0]);
        $data = $this->getReportData($year);
        $data['years'] = $years;

        return view('reports.inout', $data);
    }

    // =========================================================================
    // FUNGSI BARU: EKSPOR PDF DAN EXCEL
    // =========================================================================
    public function export(Request $request, $type)
    {
        $year = $request->input('year', date('Y'));
        $data = $this->getReportData($year);

        // Ekspor ke format Excel (Menggunakan teknik HTML to XLS native)
        if ($type == 'excel') {
            return response((string) view('reports.inout_export', $data))
                ->header('Content-Type', 'application/vnd.ms-excel')
                ->header('Content-Disposition', 'attachment; filename="Laporan_Terima_Keluar_'.$year.'.xls"');
        }

        // Ekspor ke format PDF (Menggunakan paket DOMPDF)
        if ($type == 'pdf') {
            // Validasi apakah library DOMPDF sudah terpasang di VPS/Local
            if (!class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                return back()->with('error', 'Fitur cetak PDF membutuhkan library DOMPDF. Silakan jalankan perintah ini di terminal server Anda: composer require barryvdh/laravel-dompdf');
            }
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.inout_export', $data)->setPaper('a4', 'landscape');
            return $pdf->download('Laporan_Terima_Keluar_'.$year.'.pdf');
        }

        return redirect()->back();
    }


    // =========================================================================
    // MENU A: HALAMAN SETTINGS / MAPPING
    // =========================================================================
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

            $structuredData[$cat->name] = [];

            $parents = $catMaterials->filter(function($item) {
                return empty($item->parent_id);
            });

            foreach ($parents as $parent) {
                $structuredData[$cat->name][] = [
                    'item' => $parent,
                    'is_child' => false
                ];

                $children = $catMaterials->filter(function($item) use ($parent) {
                    return $item->parent_id == $parent->id;
                });

                foreach ($children as $child) {
                    $structuredData[$cat->name][] = [
                        'item' => $child,
                        'is_child' => true
                    ];
                }
            }

            $caughtIds = collect($structuredData[$cat->name])->pluck('item.id')->toArray();
            $orphans = $catMaterials->whereNotIn('id', $caughtIds);
            foreach ($orphans as $orphan) {
                $structuredData[$cat->name][] = [
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
            Material::where('id', $id)->update([
                'tnkb_rpt'   => $data['tnkb_rpt'] ?? 0,
                'tnkb_r'     => $data['tnkb_r'] ?? null,
                'tnkb_ev'    => $data['tnkb_ev'] ?? 0,
                'sbst_judul' => $data['sbst_judul'] ?? null,
            ]);
        }
        return redirect()->route('report.inout.settings')->with('success', 'Konfigurasi Mapping Laporan berhasil diperbarui!');
    }
}
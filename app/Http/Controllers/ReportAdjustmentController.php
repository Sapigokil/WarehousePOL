<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReportAdjustment;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class ReportAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $yearsIn = DB::table('in_sppms')->selectRaw('YEAR(sppm_date) as year')->distinct()->pluck('year')->toArray();
        $yearsOut = DB::table('out_sppms')->selectRaw('YEAR(sppm_date) as year')->distinct()->pluck('year')->toArray();
        $yearsAdj = ReportAdjustment::select('year')->distinct()->pluck('year')->toArray();
        
        $years = array_unique(array_merge($yearsIn, $yearsOut, $yearsAdj));
        rsort($years);
        if (empty($years)) $years = [date('Y')];

        $year = $request->input('year', date('Y'));

        // --- TANGKAP INPUT FILTER LANGSUNG DARI NAMA FIELD FORM KIRI ---
        $filterTab = $request->input('tab_type');
        $filterMonth = $request->input('month');
        $filterBucketKey = $request->input('bucket_key');

        // --- TERAPKAN FILTER KE QUERY TABEL KANAN ---
        $query = ReportAdjustment::where('year', $year);
        
        if (!empty($filterTab)) {
            $query->where('tab_type', $filterTab);
        }
        if (!empty($filterMonth)) {
            $query->where('month', $filterMonth);
        }
        if (!empty($filterBucketKey)) {
            $query->where('bucket_key', $filterBucketKey);
        }

        $adjustments = $query->orderBy('month', 'asc')
                             ->orderBy('tab_type', 'asc')
                             ->get();

        $sbstMaterials = Material::whereNotNull('sbst_judul')->where('sbst_judul', '!=', '')->get();

        $monthsName = [
            1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL', 
            5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS', 
            9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
        ];

        $tnkbTargets = [
            'tnkb_non_ev_R2' => 'TNKB R.2 NON LISTRIK',
            'tnkb_non_ev_R4' => 'TNKB R.4 NON LISTRIK',
            'tnkb_ev_R2'     => 'TNKB R.2 LISTRIK',
            'tnkb_ev_R4'     => 'TNKB R.4 LISTRIK',
            'tckb_R2'        => 'TCKB R.2',
            'tckb_R4'        => 'TCKB R.4',
        ];

        return view('reports.adjustments.index', compact(
            'year', 'years', 'adjustments', 'sbstMaterials', 'monthsName', 'tnkbTargets',
            'filterTab', 'filterMonth', 'filterBucketKey'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'year'             => 'required|integer',
            'month'            => 'required|integer|min:1|max:12',
            'tab_type'         => 'required|in:tnkb,sbst',
            'bucket_key'       => 'required|string',
            'transaction_type' => 'required|in:in,out,sisa_awal,sisa_gudang', // <--- Update validasi
            'qty_adjustment'   => 'required|integer',
        ]);

        ReportAdjustment::create([
            'year'             => $request->year,
            'month'            => $request->month,
            'tab_type'         => $request->tab_type,
            'bucket_key'       => $request->bucket_key,
            'transaction_type' => $request->transaction_type,
            'qty_adjustment'   => $request->qty_adjustment,
            'keterangan'       => $request->keterangan,
        ]);

        return redirect()->route('report.adjustments.index', ['year' => $request->year])
                         ->with('success', 'Data penyesuaian berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'month'            => 'required|integer|min:1|max:12',
            'tab_type'         => 'required|in:tnkb,sbst',
            'bucket_key'       => 'required|string',
            'transaction_type' => 'required|in:in,out,sisa_awal,sisa_gudang', // <--- Update validasi
            'qty_adjustment'   => 'required|integer',
        ]);

        $adjustment = ReportAdjustment::findOrFail($id);
        
        $adjustment->update([
            'month'            => $request->month,
            'tab_type'         => $request->tab_type,
            'bucket_key'       => $request->bucket_key,
            'transaction_type' => $request->transaction_type,
            'qty_adjustment'   => $request->qty_adjustment,
            'keterangan'       => $request->keterangan,
        ]);

        return redirect()->route('report.adjustments.index', ['year' => $adjustment->year])
                         ->with('success', 'Data penyesuaian berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $adjustment = ReportAdjustment::findOrFail($id);
        $year = $adjustment->year;
        $adjustment->delete();

        return redirect()->route('report.adjustments.index', ['year' => $year])
                         ->with('success', 'Data penyesuaian berhasil dihapus.');
    }

    public function resetYear(Request $request, $year)
    {
        ReportAdjustment::where('year', $year)->delete();

        return redirect()->route('report.adjustments.index', ['year' => $year])
                         ->with('success', "Seluruh data penyesuaian untuk tahun $year berhasil di-reset/dihapus.");
    }
}
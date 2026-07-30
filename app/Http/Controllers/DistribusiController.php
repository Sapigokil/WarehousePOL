<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialCategory;
use App\Models\Destination;
use Illuminate\Support\Facades\DB;

class DistribusiController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil daftar tahun dinamis dari riwayat SPPM Keluar
        $years = DB::table('out_sppms')
            ->selectRaw('YEAR(sppm_date) as year')
            ->whereNotNull('sppm_date')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        $currentYear = date('Y');
        
        // 2. Tangkap Input Filter
        $year1 = $request->input('year1', $years->first() ?? $currentYear);
        $year2 = $request->input('year2', $years->first() ?? $currentYear);
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $destinations = Destination::orderBy('nomor_urut', 'asc')->get();
        
        $category2 = $request->input('category2', $categories->first()->id ?? null);

        // ==============================================================================
        // DATA 1 : Rekapitulasi Tahunan per Kategori (Berdasarkan ismain = 1)
        // ==============================================================================
        $data1Raw = DB::table('out_sppms')
            ->join('out_details', 'out_sppms.id', '=', 'out_details.out_sppm_id')
            ->join('materials', 'out_details.material_id', '=', 'materials.id')
            ->selectRaw('out_sppms.destination_id, materials.material_category_id, SUM(out_details.target_qty) as total_qty')
            ->where('out_sppms.status', 'completed')
            ->whereYear('out_sppms.sppm_date', $year1)
            ->where('materials.ismain', 1) // HANYA material utama
            ->groupBy('out_sppms.destination_id', 'materials.material_category_id')
            ->get();

        // Format ulang data agar mudah dipanggil di Blade (Array 2 Dimensi)
        $data1 = [];
        foreach ($data1Raw as $row) {
            $data1[$row->destination_id][$row->material_category_id] = $row->total_qty;
        }


        // ==============================================================================
        // DATA 2 : Rekapitulasi Bulanan per Tujuan untuk 1 Kategori (Berdasarkan ismain = 1)
        // ==============================================================================
        $data2Raw = DB::table('out_sppms')
            ->join('out_details', 'out_sppms.id', '=', 'out_details.out_sppm_id')
            ->join('materials', 'out_details.material_id', '=', 'materials.id')
            ->selectRaw('out_sppms.destination_id, MONTH(out_sppms.sppm_date) as bulan, SUM(out_details.target_qty) as total_qty')
            ->where('out_sppms.status', 'completed')
            ->whereYear('out_sppms.sppm_date', $year2)
            ->where('materials.material_category_id', $category2)
            ->where('materials.ismain', 1) // HANYA material utama
            ->groupBy('out_sppms.destination_id', DB::raw('MONTH(out_sppms.sppm_date)'))
            ->get();

        // Format ulang data agar mudah dipanggil di Blade (Array 2 Dimensi)
        $data2 = [];
        foreach ($data2Raw as $row) {
            $data2[$row->destination_id][$row->bulan] = $row->total_qty;
        }

        // Referensi Nama Bulan untuk Header Tabel Data 2
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Variabel untuk mempertahankan tab mana yang sedang aktif setelah user menekan filter
        $activeTab = $request->input('active_tab', 'tab1');

        return view('distribusi.index', compact(
            'years', 'categories', 'destinations', 
            'year1', 'year2', 'category2', 
            'data1', 'data2', 'months', 'activeTab'
        ));
    }
}
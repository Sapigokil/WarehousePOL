<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReportSettingController extends Controller
{
    /**
     * Menampilkan Halaman Builder Mapping SIMAK
     */
    public function simakMapping()
    {
        // 1. Ambil SEMUA Materiil terstruktur (Kategori -> Parent -> Child)
        $categories = \App\Models\MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $allGrouped = [];

        foreach ($categories as $cat) {
            $parents = \App\Models\Material::where('material_category_id', $cat->id)
                ->whereNull('parent_id')
                ->orderBy('nomor_urut', 'asc')
                ->get();

            if ($parents->count() > 0) {
                foreach ($parents as $parent) {
                    $parent->children_list = \App\Models\Material::where('parent_id', $parent->id)
                        ->orderBy('nomor_urut', 'asc')
                        ->get();
                }
                $allGrouped[$cat->name] = $parents;
            }
        }

        // 2. Ambil SEMUA Materiil individual yang SUDAH di-mapping (mayoritas adalah child)
        $mappedMaterials = \App\Models\Material::where('is_simak', 1)
            ->whereNotNull('simak_label')
            ->orderBy('simak_urut', 'asc')
            ->get();

        $mappedGroups = [];
        $mappedIds = [];
        
        foreach ($mappedMaterials as $mat) {
            $mappedIds[] = $mat->id;
            $mappedGroups[$mat->simak_label][] = $mat;
        }

        return view('reports.settings.simak', compact('allGrouped', 'mappedGroups', 'mappedIds'));
    }

    /**
     * Menyimpan Data Hasil Drag & Drop ke Database
     */
    public function storeSimakMapping(Request $request)
    {
        $mappingData = json_decode($request->input('mapping_data'), true);

        DB::beginTransaction();
        try {
            // 1. Reset semua data SIMAK
            Material::where('is_simak', 1)->update([
                'is_simak'    => 0,
                'simak_label' => null,
                'simak_urut'  => null
            ]);

            // 2. Update ulang berdasarkan ID individual yang dilempar dari UI
            if ($mappingData && is_array($mappingData)) {
                $urut = 1;
                foreach ($mappingData as $group) {
                    $label = trim($group['label']);
                    $matIds = $group['materials'] ?? []; 
                    
                    if (!empty($label) && !empty($matIds)) {
                        Material::whereIn('id', $matIds)->update([
                            'is_simak'    => 1,
                            'simak_label' => strtoupper($label),
                            'simak_urut'  => $urut
                        ]);
                        $urut++;
                    }
                }
            }

            // 3. Catat Log Sistem
            SystemLog::create([
                'user_id'    => Auth::id(),
                'username'   => Auth::user()->name ?? 'Sistem',
                'action'     => 'UPDATE',
                'table_name' => 'SETTING MAPPING SIMAK',
                'record_id'  => 'ALL',
                'old_values' => null,
                'new_values' => ['Status' => 'Memperbarui urutan dan grouping kolom SIMAK (Individual)'],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();
            return redirect()->route('settings.reports.simak')->with('success', 'Konfigurasi Kolom Laporan SIMAK berhasil diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan konfigurasi: ' . $e->getMessage());
        }
    }
}
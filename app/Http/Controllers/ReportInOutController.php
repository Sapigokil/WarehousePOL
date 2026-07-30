<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Material;
use App\Models\MaterialCategory;

class ReportInOutController extends Controller
{
    public function settings(Request $request)
    {
        $categoryId = $request->input('category_id');
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        // Mengambil semua kategori yang sesuai urutan
        $queryCategories = MaterialCategory::orderBy('nomor_urut', 'asc');
        if ($categoryId) {
            $queryCategories->where('id', $categoryId);
        }
        $groupedCategories = $queryCategories->get();

        // Mengambil material beserta parentnya
        $allMaterials = Material::with(['parent'])
            ->when($categoryId, function ($query) use ($categoryId) {
                return $query->where('material_category_id', $categoryId);
            })
            ->orderBy('nomor_urut', 'asc')
            ->get();

        // Menyusun array data bersarang (Kategori -> Parent -> Child)
        $structuredData = [];
        foreach ($groupedCategories as $cat) {
            $catMaterials = $allMaterials->where('material_category_id', $cat->id);
            if ($catMaterials->isEmpty()) continue;

            $structuredData[$cat->name] = [];

            // 1. Ambil Parent (yang tidak punya parent_id / nilainya kosong)
            $parents = $catMaterials->filter(function($item) {
                return empty($item->parent_id);
            });

            foreach ($parents as $parent) {
                $structuredData[$cat->name][] = [
                    'item' => $parent,
                    'is_child' => false
                ];

                // 2. Ambil Child dari parent ini
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

            // 3. Menangkap data yatim piatu (punya parent_id tapi parentnya tidak tertangkap)
            $caughtIds = collect($structuredData[$cat->name])->pluck('item.id')->toArray();
            $orphans = $catMaterials->whereNotIn('id', $caughtIds);
            foreach ($orphans as $orphan) {
                $structuredData[$cat->name][] = [
                    'item' => $orphan,
                    'is_child' => !empty($orphan->parent_id)
                ];
            }
        }

        return view('reports.settings.inout', compact('structuredData', 'categories', 'categoryId'));
    }

    public function updateSettings(Request $request)
    {
        $mappings = $request->input('mappings', []);

        foreach ($mappings as $id => $data) {
            Material::where('id', $id)->update([
                'tnkb_rpt' => $data['tnkb_rpt'] ?? 0,
                'tnkb_r'   => $data['tnkb_r'] ?? null,
                'tnkb_ev'  => $data['tnkb_ev'] ?? 0,
            ]);
        }

        return redirect()->route('report.inout.settings')->with('success', 'Konfigurasi Mapping Laporan berhasil diperbarui!');
    }
}
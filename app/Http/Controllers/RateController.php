<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RateController extends Controller
{
    /**
     * Menampilkan Halaman Setting Rate Usage & Daya Tahan Stok
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $isAutoGenerate = $request->boolean('auto_generate');

        // 1. Tarik Total Inbound per Material (in_details)
        $inTotals = DB::table('in_details')
            ->selectRaw('material_id, SUM(target_qty) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->toArray();

        // 2. Tarik Total Outbound Completed per Material (out_details)
        $outTotals = DB::table('out_details')
            ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
            ->where('out_sppms.status', 'completed')
            ->selectRaw('material_id, SUM(target_qty) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->toArray();

        // 3. Jika Mode Auto Generate Aktif, Analisa Penggunaan 12 Bulan Terakhir
        $out12Totals = [];
        if ($isAutoGenerate) {
            $out12Totals = DB::table('out_details')
                ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
                ->where('out_sppms.status', 'completed')
                ->where('out_sppms.created_at', '>=', now()->subMonths(12))
                ->selectRaw('material_id, SUM(target_qty) as total')
                ->groupBy('material_id')
                ->pluck('total', 'material_id')
                ->toArray();
        }

        // 4. Tarik Material yang Memiliki rate_usage >= 0 (Sorted by Category Order -> Material Order)
        $rateMaterials = Material::with(['category', 'parent'])
            ->select('materials.*')
            ->leftJoin('material_categories', 'materials.material_category_id', '=', 'material_categories.id')
            ->whereNotNull('materials.rate_usage')
            ->where('materials.satuan', '!=', '-') // Hanya material tunggal / varian
            ->when($search, function($q) use ($search) {
                $q->where(function($sub) use ($search) {
                    $sub->where('materials.name', 'like', '%' . $search . '%')
                        ->orWhere('materials.code', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('material_categories.nomor_urut', 'asc')
            ->orderBy('materials.nomor_urut', 'asc')
            ->get();

        // Attach Kalkulasi Stok & Auto Generate Rate
        foreach ($rateMaterials as $mat) {
            $in = $inTotals[$mat->id] ?? 0;
            $out = $outTotals[$mat->id] ?? 0;
            $mat->current_stock = max(0, $in - $out);

            if ($isAutoGenerate) {
                $total12 = $out12Totals[$mat->id] ?? 0;
                $mat->auto_rate = (int) round($total12 / 12);
            }
        }

        // 5. Tarik Seluruh Material untuk Modal
        $allMaterials = Material::with('category')
            ->select('materials.*')
            ->leftJoin('material_categories', 'materials.material_category_id', '=', 'material_categories.id')
            ->where('materials.satuan', '!=', '-')
            ->orderBy('material_categories.nomor_urut', 'asc')
            ->orderBy('materials.nomor_urut', 'asc')
            ->get();

        return view('settings.rate', compact('rateMaterials', 'allMaterials', 'search', 'isAutoGenerate'));
    }

    /**
     * Memperbarui Nilai Rate Usage Secara Massal / Replace dari Auto Generate
     */
    public function updateBulk(Request $request)
    {
        $request->validate([
            'rates' => 'nullable|array',
            'auto_rates' => 'nullable|array',
        ]);

        $rates = $request->input('rates', []);
        $autoRates = $request->input('auto_rates', []);

        DB::transaction(function () use ($rates, $autoRates) {
            foreach ($rates as $materialId => $val) {
                // Jika nilai Auto Generate ada dan tidak dibatalkan (NULL/empty), replace usage_rate
                if (array_key_exists($materialId, $autoRates) && $autoRates[$materialId] !== null && $autoRates[$materialId] !== '') {
                    $finalRate = (int) $autoRates[$materialId];
                } else {
                    $finalRate = is_numeric($val) ? (int) $val : 0;
                }

                Material::where('id', $materialId)->update([
                    'rate_usage' => $finalRate
                ]);
            }
        });

        // Redirect kembali ke index tanpa parameter auto_generate untuk menghidden kolomnya
        return redirect()->route('settings.rate.index')->with('success', 'Perubahan Rerata Penggunaan (Rate Usage) berhasil disimpan.');
    }

    /**
     * Memproses Pilihan Checkbox dari Modal "Tambah Material"
     */
    public function applySelection(Request $request)
    {
        $selectedIds = $request->input('selected_materials', []);

        DB::transaction(function () use ($selectedIds) {
            Material::whereIn('id', $selectedIds)
                ->whereNull('rate_usage')
                ->update(['rate_usage' => 0]);

            Material::whereNotIn('id', $selectedIds)
                ->where('satuan', '!=', '-')
                ->whereNotNull('rate_usage')
                ->update(['rate_usage' => null]);
        });

        return redirect()->back()->with('success', 'Daftar pemantauan Rate Usage berhasil diperbarui.');
    }

    /**
     * Men-null-kan rate_usage material tunggal
     */
    public function remove($id)
    {
        $material = Material::findOrFail($id);
        $material->update(['rate_usage' => null]);

        return redirect()->back()->with('success', 'Material "' . $material->name . '" berhasil dihapus dari pemantauan Rate Usage.');
    }
}
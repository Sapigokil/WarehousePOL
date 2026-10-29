<?php

namespace App\Http\Controllers;

use App\Models\DisplayMaterial;
use App\Models\DisplaySetting;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class DisplayController extends Controller
{
    /**
     * Halaman Setting Display Monitor (Wajib Auth)
     */
    public function index(Request $request)
    {
        // 1. Ambil Setting Global Display & Tema Aktif
        $settings = [
            'display_theme'    => DisplaySetting::getValue('display_theme', 'black'),
            'refresh_interval' => DisplaySetting::getValue('refresh_interval', '30'),
            'auto_slide'       => DisplaySetting::getValue('auto_slide', '1'),
            'slide_interval'    => DisplaySetting::getValue('slide_interval', '10'),
            'items_per_page'   => DisplaySetting::getValue('items_per_page', '9'),
        ];

        // Daftar Tema Display yang Tersedia
        $availableThemes = [
            'black'       => 'Black Grid (3x3 Minimalis)',
            'modern_table' => 'Modern Table (16 Items)',
            // 'classic_table' => 'Classic Scoreboard Table (List Baris)',
        ];

        // 2. Ambil Daftar Material yang Sudah Didaftarkan ke Display (Sorted by nomor_urut)
        $displayMaterials = DisplayMaterial::with(['material.category', 'material.parent'])
            ->orderBy('nomor_urut', 'asc')
            ->get();

        // ID Material yang sudah terdaftar di display monitor
        $existingMaterialIds = $displayMaterials->pluck('material_id')->toArray();

        // 3. Tarik Material untuk Modal Tambah & Grouping berdasarkan Kategori
        $availableMaterials = Material::with('category')
            ->select('materials.*')
            ->leftJoin('material_categories', 'materials.material_category_id', '=', 'material_categories.id')
            ->where('materials.satuan', '!=', '-')
            ->whereNotIn('materials.id', $existingMaterialIds)
            ->orderBy('material_categories.nomor_urut', 'asc')
            ->orderBy('materials.nomor_urut', 'asc')
            ->get()
            ->groupBy(function ($item) {
                return $item->category->name ?? 'Tanpa Kategori';
            });

        return view('display.setting_index', compact('settings', 'availableThemes', 'displayMaterials', 'availableMaterials'));
    }

    /**
     * Simpan Pengaturan Global & Tema Monitor (Wajib Auth)
     */
    public function updateGlobalSettings(Request $request)
    {
        $request->validate([
            'display_theme'    => 'required|string',
            'refresh_interval' => 'required|numeric|min:5',
            'slide_interval'   => 'required|numeric|min:3',
            'items_per_page'   => 'required|numeric|min:1|max:24',
        ]);

        DisplaySetting::setValue('display_theme', $request->display_theme);
        DisplaySetting::setValue('refresh_interval', $request->refresh_interval);
        DisplaySetting::setValue('auto_slide', $request->has('auto_slide') ? '1' : '0');
        DisplaySetting::setValue('slide_interval', $request->slide_interval);
        DisplaySetting::setValue('items_per_page', $request->items_per_page);

        return redirect()->back()->with('success', 'Pengaturan global & pilihan tema display monitor berhasil disimpan.');
    }

    /**
     * Tambah Material Baru ke Display Monitor (Wajib Auth)
     */
    public function storeMaterials(Request $request)
    {
        $request->validate([
            'material_ids'   => 'required|array',
            'material_ids.*' => 'exists:materials,id',
        ]);

        $maxUrut = DisplayMaterial::max('nomor_urut') ?? 0;

        DB::transaction(function () use ($request, &$maxUrut) {
            foreach ($request->material_ids as $matId) {
                $maxUrut++;
                DisplayMaterial::create([
                    'material_id'  => $matId,
                    'custom_label' => null,
                    'nomor_urut'   => $maxUrut,
                    'is_active'    => true,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Material terpilih berhasil ditambahkan ke daftar monitor.');
    }

    /**
     * Bulk Update Custom Label, Nomor Urut, & Status Aktif (Wajib Auth)
     */
    public function updateMaterialsBulk(Request $request)
    {
        $request->validate([
            'items'                => 'required|array',
            'items.*.custom_label' => 'nullable|string|max:255',
            'items.*.nomor_urut'   => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items as $id => $data) {
                DisplayMaterial::where('id', $id)->update([
                    'custom_label' => $data['custom_label'] ?? null,
                    'nomor_urut'   => (int) $data['nomor_urut'],
                    'is_active'    => isset($data['is_active']) ? true : false,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Daftar & urutan material monitor berhasil diperbarui.');
    }

    /**
     * Hapus Material dari Daftar Monitor (Wajib Auth)
     */
    public function destroyMaterial($id)
    {
        $item = DisplayMaterial::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Material berhasil dihapus dari tampilan monitor.');
    }

    /**
     * Halaman Standalone Display Monitor Dinamis Sesuai Tema (PUBLIC / NON-AUTH)
     * Route: /display/live
     */
    public function showMonitor()
    {
        $selectedTheme = DisplaySetting::getValue('display_theme', 'default');
        
        // Pengecekan lokasi view tema
        $viewPath = "display.themes.{$selectedTheme}";

        // Fallback jika file view tema spesifik belum dibuat
        if (!View::exists($viewPath)) {
            $viewPath = View::exists('display_monitor') ? 'display_monitor' : 'display.themes.default';
        }

        return view($viewPath);
    }

    /**
     * Endpoint API JSON Data Real-Time untuk Display Monitor (PUBLIC / NON-AUTH)
     */
    public function getMonitorData()
    {
        // 1. Tarik Inbound & Outbound Completed untuk Kalkulasi Stok Realtime
        $inTotals = DB::table('in_details')
            ->selectRaw('material_id, SUM(target_qty) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->toArray();

        $outTotals = DB::table('out_details')
            ->join('out_sppms', 'out_details.out_sppm_id', '=', 'out_sppms.id')
            ->where('out_sppms.status', 'completed')
            ->selectRaw('material_id, SUM(target_qty) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->toArray();

        // 2. Tarik Material Display Aktif
        $displayItems = DisplayMaterial::with(['material.category', 'material.parent'])
            ->where('is_active', true)
            ->orderBy('nomor_urut', 'asc')
            ->get()
            ->map(function ($item) use ($inTotals, $outTotals) {
                $mat = $item->material;
                if (!$mat) return null;

                $in = $inTotals[$mat->id] ?? 0;
                $out = $outTotals[$mat->id] ?? 0;
                $stok = max(0, $in - $out);
                $rate = $mat->rate_usage ?? 0;

                // Kalkulasi Daya Tahan
                $dayaTahan = '-';
                $statusColor = 'success';

                if ($stok <= 0) {
                    $dayaTahan = 'Habis';
                    $statusColor = 'danger';
                } elseif ($rate <= 0) {
                    $dayaTahan = 'Tanpa Rate';
                    $statusColor = 'secondary';
                } else {
                    $months = round($stok / $rate, 1);
                    $dayaTahan = $months . ' Bln';
                    if ($months < 2) {
                        $statusColor = 'danger';
                    } elseif ($months <= 5) {
                        $statusColor = 'warning';
                    }
                }

                return [
                    'id'            => $item->id,
                    'display_name'  => !empty($item->custom_label) ? $item->custom_label : $mat->name,
                    'original_name' => $mat->name,
                    'category'      => $mat->category->name ?? '-',
                    'stok'          => $stok,
                    'satuan'        => $mat->satuan,
                    'rate_usage'    => $rate,
                    'daya_tahan'    => $dayaTahan,
                    'status_color'  => $statusColor,
                ];
            })
            ->filter()
            ->values();

        // 3. Sertakan Konfigurasi Global
        $settings = [
            'display_theme'    => DisplaySetting::getValue('display_theme', 'default'),
            'refresh_interval' => (int) DisplaySetting::getValue('refresh_interval', '30'),
            'auto_slide'       => DisplaySetting::getValue('auto_slide', '1') === '1',
            'slide_interval'    => (int) DisplaySetting::getValue('slide_interval', '10'),
            'items_per_page'   => (int) DisplaySetting::getValue('items_per_page', '9'),
        ];

        return response()->json([
            'settings' => $settings,
            'data'     => $displayItems,
        ]);
    }
}
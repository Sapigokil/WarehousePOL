<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        $category_id = $request->input('category_id');

        // Trik agar tetap menggunakan LengthAwarePaginator saat memilih 'ALL'
        $perPage = $limit === 'all' ? 999999 : $limit;

        $materials = Material::with(['category', 'children' => function($query) {
                $query->orderBy('nomor_urut', 'ASC');
            }])
            ->select('materials.*')
            ->leftJoin('material_categories', 'materials.material_category_id', '=', 'material_categories.id')
            ->whereNull('materials.parent_id')
            ->when($search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('materials.name', 'like', '%' . $search . '%')
                      ->orWhere('materials.code', 'like', '%' . $search . '%')
                      ->orWhereHas('category', function ($cq) use ($search) {
                          $cq->where('name', 'like', '%' . $search . '%');
                      })
                      ->orWhereHas('children', function ($cq) use ($search) {
                          $cq->where('name', 'like', '%' . $search . '%');
                      });
                });
            })
            ->when($category_id, function ($query, $category_id) {
                return $query->where('materials.material_category_id', $category_id);
            })
            ->orderBy('material_categories.nomor_urut', 'ASC')
            ->orderBy('materials.nomor_urut', 'ASC')
            ->paginate($perPage)
            ->withQueryString();

        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        return view('materials.index', compact('materials', 'categories', 'search', 'limit', 'category_id'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id'            => 'nullable|exists:materials,id',
            'code'                 => 'nullable|string|max:50|unique:materials,code',
            'name'                 => 'required_if:tipe_input,tunggal|string|max:255|nullable',
            'material_category_id' => 'required|exists:material_categories,id',
            'satuan'               => 'nullable|string|max:50',
            'minimal_stok'         => 'nullable|numeric',
            'pakai_seri'           => 'nullable|boolean',
            'ismain'               => 'nullable|integer|in:0,1',
            'jmlxinduk'            => 'nullable|integer|in:0,1',
            'keterangan'           => 'nullable|string',
            'is_harga'             => 'nullable|boolean', // Ditambahkan
        ]);

        $tipeInput = $request->input('tipe_input', 'tunggal');

        if ($tipeInput == 'tunggal') {
            // --- LOGIKA PENYIMPANAN BARANG TUNGGAL ---
            $validated['ismain'] = $request->input('ismain', 0);
            if ($validated['ismain'] == 1) {
                $validated['jmlxinduk'] = 0;
            } else {
                $validated['jmlxinduk'] = $request->input('jmlxinduk', 0);
            }

            $validated['pakai_seri'] = $request->input('pakai_seri') == 1 ? 1 : 0;
            $validated['is_harga'] = $request->input('is_harga') == 1 ? 1 : 0;
            
            // Tambahkan nomor_urut otomatis jika kosong
            if (!$request->filled('nomor_urut')) {
                $maxUrut = Material::where('material_category_id', $validated['material_category_id'])->max('nomor_urut');
                $validated['nomor_urut'] = $maxUrut ? $maxUrut + 1 : 1;
            } else {
                $validated['nomor_urut'] = $request->input('nomor_urut');
            }

            Material::create($validated);

        } else {
            // --- LOGIKA PENYIMPANAN BARANG BERKELOMPOK (INDUK + VARIAN) ---
            $request->validate([
                'parent_name' => 'required|string|max:255',
                'variants'    => 'required|array|min:1',
                'variants.*.name' => 'required|string|max:255',
                'variants.*.satuan' => 'required|string',
                'variants.*.minimal_stok' => 'required|numeric',
                'variants.*.pakai_seri' => 'required|boolean',
                'variants.*.is_harga' => 'required|boolean', // Ditambahkan
            ]);

            // 1. Buat Induk (Parent)
            $parentData = [
                'name' => $request->input('parent_name'),
                'code' => $request->input('parent_code'),
                'material_category_id' => $validated['material_category_id'],
                'keterangan' => $request->input('parent_keterangan'),
                'is_harga' => $request->input('parent_is_harga', 0), // Tangkap status harga induk
                'ismain' => 0, // Induk kelompok tidak pernah main material
                'jmlxinduk' => 0,
                'pakai_seri' => 0,
            ];

            if (!$request->filled('nomor_urut')) {
                $maxUrut = Material::where('material_category_id', $validated['material_category_id'])->max('nomor_urut');
                $parentData['nomor_urut'] = $maxUrut ? $maxUrut + 1 : 1;
            } else {
                $parentData['nomor_urut'] = $request->input('nomor_urut');
            }

            $parentMaterial = Material::create($parentData);

            // 2. Buat Anak-anaknya (Variants)
            foreach ($request->variants as $variant) {
                Material::create([
                    'parent_id' => $parentMaterial->id,
                    'name' => $variant['name'],
                    'material_category_id' => $validated['material_category_id'],
                    'satuan' => $variant['satuan'],
                    'minimal_stok' => $variant['minimal_stok'],
                    'pakai_seri' => $variant['pakai_seri'] == 1 ? 1 : 0,
                    'is_harga' => $variant['is_harga'] == 1 ? 1 : 0, // Tangkap status harga anak
                    'ismain' => 0,
                    'jmlxinduk' => 0,
                    'nomor_urut' => $parentMaterial->nomor_urut, // Urutan sama dengan induk
                ]);
            }
        }

        if ($request->input('submit_action') == 'save_new') {
            return redirect()->back()->with('success', 'Material berhasil ditambahkan. Silakan tambah data lagi.')->withInput();
        }

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }

    public function update(Request $request, Material $material)
    {
        $hasChildren = $material->children()->exists();

        // 1. Sesuaikan aturan validasi dinamis berdasarkan jenis datanya
        $validated = $request->validate([
            'parent_id'            => 'nullable|exists:materials,id',
            'code'                 => 'nullable|string|max:50|unique:materials,code,' . $material->id,
            // Jika punya anak (kelompok), validasi parent_name. Jika tunggal, validasi name.
            'name'                 => $hasChildren ? 'nullable' : 'required|string|max:255',
            'parent_name'          => $hasChildren ? 'required|string|max:255' : 'nullable',
            'material_category_id' => 'required|exists:material_categories,id',
            'satuan'               => 'nullable|string|max:50',
            'minimal_stok'         => 'nullable|numeric',
            'pakai_seri'           => 'nullable|boolean',
            'ismain'               => 'nullable|integer|in:0,1',
            'jmlxinduk'            => 'nullable|integer|in:0,1',
            'keterangan'           => 'nullable|string',
            'is_harga'             => 'nullable|boolean', 
            'nomor_urut'           => 'nullable|integer',
        ]);

        // 2. Jika ini kelompok, pindahkan isi parent_name ke index 'name' agar tersimpan ke kolom database 'name'
        if ($hasChildren && $request->filled('parent_name')) {
            $validated['name'] = $request->input('parent_name');
        }

        // Aturan logika ismain & jmlxinduk yang sudah ada sebelumnya
        if ($hasChildren || !empty($material->parent_id)) {
            $validated['ismain'] = 0;
            $validated['jmlxinduk'] = 0;
        } else {
            $validated['ismain'] = $request->input('ismain', 0);
            if ($validated['ismain'] == 1) {
                $validated['jmlxinduk'] = 0;
            } else {
                $validated['jmlxinduk'] = $request->input('jmlxinduk', 0);
            }
        }

        $validated['pakai_seri'] = $request->input('pakai_seri') == 1 ? 1 : 0;
        $validated['is_harga'] = $request->input('is_harga') == 1 ? 1 : 0; 
        
        if ($request->filled('nomor_urut')) {
             $validated['nomor_urut'] = $request->input('nomor_urut');
        }

        $material->update($validated);

        if ($hasChildren) {
            $material->children()->update([
                'material_category_id' => $validated['material_category_id']
            ]);
        }

        return redirect()->back()->with('success', 'Material berhasil diperbarui.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'items'              => 'required|array',
            'items.*.id'         => 'required|exists:materials,id',
            'items.*.nomor_urut' => 'required|numeric'
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->items as $item) {
                Material::where('id', $item['id'])->update([
                    'nomor_urut' => $item['nomor_urut']
                ]);
            }
        });

        return response()->json(['status' => 'success', 'message' => 'Urutan komoditas berhasil disinkronisasi.']);
    }

    public function destroy($id)
    {
        $material = Material::findOrFail($id);
        Material::where('parent_id', $material->id)->delete();
        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Data master barang berhasil dihapus.');
    }
}
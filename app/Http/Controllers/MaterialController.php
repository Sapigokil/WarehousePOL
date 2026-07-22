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
            'code'                 => 'nullable|string|max:50',
            'name'                 => 'required|string|max:255',
            'material_category_id' => 'required|exists:material_categories,id',
            'satuan'               => 'nullable|string|max:50',
            'minimal_stok'         => 'nullable|numeric',
            'pakai_seri'           => 'nullable|boolean',
            'ismain'               => 'nullable|integer|in:0,1',
            'jmlxinduk'            => 'nullable|integer|in:0,1',
            'keterangan'           => 'nullable|string',
        ]);

        // Aturan: Jika material adalah Child (punya parent_id), nilai ismain dan jmlxinduk selalu 0
        if (!empty($validated['parent_id'])) {
            $validated['ismain'] = 0;
            $validated['jmlxinduk'] = 0;
        } else {
            // Jika ismain diset 1 (Ya), maka jmlxinduk otomatis diset 0
            $validated['ismain'] = $request->input('ismain', 0);
            if ($validated['ismain'] == 1) {
                $validated['jmlxinduk'] = 0;
            } else {
                $validated['jmlxinduk'] = $request->input('jmlxinduk', 0);
            }
        }

        $validated['pakai_seri'] = $request->has('pakai_seri') ? 1 : 0;

        Material::create($validated);

        return redirect()->back()->with('success', 'Material berhasil ditambahkan.');
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'parent_id'            => 'nullable|exists:materials,id',
            'code'                 => 'nullable|string|max:50',
            'name'                 => 'required|string|max:255',
            'material_category_id' => 'required|exists:material_categories,id',
            'satuan'               => 'nullable|string|max:50',
            'minimal_stok'         => 'nullable|numeric',
            'pakai_seri'           => 'nullable|boolean',
            'ismain'               => 'nullable|integer|in:0,1',
            'jmlxinduk'            => 'nullable|integer|in:0,1',
            'keterangan'           => 'nullable|string',
        ]);

        // Cek apakah material ini memiliki turunan (Child)
        $hasChildren = $material->children()->exists();

        // Aturan: Jika merupakan Parent (memiliki child) ATAU merupakan Child (punya parent_id)
        if ($hasChildren || !empty($validated['parent_id'])) {
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

        $validated['pakai_seri'] = $request->has('pakai_seri') ? 1 : 0;

        $material->update($validated);

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
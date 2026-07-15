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
        $tipe_input = $request->input('tipe_input');
        $submit_action = $request->input('submit_action'); // Menangkap aksi tombol yang diklik

        if ($tipe_input == 'tunggal') {
            $request->validate([
                'name'                 => 'required|string|max:255',
                'material_category_id' => 'required|exists:material_categories,id',
                'satuan'               => 'required|string|max:50',
                'minimal_stok'         => 'required|numeric|min:0',
                'pakai_seri'           => 'required|boolean',
                'keterangan'           => 'nullable|string',
                'nomor_urut'           => 'nullable|numeric|min:1',
                'code'               => 'nullable|string|max:255',
            ], ['name.required' => 'Nama barang wajib diisi.']);

            $data = $request->all();

            if (!$request->filled('nomor_urut')) {
                $maxUrut = Material::where('material_category_id', $request->material_category_id)
                    ->whereNull('parent_id')
                    ->max('nomor_urut') ?? 0;
                $data['nomor_urut'] = $maxUrut + 1;
            } else {
                Material::where('material_category_id', $request->material_category_id)
                    ->whereNull('parent_id')
                    ->where('nomor_urut', '>=', $request->nomor_urut)
                    ->increment('nomor_urut');
            }

            Material::create($data);

        } elseif ($tipe_input == 'kelompok') {
            $request->validate([
                'parent_name'          => 'required|string|max:255',
                'code'                 => 'nullable|string|max:255',
                'material_category_id' => 'required|exists:material_categories,id',
                'parent_keterangan'    => 'nullable|string',
                'nomor_urut'           => 'nullable|numeric|min:1',
                'variants'             => 'required|array|min:1',
                'variants.*.name'      => 'required|string|max:255',
                'variants.*.satuan'    => 'required|string|max:50',
                'variants.*.minimal_stok'=> 'required|numeric|min:0',
                'variants.*.pakai_seri'  => 'required|boolean',
            ], [
                'parent_name.required' => 'Nama Induk Kelompok wajib diisi.',
                'variants.*.name.required' => 'Semua baris varian wajib memiliki nama.'
            ]);

            $nomorUrutFinal = $request->nomor_urut;
            if (!$request->filled('nomor_urut')) {
                $maxUrut = Material::where('material_category_id', $request->material_category_id)
                    ->whereNull('parent_id')
                    ->max('nomor_urut') ?? 0;
                $nomorUrutFinal = $maxUrut + 1;
            } else {
                Material::where('material_category_id', $request->material_category_id)
                    ->whereNull('parent_id')
                    ->where('nomor_urut', '>=', $request->nomor_urut)
                    ->increment('nomor_urut');
            }

            DB::transaction(function () use ($request, $nomorUrutFinal) {
                $parent = Material::create([
                    'name'                 => $request->parent_name,
                    'material_category_id' => $request->material_category_id,
                    'nomor_urut'           => $nomorUrutFinal,
                    'code'                 => $request->parent_code,
                    'satuan'               => '-', 
                    'minimal_stok'         => 0,
                    'pakai_seri'           => 0,
                    'keterangan'           => $request->parent_keterangan,
                ]);

                $childUrut = 1;
                foreach ($request->variants as $variant) {
                    Material::create([
                        'parent_id'            => $parent->id,
                        'name'                 => $variant['name'],
                        'material_category_id' => $request->material_category_id,
                        'nomor_urut'           => $childUrut,
                        'satuan'               => $variant['satuan'],
                        'minimal_stok'         => $variant['minimal_stok'],
                        'pakai_seri'           => $variant['pakai_seri'],
                        'keterangan'           => null,
                    ]);
                    $childUrut++;
                }
            });
        }

        // LOGIKA REDIRECT: Jika memilih "SIMPAN & BARU", kirimkan instruksi ke view agar modal dibuka kembali
        if ($submit_action === 'save_new') {
            return redirect()->route('materials.index')
                ->with('success', 'Master barang berhasil didaftarkan.')
                ->with('keep_modal_open', true)
                ->with('old_tipe_input', $tipe_input);
        }

        return redirect()->route('materials.index')->with('success', 'Master barang berhasil didaftarkan.');
    }

    public function update(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $tipe_input = $request->input('tipe_input');

        if ($tipe_input == 'tunggal') {
            $request->validate([
                'name'                 => 'required|string|max:255',
                'material_category_id' => 'required|exists:material_categories,id',
                'satuan'               => 'required|string|max:50',
                'minimal_stok'         => 'required|numeric|min:0',
                'pakai_seri'           => 'required|boolean',
                'keterangan'           => 'nullable|string',
                'nomor_urut'           => 'required|numeric|min:1',
                'code'                 => 'nullable|string|max:255',
            ]);

            if ($material->material_category_id != $request->material_category_id || $material->nomor_urut != $request->nomor_urut) {
                Material::where('material_category_id', $request->material_category_id)
                    ->whereNull('parent_id')
                    ->where('nomor_urut', '>=', $request->nomor_urut)
                    ->increment('nomor_urut');
            }

            $material->update($request->all());

        } elseif ($tipe_input == 'kelompok') {
            $request->validate([
                'parent_name'          => 'required|string|max:255',
                'code'                 => 'nullable|string|max:255',
                'material_category_id' => 'required|exists:material_categories,id',
                'parent_keterangan'    => 'nullable|string',
                'nomor_urut'           => 'required|numeric|min:1',
                'variants'             => 'required|array|min:1',
                'variants.*.name'      => 'required|string|max:255',
                'variants.*.satuan'    => 'required|string|max:50',
                'variants.*.minimal_stok'=> 'required|numeric|min:0',
                'variants.*.pakai_seri'  => 'required|boolean',
            ]);

            DB::transaction(function () use ($request, $material) {
                if ($material->material_category_id != $request->material_category_id || $material->nomor_urut != $request->nomor_urut) {
                    Material::where('material_category_id', $request->material_category_id)
                        ->whereNull('parent_id')
                        ->where('nomor_urut', '>=', $request->nomor_urut)
                        ->increment('nomor_urut');
                }

                $material->update([
                    'name'                 => $request->parent_name,
                    'material_category_id' => $request->material_category_id,
                    'nomor_urut'           => $request->nomor_urut,
                    'code'                 => $request->parent_code,
                    'keterangan'           => $request->parent_keterangan,
                ]);

                $keepIds = [];
                if($request->has('variants')) {
                    foreach ($request->variants as $variant) {
                        if (isset($variant['id'])) {
                            $keepIds[] = $variant['id'];
                        }
                    }
                }

                Material::where('parent_id', $material->id)->whereNotIn('id', $keepIds)->delete();

                if($request->has('variants')) {
                    $childUrut = 1;
                    foreach ($request->variants as $variant) {
                        if (isset($variant['id'])) {
                            Material::where('id', $variant['id'])->update([
                                'name'                 => $variant['name'],
                                'material_category_id' => $request->material_category_id,
                                'nomor_urut'           => $childUrut,
                                'satuan'               => $variant['satuan'],
                                'minimal_stok'         => $variant['minimal_stok'],
                                'pakai_seri'           => $variant['pakai_seri'],
                            ]);
                        } else {
                            Material::create([
                                'parent_id'            => $material->id,
                                'name'                 => $variant['name'],
                                'material_category_id' => $request->material_category_id,
                                'nomor_urut'           => $childUrut,
                                'satuan'               => $variant['satuan'],
                                'minimal_stok'         => $variant['minimal_stok'],
                                'pakai_seri'           => $variant['pakai_seri'],
                                'keterangan'           => null,
                            ]);
                        }
                        $childUrut++;
                    }
                }
            });
        }

        return redirect()->route('materials.index')->with('success', 'Data master barang berhasil diperbarui.');
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
<?php

namespace App\Http\Controllers;

use App\Models\MaterialCategory;
use Illuminate\Http\Request;

class MaterialCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);

        $categories = MaterialCategory::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%')
                             ->orWhere('keterangan', 'like', '%' . $search . '%');
            })
            ->orderBy('nomor_urut', 'asc')
            ->paginate($limit)
            ->withQueryString();

        return view('categories.index', compact('categories', 'search', 'limit'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        $usedNumbers = MaterialCategory::pluck('nomor_urut')->toArray();
        $nomor_urut = 1;
        while (in_array($nomor_urut, $usedNumbers)) {
            $nomor_urut++;
        }

        $data = $request->all();
        $data['nomor_urut'] = $nomor_urut;

        MaterialCategory::create($data);

        return redirect()->route('categories.index')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $category = MaterialCategory::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
        ]);

        $category->update($request->except(['nomor_urut']));

        return redirect()->route('categories.index')->with('success', 'Data kategori berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $category = MaterialCategory::findOrFail($id);
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus dari sistem.');
    }

    public function reorder(Request $request)
    {
        $order = $request->input('order');
        if (empty($order)) {
            return response()->json(['success' => false]);
        }

        $start_urut = MaterialCategory::whereIn('id', $order)->min('nomor_urut') ?? 1;

        foreach ($order as $id) {
            MaterialCategory::where('id', $id)->update(['nomor_urut' => $start_urut]);
            $start_urut++;
        }

        return response()->json(['success' => true]);
    }
}
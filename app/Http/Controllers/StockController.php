<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Material;
use App\Models\Warehouse;
use App\Models\MaterialCategory; // Tambahan untuk memanggil data kategori
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        $category_id = $request->input('category_id'); // Menangkap filter kategori
        $warehouse_id = $request->input('warehouse_id'); // Menangkap filter gudang

        $stocks = Stock::with(['material.category', 'warehouse'])
            ->when($search, function ($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('no_surat_masuk', 'like', '%' . $search . '%')
                      ->orWhere('seri_awal', 'like', '%' . $search . '%')
                      ->orWhere('seri_akhir', 'like', '%' . $search . '%')
                      ->orWhereHas('material', function ($mq) use ($search) {
                          $mq->where('name', 'like', '%' . $search . '%')
                             ->orWhere('kode_barang', 'like', '%' . $search . '%');
                      });
                });
            })
            // Logika filter Kategori
            ->when($category_id, function ($query, $category_id) {
                return $query->whereHas('material', function ($q) use ($category_id) {
                    $q->where('material_category_id', $category_id);
                });
            })
            // Logika filter Gudang
            ->when($warehouse_id, function ($query, $warehouse_id) {
                return $query->where('warehouse_id', $warehouse_id);
            })
            ->orderBy('tgl_masuk', 'desc')
            ->paginate($limit)
            ->withQueryString();

        $materials = Material::orderBy('name', 'asc')->get();
        $warehouses = Warehouse::orderBy('name', 'asc')->get();
        $categories = MaterialCategory::orderBy('name', 'asc')->get(); // Mengambil daftar kategori

        return view('stocks.stock_index', compact('stocks', 'materials', 'warehouses', 'categories', 'search', 'limit', 'category_id', 'warehouse_id'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_surat_masuk' => 'required|string|max:100',
            'tgl_masuk'      => 'required|date',
            'material_id'    => 'required|integer',
            'warehouse_id'   => 'required|integer',
            'qty'            => 'required|numeric|min:1',
            'harga_satuan'   => 'nullable|numeric|min:0',
            'seri_awal'      => 'nullable|string',
            'seri_akhir'     => 'nullable|string',
            'keterangan'     => 'nullable|string',
        ]);

        $data = $request->all();
        $hargaSatuan = $request->input('harga_satuan', 0);
        $data['total_harga'] = $request->qty * $hargaSatuan;
        $data['status'] = 'Tersedia';

        Stock::create($data);

        return redirect()->route('stocks.index')->with('success', 'Penyesuaian stok manual berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $stock = Stock::findOrFail($id);

        $request->validate([
            'no_surat_masuk' => 'required|string|max:100',
            'tgl_masuk'      => 'required|date',
            'material_id'    => 'required|integer',
            'warehouse_id'   => 'required|integer',
            'qty'            => 'required|numeric|min:0',
            'harga_satuan'   => 'nullable|numeric|min:0',
            'seri_awal'      => 'nullable|string',
            'seri_akhir'     => 'nullable|string',
            'status'         => 'required|string',
            'keterangan'     => 'nullable|string',
        ]);

        $data = $request->all();
        $hargaSatuan = $request->input('harga_satuan', 0);
        $data['total_harga'] = $request->qty * $hargaSatuan;

        $stock->update($data);

        return redirect()->route('stocks.index')->with('success', 'Data stok berhasil diubah/dikoreksi.');
    }

    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();

        return redirect()->route('stocks.index')->with('success', 'Data stok berhasil dihapus.');
    }
}
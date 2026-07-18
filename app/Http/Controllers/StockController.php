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
        $category_filter = $request->input('category_id');

        $categories = MaterialCategory::with(['materials' => function($q) use ($search) {
            $q->whereNull('parent_id')
            ->when($search, function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
            })
            ->with(['children' => function($q2) {
                $q2->orderBy('nomor_urut', 'asc');
            }])
            ->orderBy('nomor_urut', 'asc');
        }])
        ->when($category_filter, function($q) use ($category_filter) {
            return $q->where('id', $category_filter);
        })
        ->orderBy('nomor_urut', 'asc')->get();

        $stockTotals = Stock::selectRaw('material_id, SUM(qty) as total_qty')
            ->groupBy('material_id')
            ->pluck('total_qty', 'material_id')
            ->toArray();

        $allCategories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        return view('stocks.stock_index', compact('categories', 'stockTotals', 'search', 'category_filter', 'allCategories'));
    }

    public function show($id)
    {
        // Menampilkan detail stok per barang
        $material = Material::with('category')->findOrFail($id);

        // Ambil riwayat kedatangan berdasarkan SPPM yang tercatat di tabel stocks
        $stockDetails = Stock::with('warehouse')
            ->where('material_id', $id)
            ->orderBy('tgl_masuk', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Hitung total stok dari koleksi detail ini
        $totalStock = $stockDetails->sum('qty');

        return view('stocks.stock_detail', compact('material', 'stockDetails', 'totalStock'));
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
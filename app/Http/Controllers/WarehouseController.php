<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);

        $warehouses = Warehouse::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%')
                             ->orWhere('code', 'like', '%' . $search . '%')
                             ->orWhere('lokasi', 'like', '%' . $search . '%');
            })
            ->orderBy('nomor_urut', 'asc')
            ->paginate($limit)
            ->withQueryString();

        return view('warehouses.index', compact('warehouses', 'search', 'limit'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50',
            'lokasi'     => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ], [
            'name.required'       => 'Nama gudang wajib diisi.',
            'code.required'       => 'Kode gudang wajib diisi.',
            'lokasi.required'     => 'Lokasi gudang wajib diisi.',
        ]);

        // ALGORITMA PENCARI ANGKA TERKECIL YANG BELUM DIGUNAKAN
        $usedNumbers = Warehouse::pluck('nomor_urut')->toArray();
        $nomor_urut = 1;
        while (in_array($nomor_urut, $usedNumbers)) {
            $nomor_urut++;
        }

        $data = $request->all();
        $data['nomor_urut'] = $nomor_urut;

        Warehouse::create($data);

        return redirect()->route('warehouses.index')->with('success', 'Data gudang baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50',
            'lokasi'     => 'required|string|max:255',
            'keterangan' => 'nullable|string',
        ], [
            'name.required'       => 'Nama gudang wajib diisi.',
            'code.required'       => 'Kode gudang wajib diisi.',
            'lokasi.required'     => 'Lokasi gudang wajib diisi.',
        ]);

        // Abaikan input nomor_urut jika ada, biarkan tetap seperti data aslinya
        $warehouse->update($request->except(['nomor_urut']));

        return redirect()->route('warehouses.index')->with('success', 'Data gudang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Data gudang berhasil dihapus dari sistem.');
    }

    // FUNGSI KHUSUS UNTUK MENANGANI DRAG AND DROP AJAX
    public function reorder(Request $request)
    {
        $order = $request->input('order');
        if (empty($order)) {
            return response()->json(['success' => false]);
        }

        // Cari nomor urut terkecil dari kelompok baris yang sedang tampil untuk dijadikan nilai awal
        $start_urut = Warehouse::whereIn('id', $order)->min('nomor_urut') ?? 1;

        // Terapkan nomor urut baru secara berurutan sesuai susunan id yang dikirimkan browser
        foreach ($order as $id) {
            Warehouse::where('id', $id)->update(['nomor_urut' => $start_urut]);
            $start_urut++;
        }

        return response()->json(['success' => true]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use Illuminate\Http\Request;

class DestinationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        
        $destinations = Destination::when($search, function ($query, $search) {
                return $query->where('name', 'like', '%' . $search . '%')
                             ->orWhere('nama', 'like', '%' . $search . '%')
                             ->orWhere('pangkat_nrp', 'like', '%' . $search . '%')
                             ->orWhere('jabatan', 'like', '%' . $search . '%')
                             ->orWhere('keterangan', 'like', '%' . $search . '%');
            })
            ->orderBy('nomor_urut', 'asc')
            ->paginate($limit)
            ->withQueryString();

        return view('destination.index', compact('destinations', 'search', 'limit'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255|unique:destinations,name',
            'nama'        => 'nullable|string|max:255',
            'pangkat_nrp' => 'nullable|string|max:255',
            'jabatan'     => 'nullable|string|max:255',
            'keterangan'  => 'nullable|string',
        ]);

        $lastUrut = Destination::max('nomor_urut');
        $nextUrut = $lastUrut ? $lastUrut + 1 : 1;

        Destination::create([
            'nomor_urut'  => $nextUrut,
            'name'        => $request->name,
            'nama'        => $request->nama,
            'pangkat_nrp' => $request->pangkat_nrp,
            'jabatan'     => $request->jabatan,
            'keterangan'  => $request->keterangan,
        ]);

        return redirect()->route('destinations.index')->with('success', 'Daftar penerima baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);
        
        $request->validate([
            'name'        => 'required|string|max:255|unique:destinations,name,' . $destination->id,
            'nama'        => 'nullable|string|max:255',
            'pangkat_nrp' => 'nullable|string|max:255',
            'jabatan'     => 'nullable|string|max:255',
            'keterangan'  => 'nullable|string',
        ]);

        $destination->update([
            'name'        => $request->name,
            'nama'        => $request->nama,
            'pangkat_nrp' => $request->pangkat_nrp,
            'jabatan'     => $request->jabatan,
            'keterangan'  => $request->keterangan,
        ]);

        return redirect()->route('destinations.index')->with('success', 'Data penerima berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $destination = Destination::findOrFail($id);
        $destination->delete();

        return redirect()->route('destinations.index')->with('success', 'Data penerima berhasil dihapus.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'exists:destinations,id',
        ]);

        foreach ($request->order as $index => $id) {
            Destination::where('id', $id)->update(['nomor_urut' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
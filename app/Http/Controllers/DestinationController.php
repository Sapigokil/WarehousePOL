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
            'name' => 'required|string|max:255|unique:destinations,name'
        ]);

        $lastUrut = Destination::max('nomor_urut');
        $nextUrut = $lastUrut ? $lastUrut + 1 : 1;

        Destination::create([
            'nomor_urut' => $nextUrut,
            'name'       => $request->name,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('destinations.index')->with('success', 'Daftar penerima baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $destination = Destination::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:destinations,name,' . $destination->id
        ]);

        $destination->update([
            'name'       => $request->name,
            'keterangan' => $request->keterangan,
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
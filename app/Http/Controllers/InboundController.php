<?php

namespace App\Http\Controllers;

use App\Models\InSppm;
use App\Models\InDetail;
use App\Models\InLog;
use App\Models\InStock;
use App\Models\Material;
use App\Models\MaterialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
    private $inboundMode = 'mode-1'; 

    public function index(Request $request)
    {
        $search = $request->input('search');
        $limit = $request->input('limit', 10);
        $category_id = $request->input('category_id');
        $perPage = $limit === 'all' ? 999999 : $limit;

        $sppms = InSppm::with([
                'category', 
                'details.material', 
                'logs' => function($q) {
                    $q->orderBy('batch_number', 'asc');
                }, 
                'logs.stocks'
            ])
            ->when($search, function ($query, $search) {
                return $query->where('sppm_no', 'like', '%' . $search . '%');
            })
            ->when($category_id, function ($query, $category_id) {
                return $query->where('material_category_id', $category_id);
            })
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage)
            ->withQueryString();

        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        return view('inbound.index', compact('sppms', 'categories', 'search', 'limit', 'category_id'));
    }

    public function create()
    {
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $inboundMode = $this->inboundMode;

        return view('inbound.form', compact('categories', 'inboundMode'))->with('inbound', null);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sppm_no'              => 'required|string|max:255|unique:in_sppms,sppm_no',
            'sppm_date'            => 'required|date',
            'material_category_id' => 'required|exists:material_categories,id',
            'inbound_mode'         => 'required|string|in:mode-1,mode-2',
            'items'                => 'required|array',
            'items.*.material_id'  => 'required|exists:materials,id',
            'items.*.target_qty'   => 'nullable|numeric|min:0', 
        ]);

        $currentMode = $request->input('inbound_mode');
        $batchDate = $currentMode === 'mode-2' ? $request->input('batch_date') : $request->sppm_date;

        DB::transaction(function () use ($request, $currentMode, $batchDate) {
            $sppm = InSppm::create([
                'sppm_no'              => $request->sppm_no,
                'sppm_date'            => $request->sppm_date,
                'material_category_id' => $request->material_category_id,
                'notes'                => $request->notes_manifes,
                'status'               => $currentMode === 'mode-1' ? 'completed' : 'pending'
            ]);

            $log = InLog::create([
                'in_sppm_id'   => $sppm->id,
                'batch_number' => 1,
                'receive_date' => $batchDate,
                'receiver_name'=> auth()->user()->name ?? 'Admin Gudang',
                'notes'        => $request->batch_notes ?? 'Penerimaan Tahap 1'
            ]);

            $isAllCompleted = true;

            foreach ($request->items as $item) {
                if (isset($item['target_qty']) && $item['target_qty'] > 0) {
                    
                    InDetail::create([
                        'in_sppm_id'        => $sppm->id,
                        'material_id'       => $item['material_id'],
                        'target_qty'        => $item['target_qty'],
                        'qty_huruf'         => $item['qty_huruf'] ?? null,
                        'harga_satuan'      => $item['harga_satuan'] ?? 0,
                        'harga_total'       => $item['harga_total'] ?? 0,
                        'sppm_serial_start' => $item['sppm_serial_start'] ?? null,
                        'sppm_serial_end'   => $item['sppm_serial_end'] ?? null,
                    ]);

                    $qtyReceived = $currentMode === 'mode-1' ? $item['target_qty'] : ($item['qty_received'] ?? 0);
                    
                    if ($qtyReceived < $item['target_qty']) {
                        $isAllCompleted = false;
                    }

                    if ($qtyReceived > 0) {
                        InStock::create([
                            'in_log_id'    => $log->id,
                            'material_id'  => $item['material_id'],
                            'qty_received' => $qtyReceived,
                            'serial_start' => $currentMode === 'mode-2' ? ($item['serial_start'] ?? null) : null,
                            'serial_end'   => $currentMode === 'mode-2' ? ($item['serial_end'] ?? null) : null,
                        ]);
                    }
                }
            }

            if ($currentMode === 'mode-2') {
                $sppm->update(['status' => $isAllCompleted ? 'completed' : 'partial']);
            }
        });

        if ($request->input('submit_action') === 'save_new') {
            return redirect()->route('inbound.create')->with('success', 'Data SPPM dan Tahap 1 berhasil disimpan.');
        }

        return redirect()->route('inbound.index')->with('success', 'Data SPPM dan Tahap 1 berhasil disimpan.');
    }

    public function edit($id)
    {
        $inbound = InSppm::with(['details.material', 'logs' => function($q){
            $q->orderBy('batch_number', 'asc');
        }, 'logs.stocks'])->findOrFail($id);
        
        $categories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();
        $inboundMode = $this->inboundMode;

        return view('inbound.form', compact('inbound', 'categories', 'inboundMode'));
    }

    public function update(Request $request, $id)
    {
        $sppm = InSppm::with('details', 'logs.stocks')->findOrFail($id);
        $currentMode = $request->input('inbound_mode');

        if ($currentMode === 'mode-2') {
            $request->validate([
                'batch_date'          => 'required|date',
                'items'               => 'required|array',
                'items.*.material_id' => 'required|exists:materials,id',
                'items.*.qty_received'=> 'nullable|numeric|min:0'
            ]);

            DB::transaction(function () use ($request, $sppm) {
                $nextBatch = $sppm->logs()->max('batch_number') + 1;

                $log = InLog::create([
                    'in_sppm_id'   => $sppm->id,
                    'batch_number' => $nextBatch,
                    'receive_date' => $request->batch_date,
                    'receiver_name'=> auth()->user()->name ?? 'Admin Gudang',
                    'notes'        => $request->batch_notes ?? "Penerimaan Tahap {$nextBatch}."
                ]);

                $isAllCompleted = true;

                foreach ($request->items as $item) {
                    if (isset($item['qty_received']) && $item['qty_received'] > 0) {
                        InStock::create([
                            'in_log_id'    => $log->id,
                            'material_id'  => $item['material_id'],
                            'qty_received' => $item['qty_received'],
                            'serial_start' => $item['serial_start'] ?? null,
                            'serial_end'   => $item['serial_end'] ?? null,
                        ]);
                    }

                    $detail = $sppm->details->where('material_id', $item['material_id'])->first();
                    $target = $detail ? $detail->target_qty : 0;
                    
                    $pastReceived = 0;
                    foreach ($sppm->logs as $oldLog) {
                        $st = $oldLog->stocks->where('material_id', $item['material_id'])->first();
                        $pastReceived += $st ? $st->qty_received : 0;
                    }

                    if (($pastReceived + ($item['qty_received'] ?? 0)) < $target) {
                        $isAllCompleted = false;
                    }
                }

                $sppm->update(['status' => $isAllCompleted ? 'completed' : 'partial']);
            });

            return redirect()->route('inbound.index')->with('success', 'Penerimaan fisik Tahap Baru berhasil ditambahkan.');
        }

        $request->validate([
            'sppm_no'   => 'required|string|max:255|unique:in_sppms,sppm_no,' . $sppm->id,
            'sppm_date' => 'required|date',
            'items'     => 'required|array',
        ]);

        DB::transaction(function () use ($request, $sppm) {
            $sppm->update([
                'sppm_no'   => $request->sppm_no,
                'sppm_date' => $request->sppm_date,
                'notes'     => $request->notes_manifes
            ]);

            foreach ($request->items as $item) {
                if (isset($item['target_qty'])) {
                    InDetail::where('in_sppm_id', $sppm->id)
                        ->where('material_id', $item['material_id'])
                        ->update([
                            'target_qty'        => $item['target_qty'],
                            'qty_huruf'         => $item['qty_huruf'] ?? null,
                            'harga_satuan'      => $item['harga_satuan'] ?? 0,
                            'harga_total'       => $item['harga_total'] ?? 0,
                            'sppm_serial_start' => $item['sppm_serial_start'] ?? null,
                            'sppm_serial_end'   => $item['sppm_serial_end'] ?? null,
                        ]);

                    $firstLog = $sppm->logs()->where('batch_number', 1)->first();
                    if ($firstLog) {
                        InStock::updateOrCreate(
                            ['in_log_id' => $firstLog->id, 'material_id' => $item['material_id']],
                            ['qty_received' => $item['target_qty']]
                        );
                    }
                }
            }
        });

        return redirect()->route('inbound.index')->with('success', 'Manifes dokumen SPPM berhasil diperbarui.');
    }

    public function getMaterialsByCategory($category_id)
    {
        $materials = Material::with(['children' => function($q) {
                $q->orderBy('nomor_urut', 'asc');
            }])
            ->where('material_category_id', $category_id)
            ->whereNull('parent_id')
            ->orderBy('nomor_urut', 'asc')
            ->get();

        return response()->json($materials);
    }

    public function destroy($id)
    {
        $sppm = InSppm::findOrFail($id);
        $sppm->delete();
        return redirect()->route('inbound.index')->with('success', 'Data SPPM berhasil dihapus.');
    }
}
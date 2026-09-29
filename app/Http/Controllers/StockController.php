<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\InStock;
use App\Models\OutStock;
use App\Models\InDetail;
use App\Models\Material;
use App\Models\Warehouse;
use App\Models\MaterialCategory;
use App\Models\ReportAdjustment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category_filter = $request->input('category_id');

        $categories = MaterialCategory::with(['materials' => function($q) use ($search) {
            $q->whereNull('parent_id')
              ->when($search, function($query) use ($search) {
                  $stockSearchQuery = function($sub) use ($search) {
                      $cleanNum = preg_replace('/[^0-9]/', '', $search);
                      $cleanNum = $cleanNum !== '' ? (int)$cleanNum : null;
                      $prefixStr = trim(preg_replace('/[0-9.\-]/', '', $search));

                      $sub->select('in_stocks.material_id')
                          ->from('in_stocks')
                          ->join('in_logs', 'in_stocks.in_log_id', '=', 'in_logs.id')
                          ->join('in_sppms', 'in_logs.in_sppm_id', '=', 'in_sppms.id')
                          ->where('in_sppms.sppm_no', 'like', "%{$search}%")
                          ->orWhere('in_stocks.serial_prefix', 'like', "%{$search}%");

                      if ($cleanNum !== null) {
                          $sub->orWhere(function($q) use ($cleanNum, $prefixStr) {
                              $q->where('in_stocks.serial_start', '<=', $cleanNum)
                                ->where('in_stocks.serial_end', '>=', $cleanNum);
                              
                              if (!empty($prefixStr)) {
                                  $q->where('in_stocks.serial_prefix', 'like', "%{$prefixStr}%");
                              }
                          });
                      }
                  };

                  $query->where(function($q2) use ($search, $stockSearchQuery) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('code', 'like', "%{$search}%")
                         ->orWhereIn('id', $stockSearchQuery)
                         ->orWhereHas('children', function($q3) use ($search, $stockSearchQuery) {
                             $q3->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhereIn('id', $stockSearchQuery);
                         });
                  });
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

        // 1. Ambil Total Inbound & Outbound dari detail dokumen
        $inTotals = DB::table('in_details')
            ->selectRaw('material_id, SUM(target_qty) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->toArray();
            
        $outTotals = DB::table('out_details')
            ->selectRaw('material_id, SUM(target_qty) as total')
            ->groupBy('material_id')
            ->pluck('total', 'material_id')
            ->toArray();

        // 2. Kalkulasi Data Report Adjustments (Injeksi Penyesuaian / Sisa Awal)
        $adjustments = DB::table('report_adjustments')->get();
        $materialsList = Material::all();
        
        $adjTotals = []; 
        foreach ($adjustments as $adj) {
            $qty = (int) $adj->qty_adjustment;
            $net = ($adj->transaction_type === 'out') ? -$qty : $qty;

            if (str_starts_with($adj->bucket_key, 'sbst_')) {
                $matId = (int) str_replace('sbst_', '', $adj->bucket_key);
                $adjTotals[$matId] = ($adjTotals[$matId] ?? 0) + $net;
            } else {
                $parts = explode('_', $adj->bucket_key);
                $r = array_pop($parts); // R2 atau R4
                $tnkbType = implode('_', $parts); // tnkb_non_ev, dll

                // Mapping presisi spesifik ke material ismain = 1 yang sesuai dengan tipe dan R-nya (R2/R4)
                $targetMat = $materialsList->first(function($mat) use ($r, $tnkbType) {
                    if (!$mat->tnkb_rpt || $mat->tnkb_rpt <= 0) return false;
                    if ($mat->tnkb_r !== $r) return false;
                    if ($mat->ismain != 1) return false;

                    $matType = '';
                    if ($mat->tnkb_rpt == 2) $matType = 'tckb';
                    elseif ($mat->tnkb_rpt == 1 && $mat->tnkb_ev == 1) $matType = 'tnkb_ev';
                    elseif ($mat->tnkb_rpt == 1 && $mat->tnkb_ev == 0) $matType = 'tnkb_non_ev';

                    return $matType === $tnkbType;
                });

                if ($targetMat) {
                    $adjTotals[$targetMat->id] = ($adjTotals[$targetMat->id] ?? 0) + $net;
                }
            }
        }

        // 3. Kalkulasi Final per Material ID
        $stockTotals = [];
        foreach ($materialsList as $mat) {
            $in = $inTotals[$mat->id] ?? 0;
            $out = $outTotals[$mat->id] ?? 0;
            $adj = $adjTotals[$mat->id] ?? 0;
            
            $stockTotals[$mat->id] = $in - $out + $adj; 
        }

        $allCategories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        return view('stocks.stock_index', compact('categories', 'stockTotals', 'search', 'category_filter', 'allCategories'));
    }

    public function show(Request $request, $id)
    {
        $material = Material::with('category')->findOrFail($id);
        
        $search = $request->input('search');
        $sortBy = $request->input('sort', 'tgl_masuk'); 
        $sortOrder = $request->input('order', 'desc'); 

        $inStocks = InStock::with(['log.sppm.warehouse'])
                           ->where('material_id', $id)
                           ->orderBy('created_at', 'asc')
                           ->get();
        
        $inDetails = InDetail::where('material_id', $id)->get()->keyBy('in_sppm_id');
        $outStocks = OutStock::where('material_id', $id)->get();

        $adjustments = DB::table('report_adjustments')->get();
        $materialsList = Material::all();
        $netAdj = 0;

        foreach ($adjustments as $adj) {
            $qty = (int) $adj->qty_adjustment;
            $net = ($adj->transaction_type === 'out') ? -$qty : $qty;

            if (str_starts_with($adj->bucket_key, 'sbst_')) {
                $matId = (int) str_replace('sbst_', '', $adj->bucket_key);
                if ($matId == $material->id) $netAdj += $net;
            } else {
                $parts = explode('_', $adj->bucket_key);
                $r = array_pop($parts);
                $tnkbType = implode('_', $parts);
                
                if ($material->tnkb_rpt > 0 && $material->tnkb_r === $r && $material->ismain == 1) {
                    $targetMat = $materialsList->first(function($m) use ($r, $tnkbType) {
                        if (!$m->tnkb_rpt || $m->tnkb_rpt <= 0) return false;
                        if ($m->tnkb_r !== $r) return false;
                        if ($m->ismain != 1) return false;
                        
                        $mType = '';
                        if ($m->tnkb_rpt == 2) $mType = 'tckb';
                        elseif ($m->tnkb_rpt == 1 && $m->tnkb_ev == 1) $mType = 'tnkb_ev';
                        elseif ($m->tnkb_rpt == 1 && $m->tnkb_ev == 0) $mType = 'tnkb_non_ev';
                        
                        return $mType === $tnkbType;
                    });

                    if ($targetMat && $targetMat->id == $material->id) {
                        $netAdj += $net;
                    }
                }
            }
        }

        $normalStocks = collect();
        $mergedMinusRanges = [];
        $totalMinusQty = 0;

        if ($material->pakai_seri == 1) {
            $prefixes = $inStocks->pluck('serial_prefix')->merge($outStocks->pluck('prefix'))->unique()->filter();
            
            foreach ($prefixes as $prefix) {
                $inForPrefix = $inStocks->where('serial_prefix', $prefix);
                $outForPrefix = $outStocks->where('prefix', $prefix)->map(function($o) {
                    return ['start' => $o->seri_awal, 'end' => $o->seri_akhir];
                })->toArray();
                
                foreach ($inForPrefix as $in) {
                    $availRanges = [['start' => $in->serial_start, 'end' => $in->serial_end]];
                    
                    foreach ($outForPrefix as $out) {
                        $availRanges = $this->subtractRanges($availRanges, $out);
                    }
                    
                    $price = $inDetails->get($in->log->sppm_id)->harga_satuan ?? 0;
                    
                    foreach ($availRanges as $r) {
                        $normalStocks->push((object)[
                            'id'             => $in->id,
                            'no_surat_masuk' => $in->log->sppm->sppm_no ?? 'UNKNOWN',
                            'tgl_masuk'      => $in->log->receive_date ?? $in->created_at,
                            'warehouse'      => $in->log->sppm->warehouse,
                            'warehouse_id'   => $in->log->sppm->warehouse_id ?? 1,
                            'prefix'         => $prefix,
                            'seri_awal'      => $r['start'],
                            'seri_akhir'     => $r['end'],
                            'qty'            => $r['end'] - $r['start'] + 1,
                            'harga_satuan'   => $price,
                            'keterangan'     => 'Sisa Inbound ' . ($in->log->sppm->sppm_no ?? ''),
                        ]);
                    }
                }
                
                $hutangRanges = $outForPrefix;
                foreach ($inForPrefix as $in) {
                    $hutangRanges = $this->subtractRanges($hutangRanges, ['start' => $in->serial_start, 'end' => $in->serial_end]);
                }
                
                foreach ($hutangRanges as $h) {
                    $qty = $h['end'] - $h['start'] + 1;
                    $totalMinusQty -= $qty;
                    $mergedMinusRanges[] = [
                        'prefix' => $prefix,
                        'awal'   => $h['start'],
                        'akhir'  => $h['end']
                    ];
                }
            }

            if ($netAdj != 0) {
                $normalStocks->push((object)[
                    'id'             => 'adj',
                    'no_surat_masuk' => 'PENYESUAIAN LAPORAN (CARRY OVER)',
                    'tgl_masuk'      => date('Y-m-d'),
                    'warehouse'      => (object)['name' => 'SISTEM INJEKSI'],
                    'warehouse_id'   => 999,
                    'prefix'         => 'ADJ',
                    'seri_awal'      => null,
                    'seri_akhir'     => null,
                    'qty'            => $netAdj,
                    'harga_satuan'   => 0,
                    'keterangan'     => 'Injeksi Sinkronisasi Report Adjustment',
                ]);
            }

        } else {
            $inTotalBulk = DB::table('in_details')->where('material_id', $id)->sum('target_qty');
            $outTotalBulk = DB::table('out_details')->where('material_id', $id)->sum('target_qty');
            
            $available = $inTotalBulk - $outTotalBulk + $netAdj;
            
            if ($available > 0) {
                $firstIn = $inStocks->last(); 
                $price = $firstIn ? ($inDetails->get($firstIn->log->sppm_id)->harga_satuan ?? 0) : 0;

                $normalStocks->push((object)[
                    'id'             => $firstIn->id ?? 1,
                    'no_surat_masuk' => 'AKUMULASI GLOBAL',
                    'tgl_masuk'      => date('Y-m-d'),
                    'warehouse'      => (object)['name' => $firstIn->log->sppm->warehouse->name ?? 'GUDANG UTAMA'],
                    'warehouse_id'   => $firstIn->log->sppm->warehouse_id ?? 1,
                    'prefix'         => null,
                    'seri_awal'      => null,
                    'seri_akhir'     => null,
                    'qty'            => $available,
                    'harga_satuan'   => $price,
                    'keterangan'     => 'Akumulasi Tersedia + Injeksi Adjustment',
                ]);
            } elseif ($available < 0) {
                $totalMinusQty = $available;
            }
        }

        if (!empty($search)) {
            $cleanNum = preg_replace('/[^0-9]/', '', $search);
            $cleanNum = $cleanNum !== '' ? (int)$cleanNum : null;
            $prefixStr = trim(preg_replace('/[0-9.\-]/', '', $search));

            $normalStocks = $normalStocks->filter(function($item) use ($search, $cleanNum, $prefixStr) {
                if (stripos($item->no_surat_masuk, $search) !== false) return true;
                if (stripos($item->prefix, $search) !== false) return true;
                if ($cleanNum !== null && $item->seri_awal <= $cleanNum && $item->seri_akhir >= $cleanNum) {
                    if (empty($prefixStr) || stripos($item->prefix, $prefixStr) !== false) return true;
                }
                return false;
            })->values();
        }

        $allowedSorts = ['no_surat_masuk', 'tgl_masuk', 'warehouse_id', 'seri_awal', 'qty'];
        if (in_array($sortBy, $allowedSorts)) {
            $normalStocks = $sortOrder == 'asc' ? $normalStocks->sortBy($sortBy) : $normalStocks->sortByDesc($sortBy);
        } else {
            $normalStocks = $normalStocks->sortByDesc('tgl_masuk');
        }

        $totalStock = $normalStocks->sum('qty');
        
        if ($material->pakai_seri == 1 && $normalStocks->isNotEmpty()) {
            $normalStocks = $normalStocks->groupBy(function($item) {
                $prefix = $item->prefix ?: 'TANPA PREFIX';
                $tahun = date('Y', strtotime($item->tgl_masuk));
                return $prefix . ' - TAHUN ' . $tahun;
            });
        } elseif ($material->pakai_seri == 0 && $normalStocks->isNotEmpty()) {
            $normalStocks = $normalStocks->groupBy('warehouse_id');
        }

        return view('stocks.stock_detail', compact(
            'material', 'normalStocks', 'totalStock', 'totalMinusQty', 'mergedMinusRanges', 'sortBy', 'sortOrder'
        ));
    }

    private function subtractRanges($ranges, $subtract)
    {
        $result = [];
        foreach ($ranges as $r) {
            if ($subtract['end'] < $r['start'] || $subtract['start'] > $r['end']) {
                $result[] = $r;
            } else if ($subtract['start'] <= $r['start'] && $subtract['end'] >= $r['end']) {
                continue;
            } else if ($subtract['start'] > $r['start'] && $subtract['end'] < $r['end']) {
                $result[] = ['start' => $r['start'], 'end' => $subtract['start'] - 1];
                $result[] = ['start' => $subtract['end'] + 1, 'end' => $r['end']];
            } else if ($subtract['start'] <= $r['start'] && $subtract['end'] >= $r['start']) {
                $result[] = ['start' => $subtract['end'] + 1, 'end' => $r['end']];
            } else if ($subtract['start'] <= $r['end'] && $subtract['end'] >= $r['end']) {
                $result[] = ['start' => $r['start'], 'end' => $subtract['start'] - 1];
            }
        }
        return $result;
    }

    public function bulkUpdatePrice(Request $request, $material_id)
    {
        if (!auth()->user()->can('Setting Menu')) {
            abort(403, 'Anda tidak memiliki otorisasi untuk mengubah harga satuan.');
        }

        $request->validate([
            'prices'      => 'required|array',
            'prices.seri' => 'nullable|array',
            'prices.bulk' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            if ($request->has('prices.seri')) {
                foreach ($request->input('prices.seri') as $inStockId => $price) {
                    $inStock = InStock::find($inStockId);
                    if ($inStock && $inStock->log) {
                        $inDetail = InDetail::where('in_sppm_id', $inStock->log->in_sppm_id)
                                            ->where('material_id', $material_id)
                                            ->first();
                        
                        if ($inDetail && $inDetail->harga_satuan != $price) {
                            $inDetail->harga_satuan = $price;
                            $inDetail->harga_total  = $inDetail->target_qty * $price;
                            $inDetail->save();
                        }
                    }
                }
            }

            if ($request->has('prices.bulk')) {
                foreach ($request->input('prices.bulk') as $warehouseId => $price) {
                    $inDetails = InDetail::where('material_id', $material_id)->get();
                    foreach ($inDetails as $inDetail) {
                        if ($inDetail->harga_satuan != $price) {
                            $inDetail->harga_satuan = $price;
                            $inDetail->harga_total  = $inDetail->target_qty * $price;
                            $inDetail->save();
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Harga Satuan berhasil diperbarui ke dokumen asalnya.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('error', 'Sistem Buku Besar aktif. Penyesuaian stok harus dilakukan melalui menu Inbound / Outbound.');
    }

    public function update(Request $request, $id)
    {
        return redirect()->back()->with('error', 'Sistem Buku Besar aktif. Penyesuaian stok harus dilakukan melalui menu Inbound / Outbound.');
    }

    public function destroy($id)
    {
        return redirect()->back()->with('error', 'Sistem Buku Besar aktif. Penyesuaian stok harus dilakukan melalui menu Inbound / Outbound.');
    }
}
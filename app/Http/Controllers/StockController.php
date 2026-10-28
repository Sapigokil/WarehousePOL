<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\InStock;
use App\Models\OutStock;
use App\Models\InDetail;
use App\Models\OutDetail;
use App\Models\OutLog;
use App\Models\Material;
use App\Models\Warehouse;
use App\Models\MaterialCategory;
use App\Models\StockBuku; // Model baru ditambahkan
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

        // Menggunakan dokumen In_Detail & Out_Detail sesuai kesepakatan Index
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

        $materialsList = Material::with('children')->get();

        $stockTotals = [];
        foreach ($materialsList as $mat) {
            $in = $inTotals[$mat->id] ?? 0;
            $out = $outTotals[$mat->id] ?? 0;
            
            $stockTotals[$mat->id] = $in - $out; 
        }

        $allCategories = MaterialCategory::orderBy('nomor_urut', 'asc')->get();

        return view('stocks.stock_index', compact('categories', 'stockTotals', 'search', 'category_filter', 'allCategories'));
    }

    /**
     * TAMPILAN SHOW BERDASARKAN TABEL STOCK_BUKU
     */
    public function show(Request $request, $id)
    {
        $this->executeSyncBuku($id);

        // Mutlak hanya ambil material yang pakai_seri = 1. Jika 0, akan otomatis ditolak (404 Not Found).
        $material = Material::with('category')->where('pakai_seri', 1)->findOrFail($id);
        
        $search = $request->input('search');

        // Tarik data dari Buku Alokasi (StockBuku)
        $bukuData = StockBuku::with([
            'inSppm.warehouse',
            'inSppm.details' => function($q) use ($id) {
                $q->where('material_id', $id);
            },
            'outSppm'
        ])
        ->where('material_id', $id)
        ->when($search, function($query) use ($search) {
            $cleanNum = preg_replace('/[^0-9]/', '', $search);
            $query->where(function($q) use ($search, $cleanNum) {
                $q->whereHas('inSppm', function($sub) use ($search) {
                    $sub->where('sppm_no', 'like', "%{$search}%");
                })
                ->orWhereHas('outSppm', function($sub) use ($search) {
                    $sub->where('sppm_no', 'like', "%{$search}%");
                })
                ->orWhere('prefix', 'like', "%{$search}%");
                
                if ($cleanNum !== '') {
                    $q->orWhere(function($sub2) use ($cleanNum) {
                        $sub2->where('seri_awal', '<=', (int)$cleanNum)
                             ->where('seri_akhir', '>=', (int)$cleanNum);
                    });
                }
            });
        })
        ->orderBy('prefix', 'asc')
        ->orderBy('seri_awal', 'asc')
        ->get();

        $groupedInbounds = [];
        $unserializedIns = [];
        $orphanOuts      = [];
        
        $sumAvailable      = 0;
        $sumUnserializedIn = 0;
        $sumOrphanOut      = 0;

        foreach ($bukuData as $buku) {
            if ($buku->tipe_baris === 'UNSERIALIZED_IN') {
                $unserializedIns[] = $buku;
                $sumUnserializedIn += $buku->qty;
            } elseif ($buku->tipe_baris === 'ORPHAN_OUT') {
                $orphanOuts[] = $buku;
                $sumOrphanOut += $buku->qty;
            } else {
                $sppmId = $buku->in_sppm_id ?? 0;
                
                if (!isset($groupedInbounds[$sppmId])) {
                    // AMBIL DATA SERI INBOUND LANGSUNG DARI TABEL IN_STOCKS BERDASARKAN SPPM & MATERIAL
                    $inStockInfo = InStock::where('material_id', $id)
                        ->whereHas('log', function($q) use ($sppmId) {
                            $q->where('in_sppm_id', $sppmId);
                        })
                        ->first();

                    $groupedInbounds[$sppmId] = [
                        'sppm'            => $buku->inSppm,
                        'prefix'          => $inStockInfo->serial_prefix ?? null,
                        'seri_awal'       => $inStockInfo->serial_start ?? null,
                        'seri_akhir'      => $inStockInfo->serial_end ?? null,
                        'total_in'        => 0,
                        'total_used'      => 0,
                        'total_available' => 0,
                        'items_available' => [],
                        'items_used'      => []
                    ];
                }
                
                if ($buku->tipe_baris === 'USED') {
                    $groupedInbounds[$sppmId]['items_used'][] = $buku;
                    $groupedInbounds[$sppmId]['total_used'] += $buku->qty;
                    $groupedInbounds[$sppmId]['total_in'] += $buku->qty;
                } elseif ($buku->tipe_baris === 'AVAILABLE') {
                    $groupedInbounds[$sppmId]['items_available'][] = $buku;
                    $groupedInbounds[$sppmId]['total_available'] += $buku->qty;
                    $groupedInbounds[$sppmId]['total_in'] += $buku->qty;
                    
                    $sumAvailable += $buku->qty;
                }
            }
        }

        $totalTersedia = $sumAvailable + $sumUnserializedIn - $sumOrphanOut;
        $needsSync = $bukuData->isEmpty();

        return view('stocks.stock_detail', compact(
            'material', 'groupedInbounds', 'unserializedIns', 'orphanOuts', 'totalTersedia', 'needsSync', 'search'
        ));
    }

    /**
     * FUNGSI SYNC / REBUILD BUKU ALOKASI
     */
    public function syncBuku(Request $request, $id)
    {
        // FILTER: Pastikan fungsi sync mutlak hanya beroperasi pada material berseri (pakai_seri = 1)
        $material = Material::where('pakai_seri', 1)->findOrFail($id);
        
        $inStocks = InStock::with('log.sppm.warehouse')->where('material_id', $id)->get();
        $outStocks = OutStock::with(['outLog.outSppm.details'])->where('material_id', $id)->get();

        DB::beginTransaction();
        try {
            // Hapus mutlak semua buku pada material_id aktif ini sebelum dibuild ulang
            StockBuku::where('material_id', $id)->delete();

            foreach ($outStocks as $out) {
                $outSppmId = $out->outLog->out_sppm_id ?? null;

                if (!$out->seri_awal || !$out->seri_akhir) {
                    // StockBuku::create([
                    //     'material_id'  => $id, // Mutlak menggunakan $id material aktif
                    //     'out_sppm_id'  => $outSppmId,
                    //     'out_stock_id' => $out->id,
                    //     'tipe_baris'   => 'ORPHAN_OUT',
                    //     'prefix'       => $out->prefix,
                    //     'seri_awal'    => $out->seri_awal,
                    //     'seri_akhir'   => $out->seri_akhir,
                    //     'qty'          => $out->qty_keluar,
                    //     'keterangan'   => 'Pengeluaran tanpa nomor seri.'
                    // ]);
                    continue;
                }

                $unfulfilledOut = [['start' => $out->seri_awal, 'end' => $out->seri_akhir]];
                $prefix = $out->prefix;

                foreach ($inStocks as $in) {
                    if ($in->serial_prefix !== $prefix) continue;
                    if (!$in->serial_start) continue;

                    $inSppmId = $in->log->in_sppm_id ?? null;

                    $newUnfulfilledOut = [];
                    foreach ($unfulfilledOut as $uo) {
                        $overlapStart = max($uo['start'], $in->serial_start);
                        $overlapEnd = min($uo['end'], $in->serial_end);

                        if ($overlapStart <= $overlapEnd) {
                            $qtyOverlap = ($overlapEnd - $overlapStart) + 1;
                            
                            StockBuku::create([
                                'material_id'  => $id, // Mutlak menggunakan $id material aktif
                                'in_sppm_id'   => $inSppmId,
                                'in_stock_id'  => $in->id,
                                'out_sppm_id'  => $outSppmId,
                                'out_stock_id' => $out->id,
                                'tipe_baris'   => 'USED',
                                'prefix'       => $prefix,
                                'seri_awal'    => $overlapStart,
                                'seri_akhir'   => $overlapEnd,
                                'qty'          => $qtyOverlap
                            ]);

                            if ($uo['start'] < $overlapStart) {
                                $newUnfulfilledOut[] = ['start' => $uo['start'], 'end' => $overlapStart - 1];
                            }
                            if ($uo['end'] > $overlapEnd) {
                                $newUnfulfilledOut[] = ['start' => $overlapEnd + 1, 'end' => $uo['end']];
                            }
                        } else {
                            $newUnfulfilledOut[] = $uo;
                        }
                    }
                    $unfulfilledOut = $newUnfulfilledOut;
                    if (empty($unfulfilledOut)) break;
                }

                foreach ($unfulfilledOut as $uo) {
                    $qtySisa = $uo['end'] - $uo['start'] + 1;
                    StockBuku::create([
                        'material_id'  => $id, // Mutlak menggunakan $id material aktif
                        'out_sppm_id'  => $outSppmId,
                        'out_stock_id' => $out->id,
                        'tipe_baris'   => 'ORPHAN_OUT',
                        'prefix'       => $prefix,
                        'seri_awal'    => $uo['start'],
                        'seri_akhir'   => $uo['end'],
                        'qty'          => $qtySisa,
                        'keterangan'   => 'SPPM Inbound (Asal-usul) tidak ditemukan.'
                    ]);
                }
            }

            foreach ($inStocks as $in) {
                $inSppmId = $in->log->in_sppm_id ?? null;

                if (!$in->serial_start || !$in->serial_end) {
                    StockBuku::create([
                        'material_id'  => $id, // Mutlak menggunakan $id material aktif
                        'in_sppm_id'   => $inSppmId,
                        'in_stock_id'  => $in->id,
                        'tipe_baris'   => 'UNSERIALIZED_IN',
                        'prefix'       => $in->serial_prefix,
                        'qty'          => $in->qty_received,
                        'keterangan'   => 'Inbound tanpa rincian nomor seri.'
                    ]);
                    continue;
                }

                $usedRanges = StockBuku::where('in_stock_id', $in->id)
                    ->where('tipe_baris', 'USED')
                    ->get()
                    ->map(function($u) {
                        return ['start' => $u->seri_awal, 'end' => $u->seri_akhir];
                    })->toArray();

                $availRanges = [['start' => $in->serial_start, 'end' => $in->serial_end]];
                
                foreach ($usedRanges as $used) {
                    $availRanges = $this->subtractRanges($availRanges, $used);
                }

                foreach ($availRanges as $ar) {
                    $qtyAvail = $ar['end'] - $ar['start'] + 1;
                    StockBuku::create([
                        'material_id'  => $id, // Mutlak menggunakan $id material aktif
                        'in_sppm_id'   => $inSppmId,
                        'in_stock_id'  => $in->id,
                        'tipe_baris'   => 'AVAILABLE',
                        'prefix'       => $in->serial_prefix,
                        'seri_awal'    => $ar['start'],
                        'seri_akhir'   => $ar['end'],
                        'qty'          => $qtyAvail
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Buku Alokasi berhasil disinkronisasi ulang secara sempurna.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyinkronisasi: ' . $e->getMessage());
        }
    }

    /**
     * Helper Privat untuk Sinkronisasi StockBuku per Material
     */
    private function executeSyncBuku($materialId)
    {
        $inStocks = InStock::with('log.sppm.warehouse')->where('material_id', $materialId)->get();
        $outStocks = OutStock::with(['outLog.outSppm.details'])->where('material_id', $materialId)->get();

        DB::beginTransaction();
        try {
            StockBuku::where('material_id', $materialId)->delete();

            foreach ($outStocks as $out) {
                $outSppmId = $out->outLog->out_sppm_id ?? null;

                if (!$out->seri_awal || !$out->seri_akhir) {
                    continue;
                }

                $unfulfilledOut = [['start' => $out->seri_awal, 'end' => $out->seri_akhir]];
                $prefix = $out->prefix;

                foreach ($inStocks as $in) {
                    if ($in->serial_prefix !== $prefix) continue;
                    if (!$in->serial_start) continue;

                    $inSppmId = $in->log->in_sppm_id ?? null;

                    $newUnfulfilledOut = [];
                    foreach ($unfulfilledOut as $uo) {
                        $overlapStart = max($uo['start'], $in->serial_start);
                        $overlapEnd = min($uo['end'], $in->serial_end);

                        if ($overlapStart <= $overlapEnd) {
                            $qtyOverlap = ($overlapEnd - $overlapStart) + 1;
                            
                            StockBuku::create([
                                'material_id'  => $materialId,
                                'in_sppm_id'   => $inSppmId,
                                'in_stock_id'  => $in->id,
                                'out_sppm_id'  => $outSppmId,
                                'out_stock_id' => $out->id,
                                'tipe_baris'   => 'USED',
                                'prefix'       => $prefix,
                                'seri_awal'    => $overlapStart,
                                'seri_akhir'   => $overlapEnd,
                                'qty'          => $qtyOverlap
                            ]);

                            if ($uo['start'] < $overlapStart) {
                                $newUnfulfilledOut[] = ['start' => $uo['start'], 'end' => $overlapStart - 1];
                            }
                            if ($uo['end'] > $overlapEnd) {
                                $newUnfulfilledOut[] = ['start' => $overlapEnd + 1, 'end' => $uo['end']];
                            }
                        } else {
                            $newUnfulfilledOut[] = $uo;
                        }
                    }
                    $unfulfilledOut = $newUnfulfilledOut;
                    if (empty($unfulfilledOut)) break;
                }

                foreach ($unfulfilledOut as $uo) {
                    $qtySisa = $uo['end'] - $uo['start'] + 1;
                    StockBuku::create([
                        'material_id'  => $materialId,
                        'out_sppm_id'  => $outSppmId,
                        'out_stock_id' => $out->id,
                        'tipe_baris'   => 'ORPHAN_OUT',
                        'prefix'       => $prefix,
                        'seri_awal'    => $uo['start'],
                        'seri_akhir'   => $uo['end'],
                        'qty'          => $qtySisa,
                        'keterangan'   => 'SPPM Inbound (Asal-usul) tidak ditemukan.'
                    ]);
                }
            }

            foreach ($inStocks as $in) {
                $inSppmId = $in->log->in_sppm_id ?? null;

                if (!$in->serial_start || !$in->serial_end) {
                    StockBuku::create([
                        'material_id'  => $materialId,
                        'in_sppm_id'   => $inSppmId,
                        'in_stock_id'  => $in->id,
                        'tipe_baris'   => 'UNSERIALIZED_IN',
                        'prefix'       => $in->serial_prefix,
                        'qty'          => $in->qty_received,
                        'keterangan'   => 'Inbound tanpa rincian nomor seri.'
                    ]);
                    continue;
                }

                $usedRanges = StockBuku::where('in_stock_id', $in->id)
                    ->where('tipe_baris', 'USED')
                    ->get()
                    ->map(function($u) {
                        return ['start' => $u->seri_awal, 'end' => $u->seri_akhir];
                    })->toArray();

                $availRanges = [['start' => $in->serial_start, 'end' => $in->serial_end]];
                
                foreach ($usedRanges as $used) {
                    $availRanges = $this->subtractRanges($availRanges, $used);
                }

                foreach ($availRanges as $ar) {
                    $qtyAvail = $ar['end'] - $ar['start'] + 1;
                    StockBuku::create([
                        'material_id'  => $materialId,
                        'in_sppm_id'   => $inSppmId,
                        'in_stock_id'  => $ar['start'] ? $in->id : null,
                        'tipe_baris'   => 'AVAILABLE',
                        'prefix'       => $in->serial_prefix,
                        'seri_awal'    => $ar['start'],
                        'seri_akhir'   => $ar['end'],
                        'qty'          => $qtyAvail
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
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

    public function fixOutStockMaterialIdBySequence()
{
    DB::beginTransaction();
    try {
        $updatedCount = 0;
        
        $outLogs = OutLog::has('outStocks')->with(['outStocks', 'outSppm.details'])->get();

        foreach ($outLogs as $log) {
            $details = $log->outSppm?->details;
            $stocks = $log->outStocks;

            if (!$details || $details->isEmpty() || $stocks->isEmpty()) {
                continue;
            }

            foreach ($stocks as $index => $stock) {
                if (isset($details[$index])) {
                    $correctMaterialId = $details[$index]->material_id;

                    if ($stock->material_id !== $correctMaterialId) {
                        $stock->material_id = $correctMaterialId;
                        $stock->save();
                        $updatedCount++;
                    }
                }
            }
        }

        DB::commit();
        return redirect()->back()->with('success', "Berhasil merapikan material_id pada $updatedCount data out_stock berdasarkan urutan waktu import.");

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
    }
}
}
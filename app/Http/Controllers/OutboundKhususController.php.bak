<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialCategory;
use App\Models\Material;
use App\Models\OutSppm;
use App\Models\OutDetail;
use App\Models\OutLog;
use App\Models\OutStock;
use App\Models\Stock;
use App\Models\Destination;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OutboundKhususController extends Controller
{
    // --- FUNGSI DOWNLOAD TEMPLATE EXCEL OUTBOUND KHUSUS (MENU A) ---
    public function downloadTemplate(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:material_categories,id']);
        
        $categoryId = $request->input('category_id');
        $category = MaterialCategory::findOrFail($categoryId);

        $topLevelMaterials = Material::with(['children' => function($q) {
                $q->orderBy('nomor_urut', 'asc');
            }])
            ->where('material_category_id', $categoryId)
            ->whereNull('parent_id')
            ->orderBy('nomor_urut', 'asc')
            ->get();

        if ($topLevelMaterials->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak bisa mengunduh template: Kategori ini belum memiliki data Master Barang.');
        }

        $flatMaterials = collect();
        $hasChildren = false;
        
        foreach ($topLevelMaterials as $parent) {
            if ($parent->children->count() > 0) {
                $hasChildren = true;
                foreach ($parent->children as $child) {
                    $flatMaterials->push($child);
                }
            } else {
                $flatMaterials->push($parent);
            }
        }

        $hasSeri = $flatMaterials->where('pakai_seri', 1)->count() > 0;
        $fileName = 'Template_Outbound_' . str_replace(' ', '_', strtoupper($category->name)) . '_' . date('Ymd') . '.xls';

        $headers = [
            "Content-type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($topLevelMaterials, $flatMaterials, $category, $hasChildren, $hasSeri) {
            echo '<table border="1" style="font-family: Arial; font-size: 10px; text-align: center;">';
            
            $headerRows = $hasChildren ? 3 : 2;

            // --- BARIS 1: HEADER UTAMA ---
            echo '<tr style="font-weight: bold; background-color: #f8f9fa;">';
            echo '<th rowspan="'.$headerRows.'" style="width: 40px;">NO URUT</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 100px;">KODE POLRES</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 200px;">KESATUAN (TUJUAN)</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 100px;">NO SPPM</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 100px;">BLN SPPM</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 120px;">TGL SPPM<br>(YYYY-MM-DD)</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">NAMA BAMAT</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">PANGKAT/ NRP</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 150px;">JABATAN</th>';
            echo '<th rowspan="'.$headerRows.'" style="width: 100px;">HURUF (PREFIX)</th>';
            echo '<th colspan="'.$flatMaterials->count().'" style="background-color: #fecdd3;">BARANG KELUAR: '.strtoupper($category->name).' (ISI QTY)</th>';
            echo '<th colspan="40" style="background-color: #dbeafe;">RENTANG NOMOR SERI (WAJIB JIKA BERSERI)</th>';
            if ($hasSeri) {
                echo '<th colspan="2" style="background-color: #fce7f3;">NILAI BARANG BERSERI</th>';
            }
            echo '</tr>';

            // --- BARIS 2: SUB-HEADER ---
            echo '<tr style="font-weight: bold; text-align: center;">';
            foreach ($topLevelMaterials as $mat) {
                if ($mat->children->count() > 0) {
                    echo '<th colspan="'.$mat->children->count().'" style="background-color: #ffe4e6;">'.strtoupper($mat->name).'</th>';
                } else {
                    $rs = $hasChildren ? 2 : 1;
                    if ($rs > 1) {
                        echo '<th rowspan="'.$rs.'" style="background-color: #ffe4e6;">'.strtoupper($mat->name).'</th>';
                    } else {
                        echo '<th style="background-color: #ffe4e6;">'.strtoupper($mat->name).'</th>';
                    }
                }
            }
            for ($i = 1; $i <= 20; $i++) {
                $rs = $hasChildren ? 2 : 1;
                if ($rs > 1) {
                    echo '<th rowspan="'.$rs.'" style="background-color: #bfdbfe; width: 120px;">SERI AWAL '.$i.'</th>';
                    echo '<th rowspan="'.$rs.'" style="background-color: #bfdbfe; width: 120px;">SERI AKHIR '.$i.'</th>';
                } else {
                    echo '<th style="background-color: #bfdbfe; width: 120px;">SERI AWAL '.$i.'</th>';
                    echo '<th style="background-color: #bfdbfe; width: 120px;">SERI AKHIR '.$i.'</th>';
                }
            }
            if ($hasSeri) {
                $rs = $hasChildren ? 2 : 1;
                if ($rs > 1) {
                    echo '<th rowspan="'.$rs.'" style="background-color: #fbcfe8; width: 120px;">HARGA SATUAN</th>';
                    echo '<th rowspan="'.$rs.'" style="background-color: #fbcfe8; width: 150px;">HARGA TOTAL (JUMLAH)</th>';
                } else {
                    echo '<th style="background-color: #fbcfe8; width: 120px;">HARGA SATUAN</th>';
                    echo '<th style="background-color: #fbcfe8; width: 150px;">HARGA TOTAL (JUMLAH)</th>';
                }
            }
            echo '</tr>';

            // --- BARIS 3: CHILD MATERIAL ---
            if ($hasChildren) {
                echo '<tr style="font-weight: bold; background-color: #fff1f2;">';
                foreach ($topLevelMaterials as $mat) {
                    if ($mat->children->count() > 0) {
                        foreach ($mat->children as $child) {
                            echo '<th>'.strtoupper($child->name).'</th>';
                        }
                    }
                }
                echo '</tr>';
            }

            // --- BARIS 4: DUMMY ---
            echo '<tr>';
            echo '<td>1</td><td>1</td><td>POLRESTA BANYUMAS</td><td>933</td><td>V</td><td>2024-05-24</td>';
            echo '<td>ENDRO SUSILO</td><td>BRIPKA/ 86041391</td><td>BAMAT POLRESTA BANYUMAS</td><td>H</td>';
            foreach ($flatMaterials as $mat) { echo '<td>1000</td>'; }
            for ($i = 1; $i <= 20; $i++) {
                if ($i == 1) { echo '<td>29001</td><td>30000</td>'; } 
                else { echo '<td></td><td></td>'; }
            }
            if ($hasSeri) { echo '<td>17798</td><td>17798000</td>'; }
            echo '</tr>';
            echo '</table>';
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- FUNGSI HANDLE UPLOAD EXCEL OUTBOUND (MENU B) ---
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:material_categories,id',
            'file_excel'  => 'required|file|mimes:xlsx,xls,csv,txt'
        ], [
            'file_excel.mimes' => 'File harus berupa dokumen Excel (.xlsx, .xls) atau CSV.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $categoryId = $request->input('category_id');
        $file = $request->file('file_excel');

        $defaultWarehouse = Warehouse::first();
        $defaultWarehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1; 

        $topLevelMaterials = Material::with(['children' => function($q) {
                $q->orderBy('nomor_urut', 'asc');
            }])
            ->where('material_category_id', $categoryId)
            ->whereNull('parent_id')
            ->orderBy('nomor_urut', 'asc')
            ->get();

        if ($topLevelMaterials->isEmpty()) {
            return redirect()->back()->with('error', 'Kategori ini tidak memiliki daftar material.');
        }

        $flatMaterials = collect();
        $hasChildren = false;
        foreach ($topLevelMaterials as $parent) {
            if ($parent->children->count() > 0) {
                $hasChildren = true;
                foreach ($parent->children as $child) {
                    $flatMaterials->push($child);
                }
            } else {
                $flatMaterials->push($parent);
            }
        }

        $hasSeri = $flatMaterials->where('pakai_seri', 1)->count() > 0;
        $mCount = $flatMaterials->count();
        $headerRowsToSkip = $hasChildren ? 3 : 2;

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false); 
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sistem gagal membaca file Excel: ' . $e->getMessage());
        }

        $insertedDataCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if ($index < $headerRowsToSkip) continue; 
                
                // Fitur Auto-Skip Baris Kosong (Jika Kolom Tujuan atau No SPPM Kosong, lewati baris ini)
                if (empty($row[3]) || empty($row[2])) continue; 

                $tujuanStr   = trim($row[2] ?? '');
                $noSppmRaw   = trim($row[3] ?? '');
                $blnSppm     = trim($row[4] ?? '');
                $tglSppmStr  = trim($row[5] ?? '');
                $namaBamat   = trim($row[6] ?? '');
                $pangkatNrp  = trim($row[7] ?? '');
                $jabatan     = trim($row[8] ?? '');
                $prefixRaw   = trim($row[9] ?? '');

                if (!$tglSppmStr) continue;
                // --- LOGIKA TRANSLATE TANGGAL ---
                if (is_numeric($tglSppmStr)) {
                    // Jika Excel mengirim format Serial Number (misal: 45245)
                    $tglSppm = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tglSppmStr)->format('Y-m-d');
                } else {
                    // Kamus Bulan Indonesia ke Inggris agar terbaca oleh strtotime()
                    $bulanIndo = [
                        'Januari'  => 'January',  'Februari' => 'February', 'Pebruari' => 'February',
                        'Maret'    => 'March',    'April'    => 'April',    'Mei'      => 'May',
                        'Juni'     => 'June',     'Juli'     => 'July',     'Agustus'  => 'August',
                        'September'=> 'September','Oktober'  => 'October',  'November' => 'November',
                        'Nopember' => 'November', 'Desember' => 'December'
                    ];
                    
                    // Terjemahkan nama bulan di dalam string
                    $tglSppmEng = str_ireplace(array_keys($bulanIndo), array_values($bulanIndo), $tglSppmStr);
                    $tglSppm = date('Y-m-d', strtotime($tglSppmEng));
                }

                // Fallback jika format tetap gagal terbaca
                if (!$tglSppm || $tglSppm == '1970-01-01') {
                    $tglSppm = date('Y-m-d'); 
                }

                $tahunSppm = date('Y', strtotime($tglSppm));
                // ---------------------------------
                
                $currentSppmNo = "SPPM/{$noSppmRaw}/{$blnSppm}/{$tahunSppm}/DITLANTAS";

                $cleanPrefix = preg_replace('/[^a-zA-Z]/', '', $prefixRaw);
                $prefix = $cleanPrefix !== '' ? strtoupper($cleanPrefix) : null;

                $destination = Destination::where('name', 'like', $tujuanStr)->first();
                if (!$destination) {
                    throw new \Exception("Tujuan Pengiriman '{$tujuanStr}' pada baris SPPM '{$noSppmRaw}' tidak ditemukan persis di Master Database.");
                }

                $hargaSatuan = 0;
                if ($hasSeri) {
                    $hsRaw = $row[10 + $mCount + 40] ?? 0;
                    $hargaSatuan = (int) str_replace(['.', ','], '', $hsRaw);
                }

                // --- AKUMULASI MULTI-SERI ---
                $seriesList = [];
                $totalSeriQty = 0;
                
                for ($i = 0; $i < 20; $i++) {
                    $cAw = 10 + $mCount + ($i * 2);
                    $cAk = 10 + $mCount + ($i * 2) + 1;
                    $sAw = isset($row[$cAw]) ? preg_replace('/[^0-9]/', '', $row[$cAw]) : '';
                    $sAk = isset($row[$cAk]) ? preg_replace('/[^0-9]/', '', $row[$cAk]) : '';

                    if ($sAw !== '' && $sAk !== '') {
                        $qty = ((int) $sAk - (int) $sAw) + 1;
                        $seriesList[] = [
                            'awal'  => (int) $sAw,
                            'akhir' => (int) $sAk,
                            'qty'   => $qty
                        ];
                        $totalSeriQty += $qty;
                    }
                }

                // --- 1 BARIS EXCEL = 1 DOKUMEN SPPM ---
                $existingSppm = OutSppm::where('sppm_no', $currentSppmNo)->first();
                if ($existingSppm) {
                    throw new \Exception("Ditemukan Duplikat Dokumen Keluar di Database untuk Nomor SPPM: {$currentSppmNo}");
                }

                $sppm = OutSppm::create([
                    'sppm_no'        => $currentSppmNo,
                    'sppm_date'      => $tglSppm,
                    'destination_id' => $destination->id,
                    'keterangan'     => 'Import Migrasi Data Lama',
                    'nama_bamat'     => $namaBamat,
                    'pangkat'        => $pangkatNrp,
                    'jabatan'        => $jabatan,
                    'status'         => 'completed', 
                    'created_by'     => auth()->id(),
                    'updated_by'     => auth()->id()
                ]);

                $log = OutLog::create([
                    'out_sppm_id'  => $sppm->id,
                    'batch_number' => 1,
                    'tgl_keluar'   => $tglSppm,
                    'keterangan'   => 'Import Migrasi & Realisasi otomatis',
                ]);

                foreach ($flatMaterials as $idx => $material) {
                    $isSerialized = ($material->pakai_seri == 1);
                    $targetQty = 0;
                    
                    if ($isSerialized) {
                        $targetQty = $totalSeriQty; // Gunakan total QTY dari semua rentang seri
                    } else {
                        $colIndex = 10 + $idx;
                        $qtyRaw = $row[$colIndex] ?? 0;
                        $targetQty = (int) str_replace(['.', ','], '', $qtyRaw);
                    }

                    if ($targetQty > 0) {
                        $hSatuan = $isSerialized ? $hargaSatuan : 0;
                        $hTotal  = $isSerialized ? ($hargaSatuan * $targetQty) : 0;

                        // 1 Detail menampung total QTY dari semua seri
                        OutDetail::create([
                            'out_sppm_id'  => $sppm->id,
                            'material_id'  => $material->id,
                            'target_qty'   => $targetQty,
                            'harga_satuan' => $hSatuan,
                            'harga_total'  => $hTotal,
                        ]);

                        if ($isSerialized) {
                            // Loop untuk memproses Strict Matching per rentang seri di tabel OutStock & Stock
                            foreach ($seriesList as $seri) {
                                $unfulfilled = [['awal' => $seri['awal'], 'akhir' => $seri['akhir']]];
                                
                                $queryStock = Stock::where('material_id', $material->id)
                                                   ->where('qty', '>', 0)
                                                   ->where('seri_awal', '<=', $seri['akhir'])
                                                   ->where('seri_akhir', '>=', $seri['awal']);
                                
                                if ($prefix) {
                                    $queryStock->where('prefix', $prefix);
                                }
                                
                                $availableStocks = $queryStock->orderBy('tgl_masuk', 'asc')->lockForUpdate()->get();
                                
                                foreach ($availableStocks as $stock) {
                                    if (empty($unfulfilled)) break;
                                    
                                    $newUnfulfilled = [];
                                    $stockConsumed = false;
                                    
                                    foreach ($unfulfilled as $u) {
                                        if ($stockConsumed) { 
                                            $newUnfulfilled[] = $u; 
                                            continue; 
                                        }
                                        
                                        $overlapAwal = max($stock->seri_awal, $u['awal']);
                                        $overlapAkhir = min($stock->seri_akhir, $u['akhir']);
                                        
                                        if ($overlapAwal <= $overlapAkhir) {
                                            $stockConsumed = true; 
                                            $overlapQty = $overlapAkhir - $overlapAwal + 1;
                                            
                                            // 1. Catat Barang Keluar dengan ID Log yang sama
                                            OutStock::create([
                                                'out_log_id' => $log->id,
                                                'stock_id'   => $stock->id,
                                                'qty_keluar' => $overlapQty,
                                                'prefix'     => $stock->prefix,
                                                'seri_awal'  => $overlapAwal,
                                                'seri_akhir' => $overlapAkhir,
                                            ]);
                                            
                                            // 2. Pecah Stok (Sisa Kiri) jika ada
                                            if ($stock->seri_awal < $overlapAwal) {
                                                $leftStock = $stock->replicate();
                                                $leftStock->qty = ($overlapAwal - 1) - $stock->seri_awal + 1;
                                                $leftStock->seri_akhir = $overlapAwal - 1;
                                                $leftStock->save();
                                            }
                                            
                                            // 3. Pecah Stok (Sisa Kanan) jika ada
                                            if ($stock->seri_akhir > $overlapAkhir) {
                                                $rightStock = $stock->replicate();
                                                $rightStock->qty = $stock->seri_akhir - ($overlapAkhir + 1) + 1;
                                                $rightStock->seri_awal = $overlapAkhir + 1;
                                                $rightStock->save();
                                            }
                                            
                                            // 4. Matikan Stok Asli
                                            $stock->qty = 0;
                                            $stock->seri_awal = null;
                                            $stock->seri_akhir = null;
                                            $stock->save();
                                            
                                            // 5. Hitung Sisa Utang Seri
                                            if ($u['awal'] < $overlapAwal) {
                                                $newUnfulfilled[] = ['awal' => $u['awal'], 'akhir' => $overlapAwal - 1];
                                            }
                                            if ($u['akhir'] > $overlapAkhir) {
                                                $newUnfulfilled[] = ['awal' => $overlapAkhir + 1, 'akhir' => $u['akhir']];
                                            }
                                        } else {
                                            $newUnfulfilled[] = $u;
                                        }
                                    }
                                    $unfulfilled = $newUnfulfilled;
                                }
                                
                                // REVISI: STOK MINUS SPESIFIK (Memasukkan Kolom Mandatory yang Kurang)
                                foreach ($unfulfilled as $u) {
                                    $missingQty = $u['akhir'] - $u['awal'] + 1;
                                    $negStock = Stock::create([
                                        'no_surat_masuk' => 'MINUS-' . $currentSppmNo, // Prefix agar tidak tabrakan dan mudah dilacak
                                        'material_id'    => $material->id,
                                        'warehouse_id'   => $defaultWarehouseId, 
                                        'qty'            => -$missingQty,
                                        'prefix'         => $prefix,
                                        'seri_awal'      => $u['awal'],
                                        'seri_akhir'     => $u['akhir'],
                                        'tgl_masuk'      => $tglSppm,
                                        'harga_satuan'   => $hSatuan,
                                        'total_harga'    => -($missingQty * $hSatuan),
                                        'status'         => 'Minus',
                                        'keterangan'     => 'Stok Minus Otomatis (Migrasi)',
                                    ]);
                                    
                                    OutStock::create([
                                        'out_log_id' => $log->id,
                                        'stock_id'   => $negStock->id,
                                        'qty_keluar' => $missingQty,
                                        'prefix'     => $prefix,
                                        'seri_awal'  => $u['awal'],
                                        'seri_akhir' => $u['akhir'],
                                    ]);
                                }
                            }

                        } else {
                            // --- LOGIKA BARANG BULK (NON-SERI / FIFO MURNI) ---
                            $sisaKebutuhan = $targetQty;
                            $availableStocks = Stock::where('material_id', $material->id)
                                                    ->where('qty', '>', 0)
                                                    ->orderBy('tgl_masuk', 'asc')
                                                    ->lockForUpdate()
                                                    ->get();
                                                    
                            foreach ($availableStocks as $stock) {
                                if ($sisaKebutuhan <= 0) break;
                                
                                $qtyAmbil = min($stock->qty, $sisaKebutuhan);
                                
                                OutStock::create([
                                    'out_log_id' => $log->id,
                                    'stock_id'   => $stock->id,
                                    'qty_keluar' => $qtyAmbil,
                                    'prefix'     => null,
                                    'seri_awal'  => null,
                                    'seri_akhir' => null,
                                ]);
                                
                                $stock->qty -= $qtyAmbil;
                                $stock->save();
                                
                                $sisaKebutuhan -= $qtyAmbil;
                            }
                            
                            // REVISI: STOK MINUS GENERIK (Memasukkan Kolom Mandatory yang Kurang)
                            if ($sisaKebutuhan > 0) {
                                $negStock = Stock::create([
                                    'no_surat_masuk' => 'MINUS-' . $currentSppmNo,
                                    'material_id'    => $material->id,
                                    'warehouse_id'   => $defaultWarehouseId, 
                                    'qty'            => -$sisaKebutuhan,
                                    'prefix'         => null,
                                    'seri_awal'      => null,
                                    'seri_akhir'     => null,
                                    'tgl_masuk'      => $tglSppm,
                                    'harga_satuan'   => 0,
                                    'total_harga'    => 0,
                                    'status'         => 'Minus',
                                    'keterangan'     => 'Stok Minus Otomatis (Migrasi)',
                                ]);
                                
                                OutStock::create([
                                    'out_log_id' => $log->id,
                                    'stock_id'   => $negStock->id,
                                    'qty_keluar' => $sisaKebutuhan,
                                    'prefix'     => null,
                                    'seri_awal'  => null,
                                    'seri_akhir' => null,
                                ]);
                            }
                        } 
                    }
                } 
                $insertedDataCount++;
            } 

            if ($insertedDataCount === 0) {
                throw new \Exception("Sistem berhasil membaca file, tetapi semua baris dianggap kosong. Cek kembali format Excel/CSV Anda.");
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses import: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Migrasi selesai! Memproses $insertedDataCount Dokumen SPPM (Multi-Seri Tergabung).");
    }
}
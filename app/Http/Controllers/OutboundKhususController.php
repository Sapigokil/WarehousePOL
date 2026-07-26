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

        $hasUrut1 = $flatMaterials->where('nomor_urut', 1)->count() > 0;
        $fileName = 'Template_Migrasi_' . str_replace(' ', '_', strtoupper($category->name)) . '_' . date('Ymd') . '.xls';

        $headers = [
            "Content-type"        => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($topLevelMaterials, $flatMaterials, $category, $hasChildren, $hasUrut1) {
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
            echo '<th colspan="40" style="background-color: #dbeafe;">RENTANG NOMOR SERI (OPSIONAL)</th>';
            if ($hasUrut1) {
                echo '<th colspan="2" style="background-color: #fce7f3;">NILAI BARANG (URUT 1)</th>';
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
            if ($hasUrut1) {
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
            if ($hasUrut1) { echo '<td>17798</td><td>17798000</td>'; }
            echo '</tr>';
            echo '</table>';
        };

        return response()->stream($callback, 200, $headers);
    }

    // --- FUNGSI HANDLE UPLOAD EXCEL OUTBOUND (MENU B) ---
    public function import(Request $request)
    {
        // REVISI: Menambahkan 'txt' karena CSV sering kali dideteksi sebagai text/plain oleh FileInfo PHP
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

        $hasUrut1 = $flatMaterials->where('nomor_urut', 1)->count() > 0;
        $mCount = $flatMaterials->count();
        $headerRowsToSkip = $hasChildren ? 3 : 2;

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false); 
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sistem gagal membaca file Excel: ' . $e->getMessage());
        }

        $insertedDataCount = 0;
        $importedSppms = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if ($index < $headerRowsToSkip) continue; 
                if (empty($row[3]) || empty($row[2])) continue; 

                $tujuanStr   = trim($row[2] ?? '');
                $noSppmRaw   = trim($row[3] ?? '');
                $blnSppm     = trim($row[4] ?? '');
                $tglSppmStr  = $row[5] ?? null;
                $namaBamat   = trim($row[6] ?? '');
                $pangkatNrp  = trim($row[7] ?? '');
                $jabatan     = trim($row[8] ?? '');
                $prefixRaw   = trim($row[9] ?? '');

                if (!$tglSppmStr) continue;
                $tglSppm = date('Y-m-d', strtotime($tglSppmStr));
                $tahunSppm = date('Y', strtotime($tglSppm));
                
                $baseSppmNo = "SPPM/{$noSppmRaw}/{$blnSppm}/{$tahunSppm}/DITLANTAS";

                $cleanPrefix = preg_replace('/[^a-zA-Z]/', '', $prefixRaw);
                $prefix = $cleanPrefix !== '' ? strtoupper($cleanPrefix) : null;

                $destination = Destination::where('name', 'like', $tujuanStr)->first();
                if (!$destination) {
                    throw new \Exception("Tujuan Pengiriman '{$tujuanStr}' pada baris SPPM '{$noSppmRaw}' tidak ditemukan persis di Master Database.");
                }

                $hargaSatuan = 0;
                if ($hasUrut1) {
                    $hsRaw = $row[10 + $mCount + 40] ?? 0;
                    $hargaSatuan = (int) str_replace(['.', ','], '', $hsRaw);
                }

                $seriesList = [];
                for ($i = 0; $i < 20; $i++) {
                    $cAw = 10 + $mCount + ($i * 2);
                    $cAk = 10 + $mCount + ($i * 2) + 1;
                    $sAw = isset($row[$cAw]) ? preg_replace('/[^0-9]/', '', $row[$cAw]) : '';
                    $sAk = isset($row[$cAk]) ? preg_replace('/[^0-9]/', '', $row[$cAk]) : '';

                    if ($sAw !== '' && $sAk !== '') {
                        $seriesList[] = [
                            'suffix' => ($i == 0) ? '' : '_' . ($i + 1), 
                            'awal'   => (int) $sAw,
                            'akhir'  => (int) $sAk,
                            'qty'    => ((int) $sAk - (int) $sAw) + 1
                        ];
                    }
                }

                if (count($seriesList) == 0) {
                    $seriesList[] = ['suffix' => '', 'awal' => null, 'akhir' => null, 'qty' => 0];
                }

                foreach ($seriesList as $sIndex => $seri) {
                    $currentSppmNo = $baseSppmNo . $seri['suffix'];

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
                        $isMaterialUtama = ($material->nomor_urut == 1);
                        
                        $targetQty = 0;
                        if ($isMaterialUtama && $seri['awal'] !== null) {
                            $targetQty = $seri['qty'];
                        } else {
                            if ($sIndex == 0) {
                                $colIndex = 10 + $idx;
                                $qtyRaw = $row[$colIndex] ?? 0;
                                $targetQty = (int) str_replace(['.', ','], '', $qtyRaw);
                            }
                        }

                        if ($targetQty > 0) {
                            $availableStock = Stock::where('material_id', $material->id)->sum('qty');
                            if ($targetQty > $availableStock) {
                                throw new \Exception("Stok gudang tidak mencukupi untuk materiil [{$material->name}] pada baris SPPM {$currentSppmNo}. QTY Diminta: {$targetQty}, Sisa Tersedia: {$availableStock}");
                            }

                            $hSatuan = $isMaterialUtama ? $hargaSatuan : 0;
                            $hTotal  = $isMaterialUtama ? ($hargaSatuan * $targetQty) : 0;

                            OutDetail::create([
                                'out_sppm_id'  => $sppm->id,
                                'material_id'  => $material->id,
                                'target_qty'   => $targetQty,
                                'harga_satuan' => $hSatuan,
                                'harga_total'  => $hTotal,
                            ]);

                            $sisaKebutuhan = $targetQty;
                            $queryStock = Stock::where('material_id', $material->id)->where('qty', '>', 0);
                            
                            if ($isMaterialUtama && $seri['awal'] !== null) {
                                $queryStock->orderByRaw("seri_awal <= {$seri['awal']} AND seri_akhir >= {$seri['awal']} DESC");
                            }
                            if ($prefix) {
                                $queryStock->orderByRaw("prefix = '{$prefix}' DESC");
                            }

                            $availableStocks = $queryStock->orderBy('tgl_masuk', 'asc')->orderBy('id', 'asc')->lockForUpdate()->get();

                            foreach ($availableStocks as $stock) {
                                if ($sisaKebutuhan <= 0) break;

                                $qtyAmbil = min($stock->qty, $sisaKebutuhan);
                                $outSeriAwal = null;
                                $outSeriAkhir = null;

                                if ($stock->seri_awal !== null) {
                                    $outSeriAwal = $stock->seri_awal;
                                    $outSeriAkhir = $stock->seri_awal + $qtyAmbil - 1;

                                    if ($qtyAmbil < $stock->qty) {
                                        $stock->seri_awal = $outSeriAkhir + 1;
                                    } else {
                                        $stock->seri_awal = null;
                                        $stock->seri_akhir = null;
                                    }
                                }

                                OutStock::create([
                                    'out_log_id' => $log->id,
                                    'stock_id'   => $stock->id,
                                    'qty_keluar' => $qtyAmbil,
                                    'prefix'     => $stock->prefix,
                                    'seri_awal'  => $outSeriAwal,
                                    'seri_akhir' => $outSeriAkhir,
                                ]);

                                $stock->qty -= $qtyAmbil;
                                $stock->save();
                                
                                $sisaKebutuhan -= $qtyAmbil;
                            }
                        }
                    } 

                    $insertedDataCount++;
                    $importedSppms[] = $currentSppmNo;
                } 
            } 

            if ($insertedDataCount === 0) {
                throw new \Exception("Sistem berhasil membaca file, tetapi semua baris dianggap kosong. Cek kembali format Excel/CSV Anda.");
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses import: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Migrasi selesai! Memproses $insertedDataCount Dokumen SPPM (termasuk pecahan seri).");
    }
}
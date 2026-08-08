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
    // --- FUNGSI DOWNLOAD TEMPLATE EXCEL OUTBOUND KHUSUS (MENU A) - NATIVE XLSX ---
    public function downloadTemplate(Request $request)
    {
        $request->validate(['category_id' => 'required|exists:material_categories,id']);
        
        $categoryId = $request->input('category_id');
        $category = \App\Models\MaterialCategory::findOrFail($categoryId);

        // Ambil data material level atas (Parent atau Tunggal)
        $topLevelMaterials = \App\Models\Material::with(['children' => function($q) {
                $q->orderBy('nomor_urut', 'asc');
            }])
            ->where('material_category_id', $categoryId)
            ->whereNull('parent_id')
            ->orderBy('nomor_urut', 'asc')
            ->get();

        if ($topLevelMaterials->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak bisa mengunduh template: Kategori ini belum memiliki data Master Barang.');
        }

        // Susun material menjadi array datar (flat) agar urutannya mengalir ke samping
        $flatMaterials = collect();
        foreach ($topLevelMaterials as $parent) {
            if ($parent->children->count() > 0) {
                $flatMaterials->push($parent); 
                foreach ($parent->children as $child) {
                    $flatMaterials->push($child); 
                }
            } else {
                $flatMaterials->push($parent);
            }
        }

        // --- ATURAN RENTANG SERI (SERAGAM UNTUK SEMUA KATEGORI) ---
        $maxSeriCount = 1;

        // Inisiasi PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Styling Default
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $spreadsheet->getDefaultStyle()->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // =================================================================
        // BARIS 1 & 2: HEADER UTAMA DAN SUB-HEADER
        // =================================================================
        
        // Helper untuk mengubah index angka menjadi huruf kolom (1=A, 2=B, dst)
        $colString = function($c) {
            return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
        };

        // 1. Blok Surat (Awal) - Kolom 1 sampai 6
        $headersAwal = ['NO', 'KODE POLRES', "KESATUAN\n(TUJUAN)", 'NO SPPM', 'BLN SPPM', "TGL SPPM\n(YYYY-MM-DD)"];
        $col = 1;
        foreach ($headersAwal as $head) {
            $sheet->setCellValue($colString($col) . '1', $head);
            $sheet->mergeCells($colString($col) . '1:' . $colString($col) . '2');
            $sheet->getStyle($colString($col) . '1')->getFont()->setBold(true);
            $sheet->getStyle($colString($col) . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
            $col++;
        }

        // 2. Blok Dinamis per Material
        foreach ($flatMaterials as $mat) {
            $startCol = $col;
            $colspan = 1; // Pasti punya QTY
            
            if ($mat->ismain == 1) $colspan += 1; // HURUF
            if ($mat->pakai_seri == 1) $colspan += (1 + ($maxSeriCount * 2)); // CODE + SERI AWAL & AKHIR
            if ($mat->is_harga == 1) $colspan += 2; // HARGA SATUAN & JUMLAH
            
            $endCol = $startCol + $colspan - 1;

            // Merge baris 1 untuk nama Material
            $sheet->setCellValue($colString($startCol) . '1', strtoupper($mat->name));
            $sheet->mergeCells($colString($startCol) . '1:' . $colString($endCol) . '1');
            $sheet->getStyle($colString($startCol) . '1')->getFont()->setBold(true);
            $sheet->getStyle($colString($startCol) . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFECDD3');

            // Baris 2 untuk rincian Material
            $subCol = $startCol;
            
            // Kolom QTY
            $sheet->setCellValue($colString($subCol) . '2', 'QTY');
            $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
            $sheet->getStyle($colString($subCol) . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFE4E6');
            $subCol++;

            // Kolom HURUF
            if ($mat->ismain == 1) {
                $sheet->setCellValue($colString($subCol) . '2', 'HURUF');
                $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
                $sheet->getStyle($colString($subCol) . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF08A');
                $subCol++;
            }

            // Kolom SERI
            if ($mat->pakai_seri == 1) {
                $sheet->setCellValue($colString($subCol) . '2', 'CODE');
                $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
                $sheet->getStyle($colString($subCol) . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFBFDBFE');
                $subCol++;

                for ($i = 1; $i <= $maxSeriCount; $i++) {
                    $labelNumber = $maxSeriCount > 1 ? " $i" : "";
                    
                    $sheet->setCellValue($colString($subCol) . '2', 'SERI AWAL' . $labelNumber);
                    $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
                    $sheet->getStyle($colString($subCol) . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFBFDBFE');
                    $subCol++;

                    $sheet->setCellValue($colString($subCol) . '2', 'SERI AKHIR' . $labelNumber);
                    $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
                    $sheet->getStyle($colString($subCol) . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFBFDBFE');
                    $subCol++;
                }
            }

            // Kolom HARGA
            if ($mat->is_harga == 1) {
                $sheet->setCellValue($colString($subCol) . '2', 'HARGA SATUAN');
                $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
                $sheet->getStyle($colString($subCol) . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFBCFE8');
                $subCol++;

                $sheet->setCellValue($colString($subCol) . '2', 'JUMLAH HARGA');
                $sheet->getStyle($colString($subCol) . '2')->getFont()->setBold(true);
                $sheet->getStyle($colString($subCol) . '2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFBCFE8');
                $subCol++;
            }

            $col = $endCol + 1; 
        }

        // 3. Blok Bamat (Akhir)
        $headersAkhir = ['NAMA BAMAT', 'PANGKAT / NRP', 'JABATAN'];
        foreach ($headersAkhir as $head) {
            $sheet->setCellValue($colString($col) . '1', $head);
            $sheet->mergeCells($colString($col) . '1:' . $colString($col) . '2');
            $sheet->getStyle($colString($col) . '1')->getFont()->setBold(true);
            $sheet->getStyle($colString($col) . '1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFDBEAFE');
            $col++;
        }

        // =================================================================
        // BARIS 3: CONTOH DATA (DUMMY) AGAR ADMIN PAHAM CARA MENGISI
        // =================================================================
        
        $sheet->setCellValue('A3', '1');
        $sheet->setCellValue('B3', '1');
        $sheet->setCellValue('C3', 'POLRESTA BANYUMAS');
        $sheet->setCellValue('D3', '933');
        $sheet->setCellValue('E3', 'V');
        $sheet->setCellValue('F3', '2024-05-24');

        $colDummy = 7; // Dimulai dari G (Setelah F)
        foreach ($flatMaterials as $mat) {
            // QTY
            $sheet->setCellValue($colString($colDummy) . '3', '1000'); 
            $colDummy++;
            
            // HURUF
            if ($mat->ismain == 1) {
                $sheet->setCellValue($colString($colDummy) . '3', 'H'); 
                $colDummy++;
            }
            
            // SERI
            if ($mat->pakai_seri == 1) {
                $sheet->setCellValue($colString($colDummy) . '3', 'A'); // CODE
                $colDummy++;
                for ($i = 1; $i <= $maxSeriCount; $i++) {
                    if ($i == 1) {
                        $sheet->setCellValue($colString($colDummy) . '3', '29001'); // AWAL
                        $colDummy++;
                        $sheet->setCellValue($colString($colDummy) . '3', '30000'); // AKHIR
                        $colDummy++;
                    } else {
                        $sheet->setCellValue($colString($colDummy) . '3', ''); 
                        $colDummy++;
                        $sheet->setCellValue($colString($colDummy) . '3', ''); 
                        $colDummy++;
                    }
                }
            }
            
            // HARGA
            if ($mat->is_harga == 1) {
                $sheet->setCellValue($colString($colDummy) . '3', '32838'); 
                $colDummy++;
                $sheet->setCellValue($colString($colDummy) . '3', '32838000');
                $colDummy++;
            }
        }

        // Bamat Data Dummy
        $sheet->setCellValue($colString($colDummy) . '3', 'ENDRO SUSILO'); $colDummy++;
        $sheet->setCellValue($colString($colDummy) . '3', 'BRIPKA/ 86041391'); $colDummy++;
        $sheet->setCellValue($colString($colDummy) . '3', 'BAMAT POLRESTA BANYUMAS');
        
        $maxColAlpha = $colString($colDummy);

        // =================================================================
        // AUTO SIZE COLUMNS & BORDERS
        // =================================================================
        $sheet->getStyle("A1:{$maxColAlpha}3")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        foreach (range(1, $colDummy) as $columnID) {
            $sheet->getColumnDimension($colString($columnID))->setAutoSize(true);
        }
        
        // Aktifkan Wrap Text untuk baris Header yang ada newline (\n)
        $sheet->getStyle("A1:F1")->getAlignment()->setWrapText(true);

        // =================================================================
        // OUTPUT FILE MURNI XLSX
        // =================================================================
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'Template_Outbound_' . str_replace(' ', '_', strtoupper($category->name)) . '_' . date('Ymd') . '.xlsx';

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
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
        $category = MaterialCategory::findOrFail($categoryId);

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

        // Susun material sama persis seperti saat Export
        $flatMaterials = collect();
        foreach ($topLevelMaterials as $parent) {
            if ($parent->children->count() > 0) {
                $flatMaterials->push($parent);
                foreach ($parent->children as $child) {
                    $flatMaterials->push($child);
                }
            } else {
                $flatMaterials->push($parent);
            }
        }

        // --- ATURAN RENTANG SERI (MENYAMAKAN EXPORT) ---
        $isSTNK = stripos($category->name, 'STNK') !== false;
        $maxSeriCount = $isSTNK ? 20 : 1;

        // =================================================================
        // PEMETAAN INDEX KOLOM DINAMIS (0-BASED INDEX)
        // =================================================================
        // Col 0:NO, 1:POLRES, 2:KESATUAN, 3:NO SPPM, 4:BLN, 5:TGL
        $currentCol = 6; 
        $colMap = [];
        $lastParentHargaCol = null;

        foreach ($flatMaterials as $mat) {
            $isParent = is_null($mat->parent_id) && $mat->children()->count() > 0;
            
            $map = [
                'material' => $mat,
                'qty' => $currentCol++,
                'huruf' => null,
                'code' => null,
                'series' => [],
                'harga_satuan' => null,
                'fallback_harga_satuan' => null
            ];

            if ($mat->ismain == 1) {
                $map['huruf'] = $currentCol++;
            }

            if ($mat->pakai_seri == 1) {
                $map['code'] = $currentCol++;
                for ($i = 1; $i <= $maxSeriCount; $i++) {
                    $map['series'][] = [
                        'awal'  => $currentCol++,
                        'akhir' => $currentCol++
                    ];
                }
            }

            if ($mat->is_harga == 1) {
                $map['harga_satuan'] = $currentCol++;
                $currentCol++; // Skip JUMLAH HARGA (Kita hitung ulang otomatis di sistem)
                if ($isParent) {
                    $lastParentHargaCol = $map['harga_satuan'];
                }
            } else {
                // Jika Child tidak punya is_harga, warisi kolom harga milik Parent-nya
                if (!is_null($mat->parent_id)) {
                    $map['fallback_harga_satuan'] = $lastParentHargaCol;
                }
            }

            $colMap[] = $map;
        }

        // Index untuk Blok Bamat di ujung tabel
        $colNamaBamat = $currentCol++;
        $colPangkat   = $currentCol++;
        $colJabatan   = $currentCol++;

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            // returnCellRef = false membuat array menggunakan index angka (0-based)
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false); 
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sistem gagal membaca file Excel: ' . $e->getMessage());
        }

        $insertedDataCount = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if ($index < 2) continue; // Skip Baris 1 & 2 (Header)
                
                $tujuanStr   = trim($row[2] ?? '');
                $noSppmRaw   = trim($row[3] ?? '');
                
                // Skip baris jika kosong
                if (empty($noSppmRaw) || empty($tujuanStr)) continue; 
                
                // Skip Baris Dummy Contoh (No SPPM 933 Polresta Banyumas)
                if ($noSppmRaw == '933' && str_contains(strtoupper($tujuanStr), 'BANYUMAS')) continue;

                $blnSppm     = trim($row[4] ?? '');
                $tglSppmStr  = trim($row[5] ?? '');
                $namaBamat   = trim($row[$colNamaBamat] ?? '');
                $pangkatNrp  = trim($row[$colPangkat] ?? '');
                $jabatan     = trim($row[$colJabatan] ?? '');

                if (!$tglSppmStr) continue;

                // --- LOGIKA TRANSLATE TANGGAL ---
                if (is_numeric($tglSppmStr)) {
                    $tglSppm = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tglSppmStr)->format('Y-m-d');
                } else {
                    $bulanIndo = [
                        'Januari'  => 'January',  'Februari' => 'February', 'Pebruari' => 'February',
                        'Maret'    => 'March',    'April'    => 'April',    'Mei'      => 'May',
                        'Juni'     => 'June',     'Juli'     => 'July',     'Agustus'  => 'August',
                        'September'=> 'September','Oktober'  => 'October',  'November' => 'November',
                        'Nopember' => 'November', 'Desember' => 'December'
                    ];
                    $tglSppmEng = str_ireplace(array_keys($bulanIndo), array_values($bulanIndo), $tglSppmStr);
                    $tglSppm = date('Y-m-d', strtotime($tglSppmEng));
                }

                if (!$tglSppm || $tglSppm == '1970-01-01') {
                    $tglSppm = date('Y-m-d'); 
                }

                $tahunSppm = date('Y', strtotime($tglSppm));
                $currentSppmNo = "SPPM/{$noSppmRaw}/{$blnSppm}/{$tahunSppm}/DITLANTAS";

                $destination = Destination::where('name', 'like', $tujuanStr)->first();
                if (!$destination) {
                    throw new \Exception("Tujuan Pengiriman '{$tujuanStr}' pada baris SPPM '{$noSppmRaw}' tidak ditemukan persis di Master Database.");
                }

                $existingSppm = OutSppm::where('sppm_no', $currentSppmNo)->first();
                if ($existingSppm) {
                    throw new \Exception("Ditemukan Duplikat Dokumen Keluar di Database untuk Nomor SPPM: {$currentSppmNo}");
                }

                $sppm = OutSppm::create([
                    'sppm_no'        => $currentSppmNo,
                    'sppm_date'      => $tglSppm,
                    'destination_id' => $destination->id,
                    'keterangan'     => 'Import Migrasi Data Baru',
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
                    'keterangan'   => 'Import & Realisasi Otomatis',
                ]);

                // PROSES SETIAP BLOK MATERIIL
                foreach ($colMap as $map) {
                    $mat = $map['material'];
                    $qtyRaw = $row[$map['qty']] ?? 0;
                    $targetQty = (int) str_replace(['.', ','], '', $qtyRaw);

                    if ($targetQty <= 0) continue;

                    // Ambil Prefix (Prioritas: Kolom CODE, lalu HURUF)
                    $prefix = null;
                    if ($map['code']) {
                        $prefix = strtoupper(preg_replace('/[^a-zA-Z]/', '', $row[$map['code']] ?? ''));
                    }
                    if (!$prefix && $map['huruf']) {
                        $prefix = strtoupper(preg_replace('/[^a-zA-Z]/', '', $row[$map['huruf']] ?? ''));
                    }

                    // Ambil Harga
                    $hargaSatuan = 0;
                    $hargaTotal = 0;
                    
                    // Cek apakah punya kolom harga sendiri ATAU mewarisi harga dari Parent
                    $colHargaToRead = $map['harga_satuan'] ?? $map['fallback_harga_satuan'];
                    if ($colHargaToRead !== null) {
                        $hargaSatuan = (int) str_replace(['.', ','], '', $row[$colHargaToRead] ?? 0);
                        $hargaTotal = $hargaSatuan * $targetQty;
                    }

                    // Ambil Seri
                    $seriesList = [];
                    $totalSeriQty = 0;
                    if ($mat->pakai_seri == 1 && !empty($map['series'])) {
                        foreach ($map['series'] as $seriCols) {
                            $sAw = preg_replace('/[^0-9]/', '', $row[$seriCols['awal']] ?? '');
                            $sAk = preg_replace('/[^0-9]/', '', $row[$seriCols['akhir']] ?? '');
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
                    }

                    $isSerialized = ($mat->pakai_seri == 1 && !empty($seriesList));
                    if ($isSerialized) {
                        $targetQty = $totalSeriQty; // Timpa QTY dengan perhitungan fisik seri
                        if ($hargaSatuan > 0) {
                            $hargaTotal = $hargaSatuan * $targetQty;
                        }
                    }

                    OutDetail::create([
                        'out_sppm_id'  => $sppm->id,
                        'material_id'  => $mat->id,
                        'target_qty'   => $targetQty,
                        'harga_satuan' => $hargaSatuan,
                        'harga_total'  => $hargaTotal,
                    ]);

                    // LOGIKA POTONG STOK
                    if ($isSerialized) {
                        foreach ($seriesList as $seri) {
                            $unfulfilled = [['awal' => $seri['awal'], 'akhir' => $seri['akhir']]];
                            
                            $queryStock = Stock::where('material_id', $mat->id)
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
                                        
                                        OutStock::create([
                                            'out_log_id' => $log->id,
                                            'stock_id'   => $stock->id,
                                            'qty_keluar' => $overlapQty,
                                            'prefix'     => $stock->prefix,
                                            'seri_awal'  => $overlapAwal,
                                            'seri_akhir' => $overlapAkhir,
                                        ]);
                                        
                                        if ($stock->seri_awal < $overlapAwal) {
                                            $leftStock = $stock->replicate();
                                            $leftStock->qty = ($overlapAwal - 1) - $stock->seri_awal + 1;
                                            $leftStock->seri_akhir = $overlapAwal - 1;
                                            $leftStock->save();
                                        }
                                        
                                        if ($stock->seri_akhir > $overlapAkhir) {
                                            $rightStock = $stock->replicate();
                                            $rightStock->qty = $stock->seri_akhir - ($overlapAkhir + 1) + 1;
                                            $rightStock->seri_awal = $overlapAkhir + 1;
                                            $rightStock->save();
                                        }
                                        
                                        $stock->qty = 0;
                                        $stock->seri_awal = null;
                                        $stock->seri_akhir = null;
                                        $stock->save();
                                        
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
                            
                            // JIKA STOK MINUS (SERI)
                            foreach ($unfulfilled as $u) {
                                $missingQty = $u['akhir'] - $u['awal'] + 1;
                                $negStock = Stock::create([
                                    'no_surat_masuk' => 'MINUS-' . $currentSppmNo, 
                                    'material_id'    => $mat->id,
                                    'warehouse_id'   => $defaultWarehouseId, 
                                    'qty'            => -$missingQty,
                                    'prefix'         => $prefix,
                                    'seri_awal'      => $u['awal'],
                                    'seri_akhir'     => $u['akhir'],
                                    'tgl_masuk'      => $tglSppm,
                                    'harga_satuan'   => $hargaSatuan,
                                    'total_harga'    => -($missingQty * $hargaSatuan),
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
                        // LOGIKA STOK FIFO (BULK)
                        $sisaKebutuhan = $targetQty;
                        $queryStock = Stock::where('material_id', $mat->id)
                                           ->where('qty', '>', 0);
                        
                        if ($prefix) {
                            $queryStock->where('prefix', $prefix);
                        }
                                           
                        $availableStocks = $queryStock->orderBy('tgl_masuk', 'asc')->lockForUpdate()->get();
                        
                        foreach ($availableStocks as $stock) {
                            if ($sisaKebutuhan <= 0) break;
                            
                            $qtyAmbil = min($stock->qty, $sisaKebutuhan);
                            
                            OutStock::create([
                                'out_log_id' => $log->id,
                                'stock_id'   => $stock->id,
                                'qty_keluar' => $qtyAmbil,
                                'prefix'     => $stock->prefix,
                                'seri_awal'  => null,
                                'seri_akhir' => null,
                            ]);
                            
                            $stock->qty -= $qtyAmbil;
                            $stock->save();
                            
                            $sisaKebutuhan -= $qtyAmbil;
                        }
                        
                        // JIKA STOK MINUS (FIFO)
                        if ($sisaKebutuhan > 0) {
                            $negStock = Stock::create([
                                'no_surat_masuk' => 'MINUS-' . $currentSppmNo,
                                'material_id'    => $mat->id,
                                'warehouse_id'   => $defaultWarehouseId, 
                                'qty'            => -$sisaKebutuhan,
                                'prefix'         => $prefix,
                                'seri_awal'      => null,
                                'seri_akhir'     => null,
                                'tgl_masuk'      => $tglSppm,
                                'harga_satuan'   => $hargaSatuan,
                                'total_harga'    => -($sisaKebutuhan * $hargaSatuan),
                                'status'         => 'Minus',
                                'keterangan'     => 'Stok Minus Otomatis (Migrasi)',
                            ]);
                            
                            OutStock::create([
                                'out_log_id' => $log->id,
                                'stock_id'   => $negStock->id,
                                'qty_keluar' => $sisaKebutuhan,
                                'prefix'     => $prefix,
                                'seri_awal'  => null,
                                'seri_akhir' => null,
                            ]);
                        }
                    } 
                } 
                $insertedDataCount++;
            } 

            if ($insertedDataCount === 0) {
                throw new \Exception("Sistem berhasil membaca file, tetapi semua baris dianggap kosong atau tidak memenuhi syarat (Format Tanggal/No SPPM).");
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses import: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Migrasi selesai! Memproses $insertedDataCount Dokumen SPPM dengan Layout Kolom Baru.");
    }
}
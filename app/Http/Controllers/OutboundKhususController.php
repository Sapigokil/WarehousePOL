<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaterialCategory;
use App\Models\Material;
use App\Models\OutSppm;
use App\Models\OutDetail;
use App\Models\OutLog;
use App\Models\OutStock;
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

        $colDummy = 7; 
        foreach ($flatMaterials as $mat) {
            $sheet->setCellValue($colString($colDummy) . '3', '1000'); 
            $colDummy++;
            
            if ($mat->ismain == 1) {
                $sheet->setCellValue($colString($colDummy) . '3', 'H'); 
                $colDummy++;
            }
            
            if ($mat->pakai_seri == 1) {
                $sheet->setCellValue($colString($colDummy) . '3', 'A'); 
                $colDummy++;
                for ($i = 1; $i <= $maxSeriCount; $i++) {
                    if ($i == 1) {
                        $sheet->setCellValue($colString($colDummy) . '3', '29001'); 
                        $colDummy++;
                        $sheet->setCellValue($colString($colDummy) . '3', '30000'); 
                        $colDummy++;
                    } else {
                        $sheet->setCellValue($colString($colDummy) . '3', ''); 
                        $colDummy++;
                        $sheet->setCellValue($colString($colDummy) . '3', ''); 
                        $colDummy++;
                    }
                }
            }
            
            if ($mat->is_harga == 1) {
                $sheet->setCellValue($colString($colDummy) . '3', '32838'); 
                $colDummy++;
                $sheet->setCellValue($colString($colDummy) . '3', '32838000');
                $colDummy++;
            }
        }

        $sheet->setCellValue($colString($colDummy) . '3', 'ENDRO SUSILO'); $colDummy++;
        $sheet->setCellValue($colString($colDummy) . '3', 'BRIPKA/ 86041391'); $colDummy++;
        $sheet->setCellValue($colString($colDummy) . '3', 'BAMAT POLRESTA BANYUMAS');
        
        $maxColAlpha = $colString($colDummy);

        $sheet->getStyle("A1:{$maxColAlpha}3")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        
        foreach (range(1, $colDummy) as $columnID) {
            $sheet->getColumnDimension($colString($columnID))->setAutoSize(true);
        }
        
        $sheet->getStyle("A1:F1")->getAlignment()->setWrapText(true);

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

        $maxSeriCount = 1;

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
                $currentCol++; 
                if ($isParent) {
                    $lastParentHargaCol = $map['harga_satuan'];
                }
            } else {
                if (!is_null($mat->parent_id)) {
                    $map['fallback_harga_satuan'] = $lastParentHargaCol;
                }
            }

            $colMap[] = $map;
        }

        $colNamaBamat = $currentCol++;
        $colPangkat   = $currentCol++;
        $colJabatan   = $currentCol++;

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
                if ($index < 2) continue; 
                
                $tujuanStr   = trim($row[2] ?? '');
                $noSppmRaw   = trim($row[3] ?? '');
                
                if (empty($noSppmRaw) || empty($tujuanStr)) continue; 
                
                if ($noSppmRaw == '933' && str_contains(strtoupper($tujuanStr), 'BANYUMAS')) continue;

                $blnSppm     = trim($row[4] ?? '');
                $tglSppmStr  = trim($row[5] ?? '');
                $namaBamat   = trim($row[$colNamaBamat] ?? '');
                $pangkatNrp  = trim($row[$colPangkat] ?? '');
                $jabatan     = trim($row[$colJabatan] ?? '');

                if (!$tglSppmStr) continue;

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

                foreach ($colMap as $map) {
                    $mat = $map['material'];
                    $qtyRaw = $row[$map['qty']] ?? 0;
                    $targetQty = (int) str_replace(['.', ','], '', $qtyRaw);

                    if ($targetQty <= 0) continue;

                    $prefix = null;
                    if ($map['code']) {
                        $prefix = strtoupper(preg_replace('/[^a-zA-Z]/', '', $row[$map['code']] ?? ''));
                    }
                    if (!$prefix && $map['huruf']) {
                        $prefix = strtoupper(preg_replace('/[^a-zA-Z]/', '', $row[$map['huruf']] ?? ''));
                    }

                    $hargaSatuan = 0;
                    $hargaTotal = 0;
                    
                    $colHargaToRead = $map['harga_satuan'] ?? $map['fallback_harga_satuan'];
                    if ($colHargaToRead !== null) {
                        $hargaSatuan = (int) str_replace(['.', ','], '', $row[$colHargaToRead] ?? 0);
                        $hargaTotal = $hargaSatuan * $targetQty;
                    }

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
                        $targetQty = $totalSeriQty; 
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

                    // SIMPAN LANGSUNG KE OUT_STOCK MENGGUNAKAN MATERIAL_ID TANPA STOCK_ID
                    if ($isSerialized) {
                        foreach ($seriesList as $seri) {
                            OutStock::create([
                                'out_log_id'  => $log->id,
                                'material_id' => $mat->id,
                                'qty_keluar'  => $seri['qty'],
                                'prefix'      => $prefix,
                                'seri_awal'   => $seri['awal'],
                                'seri_akhir'  => $seri['akhir'],
                            ]);
                        }
                    } else {
                        OutStock::create([
                            'out_log_id'  => $log->id,
                            'material_id' => $mat->id,
                            'qty_keluar'  => $targetQty,
                            'prefix'      => $prefix,
                            'seri_awal'   => null,
                            'seri_akhir'  => null,
                        ]);
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
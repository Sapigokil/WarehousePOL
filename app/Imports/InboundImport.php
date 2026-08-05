<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\InSppm;
use App\Models\InDetail;
use App\Models\InLog;
use App\Models\InStock;
use App\Models\Material;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class InboundImport implements ToCollection
{
    protected $categoryId;
    protected $originalFileName;

    public function __construct($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Helper untuk memparsing tanggal Excel (bisa berupa serial number excel atau string)
     */
    private function parseDate($value)
    {
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        return date('Y-m-d', strtotime($value));
    }

    public function collection(Collection $rows)
    {
        // 1. Ambil hierarki material persis seperti di downloadTemplate
        $topLevelMaterials = Material::with(['children' => function($q) {
                $q->orderBy('nomor_urut', 'asc');
            }])
            ->where('material_category_id', $this->categoryId)
            ->whereNull('parent_id')
            ->orderBy('nomor_urut', 'asc')
            ->get();

        if ($topLevelMaterials->isEmpty()) {
            throw new \Exception('Kategori ini tidak memiliki daftar material Master Barang.');
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

        // Tentukan jumlah baris header yang harus dilewati (Kembali ke default 2 atau 3)
        $headerRowsToSkip = $hasChildren ? 3 : 2;

        $parseSerial = function($string) {
            $result = ['prefix' => null, 'start' => null, 'end' => null];
            if (empty(trim($string)) || trim($string) === '-') return $result;

            $parts = explode('-', $string);
            if (count($parts) == 2) {
                $left = trim($parts[0]);
                if (preg_match('/^([a-zA-Z\.\s]+)?([\d\.]+)$/', $left, $matches)) {
                    if (isset($matches[1])) {
                        $cleanPrefix = preg_replace('/[^a-zA-Z]/', '', $matches[1]);
                        $result['prefix'] = $cleanPrefix !== '' ? strtoupper($cleanPrefix) : null;
                    } else {
                        $result['prefix'] = null;
                    }
                    
                    $result['start'] = (int) str_replace('.', '', $matches[2]);
                }
                $result['end'] = (int) str_replace('.', '', trim($parts[1]));
            }
            return $result;
        };

        $insertedDataCount = 0;
        $importedSppms = []; 

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                // Lewati baris header template
                if ($index < $headerRowsToSkip) continue; 

                // Lewati jika kolom kurang dari 6 (tidak valid)
                if (count($row) < 6) continue;

                $tglPenerimaanStr = $row[1] ?? null;
                $tglSppmStr       = $row[2] ?? null;
                $noSppm           = trim($row[3] ?? '');
                $nomorSeriStr     = trim($row[4] ?? '');
                $noBappm          = trim($row[5] ?? '');

                // Abaikan baris kosong
                if (empty($noSppm) || empty(trim($tglPenerimaanStr))) continue; 

                $tglPenerimaan = $this->parseDate($tglPenerimaanStr);
                $tglSppm = !empty(trim($tglSppmStr)) ? $this->parseDate($tglSppmStr) : $tglPenerimaan;

                $defaultWarehouse = Warehouse::first();
                $warehouseId = $defaultWarehouse ? $defaultWarehouse->id : 1;

                // Cek duplikasi SPPM
                $existingSppm = InSppm::where('sppm_no', $noSppm)->first();
                if ($existingSppm) {
                    throw new \Exception("Ditemukan duplikat SPPM di dalam database untuk nomor: {$noSppm}");
                }

                $sppm = InSppm::create([
                    'sppm_no'              => $noSppm,
                    'sppm_date'            => $tglSppm,
                    'no_bappm'             => $noBappm, 
                    'material_category_id' => $this->categoryId,
                    'warehouse_id'         => $warehouseId,
                    'notes'                => 'Import otomatis via Excel',
                    'status'               => 'completed',
                    'created_by'           => auth()->id(),
                    'updated_by'           => auth()->id()
                ]);

                $log = InLog::create([
                    'in_sppm_id'   => $sppm->id,
                    'batch_number' => 1,
                    'receive_date' => $tglPenerimaan,
                    'receiver_name'=> auth()->user()->name ?? 'Admin Gudang',
                    'notes'        => 'Import otomatis via Excel'
                ]);

                $serialParsed = $parseSerial($nomorSeriStr);

                // Tangkap Harga Satuan dari kolom paling kanan (Kolom indeks ke: 6 + Total Jenis Material)
                $priceIndex = 6 + $flatMaterials->count();
                $rawGlobalPrice = $row[$priceIndex] ?? 0;
                $globalPrice = is_numeric($rawGlobalPrice) ? (float) $rawGlobalPrice : (float) str_replace(['.', ','], '', $rawGlobalPrice);

                // Mulai membaca kolom materiil dinamis (Dimulai dari index ke-6)
                foreach ($flatMaterials as $idx => $material) {
                    $colQtyIndex = 6 + $idx; 
                    
                    // Bersihkan angka Qty
                    $rawQty = $row[$colQtyIndex] ?? 0;
                    $qty = is_numeric($rawQty) ? (int) $rawQty : (int) str_replace(['.', ','], '', $rawQty);

                    // Terapkan harga HANYA jika materiel ini adalah Ismain (Induk)
                    $isMain = ($material->ismain == 1 || $material->is_main == 1);
                    $price = $isMain ? $globalPrice : 0;
                    $totalPrice = $qty * $price;

                    // Siapkan data Prefix dan Seri 
                    $isSerialized = ($material->pakai_seri == 1);
                    $finalPrefix = $isSerialized ? $serialParsed['prefix'] : null;
                    $finalStart  = $isSerialized ? $serialParsed['start'] : null;
                    $finalEnd    = $isSerialized ? $serialParsed['end'] : null;

                    // --- PERBAIKAN: Selalu Rekam InDetail (Termasuk saat QTY 0) ---
                    InDetail::create([
                        'in_sppm_id'        => $sppm->id,
                        'material_id'       => $material->id,
                        'target_qty'        => $qty,
                        'qty_huruf'         => null,
                        'harga_satuan'      => $price,
                        'harga_total'       => $totalPrice,
                        'sppm_serial_prefix'=> $finalPrefix,
                        'sppm_serial_start' => $finalStart,
                        'sppm_serial_end'   => $finalEnd,
                    ]);

                    // --- PERBAIKAN: Proses fisik stok dan rekonsiliasi HANYA berjalan jika QTY > 0 ---
                    if ($qty > 0) {
                        InStock::create([
                            'in_log_id'    => $log->id,
                            'material_id'  => $material->id,
                            'qty_received' => $qty,
                            'serial_prefix'=> $finalPrefix,
                            'serial_start' => $finalStart,
                            'serial_end'   => $finalEnd,
                        ]);

                        // 2. Logika Auto-Reconciliation (Pelunasan Utang Gudang)
                        if ($isSerialized) {
                            $unfulfilled = [['awal' => $finalStart, 'akhir' => $finalEnd]];

                            $negStocks = Stock::where('material_id', $material->id)
                                              ->where('qty', '<', 0)
                                              ->where('prefix', $finalPrefix)
                                              ->orderBy('created_at', 'asc')
                                              ->lockForUpdate()
                                              ->get();

                            foreach ($negStocks as $negStock) {
                                if (empty($unfulfilled)) break;
                                $new_unfulfilled = [];

                                foreach ($unfulfilled as $inc) {
                                    $overlapAwal = max($negStock->seri_awal, $inc['awal']);
                                    $overlapAkhir = min($negStock->seri_akhir, $inc['akhir']);

                                    if ($overlapAwal <= $overlapAkhir) {
                                        // Ditemukan kecocokan
                                        if ($overlapAwal == $negStock->seri_awal && $overlapAkhir == $negStock->seri_akhir) {
                                            $negStock->qty = 0;
                                            $negStock->no_surat_masuk = preg_replace('/^MINUS-/', '', $negStock->no_surat_masuk);
                                            $negStock->status = '-';
                                            $negStock->harga_satuan = $price;
                                            $negStock->total_harga = $qty * $price;
                                            $negStock->save();
                                        } else {
                                            $paidStock = $negStock->replicate();
                                            $paidStock->qty = 0;
                                            $paidStock->seri_awal = $overlapAwal;
                                            $paidStock->seri_akhir = $overlapAkhir;
                                            $paidStock->no_surat_masuk = preg_replace('/^MINUS-/', '', $negStock->no_surat_masuk);
                                            $paidStock->status = '-';
                                            $paidStock->harga_satuan = $price;
                                            $paidStock->total_harga = ($overlapAkhir - $overlapAwal + 1) * $price;
                                            $paidStock->save();

                                            if ($negStock->seri_awal < $overlapAwal) {
                                                $leftNeg = $negStock->replicate();
                                                $leftNeg->qty = -( ($overlapAwal - 1) - $negStock->seri_awal + 1 );
                                                $leftNeg->seri_akhir = $overlapAwal - 1;
                                                $leftNeg->save();
                                            }
                                            if ($negStock->seri_akhir > $overlapAkhir) {
                                                $rightNeg = $negStock->replicate();
                                                $rightNeg->qty = -( $negStock->seri_akhir - ($overlapAkhir + 1) + 1 );
                                                $rightNeg->seri_awal = $overlapAkhir + 1;
                                                $rightNeg->save();
                                            }
                                            $negStock->delete();
                                        }

                                        if ($inc['awal'] < $overlapAwal) {
                                            $new_unfulfilled[] = ['awal' => $inc['awal'], 'akhir' => $overlapAwal - 1];
                                        }
                                        if ($inc['akhir'] > $overlapAkhir) {
                                            $new_unfulfilled[] = ['awal' => $overlapAkhir + 1, 'akhir' => $inc['akhir']];
                                        }
                                    } else {
                                        $new_unfulfilled[] = $inc; 
                                    }
                                }
                                $unfulfilled = $new_unfulfilled;
                            }

                            // Sisa positif
                            foreach ($unfulfilled as $u) {
                                $qtySisa = $u['akhir'] - $u['awal'] + 1;
                                Stock::create([
                                    'no_surat_masuk' => $sppm->sppm_no,
                                    'tgl_masuk'      => $tglPenerimaan,
                                    'material_id'    => $material->id,
                                    'warehouse_id'   => $warehouseId,
                                    'prefix'         => $finalPrefix,
                                    'seri_awal'      => $u['awal'],
                                    'seri_akhir'     => $u['akhir'],
                                    'qty'            => $qtySisa,
                                    'harga_satuan'   => $price,
                                    'total_harga'    => $qtySisa * $price,
                                    'status'         => '-',
                                    'keterangan'     => 'Import otomatis via Excel',
                                ]);
                            }

                        } else {
                            // --- AUTO RECONCILIATION BULK (NON-SERI) ---
                            $qty_incoming = $qty;
                            $negStocks = Stock::where('material_id', $material->id)
                                              ->where('qty', '<', 0)
                                              ->orderBy('created_at', 'asc')
                                              ->lockForUpdate()
                                              ->get();

                            foreach($negStocks as $negStock) {
                                if ($qty_incoming <= 0) break;

                                $utang = abs($negStock->qty);
                                $bayar = min($utang, $qty_incoming);

                                if ($bayar == $utang) {
                                    $negStock->qty = 0;
                                    $negStock->no_surat_masuk = preg_replace('/^MINUS-/', '', $negStock->no_surat_masuk);
                                    $negStock->status = '-';
                                    $negStock->harga_satuan = $price;
                                    $negStock->total_harga = $bayar * $price;
                                    $negStock->save();
                                } else {
                                    $paidStock = $negStock->replicate();
                                    $paidStock->qty = 0;
                                    $paidStock->no_surat_masuk = preg_replace('/^MINUS-/', '', $negStock->no_surat_masuk);
                                    $paidStock->status = '-';
                                    $paidStock->harga_satuan = $price;
                                    $paidStock->total_harga = $bayar * $price;
                                    $paidStock->save();

                                    $negStock->qty += $bayar; 
                                    $negStock->save();
                                }
                                $qty_incoming -= $bayar;
                            }

                            if ($qty_incoming > 0) {
                                Stock::create([
                                    'no_surat_masuk' => $sppm->sppm_no,
                                    'tgl_masuk'      => $tglPenerimaan,
                                    'material_id'    => $material->id,
                                    'warehouse_id'   => $warehouseId,
                                    'prefix'         => null,
                                    'seri_awal'      => null,
                                    'seri_akhir'     => null,
                                    'qty'            => $qty_incoming,
                                    'harga_satuan'   => $price,
                                    'total_harga'    => $qty_incoming * $price,
                                    'status'         => '-',
                                    'keterangan'     => 'Import otomatis via Excel',
                                ]);
                            }
                        }
                    }
                }
                
                $insertedDataCount++;
                $importedSppms[] = $noSppm; 
            }
            
            if ($insertedDataCount === 0) {
                throw new \Exception("Sistem membaca file, tetapi tidak ada baris data yang valid. Pastikan TGL PENERIMAAN dan NO. SPPM terisi, serta susunan kolom tidak diubah.");
            }

            // --- CATAT LOG SISTEM ---
            SystemLog::create([
                'user_id'    => auth()->id(),
                'username'   => auth()->user()->name ?? 'Sistem',
                'action'     => 'IMPORT',
                'table_name' => 'DOKUMEN SPPM',
                'new_values' => [
                    'Total Baris Sukses'  => $insertedDataCount,
                    'Daftar SPPM Masuk'   => implode(', ', $importedSppms)
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; 
        }
    }
}
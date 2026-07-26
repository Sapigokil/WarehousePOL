<?php

namespace App\Imports;

use App\Models\Destination;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class DestinationsImport implements ToCollection, WithStartRow
{
    /**
     * Memulai pembacaan dari baris ke-2 (melewati header)
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Abaikan jika kolom nama tujuan (Index 1) kosong
            if (!isset($row[1]) || trim($row[1]) === '') {
                continue;
            }

            $name = trim($row[1]); // Kolom B: Nama Tujuan/Polres
            $nama_penerima = isset($row[2]) ? trim($row[2]) : null; // Kolom C: Nama Orang
            $pangkat_nrp = isset($row[3]) ? trim($row[3]) : null; // Kolom D: Pangkat / NRP
            $jabatan = isset($row[4]) ? trim($row[4]) : null; // Kolom E: Jabatan

            // Cek apakah tujuan (Polres) sudah ada di database
            $destination = Destination::where('name', $name)->first();

            if ($destination) {
                // Jika sudah ada, update data penerimanya
                $destination->update([
                    'nama'        => $nama_penerima,
                    'pangkat_nrp' => $pangkat_nrp,
                    'jabatan'     => $jabatan,
                ]);
            } else {
                // Jika belum ada, buat baru dan set nomor urut terakhir + 1
                $lastUrut = Destination::max('nomor_urut');
                $nextUrut = $lastUrut ? $lastUrut + 1 : 1;

                Destination::create([
                    'nomor_urut'  => $nextUrut,
                    'name'        => $name,
                    'nama'        => $nama_penerima,
                    'pangkat_nrp' => $pangkat_nrp,
                    'jabatan'     => $jabatan,
                    'keterangan'  => null,
                ]);
            }
        }
    }
}
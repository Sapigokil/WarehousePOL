<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TemplateKhususExport implements WithHeadings, WithStyles
{
    protected $kategori;

    public function __construct($kategori)
    {
        $this->kategori = $kategori;
    }

    public function headings(): array
    {
        // Jika kategori yang dipilih adalah STNK
        if ($this->kategori === 'STNK') {
            // Revisi: Kolom kosong setelah 'JUMLAH BLANKO STNK' sudah dihapus
            $headings = [
                'NO URUT', 'KODE POLRES', 'KESATUAN', 'NO SPPM', 'BLN SPPM',
                'TGL SPPM', 'JUMLAH BLANKO STNK', 'HURUF', 'KODE'
            ];

            // Looping 20 pasang Seri Awal & Akhir
            for ($i = 1; $i <= 20; $i++) {
                $headings[] = 'NO SERI AWAL ' . $i;
                $headings[] = 'NO SERI AKHIR ' . $i;
            }

            // Sisa kolom di sebelah kanan
            $suffix = [
                'HARGA SATUAN', 'JML STNK', 'SPRKB STNK', 'NO AWAL', 'NO AKHIR',
                'MAP ARSIP STNK', 'DOMPET PLASTIK STNK', 'BLANKO CF', 'KWITA NSI STNK',
                'BUKU REG INDUK', 'BUKU PERPAN JANG', 'BUKU PENE RBITAN',
                'PITA LQ 2190', 'PITA LX 310', 'PRINTER CAIR L4150/ 001',
                '', 'NAMA BAMAT', 'PANGKAT/ NRP', 'JABATAN'
            ];

            return array_merge($headings, $suffix);
        }

        // Return array kosong jika kategori lain
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        // Membuat baris header (Baris 1) menjadi bold agar terlihat seperti template
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
    }
}
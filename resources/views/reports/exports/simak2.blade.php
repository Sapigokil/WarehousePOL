<table style="border-collapse: collapse; width: 100%;">
    <thead>
        <!-- Judul Laporan (Baris 1, Center) Total Kolom: NO(1) + JENIS(1) + BULAN(12) + JUMLAH(1) + KET(1) = 16 -->
        <tr>
            <td colspan="16" style="text-align: center; font-weight: bold; font-family: Arial; font-size: 14px;">
                PENDISTRIBUSIAN MATERIEL {{ strtoupper($selectedLabel) }} TAHUN {{ $year }}
            </td>
        </tr>
        
        <!-- Kop Surat (Baris 2, 3, 4, Kiri) -->
        <tr>
            <td colspan="16" style="text-align: left; font-weight: bold; font-family: Arial; font-size: 11px;">KEPOLISIAN NEGARA REPUBLIK INDONESIA</td>
        </tr>
        <tr>
            <td colspan="16" style="text-align: left; font-weight: bold; font-family: Arial; font-size: 11px;">DAERAH JAWA TENGAH</td>
        </tr>
        <tr>
            <td colspan="16" style="text-align: left; font-weight: bold; font-family: Arial; font-size: 11px;">DIREKTORAT LALU LINTAS</td>
        </tr>
        
        <!-- Spacer Kosong 1 Baris -->
        <tr>
            <td colspan="16"></td>
        </tr>

        <!-- Header Tabel (Dengan Border) -->
        <tr>
            <th rowspan="2" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">NO</th>
            <th rowspan="2" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">JENIS MATERIIL</th>
            <th colspan="12" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">{{ strtoupper($selectedLabel) }}</th>
            <th rowspan="2" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">JUMLAH</th>
            <th rowspan="2" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">KET</th>
        </tr>
        <tr>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">JAN</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">FEB</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">MAR</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">APR</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">MEI</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">JUN</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">JUL</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">AGUST</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">SEPT</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">OKT</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">NOP</th>
            <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">DES</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalsBulan = array_fill(1, 12, 0);
            $grandTotal = 0;
        @endphp

        @foreach($destinations as $index => $dest)
            <tr>
                <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ strtoupper($dest->name) }}</td>
                
                @for($m = 1; $m <= 12; $m++)
                    @php 
                        $qtyM = $simakDataTab2[$dest->id][$m] ?? 0; 
                        $totalsBulan[$m] += $qtyM;
                    @endphp
                    <!-- Format angka murni tanpa separator untuk Excel -->
                    <td style="border: 1px solid #000000; text-align: center;">
                        {{ $qtyM > 0 ? $qtyM : '0' }}
                    </td>
                @endfor

                @php 
                    $rowTotal = $simakDataTab2[$dest->id]['jumlah'] ?? 0; 
                    $grandTotal += $rowTotal;
                @endphp
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">
                    {{ $rowTotal > 0 ? $rowTotal : '0' }}
                </td>
                <td style="border: 1px solid #000000;"></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="border: 1px solid #000000; text-align: center; font-weight: bold;">JUMLAH</td>
            @for($m = 1; $m <= 12; $m++)
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">
                    {{ $totalsBulan[$m] > 0 ? $totalsBulan[$m] : '0' }}
                </td>
            @endfor
            <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">
                {{ $grandTotal > 0 ? $grandTotal : '0' }}
            </td>
            <td style="border: 1px solid #000000;"></td>
        </tr>
    </tfoot>
</table>
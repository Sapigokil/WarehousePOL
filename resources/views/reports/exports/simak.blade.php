<table style="border-collapse: collapse; width: 100%;">
    <thead>
        <!-- Judul Laporan (Baris 1, Center) -->
        <tr>
            <td colspan="{{ count($simakHeaders) + 2 }}" style="text-align: center; font-weight: bold; font-family: Arial; font-size: 14px;">
                PENDISTRIBUSIAN MATERIEL {{ $monthName }} TAHUN {{ $year }}
            </td>
        </tr>
        
        <!-- Kop Surat (Baris 2, 3, 4, Kiri) -->
        <tr>
            <td colspan="{{ count($simakHeaders) + 2 }}" style="text-align: left; font-weight: bold; font-family: Arial; font-size: 11px;">KEPOLISIAN NEGARA REPUBLIK INDONESIA</td>
        </tr>
        <tr>
            <td colspan="{{ count($simakHeaders) + 2 }}" style="text-align: left; font-weight: bold; font-family: Arial; font-size: 11px;">DAERAH JAWA TENGAH</td>
        </tr>
        <tr>
            <td colspan="{{ count($simakHeaders) + 2 }}" style="text-align: left; font-weight: bold; font-family: Arial; font-size: 11px;">DIREKTORAT LALU LINTAS</td>
        </tr>
        
        <!-- Spacer Kosong 1 Baris -->
        <tr>
            <td colspan="{{ count($simakHeaders) + 2 }}"></td>
        </tr>

        <!-- Header Tabel (Dengan Border) -->
        <tr>
            <th rowspan="2" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">NO</th>
            <th rowspan="2" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">JENIS MATERIIL</th>
            <!-- Header Nama Bulan Gabungan (Colspan sesuai jumlah data mapping) -->
            <th colspan="{{ count($simakHeaders) }}" style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">{{ $monthName }}</th>
        </tr>
        <tr>
            <!-- Header Nama Materiil / Mapping -->
            @foreach($simakHeaders as $label)
                <th style="border: 1px solid #000000; text-align: center; vertical-align: middle; font-weight: bold;">{{ strtoupper($label) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @php
            // Persiapan Array untuk menghitung Total baris JUMLAH
            $totals = [];
            foreach($simakHeaders as $label) {
                $totals[$label] = 0;
            }
        @endphp

        <!-- Data per Tujuan Pengiriman -->
        @foreach($destinations as $index => $dest)
            <tr>
                <td style="border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000000;">{{ strtoupper($dest->name) }}</td>
                
                @foreach($simakHeaders as $label)
                    @php
                        $qty = $simakData[$dest->id][$label] ?? 0;
                        // Akumulasi QTY ke array Totals
                        $totals[$label] += $qty;
                    @endphp
                    <!-- Format angka murni tanpa separator untuk Excel -->
                    <td style="border: 1px solid #000000; text-align: center;">
                        {{ $qty > 0 ? $qty : '0' }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <!-- Baris Paling Bawah: JUMLAH -->
        <tr>
            <td colspan="2" style="border: 1px solid #000000; text-align: center; font-weight: bold;">JUMLAH</td>
            @foreach($simakHeaders as $label)
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">
                    {{ $totals[$label] > 0 ? $totals[$label] : '0' }}
                </td>
            @endforeach
        </tr>
    </tfoot>
</table>
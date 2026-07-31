<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Terima Keluar Tahun {{ $year }}</title>
    <style>
        /* PDF Scaling Optimization */
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: 'Arial', Helvetica, sans-serif; font-size: 9pt; color: #000; margin: 0; padding: 0; }
        
        .report-kop { text-transform: uppercase; font-weight: bold; font-size: 9pt; line-height: 1.2; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 3px; width: max-content; }
        .report-title { text-align: center; font-weight: bold; font-size: 11pt; text-transform: uppercase; margin-bottom: 15px; }
        
        table.table-data { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.table-data th, table.table-data td { border: 1px solid #000; padding: 4px; vertical-align: middle; }
        table.table-data th { text-align: center; font-weight: bold; background-color: #f0f0f0; }
        
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .bg-light { background-color: #f0f0f0; }
        
        .page-break { page-break-after: always; }
        .avoid-break { page-break-inside: avoid; }
    </style>
</head>
<body>

    @php
        // Deteksi jenis ekspor dari URL Route (pdf atau excel)
        $exportType = request()->route('type');
        
        $tables = [
            [
                'key' => 'tnkb_non_ev',
                'title' => 'DATA PENERIMAAN, PENDISTRIBUSIAN DAN SISA TNKB R.2 & R.4 NON LISTRIK<br>GUDANG DIT LANTAS POLDA JATENG TAHUN ' . $year
            ],
            [
                'key' => 'tnkb_ev',
                'title' => 'DATA PENERIMAAN, PENDISTRIBUSIAN TNKB R.2 & R.4 LISTRIK<br>GUDANG DIT LANTAS POLDA JATENG TAHUN ' . $year
            ],
            [
                'key' => 'tckb',
                'title' => 'DATA PENERIMAAN DAN PENDISTRIBUSIAN TCKB R.2 & R.4<br>GUDANG DIT LANTAS POLDA JATENG TAHUN ' . $year
            ]
        ];
    @endphp

    <!-- ============================================== -->
    <!-- BAGIAN 1: TABEL TNKB & TCKB                    -->
    <!-- ============================================== -->
    @foreach($tables as $tbl)
        @php $dataMatrix = $reportData[$tbl['key']]; @endphp
        
        <div class="report-kop">
            Kepolisian Negara Republik Indonesia<br>
            Daerah Jawa Tengah<br>
            Direktorat Lalu Lintas
        </div>
        
        <div class="report-title">{!! $tbl['title'] !!}</div>
        
        <table class="table-data">
            <thead>
                <tr>
                    <th rowspan="2" width="5%">NO.</th>
                    <th rowspan="2" width="15%">BULAN</th>
                    <th colspan="2">SISA AWAL</th>
                    <th colspan="2">P E N E R I M A A N</th>
                    <th colspan="2">PENDISTRIBUSIAN</th>
                    <th colspan="2">SISA GUDANG</th>
                </tr>
                <tr>
                    <th width="10%">R2</th>
                    <th width="10%">R4</th>
                    <th width="10%">R2</th>
                    <th width="10%">R4</th>
                    <th width="10%">R2</th>
                    <th width="10%">R4</th>
                    <th width="10%">R2</th>
                    <th width="10%">R4</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" class="fw-bold">SISA AKHIR BLN DES.{{ $year - 1 }}</td>
                    <td class="text-center bg-light"></td>
                    <td class="text-center bg-light"></td>
                    <td class="text-center bg-light"></td>
                    <td class="text-center bg-light"></td>
                    <td class="text-center bg-light"></td>
                    <td class="text-center bg-light"></td>
                    <td class="text-center fw-bold">{{ number_format($dataMatrix['R2']['sisa_awal_tahun'], 0, ',', '.') }}</td>
                    <td class="text-center fw-bold">{{ number_format($dataMatrix['R4']['sisa_awal_tahun'], 0, ',', '.') }}</td>
                </tr>

                @php
                    $totalInR2 = 0; $totalInR4 = 0;
                    $totalOutR2 = 0; $totalOutR4 = 0;
                @endphp

                @foreach($monthsName as $m => $monthName)
                    @php
                        $totalInR2 += $dataMatrix['R2']['months'][$m]['in'];
                        $totalInR4 += $dataMatrix['R4']['months'][$m]['in'];
                        $totalOutR2 += $dataMatrix['R2']['months'][$m]['out'];
                        $totalOutR4 += $dataMatrix['R4']['months'][$m]['out'];
                    @endphp
                    <tr>
                        <td class="text-center">{{ $m }}</td>
                        <td>{{ $monthName }}</td>
                        <td class="text-end">{{ number_format($dataMatrix['R2']['months'][$m]['sisa_awal'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMatrix['R4']['months'][$m]['sisa_awal'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMatrix['R2']['months'][$m]['in'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMatrix['R4']['months'][$m]['in'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMatrix['R2']['months'][$m]['out'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMatrix['R4']['months'][$m]['out'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMatrix['R2']['months'][$m]['sisa_gudang'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMatrix['R4']['months'][$m]['sisa_gudang'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach

                <tr class="bg-light">
                    <td colspan="2" class="text-center fw-bold">JUMLAH</td>
                    <td></td>
                    <td></td>
                    <td class="text-end fw-bold">{{ number_format($totalInR2, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">{{ number_format($totalInR4, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">{{ number_format($totalOutR2, 0, ',', '.') }}</td>
                    <td class="text-end fw-bold">{{ number_format($totalOutR4, 0, ',', '.') }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Layout Tanda Tangan Terpisah Sesuai Jenis Ekspor -->
        <div class="avoid-break">
            @if($exportType == 'excel')
                <table style="width: 100%; border: none; font-size: 9pt;">
                    <tr>
                        <td colspan="7" style="border: none;"></td>
                        <td colspan="3" style="border: none; text-align: center; vertical-align: top;">
                            Semarang, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $monthsName[date('n')] }} {{ date('Y') }}<br>
                            {{ $signatureSettings['Jabatan_tnkb_ttd'] ?? 'KASI FASMAT SBST' }}
                            <br><br><br>
                            <span style="text-decoration: underline; font-weight: bold;">{{ $signatureSettings['Nama_tnkb_ttd'] ?? 'NAMA PENANDATANGAN' }}</span><br>
                            {{ $signatureSettings['pangkatnrp_tnkb_ttd'] ?? 'PANGKAT / NRP' }}
                        </td>
                    </tr>
                </table>
            @else
                <table style="width: 100%; border: none; font-size: 9pt;">
                    <tr>
                        <td style="border: none; width: 65%;"></td>
                        <td style="border: none; width: 35%; text-align: center; vertical-align: top;">
                            Semarang, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $monthsName[date('n')] }} {{ date('Y') }}<br>
                            {{ $signatureSettings['Jabatan_tnkb_ttd'] ?? 'KASI FASMAT SBST' }}
                            <br><br><br>
                            <span style="text-decoration: underline; font-weight: bold;">{{ $signatureSettings['Nama_tnkb_ttd'] ?? 'NAMA PENANDATANGAN' }}</span><br>
                            {{ $signatureSettings['pangkatnrp_tnkb_ttd'] ?? 'PANGKAT / NRP' }}
                        </td>
                    </tr>
                </table>
            @endif
        </div>

        <div class="page-break"></div>
    @endforeach

    <!-- ============================================== -->
    <!-- BAGIAN 2: TABEL SBST                           -->
    <!-- ============================================== -->
    @foreach($sbstData as $matId => $dataMat)
        <div class="report-kop">
            Kepolisian Negara Republik Indonesia<br>
            Daerah Jawa Tengah<br>
            Direktorat Lalu Lintas
        </div>
        
        <div class="report-title">
            PENERIMAAN & PENDISTRIBUSIAN {{ strtoupper($dataMat['judul']) }}<br>
            TAHUN {{ $year }}
        </div>
        
        <table class="table-data">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="15%">TANGGAL</th>
                    <th width="12%">SISA LALU</th>
                    <th width="15%">TERIMA DR MABES</th>
                    <th width="15%">JUMLAH</th>
                    <th width="15%">PENDISTRIBUSIAN</th>
                    <th width="12%">SISA</th>
                    <th width="11%">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" class="fw-bold">SISA AKHIR BLN DES.{{ $year - 1 }}</td>
                    <td class="bg-light"></td>
                    <td class="bg-light"></td>
                    <td class="bg-light"></td>
                    <td class="bg-light"></td>
                    <td class="text-end fw-bold">{{ number_format($dataMat['sisa_awal_tahun'], 0, ',', '.') }}</td>
                    <td class="bg-light"></td>
                </tr>

                @php
                    $totalIn = 0;
                    $totalOut = 0;
                @endphp

                @foreach($monthsName as $m => $monthName)
                    @php
                        $totalIn += $dataMat['months'][$m]['in'];
                        $totalOut += $dataMat['months'][$m]['out'];
                    @endphp
                    <tr>
                        <td class="text-center">{{ $m }}</td>
                        <td>{{ $monthName }}</td>
                        <td class="text-end">{{ number_format($dataMat['months'][$m]['sisa_lalu'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMat['months'][$m]['in'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMat['months'][$m]['jumlah'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMat['months'][$m]['out'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($dataMat['months'][$m]['sisa'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                @endforeach

                <tr class="bg-light">
                    <td colspan="2" class="text-center fw-bold">JUMLAH</td>
                    <td></td>
                    <td class="text-end fw-bold">{{ number_format($totalIn, 0, ',', '.') }}</td>
                    <td></td>
                    <td class="text-end fw-bold">{{ number_format($totalOut, 0, ',', '.') }}</td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- Layout Tanda Tangan Terpisah Sesuai Jenis Ekspor -->
        <div class="avoid-break">
            @if($exportType == 'excel')
                <table style="width: 100%; border: none; font-size: 9pt;">
                    <tr>
                        <td colspan="5" style="border: none;"></td>
                        <td colspan="3" style="border: none; text-align: center; vertical-align: top;">
                            Semarang, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $monthsName[date('n')] }} {{ date('Y') }}<br>
                            {{ $signatureSettings['Jabatan_tnkb_ttd'] ?? 'KASI FASMAT SBST' }}
                            <br><br><br>
                            <span style="text-decoration: underline; font-weight: bold;">{{ $signatureSettings['Nama_tnkb_ttd'] ?? 'NAMA PENANDATANGAN' }}</span><br>
                            {{ $signatureSettings['pangkatnrp_tnkb_ttd'] ?? 'PANGKAT / NRP' }}
                        </td>
                    </tr>
                </table>
            @else
                <table style="width: 100%; border: none; font-size: 9pt;">
                    <tr>
                        <td style="border: none; width: 65%;"></td>
                        <td style="border: none; width: 35%; text-align: center; vertical-align: top;">
                            Semarang, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $monthsName[date('n')] }} {{ date('Y') }}<br>
                            {{ $signatureSettings['Jabatan_tnkb_ttd'] ?? 'KASI FASMAT SBST' }}
                            <br><br><br>
                            <span style="text-decoration: underline; font-weight: bold;">{{ $signatureSettings['Nama_tnkb_ttd'] ?? 'NAMA PENANDATANGAN' }}</span><br>
                            {{ $signatureSettings['pangkatnrp_tnkb_ttd'] ?? 'PANGKAT / NRP' }}
                        </td>
                    </tr>
                </table>
            @endif
        </div>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
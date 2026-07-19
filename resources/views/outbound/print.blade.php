<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak SPPM - {{ $sppm->sppm_no }}</title>
    <style>
        /* Menggunakan Arial / sans-serif */
        body { 
            background-color: #f1f5f9; 
            font-family: Arial, Helvetica, sans-serif; 
            margin: 0; 
            color: #000; 
            -webkit-text-size-adjust: 100%;
        }

        .paper-preview {
            background-color: #fff;
            width: 210mm; /* Standar A4 / F4 */
            min-height: 297mm;
            margin: 20px auto;
            padding: 15mm 20mm;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        /* Kop Surat */
        .header-table { width: 100%; margin-bottom: 25px; border: none; }
        .kop-kiri-container { 
            display: inline-block; 
            text-align: center; 
            border-bottom: 1px solid #000; 
            padding-bottom: 4px; 
        }
        .kop-kiri-text { font-size: 12pt; font-weight: bold; line-height: 1.2; }
        .kop-kanan { font-size: 10pt; line-height: 1.2; text-align: left; vertical-align: top; }

        /* Judul Surat */
        .surat-title-block { text-align: center; margin-bottom: 25px; }
        .surat-title { font-weight: bold; font-size: 14pt; margin-bottom: 2px; }
        .surat-subtitle { font-weight: bold; font-size: 14pt; margin-bottom: 5px; }
        .surat-no { font-size: 12pt; }
        
        /* Informasi Tujuan */
        .info-table { width: 100%; margin-bottom: 20px; font-size: 11pt; border: none;}
        .info-table td { padding: 3px 2px; vertical-align: top; }
        
        /* Class untuk titik-titik (dots) otomatis terpotong di batas kanan */
        .dots-line {
            display: block;
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
        }

        /* Tabel Utama Bersih */
        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 30px; 
            font-size: 11pt; 
            border-bottom: 1px solid #000; 
        }
        .data-table th { 
            border: 1px solid #000; 
            padding: 6px 8px; 
            text-align: center; 
            font-weight: bold; 
            vertical-align: middle; 
        }
        .data-table td { 
            border-left: 1px solid #000; 
            border-right: 1px solid #000; 
            border-top: none; 
            border-bottom: none; 
            padding: 6px 8px; 
            vertical-align: top;
        }
        
        /* Area Tanda Tangan */
        .signature-table { width: 100%; font-size: 12pt; margin-top: 30px; page-break-inside: avoid; border: none; }
        .signature-table td { vertical-align: top; }
        .sign-box { text-align: left; }
        .sign-box-center { text-align: center; }
        .sign-space { height: 70px; }

        @media print {
            body { background-color: transparent; }
            .paper-preview { box-shadow: none; margin: 0; padding: 0; width: 100%; min-height: auto; }
            @page { margin: 15mm 20mm; }
        }
    </style>
</head>
<body>

    @php 
        function terbilang($n) {
            $bil = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
            $n = (int)$n;
            if ($n <= 0) return ""; 
            if ($n < 12) return $bil[$n];
            if ($n < 20) return terbilang($n - 10) . " belas";
            if ($n < 100) return terbilang(floor($n / 10)) . " puluh " . ($bil[$n % 10] === "" ? "" : " " . $bil[$n % 10]);
            if ($n < 200) return "seratus " . ($n - 100 === 0 ? "" : terbilang($n - 100));
            if ($n < 1000) return terbilang(floor($n / 100)) . " ratus " . ($n % 100 === 0 ? "" : " " . terbilang($n % 100));
            if ($n < 2000) return "seribu " . ($n - 1000 === 0 ? "" : terbilang($n - 1000));
            if ($n < 1000000) return terbilang(floor($n / 1000)) . " ribu " . ($n % 1000 === 0 ? "" : " " . terbilang($n % 1000));
            if ($n < 1000000000) return terbilang(floor($n / 1000000)) . " juta " . ($n % 1000000 === 0 ? "" : " " . terbilang($n % 1000000));
            return "";
        }

        $formatSeri = function($prefix, $start, $end) {
            if (is_null($start) && is_null($end)) return '-';
            $padAndDot = function($num) {
                $s = str_pad($num ?? 0, 9, '0', STR_PAD_LEFT);
                return substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3);
            };
            $p = $prefix ? $prefix . ' ' : '';
            return "NO : " . $p . $padAndDot($start) . " - " . $padAndDot($end);
        };
    @endphp

    <div class="paper-preview">
        <!-- HEADER KOP SURAT -->
        <table class="header-table">
            <tr>
                <td style="vertical-align: top; width: 80%; text-align: left; padding: 0;">
                    <div class="kop-kiri-container">
                        <span class="kop-kiri-text">
                            KEPOLISIAN NEGARA REPUBLIK INDONESIA<br>
                            DAERAH JAWA TENGAH<br>
                            DIREKTORAT LALU LINTAS
                        </span>
                    </div>
                </td>
                <td class="kop-kanan" style="width: 20%; padding: 0;">
                    Bentuk : 007/LOG/POLRI<br>
                    Lembar ke 
                </td>
            </tr>
        </table>

        <!-- TITLE -->
        <div class="surat-title-block">
            <div class="surat-title">SURAT PERINTAH PENGELUARAN MATERIEL</div>
            <div class="surat-subtitle">( S.P.P.M. )</div>
            <div class="surat-no">Nomor : {{ $sppm->sppm_no }}</div>
        </div>

        <!-- INFO PENGIRIMAN -->
        <table class="info-table">
            <tr>
                <td width="40%">Kepada Pa/Ba Gudang Materiel Golongan</td>
                <td width="2%">:</td>
                <td>FASMAT SBST DITLANTAS POLDA JATENG</td>
            </tr>
            <tr>
                <td>Diperintahkan untuk mendistribusikan kepada</td>
                <td>:</td>
                <td>{{ strtoupper($sppm->destination->name) }}</td>
            </tr>
            <tr>
                <td>Berdasarkan</td>
                <td>:</td>
                <td>
                    <div class="dots-line">
                        {{ $sppm->keterangan ? $sppm->keterangan . ' ' . str_repeat('.', 110) : str_repeat('.', 110) }}
                    </div>
                </td>
            </tr>
            <tr>
                <!-- Baris full titik-titik (Berdasarkan Lanjutan) -->
                <td colspan="3">
                    <div class="dots-line">
                        {{ str_repeat('.', 192) }}
                    </div>
                </td>
            </tr>
        </table>

        <div style="font-size: 11pt; margin-bottom: 5px;">Materiel sebagai berikut :</div>

        <!-- TABEL MATERIEL -->
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" width="5%">No.<br>Urut</th>
                    <th rowspan="2" width="28%">Nama dan Kode Materiel</th>
                    <th rowspan="2" width="8%">Satuan</th>
                    <th colspan="2" width="24%">Banyaknya</th>
                    <th colspan="2" width="20%">Harga (Rp.)</th>
                    <th rowspan="2" width="15%">Keterangan</th>
                </tr>
                <tr>
                    <th width="10%">Angka</th>
                    <th>Huruf</th>
                    <th>Satuan</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($sppm->details as $detail)
                <tr>
                    <td style="text-align: center;">{{ $no++ }}</td>
                    <td>
                        {{ strtoupper($detail->material->name) }}
                        
                        @if($detail->material->pakai_seri == 1)
                            @php
                                $outStocks = App\Models\OutStock::whereHas('outLog', function($q) use ($sppm) {
                                    $q->where('out_sppm_id', $sppm->id);
                                })->whereHas('stock', function($q) use ($detail) {
                                    $q->where('material_id', $detail->material_id);
                                })->get();
                            @endphp
                            @foreach($outStocks as $st)
                                @if($st->seri_awal !== null)
                                    <div style="font-size: 8.5pt; margin-top: 3px; margin-bottom: 30px;">
                                        {!! $formatSeri($st->prefix, $st->seri_awal, $st->seri_akhir) !!}
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </td>
                    <td style="text-align: center;">{{ strtoupper($detail->material->satuan) }}</td>
                    <td style="text-align: center;">{{ number_format($detail->target_qty, 0, ',', '.') }}</td>
                    <!-- Kolom huruf di center sesuai request -->
                    <td style="text-align: center;">{{ ucfirst(terbilang($detail->target_qty)) }}</td>
                    <td style="text-align: right;">{{ $detail->harga_satuan > 0 ? number_format($detail->harga_satuan, 0, ',', '.') : '-' }}</td>
                    <td style="text-align: right;">{{ $detail->harga_total > 0 ? number_format($detail->harga_total, 0, ',', '.') : '-' }}</td>
                    <td></td>
                </tr>
                @endforeach
                
                <!-- Baris Spacer Pembentuk Blanko -->
                <tr>
                    <td style="height: 120px;"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <!-- AREA TANDA TANGAN (Lebar 50% 0% 50%) -->
        <table class="signature-table">
            <tr>
                <td width="50%" class="sign-box">
                    <div style="margin-bottom: 5px;">Untuk Penerima :</div>
                    <table width="100%" style="font-size: 12pt; border: none;">
                        <tr>
                            <td width="30%" style="padding: 2px 0;">Nama</td>
                            <td width="5%" style="padding: 2px 0;">:</td>
                            <td style="padding: 2px 0;">{{ strtoupper($sppm->destination->nama ?? str_repeat('.', 20)) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">Pangkat/ NRP</td>
                            <td style="padding: 2px 0;">:</td>
                            <td style="padding: 2px 0;">{{ strtoupper($sppm->destination->pangkat_nrp ?? str_repeat('.', 20)) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">Jabatan</td>
                            <td style="padding: 2px 0;">:</td>
                            <td style="padding: 2px 0;">{{ strtoupper($sppm->destination->jabatan ?? str_repeat('.', 20)) }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 2px 0;">Tanda Tangan</td>
                            <td style="padding: 2px 0;">:</td>
                            <td style="padding: 2px 0;">..............................</td>
                        </tr>
                    </table>
                </td>
                <td width="0%"></td>
                <td width="50%" class="sign-box-center">
                    Semarang, {{ \Carbon\Carbon::parse($sppm->sppm_date)->translatedFormat('d F Y') }}<br>
                    <br>
                    DIRLANTAS POLDA JAWA TENGAH<br>
                    <br>
                    <div class="sign-space"></div>
                    <span style="text-decoration: underline; font-weight: bold;">{{ strtoupper($signatory['name']) }}</span><br>
                    {{ strtoupper($signatory['position']) }}
                </td>
            </tr>
        </table>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500); 
        };
    </script>
</body>
</html>
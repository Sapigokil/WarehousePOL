<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak SPPM - {{ $sppm->sppm_no }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Times+New+Roman:wght@400;700&display=swap');
        
        body { background-color: transparent; font-family: 'Times New Roman', Times, serif; margin: 0; color: #000; overflow-x: hidden; }
        
        #paperWrapper { width: 100%; display: flex; justify-content: center; padding: 20px 0; transition: height 0.3s ease; }

        /* Container Kertas */
        .paper-container { background-color: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: 20mm; transform-origin: top center; transition: transform 0.3s ease; }
        
        /* Ukuran Kertas Dinamis (Ukuran Fisik Asli) */
        .paper-A4.portrait { width: 210mm; min-height: 297mm; }
        .paper-A4.landscape { width: 297mm; min-height: 210mm; }
        .paper-F4.portrait { width: 215.9mm; min-height: 330.2mm; }
        .paper-F4.landscape { width: 330.2mm; min-height: 215.9mm; }

        /* Typography Surat */
        .kop-surat { border-bottom: 3px solid #000; padding-bottom: 10px; margin-bottom: 20px; text-align: center; }
        .kop-surat h4 { font-weight: bold; margin: 0; font-size: 16pt; }
        .kop-surat p { margin: 0; font-size: 10pt; }
        .surat-title { text-align: center; font-weight: bold; text-decoration: underline; font-size: 14pt; margin-bottom: 2px; }
        .surat-no { text-align: center; font-size: 11pt; margin-bottom: 30px; }
        
        .info-table { width: 100%; margin-bottom: 20px; font-size: 11pt; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 11pt; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 6px 10px; vertical-align: middle; }
        .data-table th { text-align: center; font-weight: bold; background-color: #f8f9fa; }
        
        .signature-area { width: 100%; font-size: 11pt; text-align: center; margin-top: 40px; page-break-inside: avoid; }
        .signature-area td { padding-top: 80px; }

        /* Reset untuk proses Printing Aktual */
        @media print {
            body { padding: 0; background-color: transparent; }
            #paperWrapper { padding: 0; display: block; height: auto !important; }
            .paper-container { box-shadow: none; padding: 0; width: 100% !important; min-height: auto !important; transform: none !important; }
            @page { margin: 15mm; }
        }
    </style>
</head>
<body id="printBody" class="paper-A4 portrait">

    <div id="paperWrapper">
        <div class="paper-container" id="documentContent">
            <!-- KOP SURAT -->
            <div class="kop-surat">
                <h4>KEPOLISIAN NEGARA REPUBLIK INDONESIA</h4>
                <h4>DAERAH JAWA TENGAH</h4>
                <p>Jalan Pahlawan No. 1, Semarang 50243</p>
            </div>

            <div class="surat-title">SURAT PERINTAH PENGIRIMAN MATERIIL</div>
            <div class="surat-no">Nomor: {{ $sppm->sppm_no }}</div>

            <table class="info-table">
                <tr>
                    <td width="20%">Berdasarkan</td>
                    <td width="2%">:</td>
                    <td>Arahan / Kebutuhan Distribusi Logistik</td>
                </tr>
                <tr>
                    <td>Tujuan / Kepada</td>
                    <td>:</td>
                    <td><strong>{{ $sppm->destination->name }}</strong><br>
                        @if($sppm->destination->nama)
                            (UP: {{ $sppm->destination->nama }} - {{ $sppm->destination->pangkat_nrp ?? '' }})
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>Tanggal Keluar</td>
                    <td>:</td>
                    <td>{{ \Carbon\Carbon::parse($sppm->sppm_date)->translatedFormat('d F Y') }}</td>
                </tr>
                <tr>
                    <td>Keterangan</td>
                    <td>:</td>
                    <td>{{ $sppm->keterangan ?? '-' }}</td>
                </tr>
            </table>

            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="35%">Nama Barang / Materiil</th>
                        <th width="10%">Satuan</th>
                        <th width="15%">Banyaknya</th>
                        <th width="35%">Keterangan / Nomor Seri</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $no = 1; 
                        $formatSeri = function($prefix, $start, $end) {
                            if (is_null($start) && is_null($end)) return '-';
                            $padAndDot = function($num) {
                                $s = str_pad($num ?? 0, 9, '0', STR_PAD_LEFT);
                                return substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3);
                            };
                            $p = $prefix ? $prefix . ' ' : '';
                            return $p . $padAndDot($start) . " s/d " . $padAndDot($end);
                        };
                    @endphp

                    @foreach($sppm->details as $detail)
                    <tr>
                        <td style="text-align: center;">{{ $no++ }}</td>
                        <td>
                            <strong>{{ $detail->material->name }}</strong>
                            @if($detail->material->code) <br><small>Kode: {{ $detail->material->code }}</small> @endif
                        </td>
                        <td style="text-align: center;">{{ $detail->material->satuan }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ number_format($detail->target_qty, 0, ',', '.') }}</td>
                        <td>
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
                                        <div style="font-size: 9pt;">&bull; {!! $formatSeri($st->prefix, $st->seri_awal, $st->seri_akhir) !!} ({{ $st->qty_keluar }} {{ $detail->material->satuan }})</div>
                                    @endif
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="signature-area">
                <tr>
                    <td width="33%">
                        Yang Menerima,<br>
                        <strong>{{ $sppm->destination->name }}</strong>
                    </td>
                    <td width="34%">
                        Mengetahui,<br>
                        <strong>Kepala Gudang</strong>
                    </td>
                    <td width="33%">
                        Semarang, {{ \Carbon\Carbon::parse($sppm->sppm_date)->translatedFormat('d F Y') }}<br>
                        Yang Menyerahkan,
                    </td>
                </tr>
                <tr>
                    <td>( ......................................... )</td>
                    <td>( ......................................... )</td>
                    <td>( <strong>{{ $sppm->creator->name ?? '.......................' }}</strong> )</td>
                </tr>
            </table>
        </div>
    </div>

    <script>
        // Logika Auto-Scaling Dinamis untuk Preview di Perangkat Berbeda
        function applyScaling() {
            // Hindari scaling saat sedang dicetak fisik (Ctrl+P)
            if (window.matchMedia('print').matches) {
                document.getElementById('documentContent').style.transform = 'none';
                return;
            }

            const paper = document.getElementById('documentContent');
            const wrapper = document.getElementById('paperWrapper');
            
            // Reset dulu ke ukuran asli untuk kalkulasi yang benar
            paper.style.transform = 'none';
            wrapper.style.height = 'auto';

            const paperWidth = paper.offsetWidth;
            const windowWidth = window.innerWidth;
            const padding = 40; // Margin aman kiri-kanan
            
            // Jika ukuran kertas melebihi lebar layar, kecilkan kertasnya
            if (paperWidth > (windowWidth - padding)) {
                const scale = (windowWidth - padding) / paperWidth;
                paper.style.transform = `scale(${scale})`;
                
                // Sesuaikan tinggi container luar agar tidak ada white-space kosong di bawah
                const scaledHeight = paper.offsetHeight * scale;
                wrapper.style.height = `${scaledHeight}px`;
            }
        }

        // Jalankan saat layar di-resize
        window.addEventListener('resize', applyScaling);

        window.addEventListener('message', function(event) {
            const data = event.data;

            if (data.action === 'changeLayout') {
                const body = document.getElementById('printBody');
                body.className = `paper-${data.size} ${data.orientation}`;
                
                // Beri sedikit jeda agar CSS kertas ter-render sebelum di-scaling ulang
                setTimeout(applyScaling, 100);
            }

            if (data.action === 'print') {
                // Reset scale sebelum print
                document.getElementById('documentContent').style.transform = 'none';
                document.getElementById('paperWrapper').style.height = 'auto';
                window.print();
                setTimeout(applyScaling, 500); // Kembalikan scale setelah dialog print tertutup
            }

            if (data.action === 'savePdf') {
                // Reset scale agar resolusi PDF HD (tidak blur)
                document.getElementById('documentContent').style.transform = 'none';
                
                const element = document.getElementById('documentContent');
                const opt = {
                    margin:       10,
                    filename:     'SPPM_{{ $sppm->sppm_no }}.pdf',
                    image:        { type: 'jpeg', quality: 0.98 },
                    html2canvas:  { scale: 2 },
                    jsPDF:        { unit: 'mm', format: data.size.toLowerCase(), orientation: data.orientation }
                };
                
                html2pdf().set(opt).from(element).save().then(() => {
                    // Kembalikan scale setelah PDF selesai di-generate
                    applyScaling();
                });
            }
            
            if (data.action === 'copyText') {
                const element = document.getElementById('documentContent');
                navigator.clipboard.writeText(element.innerText).then(() => {
                    window.parent.postMessage({ status: 'copied' }, '*');
                }).catch(err => {
                    console.error('Gagal menyalin teks', err);
                });
            }
        });

        // Terapkan scaling pada load awal
        window.onload = applyScaling;
    </script>
</body>
</html>
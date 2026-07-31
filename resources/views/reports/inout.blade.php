@extends('layouts.app')
@section('title', 'Data Terima Keluar')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; background: linear-gradient(135deg, #1e40af, #3b82f6); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.15; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }

    /* Custom Tabs */
    .custom-tabs .nav-link { color: #64748b; font-weight: 600; border: none; border-bottom: 3px solid transparent; padding: 12px 25px; transition: all 0.2s; border-radius: 0; }
    .custom-tabs .nav-link:hover { color: #3b82f6; border-bottom-color: #bfdbfe; }
    .custom-tabs .nav-link.active { color: #1e40af; border-bottom-color: #1e40af; background: transparent; }
    
    /* Report Table Styling (Excel Like) */
    .report-table-wrapper { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .report-kop { text-transform: uppercase; font-weight: bold; font-size: 0.8rem; line-height: 1.2; margin-bottom: 20px; color: #000; border-bottom: 2px solid #000; padding-bottom: 5px; width: max-content; }
    .report-title { text-align: center; font-weight: bold; font-size: 1.1rem; text-transform: uppercase; color: #000; margin-bottom: 20px; }
    
    .table-excel { width: 100%; border-collapse: collapse; font-family: 'Arial', sans-serif; font-size: 0.8rem; color: #000; }
    .table-excel th, .table-excel td { border: 1px solid #000; padding: 6px 10px; vertical-align: middle; }
    .table-excel thead th { text-align: center; font-weight: bold; background-color: #f8fafc; }
    .table-excel tbody td.text-center { text-align: center; }
    .table-excel tbody td.text-end { text-align: right; }
    .table-excel tbody td.fw-bold { font-weight: bold; }
    .table-excel .row-total td { font-weight: bold; background-color: #f1f5f9; }
    
    .signature-area { display: flex; justify-content: flex-end; margin-top: 30px; font-family: 'Arial', sans-serif; font-size: 0.85rem; color: #000; }
    .signature-box { text-align: center; width: 300px; }
    .signature-name { text-decoration: underline; font-weight: bold; margin-bottom: 2px; margin-top: 60px; }
</style>
@endpush

@section('content')

<!-- Notifikasi Error jika DOMPDF belum terinstall -->
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-3 fw-bold" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2 fs-5"></i>{{ session('error') }}
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-file-invoice header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-line me-2"></i> Laporan Data Terima Keluar</h4>
        <p class="mb-0 text-white-50 small">Rekapitulasi penerimaan dan pendistribusian materiil TNKB & SBST per bulan.</p>
    </div>
    <div class="header-content d-flex gap-2">
        <a href="{{ route('report.inout.settings') }}" class="btn btn-light fw-bold text-primary shadow-sm px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-cogs me-1"></i> Pengaturan Mapping
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <ul class="nav nav-tabs custom-tabs" id="reportTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-tnkb-tab" data-bs-toggle="tab" data-bs-target="#tab-tnkb" type="button" role="tab">
                    <i class="fa-solid fa-car-side me-2"></i> Data TNKB & TCKB
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-sbst-tab" data-bs-toggle="tab" data-bs-target="#tab-sbst" type="button" role="tab">
                    <i class="fa-solid fa-id-card me-2"></i> Data SBST
                </button>
            </li>
        </ul>
        
        <div class="d-flex align-items-center gap-3 mb-2">
            <!-- Form Pilih Tahun -->
            <form method="GET" action="{{ route('report.inout.index') }}" class="d-flex align-items-center gap-2 m-0">
                <label class="fw-bold text-secondary mb-0 small">Tahun:</label>
                <select name="year" class="form-select form-select-sm border-secondary" onchange="this.form.submit()" style="width: 100px;">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
            </form>
            
            <!-- Tombol Export -->
            <div class="border-start ps-3 d-flex gap-2">
                <a href="{{ route('report.inout.export', ['type' => 'pdf', 'year' => $year]) }}" class="btn btn-danger btn-sm fw-bold shadow-sm px-3">
                    <i class="fa-solid fa-file-pdf me-1"></i> Cetak PDF
                </a>
                <a href="{{ route('report.inout.export', ['type' => 'excel', 'year' => $year]) }}" class="btn btn-success btn-sm fw-bold shadow-sm px-3">
                    <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                </a>
            </div>
        </div>
    </div>
    
    <div class="card-body p-4 bg-light">
        <div class="tab-content" id="reportTabsContent">
            
            <!-- TAB 1: TNKB & TCKB -->
            <div class="tab-pane fade show active" id="tab-tnkb" role="tabpanel">
                @php
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

                @foreach($tables as $tbl)
                    @php $dataMatrix = $reportData[$tbl['key']]; @endphp
                    <div class="report-table-wrapper">
                        <div class="report-kop">
                            Kepolisian Negara Republik Indonesia<br>
                            Daerah Jawa Tengah<br>
                            Direktorat Lalu Lintas
                        </div>
                        
                        <div class="report-title">{!! $tbl['title'] !!}</div>
                        
                        <div class="table-responsive">
                            <table class="table-excel">
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

                                    <tr class="row-total">
                                        <td colspan="2" class="text-center">JUMLAH</td>
                                        <td></td>
                                        <td></td>
                                        <td class="text-end">{{ number_format($totalInR2, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($totalInR4, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($totalOutR2, 0, ',', '.') }}</td>
                                        <td class="text-end">{{ number_format($totalOutR4, 0, ',', '.') }}</td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="signature-area">
                            <div class="signature-box">
                                Semarang, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $monthsName[date('n')] }} {{ date('Y') }}<br>
                                {{ $signatureSettings['Jabatan_tnkb_ttd'] ?? 'KASI FASMAT SBST' }}
                                <div class="signature-name">{{ $signatureSettings['Nama_tnkb_ttd'] ?? 'NAMA PENANDATANGAN' }}</div>
                                {{ $signatureSettings['pangkatnrp_tnkb_ttd'] ?? 'PANGKAT / NRP' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- TAB 2: SBST -->
            <div class="tab-pane fade" id="tab-sbst" role="tabpanel">
                @if(empty($sbstData))
                    <div class="text-center py-5 text-muted">
                        <i class="fa-solid fa-folder-open fs-1 mb-3 opacity-25 d-block"></i>
                        <h5 class="fw-bold">Belum Ada Mapping SBST</h5>
                        <p>Silakan isi judul pada menu <a href="{{ route('report.inout.settings') }}">Pengaturan Mapping</a> terlebih dahulu.</p>
                    </div>
                @else
                    @foreach($sbstData as $matId => $dataMat)
                        <div class="report-table-wrapper">
                            <div class="report-kop">
                                Kepolisian Negara Republik Indonesia<br>
                                Daerah Jawa Tengah<br>
                                Direktorat Lalu Lintas
                            </div>
                            
                            <div class="report-title">
                                PENERIMAAN & PENDISTRIBUSIAN {{ strtoupper($dataMat['judul']) }}<br>
                                TAHUN {{ $year }}
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table-excel">
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

                                        <tr class="row-total">
                                            <td colspan="2" class="text-center">JUMLAH</td>
                                            <td></td>
                                            <td class="text-end">{{ number_format($totalIn, 0, ',', '.') }}</td>
                                            <td></td>
                                            <td class="text-end">{{ number_format($totalOut, 0, ',', '.') }}</td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="signature-area">
                                <div class="signature-box">
                                    Semarang, &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; {{ $monthsName[date('n')] }} {{ date('Y') }}<br>
                                    {{ $signatureSettings['Jabatan_tnkb_ttd'] ?? 'KASI FASMAT SBST' }}
                                    <div class="signature-name">{{ $signatureSettings['Nama_tnkb_ttd'] ?? 'NAMA PENANDATANGAN' }}</div>
                                    {{ $signatureSettings['pangkatnrp_tnkb_ttd'] ?? 'PANGKAT / NRP' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

        </div>
    </div>
</div>

@endsection
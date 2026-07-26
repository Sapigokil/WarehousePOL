@extends('layouts.app')
@section('title', 'Laporan Pendistribusian SIMAK')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; background: linear-gradient(135deg, #0f766e, #042f2e); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.15; pointer-events: none; }
    .header-content { position: relative; z-index: 2; }
    
    .table-simak th, .table-simak td { vertical-align: middle; border: 1px solid #dee2e6; }
    .table-simak thead th { background-color: #f8fafc; color: #334155; text-align: center; font-weight: 700; }
    
    .nav-tabs .nav-link { color: #475569; font-weight: 600; padding: 12px 20px; border: none; border-bottom: 3px solid transparent; }
    .nav-tabs .nav-link.active { color: #0f766e; border-bottom: 3px solid #0f766e; background-color: transparent; }
    .nav-tabs .nav-link:hover:not(.active) { border-bottom: 3px solid #cbd5e1; }
</style>
@endpush

@section('content')

<!-- Header Banner -->
<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-file-contract header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-contract me-2"></i> Laporan Pendistribusian MATERIEL (SIMAK)</h4>
        <p class="mb-0 text-white-50 small">Pantau distribusi materiil berdasarkan pengelompokan laporan SIMAK.</p>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-0">
        <!-- TAB NAVIGATION (Dibuat menjadi link agar Auto-Load saat diklik) -->
        <ul class="nav nav-tabs px-3 pt-2" id="simakTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="{{ route('reports.simak', ['tab' => '1', 'month' => $month, 'year' => $year]) }}" class="nav-link {{ $tab == '1' ? 'active' : '' }}">
                    <i class="fa-solid fa-calendar-days me-2"></i> Keseluruhan (Bulanan)
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="{{ route('reports.simak', ['tab' => '2', 'year' => $year]) }}" class="nav-link {{ $tab == '2' ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-line me-2"></i> Per Materiil (Tahunan)
                </a>
            </li>
        </ul>

        <div class="tab-content" id="simakTabContent">
            
            <!-- ====================================================================== -->
            <!-- TAB 1: KESELURUHAN (BULANAN) -->
            <!-- ====================================================================== -->
            @if($tab == '1')
            <div class="tab-pane fade show active p-4" id="mode1-pane" role="tabpanel" tabindex="0">
                
                <!-- Filter Tab 1 -->
                <form action="{{ route('reports.simak') }}" method="GET" class="mb-4">
                    <input type="hidden" name="tab" value="1">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-muted mb-1">Bulan</label>
                            <select name="month" class="form-select form-select-sm shadow-sm border-0 bg-light" onchange="this.form.submit()">
                                @php
                                    $months = [
                                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                                    ];
                                @endphp
                                @foreach($months as $key => $name)
                                    <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1">Tahun</label>
                            <select name="year" class="form-select form-select-sm shadow-sm border-0 bg-light" onchange="this.form.submit()">
                                @for($i = date('Y'); $i >= date('Y') - 3; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-7 d-flex align-items-end justify-content-end">
                            <a href="{{ route('reports.simak.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-sm btn-outline-success fw-bold px-3 shadow-sm" target="_blank">
                                <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Tabel Tab 1 -->
                <div class="table-responsive">
                    <table class="table table-simak table-hover mb-0" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 50px;">NO</th>
                                <th rowspan="2">JENIS MATERIIL</th>
                                <th colspan="{{ count($simakHeaders) }}">{{ strtoupper($months[$month]) }}</th>
                            </tr>
                            <tr>
                                @foreach($simakHeaders as $label)
                                    <th>{{ strtoupper($label) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalsTab1 = [];
                                foreach($simakHeaders as $label) {
                                    $totalsTab1[$label] = 0;
                                }
                            @endphp

                            @forelse($destinations as $index => $dest)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="fw-bold text-dark">{{ strtoupper($dest->name) }}</td>
                                    @foreach($simakHeaders as $label)
                                        @php
                                            $qty = $simakDataTab1[$dest->id][$label] ?? 0;
                                            $totalsTab1[$label] += $qty;
                                        @endphp
                                        <td class="text-center">{{ $qty > 0 ? number_format($qty, 0, ',', '.') : '0' }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($simakHeaders) + 2 }}" class="text-center py-3 text-muted">Data tujuan distribusi tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="2" class="text-center fw-bold">JUMLAH</td>
                                @foreach($simakHeaders as $label)
                                    <td class="text-center fw-bold text-primary">{{ $totalsTab1[$label] > 0 ? number_format($totalsTab1[$label], 0, ',', '.') : '0' }}</td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            <!-- ====================================================================== -->
            <!-- TAB 2: PER MATERIIL (TAHUNAN) -->
            <!-- ====================================================================== -->
            @if($tab == '2')
            <div class="tab-pane fade show active p-4" id="mode2-pane" role="tabpanel" tabindex="0">
                
                <!-- Filter Tab 2 -->
                <form action="{{ route('reports.simak') }}" method="GET" class="mb-4">
                    <input type="hidden" name="tab" value="2">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">Pilih Label SIMAK</label>
                            <select name="simak_label" class="form-select form-select-sm shadow-sm border-0 bg-light" onchange="this.form.submit()">
                                @foreach($simakHeaders as $label)
                                    <option value="{{ $label }}" {{ $selectedLabel == $label ? 'selected' : '' }}>{{ strtoupper($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-muted mb-1">Tahun</label>
                            <select name="year" class="form-select form-select-sm shadow-sm border-0 bg-light" onchange="this.form.submit()">
                                @for($i = date('Y'); $i >= date('Y') - 3; $i--)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end justify-content-end">
                            <a href="{{ route('reports.simak2.export', ['simak_label' => $selectedLabel, 'year' => $year]) }}" class="btn btn-sm btn-outline-success fw-bold px-3 shadow-sm" target="_blank">
                                <i class="fa-solid fa-file-excel me-1"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Tabel Tab 2 -->
                <div class="table-responsive">
                    <table class="table table-simak table-hover mb-0" style="font-size: 0.85rem; min-width: 1000px;">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 50px;">NO</th>
                                <th rowspan="2">JENIS MATERIIL</th>
                                <th colspan="12" class="text-center" style="letter-spacing: 1px;">{{ strtoupper($selectedLabel ?? 'PILIH MATERIIL') }}</th>
                                <th rowspan="2">JUMLAH</th>
                                <th rowspan="2">KET</th>
                            </tr>
                            <tr>
                                <th style="width: 60px;">JAN</th>
                                <th style="width: 60px;">FEB</th>
                                <th style="width: 60px;">MAR</th>
                                <th style="width: 60px;">APR</th>
                                <th style="width: 60px;">MEI</th>
                                <th style="width: 60px;">JUN</th>
                                <th style="width: 60px;">JUL</th>
                                <th style="width: 60px;">AGUST</th>
                                <th style="width: 60px;">SEPT</th>
                                <th style="width: 60px;">OKT</th>
                                <th style="width: 60px;">NOP</th>
                                <th style="width: 60px;">DES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Array untuk akumulasi jumlah per bulan di tfoot
                                $totalsBulan = array_fill(1, 12, 0);
                                $grandTotal = 0;
                            @endphp

                            @forelse($destinations as $index => $dest)
                                <tr>
                                    <td class="text-center">{{ $index + 1 }}</td>
                                    <td class="fw-bold text-dark">{{ strtoupper($dest->name) }}</td>
                                    
                                    @for($m = 1; $m <= 12; $m++)
                                        @php 
                                            $qtyM = $simakDataTab2[$dest->id][$m] ?? 0; 
                                            $totalsBulan[$m] += $qtyM;
                                        @endphp
                                        <td class="text-center">{{ $qtyM > 0 ? number_format($qtyM, 0, ',', '.') : '' }}</td>
                                    @endfor

                                    @php 
                                        $rowTotal = $simakDataTab2[$dest->id]['jumlah'] ?? 0; 
                                        $grandTotal += $rowTotal;
                                    @endphp
                                    <td class="text-center fw-bold">{{ $rowTotal > 0 ? number_format($rowTotal, 0, ',', '.') : '' }}</td>
                                    <td></td> <!-- Kolom KET Kosong -->
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="text-center py-3 text-muted">Data tujuan distribusi tidak ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="2" class="text-center fw-bold">JUMLAH</td>
                                @for($m = 1; $m <= 12; $m++)
                                    <td class="text-center fw-bold text-primary">{{ $totalsBulan[$m] > 0 ? number_format($totalsBulan[$m], 0, ',', '.') : '' }}</td>
                                @endfor
                                <td class="text-center fw-bold text-danger">{{ $grandTotal > 0 ? number_format($grandTotal, 0, ',', '.') : '' }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
            @endif

        </div>
    </div>
</div>
@endsection
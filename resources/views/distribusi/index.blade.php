@extends('layouts.app')
@section('title', 'Laporan Distribusi Barang')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; background: linear-gradient(135deg, #1e40af, #3b82f6); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.15; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }
    
    .table-dense { width: 100%; border-collapse: collapse; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .table-dense thead { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .table-dense thead th { color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 15px; font-weight: 700; vertical-align: middle; text-align: center; border-right: 1px solid #e2e8f0; }
    .table-dense tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
    .table-dense tbody tr:hover { background-color: #f8fafc; }
    .table-dense td { padding: 10px 15px; vertical-align: middle; color: #334155; font-size: 0.85rem; border-right: 1px solid #f1f5f9; text-align: center; }
    .table-dense td.text-start { text-align: left; }
    .table-dense tfoot { background-color: #f1f5f9; font-weight: bold; }
    .table-dense tfoot td { padding: 12px 15px; border-right: 1px solid #e2e8f0; }
    
    /* Styling Nav Tabs Custom */
    .custom-tabs .nav-link { color: #64748b; font-weight: 600; border: none; border-bottom: 3px solid transparent; padding: 12px 20px; transition: all 0.2s; }
    .custom-tabs .nav-link:hover { color: #3b82f6; border-bottom-color: #bfdbfe; }
    .custom-tabs .nav-link.active { color: #1e40af; border-bottom-color: #1e40af; background: transparent; }
</style>
@endpush

@section('content')

<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-chart-pie header-banner-icon"></i>
    
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-area me-2"></i> Laporan Distribusi</h4>
        <p class="mb-0 text-white-50 small">Peta sebaran material utama (Main Material) ke berbagai kesatuan.</p>
    </div>
    
    <!-- Tambahan Tombol Laporan -->
    <div class="header-content d-flex gap-2">
        <a href="{{ route('reports.simak') }}" class="btn btn-light fw-bold text-dark shadow-sm px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-file-contract text-primary me-1"></i> Lihat Laporan SIMAK
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0 px-4">
        <ul class="nav nav-tabs custom-tabs" id="distribusiTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab == 'tab1' ? 'active' : '' }}" id="tab1-tab" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab" aria-controls="tab1" aria-selected="{{ $activeTab == 'tab1' ? 'true' : 'false' }}">
                    <i class="fa-solid fa-layer-group me-2"></i> Data 1: Rekap Kategori per Tahun
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab == 'tab2' ? 'active' : '' }}" id="tab2-tab" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab" aria-controls="tab2" aria-selected="{{ $activeTab == 'tab2' ? 'true' : 'false' }}">
                    <i class="fa-solid fa-calendar-days me-2"></i> Data 2: Rekap Bulanan per Kategori
                </button>
            </li>
        </ul>
    </div>
    
    <div class="card-body p-4">
        <div class="tab-content" id="distribusiTabsContent">
            
            {{-- ========================================================== --}}
            {{-- TAB 1: DATA REKAP KATEGORI TAHUNAN --}}
            {{-- ========================================================== --}}
            <div class="tab-pane fade {{ $activeTab == 'tab1' ? 'show active' : '' }}" id="tab1" role="tabpanel" aria-labelledby="tab1-tab">
                
                <form method="GET" action="{{ route('distribusi.index') }}" class="mb-4 p-3 bg-light rounded border d-flex align-items-center gap-3 w-50">
                    <input type="hidden" name="active_tab" value="tab1">
                    <label class="fw-bold text-secondary mb-0">Filter Data :</label>
                    <select name="year1" class="form-select w-auto" onchange="this.form.submit()">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ $year1 == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                        @endforeach
                    </select>
                </form>

                <div class="table-responsive">
                    <table class="table-dense">
                        <thead>
                            <tr>
                                <th class="text-start" style="width: 25%;">Kesatuan / Penerima</th>
                                @foreach($categories as $cat)
                                    <th>{{ strtoupper($cat->name) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                // Array untuk menyimpan Total Bawah
                                $grandTotal1 = [];
                                foreach($categories as $cat) { $grandTotal1[$cat->id] = 0; }
                            @endphp

                            @foreach($destinations as $dest)
                                <tr>
                                    <td class="text-start fw-bold text-dark">
                                        <i class="fa-solid fa-map-location-dot text-muted me-2 opacity-50"></i>{{ $dest->name }}
                                    </td>
                                    @foreach($categories as $cat)
                                        @php
                                            $val = $data1[$dest->id][$cat->id] ?? 0;
                                            $grandTotal1[$cat->id] += $val;
                                        @endphp
                                        <td>
                                            @if($val > 0)
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fs-6 px-2 py-1">
                                                    {{ number_format($val, 0, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-muted opacity-25">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-start text-uppercase">Total Distribusi Keseluruhan</td>
                                @foreach($categories as $cat)
                                    <td class="text-primary fs-6">
                                        {{ number_format($grandTotal1[$cat->id], 0, ',', '.') }}
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- ========================================================== --}}
            {{-- TAB 2: DATA REKAP BULANAN --}}
            {{-- ========================================================== --}}
            <div class="tab-pane fade {{ $activeTab == 'tab2' ? 'show active' : '' }}" id="tab2" role="tabpanel" aria-labelledby="tab2-tab">
                
                <form method="GET" action="{{ route('distribusi.index') }}" class="mb-4 p-3 bg-light rounded border d-flex align-items-center gap-3">
                    <input type="hidden" name="active_tab" value="tab2">
                    <label class="fw-bold text-secondary mb-0">Filter Data :</label>
                    <select name="year2" class="form-select w-auto" onchange="this.form.submit()">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ $year2 == $yr ? 'selected' : '' }}>Tahun {{ $yr }}</option>
                        @endforeach
                    </select>
                    <select name="category2" class="form-select w-auto" onchange="this.form.submit()">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $category2 == $cat->id ? 'selected' : '' }}>Kategori: {{ strtoupper($cat->name) }}</option>
                        @endforeach
                    </select>
                </form>

                <div class="table-responsive">
                    <table class="table-dense">
                        <thead>
                            <tr>
                                <th class="text-start" style="width: 20%;">Kesatuan / Penerima</th>
                                @foreach($months as $num => $name)
                                    <th>{{ strtoupper(substr($name, 0, 3)) }}</th>
                                @endforeach
                                <th class="bg-light">TOTAL TAHUNAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $grandTotalBulan = [];
                                $grandTotalSemua = 0;
                                foreach($months as $num => $name) { $grandTotalBulan[$num] = 0; }
                            @endphp

                            @foreach($destinations as $dest)
                                @php $totalBaris = 0; @endphp
                                <tr>
                                    <td class="text-start fw-bold text-dark">
                                        <i class="fa-solid fa-map-location-dot text-muted me-2 opacity-50"></i>{{ $dest->name }}
                                    </td>
                                    @foreach($months as $num => $name)
                                        @php
                                            $val = $data2[$dest->id][$num] ?? 0;
                                            $totalBaris += $val;
                                            $grandTotalBulan[$num] += $val;
                                        @endphp
                                        <td>
                                            @if($val > 0)
                                                <span class="text-dark fw-semibold">{{ number_format($val, 0, ',', '.') }}</span>
                                            @else
                                                <span class="text-muted opacity-25">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="bg-light fw-bold text-primary border-left">
                                        {{ number_format($totalBaris, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @php $grandTotalSemua += $totalBaris; @endphp
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-start text-uppercase">Total Distribusi Per Bulan</td>
                                @foreach($months as $num => $name)
                                    <td class="text-dark fs-6">{{ number_format($grandTotalBulan[$num], 0, ',', '.') }}</td>
                                @endforeach
                                <td class="text-danger fs-6">{{ number_format($grandTotalSemua, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
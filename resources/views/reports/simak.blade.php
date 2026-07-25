@extends('layouts.app')
@section('title', 'Laporan SIMAK - Pendistribusian Materiel')

@push('styles')
<style>
    /* Styling Banner Seragam (Tema SIMAK - Hijau/Teal) */
    .header-banner {
        border-radius: 10px;
        padding: 25px;
        color: white;
        margin-bottom: 20px;
        position: relative; 
        overflow: hidden; 
        background: linear-gradient(135deg, #0d9488, #0f766e);
    }
    .header-banner-icon {
        position: absolute;
        right: -2%;
        top: 50%;
        transform: translateY(-50%);
        font-size: 10rem;
        color: #ffffff;
        opacity: 0.15; 
        pointer-events: none;
        z-index: 1;
    }
    .header-content {
        position: relative;
        z-index: 2;
    }

    /* Styling Tabel Seragam */
    .table-dense {
        width: 100%;
        border-collapse: collapse;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .table-dense thead {
        background-color: #f8fafc;
    }
    .table-dense thead th {
        color: #1e293b;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 10px;
        font-weight: 800;
        vertical-align: middle;
        border: 1px solid #cbd5e1;
    }
    .table-dense tbody tr {
        transition: background-color 0.15s ease;
    }
    .table-dense tbody tr:hover {
        background-color: #f1f5f9;
    }
    .table-dense td {
        padding: 8px 10px;
        vertical-align: middle;
        color: #334155;
        font-size: 0.8rem;
        border: 1px solid #cbd5e1;
    }
</style>
@endpush

@section('content')

<!-- Header Banner -->
<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-file-contract header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-clipboard-list me-2"></i> Pendistribusian MATERIEL (SIMAK)</h4>
        <p class="mb-0 text-white-50 small">Laporan khusus format SIMAK untuk rekapitulasi distribusi materiil utama per bulan.</p>
    </div>
    <div class="header-content">
        <!-- Tombol Export Excel -->
        <a href="{{ route('reports.simak.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-light fw-bold text-teal shadow-sm px-4 py-2" style="border-radius: 8px; color: #0f766e;" target="_blank">
            <i class="fa-solid fa-file-excel me-1 text-success"></i> Export Excel SIMAK
        </a>
    </div>
</div>

<!-- Filter Section -->
<div class="bg-white p-3 rounded-3 shadow-sm border mb-4">
    <form method="GET" action="{{ route('reports.simak') }}" class="row g-3 align-items-end m-0">
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-regular fa-calendar me-1"></i> Bulan</label>
            <select name="month" class="form-select form-select-sm border-0 bg-light px-3 py-2" onchange="this.form.submit()" style="border-radius: 6px;">
                @foreach($months as $num => $name)
                    <option value="{{ $num }}" {{ (str_pad($month, 2, '0', STR_PAD_LEFT) == $num) ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-regular fa-calendar-check me-1"></i> Tahun</label>
            <input type="number" name="year" class="form-control form-control-sm border-0 bg-light px-3 py-2" value="{{ $year }}" min="2020" max="2099" onchange="this.form.submit()" style="border-radius: 6px;">
        </div>
        <div class="col-md-4">
            <!-- Space filler -->
        </div>
        <div class="col-md-2">
            @if(request()->hasAny(['month', 'year']))
                <a href="{{ route('reports.simak') }}" class="btn btn-sm btn-light border text-danger w-100 fw-bold py-2 shadow-sm" style="border-radius: 6px;">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Filter
                </a>
            @else
                <button type="button" class="btn btn-sm btn-secondary w-100 fw-bold py-2 opacity-50" disabled style="border-radius: 6px;">
                    <i class="fa-solid fa-filter me-1"></i> Default Aktif
                </button>
            @endif
        </div>
    </form>
</div>

<!-- Table Section -->
<div class="table-responsive shadow-sm pb-2" style="border-radius: 8px; background: white;">
    <table class="table-dense text-nowrap">
        <thead>
            <tr>
                <th rowspan="2" class="text-center" style="width: 40px;">NO</th>
                <th rowspan="2" class="text-center" style="min-width: 200px;">JENIS MATERIIL</th>
                <!-- Header Bulan -->
                <th colspan="{{ count($simakHeaders) }}" class="text-center" style="font-size: 0.9rem;">
                    {{ $monthName }} {{ $year }}
                </th>
            </tr>
            <tr>
                <!-- Header Nama Label SIMAK (Kolom) -->
                @foreach($simakHeaders as $label)
                    <th class="text-center">{{ strtoupper($label) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($destinations as $index => $dest)
                <tr>
                    <!-- Kolom No -->
                    <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                    
                    <!-- Kolom Nama Tujuan / Polres -->
                    <td class="fw-bold text-dark">{{ strtoupper($dest->name) }}</td>
                    
                    <!-- Kolom Data Angka per Label SIMAK -->
                    @foreach($simakHeaders as $label)
                        @php
                            $qty = $simakData[$dest->id][$label] ?? 0;
                        @endphp
                        <td class="text-center {{ $qty > 0 ? 'fw-bold text-dark' : 'text-muted' }}">
                            {{ $qty > 0 ? number_format($qty, 0, ',', '.') : '0' }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($simakHeaders) + 2 }}" class="text-center py-5 text-muted bg-white">
                        <i class="fa-solid fa-folder-open fs-2 mb-3 opacity-25 d-block"></i>
                        <span class="small fw-semibold">Master Data Tujuan atau Label SIMAK belum tersedia.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
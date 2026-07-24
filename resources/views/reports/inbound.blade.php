@extends('layouts.app')
@section('title', 'Laporan Riwayat Penerimaan')

@push('styles')
<style>
    /* Styling Banner Seragam */
    .header-banner {
        border-radius: 10px;
        padding: 25px;
        color: white;
        margin-bottom: 20px;
        position: relative; 
        overflow: hidden; 
        background: linear-gradient(135deg, #0284c7, #0369a1);
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
        border-radius: 8px;
        overflow: hidden;
    }
    .table-dense thead {
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-dense thead th {
        color: #475569;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 15px;
        font-weight: 700;
        vertical-align: middle;
    }
    .table-dense tbody tr.main-row {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }
    .table-dense tbody tr.main-row:hover {
        background-color: #f8fafc;
    }
    .table-dense td {
        padding: 10px 15px;
        vertical-align: middle;
        color: #334155;
        font-size: 0.85rem;
    }

    /* Spesifik Laporan (Mutasi/Inbound/Outbound) */
    .category-header {
        background-color: #f1f5f9 !important;
        color: #334155;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    .child-material {
        padding-left: 2.5rem !important;
        position: relative;
    }
    .child-material::before {
        content: "↳";
        position: absolute;
        left: 1.2rem;
        color: #94a3b8;
        font-weight: bold;
    }

    /* Styling Accordion */
    .accordion-toggle { cursor: pointer; text-decoration: none; color: inherit; display: block; width: 100%; text-align: left; background: none; border: none; padding: 0; }
    .accordion-toggle i.fa-chevron-right { transition: transform 0.2s ease; font-size: 0.8rem; color: #94a3b8; }
    .accordion-toggle:not(.collapsed) i.fa-chevron-right { transform: rotate(90deg); }
    .nested-table-container { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .nested-table { width: 100%; font-size: 0.8rem; background: #fff; }
    .nested-table th { font-weight: 700; text-transform: uppercase; font-size: 0.7rem; color: #64748b; border-bottom: 1px solid #e2e8f0; padding: 10px; }
    .nested-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
</style>
@endpush

@section('content')

@php
    $formatSeri = function($prefix, $start, $end) {
        if (is_null($start) && is_null($end)) return '-';
        $padAndDot = function($num) {
            $s = str_pad($num ?? 0, 9, '0', STR_PAD_LEFT);
            return substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3);
        };
        $s_formatted = $padAndDot($start);
        $e_formatted = $padAndDot($end);
        
        $p = $prefix ? "<span class='text-primary fw-bold me-1'>{$prefix}</span>" : '';
        return "{$p}<span class='fw-bold'>{$s_formatted}</span> s/d <span class='fw-bold'>{$e_formatted}</span>";
    };
@endphp

<!-- Header Banner -->
<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-boxes-packing header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-download me-2"></i> Laporan Penerimaan / Inbound</h4>
        <p class="mb-0 text-white-50 small">Pantau histori transaksi barang masuk secara spesifik per materiil.</p>
    </div>
    <div class="header-content">
        <a href="{{ route('reports.inbound.export', request()->all()) }}" class="btn btn-light fw-bold text-primary shadow-sm px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-file-excel me-1 text-success"></i> Export Excel
        </a>
    </div>
</div>

<!-- Filter Section -->
<div class="bg-white p-3 rounded-3 shadow-sm border mb-4">
    <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end m-0">
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-regular fa-calendar me-1"></i> Tanggal Awal</label>
            <!-- Ubah value menjadi $startDate -->
            <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-light px-3 py-2" value="{{ $startDate }}" onchange="this.form.submit()" style="border-radius: 6px;">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-regular fa-calendar-check me-1"></i> Tanggal Akhir</label>
            <!-- Ubah value menjadi $endDate -->
            <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-light px-3 py-2" value="{{ $endDate }}" onchange="this.form.submit()" style="border-radius: 6px;">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold small text-muted mb-1"><i class="fa-solid fa-layer-group me-1"></i> Filter Kategori</label>
            <select name="category_id" class="form-select form-select-sm border-0 bg-light px-3 py-2" onchange="this.form.submit()" style="border-radius: 6px;">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <!-- Tampilkan tombol reset hanya jika user sudah menekan filter / ada query string di URL -->
            @if(request()->hasAny(['start_date', 'end_date', 'category_id']))
                <a href="{{ url()->current() }}" class="btn btn-sm btn-light border text-danger w-100 fw-bold py-2 shadow-sm" style="border-radius: 6px;">
                    <i class="fa-solid fa-rotate-left me-1"></i> Reset Filter
                </a>
            @else
                <button type="button" class="btn btn-sm btn-secondary w-100 fw-bold py-2 opacity-50" disabled style="border-radius: 6px;">
                    <i class="fa-solid fa-filter me-1"></i> Filter Aktif
                </button>
            @endif
        </div>
    </form>
</div>

<!-- Table Section -->
<div class="table-responsive shadow-sm" style="border-radius: 8px; background: white;">
    <table class="table-dense">
        <thead>
            <tr>
                <th class="text-center" width="5%">NO</th>
                <th width="45%">Nama Materiil / Komoditas</th>
                <th class="text-center" width="20%">Total Ter-Input (Masuk)</th>
                <th class="text-center" width="30%">Aksi / Histori SPPM</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($groupedInbounds as $group)
                
                <!-- BARIS HEADER KATEGORI -->
                <tr>
                    <td colspan="4" class="category-header">
                        <i class="fa-solid fa-tags me-2 text-primary opacity-75"></i> {{ $group['category_name'] }}
                    </td>
                </tr>

                <!-- BARIS ITEM MATERIIL -->
                @foreach($group['items'] as $row)
                    <tr class="main-row">
                        <!-- Nomor: Hanya parent yang mendapat nomor -->
                        <td class="text-center fw-bold text-muted" style="font-size: 0.85rem;">
                            {{ !$row['is_child'] ? $no++ : '' }}
                        </td>

                        <!-- Nama Materiil -->
                        <td class="{{ $row['is_child'] ? 'child-material' : '' }}">
                            @if(!$row['has_children'] && $row['total_in'] > 0)
                                <button class="accordion-toggle collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMat{{ $row['material_id'] }}">
                                    <i class="fa-solid fa-chevron-right me-2 text-primary"></i>
                                    {{ strtoupper($row['material_name']) }}
                                </button>
                            @else
                                <span class="fw-bold {{ $row['has_children'] ? 'text-dark' : 'text-muted' }} d-inline-block py-1" style="padding-left: {{ $row['is_child'] ? '0' : '1.75rem' }};">
                                    {{ strtoupper($row['material_name']) }}
                                </span>
                            @endif
                        </td>
                        
                        <!-- Total Masuk -->
                        <td class="text-center align-middle">
                            @if($row['has_children'])
                                <span class="text-muted">-</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success border border-success fw-bold px-3 py-1">
                                    <i class="fa-solid fa-arrow-down me-1"></i> {{ number_format($row['total_in'], 0, ',', '.') }} {{ $row['satuan'] }}
                                </span>
                            @endif
                        </td>

                        <!-- Aksi / Keterangan -->
                        <td class="text-center align-middle">
                            @if(!$row['has_children'] && $row['total_in'] > 0)
                                <button class="btn btn-sm btn-light border text-primary shadow-none accordion-toggle collapsed py-1 px-3 d-inline-block w-auto" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMat{{ $row['material_id'] }}">
                                    <i class="fa-solid fa-list-check me-1"></i> Rincian ({{ count($row['transactions']) }} Data)
                                </button>
                            @elseif(!$row['has_children'])
                                <span class="text-muted small fst-italic">Belum ada transaksi</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>

                    <!-- ACCORDION: DETAIL HISTORI SPPM MASUK (HANYA MUNCUL JIKA ADA TRANSAKSI) -->
                    @if(!$row['has_children'] && $row['total_in'] > 0)
                    <tr class="border-0">
                        <td colspan="4" class="p-0 border-0">
                            <div class="collapse" id="collapseMat{{ $row['material_id'] }}">
                                <div class="nested-table-container px-4 py-3">
                                    <h6 class="fw-bold text-primary mb-2" style="font-size: 0.8rem;"><i class="fa-solid fa-clock-rotate-left me-1"></i> HISTORI KEDATANGAN BARANG</h6>
                                    
                                    <div class="table-responsive bg-white border rounded">
                                        <table class="table nested-table mb-0 text-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>Tgl Terima Fisik</th>
                                                    <th>Nomor SPPM / BAPPM</th>
                                                    <th>Gudang Penempatan</th>
                                                    <th>Rentang Nomor Seri</th>
                                                    <th class="text-center text-success border-start">Qty Masuk</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($row['transactions'] as $trx)
                                                    <tr>
                                                        <td class="align-middle fw-semibold">
                                                            {{ \Carbon\Carbon::parse($trx->log->receive_date ?? $trx->created_at)->format('d M Y') }}
                                                        </td>
                                                        <td class="align-middle fw-bold text-dark">
                                                            {{ $trx->log->sppm->sppm_no ?? 'No SPPM Tidak Diketahui' }}
                                                        </td>
                                                        <td class="align-middle text-muted">
                                                            <i class="fa-solid fa-warehouse me-1 opacity-50"></i> {{ $trx->log->sppm->warehouse->name ?? 'Gudang Utama' }}
                                                        </td>
                                                        <td class="align-middle">
                                                            @if($trx->serial_start || $trx->serial_end)
                                                                <span class="d-inline-block text-muted" style="font-size: 0.7rem; background:#f8fafc; border: 1px solid #e2e8f0; border-radius:4px; padding:3px 8px;">
                                                                    {!! $formatSeri($trx->serial_prefix, $trx->serial_start, $trx->serial_end) !!}
                                                                </span>
                                                            @else
                                                                <span class="text-muted fst-italic small">Tidak memakai seri</span>
                                                            @endif
                                                        </td>
                                                        <td class="text-center fw-bold text-success bg-success bg-opacity-10 border-start align-middle">
                                                            +{{ number_format($trx->qty_received, 0, ',', '.') }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif
                @endforeach

            @empty
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted bg-white">
                        <i class="fa-solid fa-folder-open fs-2 mb-3 opacity-25 d-block"></i>
                        <span class="small fw-semibold">Belum ada data barang masuk untuk kriteria yang dipilih.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
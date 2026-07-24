@extends('layouts.app')
@section('title', 'Laporan Mutasi Stock')

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
        background: linear-gradient(135deg, #3b82f6, #1d4ed8); /* Tema Biru untuk Laporan */
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

    /* Styling Spesifik Mutasi */
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
</style>
@endpush

@section('content')

<!-- Header Banner -->
<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-chart-pie header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-chart-pie me-2"></i> Laporan Mutasi Stock</h4>
        <p class="mb-0 text-white-50 small">Pantau rekapitulasi barang masuk, keluar, dan saldo akhir fisik gudang.</p>
    </div>
    <div class="header-content">
        <a href="{{ route('reports.mutation.export', request()->all()) }}" class="btn btn-light fw-bold text-primary shadow-sm px-4 py-2" style="border-radius: 8px;">
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
                <th>Nama Materiil / Komoditas</th>
                <th class="text-center" width="15%">Total Masuk</th>
                <th class="text-center" width="15%">Total Keluar</th>
                <th class="text-center" width="20%">Saldo Akhir Fisik</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($groupedMutations as $group)
                
                <!-- BARIS HEADER KATEGORI -->
                <tr>
                    <td colspan="5" class="category-header">
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
                        <td class="{{ $row['is_child'] ? 'child-material text-muted fw-bold' : 'fw-bold text-dark' }}">
                            {{ strtoupper($row['material_name']) }}
                        </td>
                        
                        <!-- Total Masuk -->
                        <td class="text-center align-middle">
                            @if(isset($row['has_children']) && $row['has_children'])
                                <span class="text-muted">-</span>
                            @else
                                <span class="badge bg-success bg-opacity-10 text-success border border-success fw-bold px-3 py-1">
                                    <i class="fa-solid fa-arrow-down me-1"></i> {{ number_format($row['total_in'], 0, ',', '.') }}
                                </span>
                            @endif
                        </td>
                        
                        <!-- Total Keluar -->
                        <td class="text-center align-middle">
                            @if(isset($row['has_children']) && $row['has_children'])
                                <span class="text-muted">-</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger fw-bold px-3 py-1">
                                    <i class="fa-solid fa-arrow-up me-1"></i> {{ number_format($row['total_out'], 0, ',', '.') }}
                                </span>
                            @endif
                        </td>
                        
                        <!-- Saldo Akhir -->
                        <td class="text-center align-middle">
                            @if(isset($row['has_children']) && $row['has_children'])
                                <span class="text-muted">-</span>
                            @else
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fw-bold px-3 py-1" style="font-size: 0.85rem;">
                                    {{ number_format($row['saldo_akhir'], 0, ',', '.') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach

            @empty
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted bg-white">
                        <i class="fa-solid fa-folder-open fs-2 mb-3 opacity-25 d-block"></i>
                        <span class="small fw-semibold">Belum ada data mutasi stock untuk kriteria yang dipilih.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
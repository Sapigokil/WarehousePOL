@extends('layouts.app')
@section('title', 'Laporan Mutasi Stock')

@push('styles')
<style>
    /* Styling khusus untuk membedakan Child dan Kategori */
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
    .category-header {
        background-color: #f1f5f9 !important;
        color: #334155;
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="card shadow-sm border-0 rounded-3 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-chart-pie me-2 text-primary"></i> Laporan Mutasi Stock</h4>
        <a href="{{ route('reports.mutation.export', request()->all()) }}" class="btn btn-success fw-bold">
            <i class="fa-solid fa-file-excel me-2"></i> Export Excel
        </a>
    </div>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label fw-bold small text-muted">Filter Kategori</label>
            <select name="category_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nama Materiil</th>
                    <th class="text-center" width="15%">Total Masuk</th>
                    <th class="text-center" width="15%">Total Keluar</th>
                    <th class="text-center" width="20%">Saldo Akhir (Gudang)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groupedMutations as $group)
                    
                    <!-- BARIS HEADER KATEGORI -->
                    <tr>
                        <td colspan="4" class="category-header">
                            <i class="fa-solid fa-layer-group me-2"></i> {{ $group['category_name'] }}
                        </td>
                    </tr>

                    <!-- BARIS ITEM MATERIIL -->
                    @foreach($group['items'] as $row)
                        <tr>
                            <!-- Logika Class CSS berdasarkan status Parent / Child -->
                            <td class="{{ $row['is_child'] ? 'child-material text-muted fw-bold' : 'fw-bold text-dark' }}">
                                {{ strtoupper($row['material_name']) }}
                            </td>
                            <td class="text-center text-success fw-bold">{{ number_format($row['total_in'], 0, ',', '.') }}</td>
                            <td class="text-center text-danger fw-bold">{{ number_format($row['total_out'], 0, ',', '.') }}</td>
                            <td class="text-center text-primary fw-bold">{{ number_format($row['saldo_akhir'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach

                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-folder-open fs-2 mb-2"></i><br>
                            Belum ada data mutasi stock untuk ditampilkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
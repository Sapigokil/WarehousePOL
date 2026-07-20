@extends('layouts.app')
@section('title', 'Laporan Mutasi Stock')

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
                    <th>Kategori</th>
                    <th class="text-center">Total Masuk</th>
                    <th class="text-center">Total Keluar</th>
                    <th class="text-center">Saldo Akhir (Gudang)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutations as $row)
                <tr>
                    <td class="fw-bold">{{ strtoupper($row['material_name']) }}</td>
                    <td>{{ $row['category_name'] }}</td>
                    <td class="text-center text-success fw-bold">{{ number_format($row['total_in'], 0, ',', '.') }}</td>
                    <td class="text-center text-danger fw-bold">{{ number_format($row['total_out'], 0, ',', '.') }}</td>
                    <td class="text-center text-primary fw-bold">{{ number_format($row['saldo_akhir'], 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">Belum ada data mutasi stock.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
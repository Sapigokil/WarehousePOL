@extends('layouts.app')
@section('title', 'Laporan Riwayat Distribusi')

@section('content')
<div class="card shadow-sm border-0 rounded-3 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-arrow-right-from-bracket me-2 text-danger"></i> Riwayat Distribusi (Outbound)</h4>
        <a href="{{ route('reports.outbound.export', request()->all()) }}" class="btn btn-success fw-bold">
            <i class="fa-solid fa-file-excel me-2"></i> Export Excel
        </a>
    </div>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold small text-muted">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary fw-bold w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" style="font-size: 0.9rem;">
            <thead class="table-light">
                <tr>
                    <th>Tanggal Keluar</th>
                    <th>No. SPPM Keluar</th>
                    <th>Tujuan Distribusi</th>
                    <th>Materiil</th>
                    <th class="text-center">Rentang Seri Keluar</th>
                    <th class="text-center">Qty Keluar</th>
                </tr>
            </thead>
            <tbody>
                @forelse($outbounds as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="fw-bold text-danger">{{ optional(optional(optional($row->outLog)->outSppm))->sppm_no ?? '-' }}</td>
                    <td>{{ strtoupper(optional(optional(optional(optional($row->outLog)->outSppm)->destination))->name ?? '-') }}</td>
                    <td>{{ strtoupper(optional(optional($row->stock)->material)->name ?? '-') }}</td>
                    <td class="text-center"><span class="badge bg-light text-dark border">{{ optional($row->stock)->prefix ?? '' }}{{ str_pad($row->seri_awal, 9, '0', STR_PAD_LEFT) }} s/d {{ optional($row->stock)->prefix ?? '' }}{{ str_pad($row->seri_akhir, 9, '0', STR_PAD_LEFT) }}</span></td>
                    <td class="text-center fw-bold">{{ number_format($row->qty_keluar, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada data riwayat distribusi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $outbounds->links() }}
    </div>
</div>
@endsection
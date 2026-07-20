@extends('layouts.app')
@section('title', 'Laporan Riwayat Penerimaan')

@section('content')
<div class="card shadow-sm border-0 rounded-3 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-arrow-right-to-bracket me-2 text-success"></i> Riwayat Penerimaan (Inbound)</h4>
        <a href="{{ route('reports.inbound.export', request()->all()) }}" class="btn btn-success fw-bold">
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
                    <th>Tanggal Terima</th>
                    <th>No. SPPM Masuk</th>
                    <th>Materiil</th>
                    <th class="text-center">Batch/Tahap</th>
                    <th class="text-center">Rentang Seri</th>
                    <th class="text-center">Qty Diterima</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inbounds as $row)
                <tr>
                    <td>{{ optional($row->log)->receive_date ? \Carbon\Carbon::parse($row->log->receive_date)->format('d/m/Y') : '-' }}</td>
                    <td class="fw-bold text-success">{{ optional(optional($row->log)->sppm)->sppm_no ?? '-' }}</td>
                    <td>{{ strtoupper(optional($row->material)->name ?? '-') }}</td>
                    <td class="text-center">Tahap {{ optional($row->log)->batch_number ?? '-' }}</td>
                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $row->serial_prefix ?? '' }}{{ str_pad($row->serial_start, 9, '0', STR_PAD_LEFT) }} s/d {{ $row->serial_prefix ?? '' }}{{ str_pad($row->serial_end, 9, '0', STR_PAD_LEFT) }}</span></td>
                    <td class="text-center fw-bold">{{ number_format($row->qty_received, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada data riwayat penerimaan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $inbounds->links() }}
    </div>
</div>
@endsection
@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .kpi-card {
        border: none;
        border-radius: 10px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .card-custom {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
    }
    .table-dashboard th {
        background-color: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        padding: 10px 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-dashboard td {
        padding: 10px 12px;
        vertical-align: middle;
        font-size: 0.85rem;
        border-bottom: 1px solid #f1f5f9;
    }
</style>
@endpush

@section('content')

    @if(session('warning_session'))
        <div class="alert alert-warning alert-dismissible fade show border-warning shadow-sm" role="alert">
            <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> Perhatian:</strong> {{ session('warning_session') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Selamat Datang, {{ Auth::user()->name }}! 👋</h4>
            <p class="text-muted small mb-0">Ini adalah ringkasan operasional dan pemantauan stok gudang Anda hari ini.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('outbounds.create') }}" class="btn btn-sm btn-theme fw-bold px-3 shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> SPPM Outbound
            </a>
        </div>
    </div>

    {{-- KARTU RINGKASAN (KPI CARDS) --}}
    <div class="row g-3 mb-4">
        {{-- Total Komoditas --}}
        <div class="col-md-3">
            <div class="card kpi-card shadow-sm p-3" style="border-left: 4px solid #01a9ac !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.75rem;">Total Komoditas</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalMaterialCount, 0, ',', '.') }}</h3>
                        <small class="text-muted">Item Terdaftar</small>
                    </div>
                    <div class="kpi-icon bg-info bg-opacity-10 text-info">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stok Minimal Alert --}}
        <div class="col-md-3">
            <div class="card kpi-card shadow-sm p-3" style="border-left: 4px solid #f1c40f !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.75rem;">Warning Min. Stok</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $lowStockCount }}</h3>
                        <small class="text-warning fw-semibold">Perlu Re-order</small>
                    </div>
                    <div class="kpi-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Depleksi Kritis (< 3 Bulan) --}}
        <div class="col-md-3">
            <div class="card kpi-card shadow-sm p-3" style="border-left: 4px solid #e74c3c !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.75rem;">Depleksi Kritis (<3 Bln)</h6>
                        <h3 class="fw-bold mb-0 text-danger">{{ $criticalDepletionCount }}</h3>
                        <small class="text-danger fw-semibold">Terancam Habis</small>
                    </div>
                    <div class="kpi-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inbound Draft / Pending --}}
        <div class="col-md-3">
            <div class="card kpi-card shadow-sm p-3" style="border-left: 4px solid #2ecc71 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted fw-bold text-uppercase mb-1" style="font-size: 0.75rem;">Inbound Process</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ $pendingInboundCount }}</h3>
                        <small class="text-success fw-semibold">Dokumen Pending</small>
                    </div>
                    <div class="kpi-icon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-truck-ramp-box"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- GRAFIK & PANAUAN DEPLEKSI KRITIS --}}
    <div class="row g-3 mb-4">
        {{-- GRAFIK TREN 6 BULAN --}}
        <div class="col-lg-7">
            <div class="card card-custom shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-chart-area text-theme me-2"></i> Tren Pergerakan Barang (6 Bulan Terakhir)
                    </h6>
                </div>
                <div style="height: 280px;">
                    <canvas id="chartMovement"></canvas>
                </div>
            </div>
        </div>

        {{-- TABEL ANCAMAN DEPLEKSI STOK TERRENDAH (< 3 BULAN) --}}
        <div class="col-lg-5">
            <div class="card card-custom shadow-sm h-100 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-fire text-danger me-2"></i> Depleksi Stok Kritis (< 3 Bulan)
                    </h6>
                    <a href="{{ route('settings.rate.index') }}" class="btn btn-link btn-sm text-theme p-0 fw-semibold text-decoration-none" style="font-size: 0.75rem;">Lihat Semua</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dashboard mb-0">
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th class="text-center">Sisa Stok</th>
                                <th class="text-center">Daya Tahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($depletionItems as $item)
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block">{{ $item->name }}</strong>
                                        @if($item->parent)
                                            <small class="text-muted" style="font-size: 0.7rem;">{{ $item->parent->name }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        {{ number_format($item->current_stock, 0, ',', '.') }} {{ $item->satuan }}
                                    </td>
                                    <td class="text-center">
                                        @if($item->current_stock <= 0)
                                            <span class="badge bg-danger px-2 py-1" style="font-size: 0.7rem;">Stok Habis</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1" style="font-size: 0.7rem;">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $item->months_left }} Bulan
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-circle-check text-success fs-3 mb-1 d-block"></i>
                                        <span class="small">Tidak ada stok dalam ancaman depleksi kritis (< 3 bulan).</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- AKTIVITAS TERBARU & LOW STOCK WARNING --}}
    <div class="row g-3">
        {{-- LOW STOCK WARNING (MINIMAL STOK) --}}
        <div class="col-lg-5">
            <div class="card card-custom shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Warning Stok Minimal
                    </h6>
                    <a href="{{ route('stocks.index') }}" class="btn btn-link btn-sm text-theme p-0 fw-semibold text-decoration-none" style="font-size: 0.75rem;">Stock Index</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-dashboard mb-0">
                        <thead>
                            <tr>
                                <th>Barang</th>
                                <th class="text-center">Sisa</th>
                                <th class="text-center">Min. Stok</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems as $item)
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block">{{ $item->name }}</strong>
                                    </td>
                                    <td class="text-center fw-bold text-danger">
                                        {{ number_format($item->current_stock, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center text-muted">
                                        {{ number_format($item->minimal_stok, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark px-2 py-0.5" style="font-size: 0.7rem;">Low</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fa-solid fa-shield-halved text-success fs-3 mb-1 d-block"></i>
                                        <span class="small">Seluruh stok barang berada di atas batas minimal.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- AKTIVITAS DOKUMEN TERBARU --}}
        <div class="col-lg-7">
            <div class="card card-custom shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Dokumen Transaksi Terbaru
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-dashboard mb-0">
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>No. SPPM / Dokumen</th>
                                <th>Tujuan / Asal</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivities as $act)
                                <tr>
                                    <td>
                                        @if($act->type == 'INBOUND')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-0.5" style="font-size: 0.7rem;">INBOUND</span>
                                        @else
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-2 py-0.5" style="font-size: 0.7rem;">OUTBOUND</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">
                                        {{ $act->sppm_no ?? $act->no_sppm ?? '-' }}
                                    </td>
                                    <td class="text-muted">
                                        {{ $act->warehouse->name ?? $act->destination->name ?? '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if($act->status == 'completed')
                                            <span class="badge bg-success px-2 py-0.5" style="font-size: 0.7rem;">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark px-2 py-0.5" style="font-size: 0.7rem;">Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-center text-muted" style="font-size: 0.8rem;">
                                        {{ $act->created_at->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <span class="small">Belum ada aktivitas transaksi dokumen.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
{{-- Load Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('chartMovement').getContext('2d');
        
        const chartLabels = @json($chartLabels);
        const chartInbound = @json($chartInbound);
        const chartOutbound = @json($chartOutbound);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Barang Masuk (Inbound)',
                        data: chartInbound,
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 4
                    },
                    {
                        label: 'Barang Keluar (Outbound)',
                        data: chartOutbound,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { size: 11, weight: 'bold' } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });
    });
</script>
@endpush
@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    @if(session('warning_session'))
        <div class="alert alert-warning alert-dismissible fade show border-warning shadow-sm" role="alert">
            <strong><i class="fa-solid fa-triangle-exclamation"></i> Perhatian:</strong> {{ session('warning_session') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold text-dark mb-1">Selamat Datang, {{ Auth::user()->name }}!</h4>
            <p class="text-muted">Ini adalah ringkasan operasional gudang Anda hari ini.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm" style="border-bottom: 4px solid #01a9ac !important;">
                <div class="card-body">
                    <h6 class="text-muted fw-bold text-uppercase mb-2">Total Barang</h6>
                    <h3 class="fw-bold mb-0">12,450 <span class="text-success fs-6"><i class="fa-solid fa-arrow-up"></i></span></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm" style="border-bottom: 4px solid #f1c40f !important;">
                <div class="card-body">
                    <h6 class="text-muted fw-bold text-uppercase mb-2">Low Stock Alert</h6>
                    <h3 class="fw-bold mb-0">45 <span class="fs-6 text-muted">Items</span></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm" style="border-bottom: 4px solid #e74c3c !important;">
                <div class="card-body">
                    <h6 class="text-muted fw-bold text-uppercase mb-2">Barang Keluar Hari Ini</h6>
                    <h3 class="fw-bold mb-0">320 <span class="fs-6 text-muted">Packs</span></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm" style="border-bottom: 4px solid #2ecc71 !important;">
                <div class="card-body">
                    <h6 class="text-muted fw-bold text-uppercase mb-2">Inbound Proses</h6>
                    <h3 class="fw-bold mb-0">12 <span class="fs-6 text-muted">Dokumen</span></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa-solid fa-chart-line me-2 text-primary"></i> Aktivitas Terbaru</span>
                </div>
                <div class="card-body">
                    <p class="text-muted">Grafik dan tabel pergerakan barang akan ditampilkan di sini.</p>
                </div>
            </div>
        </div>
    </div>

@endsection
@extends('layouts.app')
@section('title', 'Detail Riwayat Stok Barang')

@push('styles')
<style>
    /* Card & General Layout */
    .detail-card { 
        background: #ffffff; 
        border-radius: 8px; 
        border: 1px solid #e2e8f0; 
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
    }
    
    /* Compact Table Styling */
    .table-detail { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .table-detail thead th { 
        background-color: #f1f5f9; 
        color: #475569; 
        font-size: 0.7rem; 
        font-weight: 700;
        text-transform: uppercase; 
        letter-spacing: 0.5px;
        padding: 10px 12px; /* Dibuat lebih rapat */
        border-bottom: 2px solid #cbd5e1; 
        vertical-align: middle; 
    }
    .table-detail tbody tr { border-bottom: 1px solid #f1f5f9; }
    .table-detail td { 
        padding: 6px 12px; /* Dibuat lebih rapat */
        vertical-align: middle; 
        color: #334155; 
        font-size: 0.8rem; /* Font sedikit diperkecil */
    }
    
    /* Serial Box - Compact */
    .serial-box { 
        background-color: #f8fafc; 
        border: 1px solid #e2e8f0; 
        padding: 2px 6px; 
        border-radius: 4px; 
        font-family: 'Courier New', Courier, monospace; 
        font-size: 0.75rem; 
        font-weight: 600; 
        display: inline-block;
    }
    
    /* Group Headers (Parent) - Visual yang kontras */
    .row-group-header { 
        cursor: pointer; 
        background-color: #f8fafc !important; 
        border-left: 4px solid #3b82f6 !important;
        border-top: 1px solid #cbd5e1 !important;
        border-bottom: 1px solid #cbd5e1 !important;
    }
    .row-group-header:hover { background-color: #e2e8f0 !important; }
    .row-group-header .chevron-icon { transition: transform 0.2s; }
    .row-group-header[aria-expanded="true"] .chevron-icon { transform: rotate(180deg); }

    /* Minus Header (Orphan) */
    .row-group-minus-header { 
        cursor: pointer; 
        background-color: #fff1f2 !important; 
        border-left: 4px solid #ef4444 !important;
        border-top: 1px solid #fecaca !important;
        border-bottom: 1px solid #fecaca !important;
    }
    .row-group-minus-header:hover { background-color: #ffe4e6 !important; }

    /* Child Rows - Hierarki Garis (Tree-line effect) */
    .row-child { background-color: #ffffff !important; }
    .row-child:hover { background-color: #f8fafc !important; }
    .row-child td:first-child {
        border-left: 4px solid transparent; /* placeholder agar sejajar */
        position: relative;
    }
    /* Elemen visual penanda child */
    .tree-line {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-bottom: 2px solid #cbd5e1;
        border-left: 2px solid #cbd5e1;
        margin-right: 5px;
        margin-bottom: 3px;
    }

    /* Status & Highlight Rows */
    .row-child-used { background-color: #fffafb !important; } 
    .row-child-avail { background-color: #f8fff9 !important; } 

    /* Soft Badges */
    .badge-soft-success { background-color: #dcfce7; color: #166534; }
    .badge-soft-danger { background-color: #fee2e2; color: #991b1b; }
    .badge-soft-warning { background-color: #fef9c3; color: #854d0e; }
    .badge-soft-primary { background-color: #dbeafe; color: #1e40af; }
    .badge-soft-secondary { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    /* Utilities */
    .text-xs { font-size: 0.7rem; }
    .hidden-row { display: none !important; }
</style>
@endpush

@section('content')

@php
    $formatSeri = function($prefix, $start, $end) {
        if (is_null($start) && is_null($end)) return '<span class="text-muted fw-normal fst-italic">Seri Tidak Tercatat / Non Seri</span>';

        $padAndDot = function($num) {
            $s = str_pad($num ?? 0, 9, '0', STR_PAD_LEFT);
            return substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3);
        };

        $s_formatted = $padAndDot($start);
        $e_formatted = $padAndDot($end);
        
        $p = $prefix ? "<span class='text-primary fw-bold'>{$prefix}</span> " : '';
        return "{$p}{$s_formatted} <span class='text-muted fw-normal mx-1'>s/d</span> {$e_formatted}";
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-1 text-dark">
            <i class="fa-solid fa-boxes-packing text-primary me-2"></i> Rincian & Riwayat Buku Alokasi
        </h5>
        <div class="text-muted small">Pelacakan mutasi seri masuk dan keluar secara akurat per dokumen SPPM</div>
    </div>
    <div class="d-flex gap-2">
        <form action="{{ route('stocks.sync', $material->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menyinkronkan ulang Buku Alokasi untuk barang ini?')">
            @csrf
            <button type="submit" class="btn btn-sm btn-warning fw-bold px-3 text-dark border-0">
                <i class="fa-solid fa-rotate me-1"></i> Sync / Rebuild
            </button>
        </form>
        <button onclick="window.close()" class="btn btn-sm btn-light border fw-bold px-3">
            <i class="fa-solid fa-xmark me-1"></i> Tutup Tab
        </button>
    </div>
</div>

@if($material->pakai_seri != 1)
<div class="alert alert-info d-flex align-items-center mb-3 py-2 border-0 bg-info bg-opacity-10 text-info-emphasis shadow-sm" role="alert">
    <i class="fa-solid fa-circle-info fs-4 me-3"></i>
    <div>
        <strong class="d-block">Filter Aktif: Barang Non-Seri</strong>
        <span class="small">Barang ini diatur sebagai materiil <strong>Tanpa Nomor Seri (Bulk)</strong> di Master Data. Pelacakan hanya berdasarkan kuantitas total (QTY).</span>
    </div>
</div>
@endif

@if($needsSync)
<div class="alert alert-warning d-flex align-items-center mb-3 py-2 border-0 shadow-sm" role="alert">
    <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-warning"></i>
    <div>
        <strong class="d-block">Buku Alokasi Belum Disinkronisasi!</strong>
        <span class="small">Sistem mendeteksi buku alokasi untuk barang ini kosong. Silakan klik tombol <strong>Sync / Rebuild</strong>.</span>
    </div>
</div>
@endif

<!-- Summary Card -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="detail-card p-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex justify-content-center align-items-center" style="width: 50px; height: 50px;">
                    <i class="fa-solid fa-box fs-4"></i>
                </div>
                <div>
                    <span class="badge badge-soft-secondary mb-1 px-2">{{ $material->category->name ?? 'Kategori Umum' }}</span>
                    <h5 class="fw-bold text-dark m-0">{{ $material->name }}</h5>
                    @if($material->code)
                        <div class="text-muted fw-semibold mt-1" style="font-size: 0.75rem;">KODE: <span class="text-dark">{{ $material->code }}</span></div>
                    @endif
                </div>
            </div>
            <div class="text-end border-start ps-4">
                {{-- <div class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Sisa Stok Tersedia</div>
                <h3 class="fw-bold text-success m-0">
                    {{ number_format($totalTersedia, 0, ',', '.') }} 
                    <small class="text-muted fw-normal ms-1" style="font-size: 0.9rem;">{{ $material->satuan }}</small>
                </h3> --}}
            </div>
        </div>
    </div>
</div>

<!-- Table Card -->
<div class="detail-card overflow-hidden">
    <div class="bg-white px-3 py-2 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> Detail Mutasi</h6>
        
        <div class="d-flex align-items-center gap-3">
            <!-- Toggle Stok Nol -->
            <div class="form-check form-switch m-0 pt-1">
                <input class="form-check-input" type="checkbox" id="toggleZeroStock">
                <label class="form-check-label text-dark fw-semibold small" for="toggleZeroStock" style="cursor:pointer;">
                    Tampilkan Stok Habis (0)
                </label>
            </div>

            <form action="{{ route('stocks.show', $material->id) }}" method="GET" class="d-flex" style="min-width: 250px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 bg-light" placeholder="Cari SPPM / Seri..." value="{{ $search }}">
                    <button class="btn btn-primary px-3" type="submit">Cari</button>
                    @if($search)
                        <a href="{{ route('stocks.show', $material->id) }}" class="btn btn-outline-danger" title="Reset"><i class="fa-solid fa-times"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <form action="{{ route('stocks.bulk_update_price', $material->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="table-responsive">
            <table class="table-detail">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">No</th>
                        <th width="24%">Dokumen / Keterangan</th>
                        <th width="10%">Tanggal</th>
                        <th width="10%">Gudang</th>
                        <th width="28%">Rentang Nomor Seri (Inbound)</th>
                        <th width="12%" class="text-end">Kuantitas</th>
                        <th width="12%" class="text-center">Aksi / Status</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($groupedInbounds) && empty($unserializedIns) && empty($orphanOuts))
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted bg-white">
                                <i class="fa-solid fa-folder-open fs-2 mb-2 text-light"></i>
                                <h6 class="fw-bold text-secondary mb-1">Belum Ada Data</h6>
                                <p class="mb-0 small">Belum ada alokasi stok @if($search) atau pencarian tidak ditemukan. @endif</p>
                            </td>
                        </tr>
                    @else
                        
                        @php $counter = 1; @endphp

                        @foreach($groupedInbounds as $sppmId => $group)
                            @php 
                                $sppm = $group['sppm'];
                                $gId = 'sppm_' . $sppmId;
                                $isZeroStock = ($group['total_available'] <= 0);
                                $rowClass = $isZeroStock ? 'zero-stock-group hidden-row' : 'active-stock-group';
                                
                                // Ambil rentang seri asli dari in_details jika ada
                                $inDetail = isset($sppm->details) ? $sppm->details->where('material_id', $material->id)->first() : null;
                                $inPrefix = $inDetail->prefix ?? null;
                                $inAwal = $inDetail->seri_awal ?? null;
                                $inAkhir = $inDetail->seri_akhir ?? null;
                            @endphp
                            
                            <!-- PARENT ROW -->
                            <tr class="row-group-header {{ $rowClass }}" data-bs-toggle="collapse" data-bs-target=".group-{{ $gId }}" aria-expanded="false" title="Klik untuk melihat detail keluar/masuk">
                                <td class="text-center fw-bold text-muted">{{ $counter++ }}</td>
                                <td>
                                    <span class="fw-bold text-dark">
                                        <i class="fa-solid fa-file-arrow-down text-primary me-1"></i> {{ $sppm->sppm_no ?? 'SPPM TIDAK DIKETAHUI' }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark text-xs">
                                    {{ isset($sppm->sppm_date) ? \Carbon\Carbon::parse($sppm->sppm_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td>
                                    <span class="badge badge-soft-secondary text-xs"><i class="fa-solid fa-warehouse me-1"></i> {{ $sppm->warehouse->name ?? 'GUDANG' }}</span>
                                </td>
                                <td>
                                    <!-- Menampilkan rentang asli seri Inbound dari detail Inbound -->
                                    <div class="serial-box bg-white border-secondary-subtle">
                                        {!! $formatSeri($group['prefix'], $group['seri_awal'], $group['seri_akhir']) !!}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        <span class="badge badge-soft-secondary" title="Total Masuk">{{ number_format($group['total_in'], 0, ',', '.') }}</span>
                                        <span class="text-muted" style="font-size: 0.6rem;">-</span>
                                        <span class="badge badge-soft-danger" title="Terpakai/Keluar">{{ number_format($group['total_used'], 0, ',', '.') }}</span>
                                        <span class="text-muted fw-bold" style="font-size: 0.6rem;">=</span>
                                        <span class="badge {{ $isZeroStock ? 'badge-soft-secondary' : 'bg-success text-white' }}" title="Sisa Tersedia">{{ number_format($group['total_available'], 0, ',', '.') }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-soft-primary px-2" style="cursor:pointer;">
                                        Rincian <i class="fa-solid fa-chevron-down ms-1 chevron-icon"></i>
                                    </span>
                                </td>
                            </tr>

                            <!-- CHILD ROWS: USED ITEMS -->
                            @foreach($group['items_used'] as $childIdx => $item)
                                <tr class="collapse group-{{ $gId }} row-child row-child-used {{ $rowClass }}">
                                    <td class="text-end text-muted text-xs pe-3 border-start border-2 border-danger"><span class="tree-line"></span></td>
                                    <td>
                                        <div class="fw-semibold text-danger ps-2">
                                            <i class="fa-solid fa-arrow-right-from-bracket me-1 text-xs"></i> {{ $item->outSppm->sppm_no ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-muted text-xs">
                                        {{ isset($item->outSppm->sppm_date) ? \Carbon\Carbon::parse($item->outSppm->sppm_date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="text-muted text-center">-</td>
                                    <td>
                                        <div class="serial-box text-danger border-danger-subtle bg-danger bg-opacity-10">{!! $formatSeri($item->prefix, $item->seri_awal, $item->seri_akhir) !!}</div>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-danger">-{{ number_format($item->qty, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-soft-danger px-2 text-xs">KELUAR</span>
                                    </td>
                                </tr>
                            @endforeach

                            <!-- CHILD ROWS: AVAILABLE ITEMS -->
                            @foreach($group['items_available'] as $childIdx => $item)
                                <tr class="collapse group-{{ $gId }} row-child row-child-avail {{ $rowClass }}">
                                    <td class="text-end text-muted text-xs pe-3 border-start border-2 border-success"><span class="tree-line"></span></td>
                                    <td>
                                        <div class="fw-semibold text-success ps-2">
                                            <i class="fa-solid fa-box-open me-1 text-xs"></i> Sisa Stok Tersedia
                                        </div>
                                    </td>
                                    <td class="text-muted text-center">-</td>
                                    <td class="text-muted text-center">-</td>
                                    <td>
                                        <div class="serial-box text-success border-success-subtle bg-success bg-opacity-10">{!! $formatSeri($item->prefix, $item->seri_awal, $item->seri_akhir) !!}</div>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-success">+{{ number_format($item->qty, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-soft-success px-2 text-xs">TERSEDIA</span>
                                    </td>
                                </tr>
                            @endforeach

                        @endforeach

                        <!-- UNSERIALIZED INBOUND (NON-SERI) -->
                        @foreach($unserializedIns as $unser)
                            <tr class="row-group-header">
                                <td class="text-center fw-bold text-muted">{{ $counter++ }}</td>
                                <td>
                                    <span class="fw-bold text-dark">
                                        <i class="fa-solid fa-boxes-stacked text-success me-1"></i> {{ $unser->inSppm->sppm_no ?? 'SPPM UTAMA' }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark text-xs">
                                    {{ isset($unser->inSppm->sppm_date) ? \Carbon\Carbon::parse($unser->inSppm->sppm_date)->format('d/m/Y') : '-' }}
                                </td>
                                <td>
                                    <span class="badge badge-soft-secondary text-xs"><i class="fa-solid fa-warehouse me-1"></i> {{ $unser->inSppm->warehouse->name ?? 'GUDANG' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted fst-italic text-xs">Data Non-Seri (Bulk) / Kuantitas Saja</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge badge-soft-success px-2">
                                        {{ number_format($unser->qty, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-soft-warning px-2 text-xs">NON-SERI</span>
                                </td>
                            </tr>
                        @endforeach

                        <!-- ORPHAN OUTS (MINUS) -->
                        @if(!empty($orphanOuts) && count($orphanOuts) > 0)
                            @php
                                $totalOrphanQty = collect($orphanOuts)->sum('qty');
                            @endphp
                            <tr class="row-group-minus-header" data-bs-toggle="collapse" data-bs-target="#collapseOrphan" aria-expanded="false" title="Klik untuk melihat detail">
                                <td class="text-center fw-bold text-danger">{{ $counter++ }}</td>
                                <td>
                                    <span class="fw-bold text-danger">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i> SPPM TIDAK DIKETAHUI
                                    </span>
                                    <div class="small text-danger opacity-75 mt-1" style="font-size: 0.7rem;">Orphan Outbound</div>
                                </td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center text-muted">-</td>
                                <td><span class="text-danger text-xs fst-italic">Pengeluaran tanpa asal-usul inbound</span></td>
                                <td class="text-end">
                                    <span class="badge bg-danger text-white px-2 shadow-sm">
                                        -{{ number_format($totalOrphanQty, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-soft-danger px-2 text-xs">
                                        RINCIAN <i class="fa-solid fa-chevron-down ms-1 chevron-icon"></i>
                                    </span>
                                </td>
                            </tr>

                            @foreach($orphanOuts as $optIdx => $orphan)
                                <tr class="collapse group-orphan row-child" id="collapseOrphan">
                                    <td class="text-end text-muted text-xs pe-3 border-start border-2 border-danger"><span class="tree-line"></span></td>
                                    <td>
                                        <div class="fw-semibold text-danger ps-2">
                                            <i class="fa-solid fa-arrow-right-from-bracket me-1 text-xs"></i> {{ $orphan->outSppm->sppm_no ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="text-muted text-xs">
                                        {{ isset($orphan->outSppm->sppm_date) ? \Carbon\Carbon::parse($orphan->outSppm->sppm_date)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="text-muted text-center">-</td>
                                    <td>
                                        <div class="serial-box text-danger border-danger-subtle bg-danger bg-opacity-10">{!! $formatSeri($orphan->prefix, $orphan->seri_awal, $orphan->seri_akhir) !!}</div>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-danger">-{{ number_format($orphan->qty, 0, ',', '.') }}</span>
                                    </td>
                                    <td class="text-center text-danger fw-semibold text-xs">Menunggu Inbound</td>
                                </tr>
                            @endforeach
                        @endif

                    @endif
                </tbody>
            </table>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleZeroBtn = document.getElementById('toggleZeroStock');
        const zeroStockRows = document.querySelectorAll('.zero-stock-group');

        function updateZeroStockVisibility() {
            const showZero = toggleZeroBtn.checked;
            zeroStockRows.forEach(row => {
                if(showZero) {
                    row.classList.remove('hidden-row');
                } else {
                    row.classList.add('hidden-row');
                }
            });
        }

        if(toggleZeroBtn) {
            // Pasang event listener
            toggleZeroBtn.addEventListener('change', updateZeroStockVisibility);
            
            // Eksekusi pertama kali saat halaman dimuat
            updateZeroStockVisibility();
        }
    });
</script>
@endpush

@endsection
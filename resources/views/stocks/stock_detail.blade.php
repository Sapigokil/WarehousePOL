@extends('layouts.app')
@section('title', 'Detail Riwayat Stok Barang')

@push('styles')
<style>
    .detail-card { background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; }
    .table-detail { width: 100%; border-collapse: collapse; }
    .table-detail thead th { background-color: #f8fafc; color: #475569; font-size: 0.75rem; text-transform: uppercase; padding: 12px 15px; border-bottom: 2px solid #e2e8f0; vertical-align: middle; }
    .table-detail tbody tr { border-bottom: 1px solid #f1f5f9; }
    .table-detail tbody tr:hover { background-color: #f8fafc; }
    .table-detail td { padding: 10px 15px; vertical-align: middle; color: #334155; font-size: 0.85rem; }
    .serial-box { background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 0.8rem; font-weight: bold; color: #475569; display: inline-block;}
</style>
@endpush

@section('content')

@php
    $formatSeri = function($prefix, $start, $end) {
        if (is_null($start) && is_null($end)) return '-';

        // Fungsi internal untuk memformat 000.000.000
        $padAndDot = function($num) {
            // 1. Pastikan string 9 digit dengan nol di depan
            $s = str_pad($num ?? 0, 9, '0', STR_PAD_LEFT);
            // 2. Potong manual menjadi kelompok 3 digit (000, 000, 000)
            return substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3);
        };

        $s_formatted = $padAndDot($start);
        $e_formatted = $padAndDot($end);
        
        $p = $prefix ? "<span class='text-primary'>{$prefix}</span> " : '';
        return "{$p}{$s_formatted} <span class='text-muted fw-normal mx-1'>s/d</span> {$e_formatted}";
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0 text-theme">
            <i class="fa-solid fa-magnifying-glass-chart me-2"></i> Rincian & Riwayat Stok
        </h5>
        <div class="text-muted small mt-1">Laporan mutasi masuk barang ke dalam gudang</div>
    </div>
    <button onclick="window.close()" class="btn btn-sm btn-light border fw-bold px-3"><i class="fa-solid fa-xmark me-1"></i> Tutup Tab</button>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="detail-card p-4 shadow-sm d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-light text-secondary border mb-2">{{ $material->category->name ?? 'Kategori Umum' }}</span>
                <h4 class="fw-bold text-dark m-0">{{ $material->name }}</h4>
                @if($material->code)
                    <div class="text-muted fw-semibold mt-1" style="font-size: 0.85rem;">KODE BARANG: {{ $material->code }}</div>
                @endif
            </div>
            <div class="text-end border-start ps-4">
                <div class="text-uppercase fw-bold text-muted mb-1" style="font-size: 0.7rem;">TOTAL STOK TERSEDIA</div>
                <h2 class="fw-bold text-success m-0">{{ number_format($totalStock, 0, ',', '.') }} <small class="text-muted fs-6">{{ $material->satuan }}</small></h2>
            </div>
        </div>
    </div>
</div>

<div class="detail-card shadow-sm overflow-hidden">
    <div class="bg-light px-4 py-3 border-bottom">
        <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-clock-rotate-left me-2 text-theme"></i> Rincian Gelombang Masuk (SPPM)</h6>
    </div>
    <div class="table-responsive">
        <table class="table-detail">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="20%">No. Surat (SPPM)</th>
                    <th width="12%">Tgl Masuk</th>
                    <th width="10%">Lokasi Gudang</th>
                    <th width="25%">Rentang Nomor Seri</th>
                    <th width="10%" class="text-end">Qty Masuk</th>
                    <th width="18%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stockDetails as $index => $detail)
                    <tr>
                        <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                        <td>
                            <span class="fw-bold text-theme"><i class="fa-solid fa-file-invoice me-1 opacity-50"></i> {{ $detail->no_surat_masuk }}</span>
                        </td>
                        <td class="fw-semibold text-dark">
                            {{ \Carbon\Carbon::parse($detail->tgl_masuk)->format('d M Y') }}
                        </td>
                        <td>
                            <i class="fa-solid fa-warehouse text-muted me-1"></i> {{ $detail->warehouse->name ?? '-' }}
                        </td>
                        <td>
                            @if($material->pakai_seri == 1 && ($detail->seri_awal || $detail->seri_akhir))
                                <div class="serial-box">{!! $formatSeri($detail->prefix, $detail->seri_awal, $detail->seri_akhir) !!}</div>
                            @else
                                <span class="text-muted fst-italic">- Non Seri -</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fs-6 px-2 py-1">
                                + {{ number_format($detail->qty, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-muted" style="font-size: 0.75rem;">
                            {{ $detail->keterangan ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted bg-white">
                            <i class="fa-solid fa-clipboard-list fs-2 mb-2 opacity-25"></i>
                            <p class="mb-0 small">Belum ada riwayat penerimaan fisik untuk barang ini.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
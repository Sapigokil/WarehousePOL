@extends('layouts.app')
@section('title', 'Detail Riwayat Stok Barang')

@push('styles')
<style>
    .detail-card { background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; }
    .table-detail { width: 100%; border-collapse: collapse; }
    .table-detail thead th { background-color: #f8fafc; color: #475569; font-size: 0.75rem; text-transform: uppercase; padding: 12px 15px; border-bottom: 2px solid #e2e8f0; vertical-align: middle; }
    
    .sortable-header { color: #475569; text-decoration: none; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .sortable-header:hover { color: #2563eb; }

    .table-detail tbody tr { border-bottom: 1px solid #f1f5f9; }
    .table-detail tbody tr:hover { background-color: #f8fafc; }
    .table-detail td { padding: 10px 15px; vertical-align: middle; color: #334155; font-size: 0.85rem; }
    .serial-box { background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 0.8rem; font-weight: bold; color: #475569; display: inline-block;}
    
    /* Styling Group Header Normal */
    .row-group-header { cursor: pointer; background-color: #f1f5f9 !important; border-top: 2px solid #e2e8f0 !important; border-bottom: 2px solid #e2e8f0 !important; transition: all 0.2s ease;}
    .row-group-header:hover { background-color: #e2e8f0 !important; }

    /* Styling Group Header Minus (Merah) */
    .row-group-minus-header { cursor: pointer; background-color: #fff1f2 !important; border-top: 2px solid #fda4af !important; border-bottom: 2px solid #fda4af !important; transition: all 0.2s ease;}
    .row-group-minus-header:hover { background-color: #ffe4e6 !important; }

    /* Tampilan baris detail minus */
    .row-minus { background-color: #fffbfa !important; }
    .row-minus:hover { background-color: #fff1f2 !important; }
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
        
        $p = $prefix ? "<span class='text-primary'>{$prefix}</span> " : '';
        return "{$p}{$s_formatted} <span class='text-muted fw-normal mx-1'>s/d</span> {$e_formatted}";
    };

    $sortUrl = function($column) use ($material, $sortBy, $sortOrder) {
        $newOrder = ($sortBy == $column && $sortOrder == 'asc') ? 'desc' : 'asc';
        return route('stocks.show', ['stock' => $material->id, 'search' => request('search'), 'sort' => $column, 'order' => $newOrder]);
    };
    
    $sortIcon = function($column) use ($sortBy, $sortOrder) {
        if ($sortBy != $column) return '<i class="fa-solid fa-sort opacity-25"></i>';
        return $sortOrder == 'asc' ? '<i class="fa-solid fa-sort-up text-primary"></i>' : '<i class="fa-solid fa-sort-down text-primary"></i>';
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
    @if($material->pakai_seri == 1)
    <div class="bg-light px-4 py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-clock-rotate-left me-2 text-theme"></i> Rincian Gelombang Masuk (SPPM)</h6>
        <form action="{{ route('stocks.show', $material->id) }}" method="GET" class="d-flex" style="min-width: 320px;">
            <input type="hidden" name="sort" value="{{ $sortBy }}">
            <input type="hidden" name="order" value="{{ $sortOrder }}">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Cari SPPM / Rentang Seri..." value="{{ request('search') }}">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-search"></i> Cari</button>
                @if(request('search'))
                    <a href="{{ route('stocks.show', ['stock' => $material->id, 'sort' => $sortBy, 'order' => $sortOrder]) }}" class="btn btn-outline-danger" title="Reset Pencarian"><i class="fa-solid fa-times"></i></a>
                @endif
            </div>
        </form>
    </div>
    @else
    <div class="bg-light px-4 py-3 border-bottom">
        <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-clock-rotate-left me-2 text-theme"></i> Rincian Gelombang Masuk (SPPM)</h6>
    </div>
    @endif

    <div class="table-responsive">
        <table class="table-detail">
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="20%">
                        <a href="{{ $sortUrl('no_surat_masuk') }}" class="sortable-header">
                            {{ $material->pakai_seri == 1 ? 'No. Surat (SPPM)' : 'Kelompok Gudang' }} {!! $sortIcon('no_surat_masuk') !!}
                        </a>
                    </th>
                    <th width="12%">
                        <a href="{{ $sortUrl('tgl_masuk') }}" class="sortable-header">
                            {{ $material->pakai_seri == 1 ? 'Tgl Masuk' : 'Update Terakhir' }} {!! $sortIcon('tgl_masuk') !!}
                        </a>
                    </th>
                    <th width="10%">
                        <a href="{{ $sortUrl('warehouse_id') }}" class="sortable-header">Lokasi Gudang {!! $sortIcon('warehouse_id') !!}</a>
                    </th>
                    <th width="25%">
                        <a href="{{ $sortUrl('seri_awal') }}" class="sortable-header">Rentang Nomor Seri {!! $sortIcon('seri_awal') !!}</a>
                    </th>
                    <th width="10%" class="text-end">
                        <a href="{{ $sortUrl('qty') }}" class="sortable-header justify-content-end">Qty Tersedia {!! $sortIcon('qty') !!}</a>
                    </th>
                    <th width="18%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @if($normalStocks->isEmpty() && $totalMinusQty == 0)
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted bg-white">
                            <i class="fa-solid fa-clipboard-list fs-2 mb-2 opacity-25"></i>
                            <p class="mb-0 small">Belum ada data stok @if(request('search')) atau pencarian tidak ditemukan. @endif</p>
                        </td>
                    </tr>
                @else
                    
                    @if($material->pakai_seri == 1)
                        {{-- 1A. STOK BERSERI (GROUPING BY PREFIX & TAHUN DENGAN ACCORDION) --}}
                        @foreach($normalStocks as $groupLabel => $stocksGroup)
                            @php $gId = Str::slug($groupLabel); @endphp
                            
                            <tr class="row-group-header" data-bs-toggle="collapse" data-bs-target=".group-{{ $gId }}" aria-expanded="false">
                                <td colspan="7">
                                    <div class="d-flex justify-content-between align-items-center px-2">
                                        <span class="fw-bold text-theme fs-6">
                                            <i class="fa-solid fa-folder-tree me-2 text-secondary"></i> LABEL: <span class="text-dark">{{ $groupLabel }}</span>
                                        </span>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 py-2 me-3 fs-6">
                                                {{ number_format($stocksGroup->sum('qty'), 0, ',', '.') }} Stok
                                            </span>
                                            <i class="fa-solid fa-chevron-down text-secondary"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            
                            @foreach($stocksGroup as $index => $detail)
                                <tr class="collapse group-{{ $gId }}">
                                    <td class="text-center fw-bold text-muted bg-light">{{ $index + 1 }}</td>
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
                                        <div class="serial-box">{!! $formatSeri($detail->prefix, $detail->seri_awal, $detail->seri_akhir) !!}</div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fs-6 px-2 py-1">
                                             {{ number_format($detail->qty, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.75rem;">
                                        {{ $detail->keterangan ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                        
                    @else
                        {{-- 1B. STOK NON-SERI / BULK (MERGED) --}}
                        @foreach($normalStocks as $index => $detail)
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <span class="fw-bold text-success"><i class="fa-solid fa-boxes-stacked me-1 opacity-50"></i> {{ $detail->no_surat_masuk }}</span>
                                </td>
                                <td class="fw-semibold text-dark">
                                    {{ \Carbon\Carbon::parse($detail->tgl_masuk)->format('d M Y') }}
                                </td>
                                <td>
                                    <i class="fa-solid fa-warehouse text-muted me-1"></i> {{ $detail->warehouse->name ?? '-' }}
                                </td>
                                <td>
                                    <span class="text-muted fst-italic">- Non Seri -</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary fs-6 px-2 py-1">
                                         {{ number_format($detail->qty, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-muted" style="font-size: 0.75rem;">
                                    {{ $detail->keterangan ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    {{-- 2. BARIS KHUSUS STOK MINUS (IDENTIK DENGAN ACCORDION GROUP) --}}
                    @if($totalMinusQty < 0)
                        
                        @if($material->pakai_seri == 1 && count($mergedMinusRanges) > 0)
                            
                            {{-- ACCORDION HEADER KHUSUS MINUS --}}
                            <tr class="row-group-minus-header" data-bs-toggle="collapse" data-bs-target=".group-minus-data" aria-expanded="false">
                                <td colspan="7">
                                    <div class="d-flex justify-content-between align-items-center px-2">
                                        <span class="fw-bold text-danger fs-6">
                                            <i class="fa-solid fa-circle-exclamation me-2 text-danger"></i> LABEL: <span class="text-danger">DATA MASUK TIDAK DIKETAHUI</span>
                                        </span>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-danger text-white px-3 py-2 me-3 fs-6">
                                                {{ number_format($totalMinusQty, 0, ',', '.') }} Stok
                                            </span>
                                            <i class="fa-solid fa-chevron-down text-danger"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- BARIS DETAIL MINUS --}}
                            @foreach($mergedMinusRanges as $index => $mRange)
                                @php 
                                    // Hitung QTY spesifik untuk rentang ini
                                    $rangeQty = -($mRange['akhir'] - $mRange['awal'] + 1); 
                                @endphp
                                <tr class="collapse group-minus-data row-minus">
                                    <td class="text-center fw-bold text-muted bg-light">{{ $index + 1 }}</td>
                                    
                                    {{-- Nama SPPM, TGL, dan Lokasi dikosongkan sesuai instruksi --}}
                                    <td class="text-center text-muted">-</td>
                                    <td class="text-center text-muted">-</td>
                                    <td class="text-center text-muted">-</td>
                                    
                                    <td>
                                        <div class="serial-box bg-white border-danger text-danger">
                                            {!! $formatSeri($mRange['prefix'], $mRange['awal'], $mRange['akhir']) !!}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-danger text-white fs-6 px-2 py-1">
                                            {{ number_format($rangeQty, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-danger fw-semibold" style="font-size: 0.75rem;">
                                        Menunggu Rekonsiliasi
                                    </td>
                                </tr>
                            @endforeach

                        @else
                            {{-- TAMPILAN BULK MINUS JIKA NON-SERI --}}
                            <tr class="row-minus">
                                <td class="text-center fw-bold text-danger fs-5">*</td>
                                <td>
                                    <span class="fw-bold text-danger"><i class="fa-solid fa-circle-exclamation me-1 opacity-75"></i> Data Masuk tidak diketahui</span>
                                </td>
                                <td class="fw-semibold text-center text-muted">-</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center text-muted">
                                    <span class="fst-italic">- Non Seri -</span>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-danger text-white fs-6 px-2 py-1">
                                        {{ number_format($totalMinusQty, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-danger fw-semibold" style="font-size: 0.75rem;">
                                    Menunggu Rekonsiliasi
                                </td>
                            </tr>
                        @endif

                    @endif

                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection
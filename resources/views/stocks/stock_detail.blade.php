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
    
    /* Styling khusus untuk accordion di dalam tabel */
    .accordion-minus .accordion-button { padding: 6px 12px; font-size: 0.8rem; font-weight: 600; background-color: #fff1f2; color: #e11d48; border-radius: 6px !important; box-shadow: none; border: 1px solid #fecdd3; }
    .accordion-minus .accordion-button:not(.collapsed) { background-color: #ffe4e6; color: #be123c; border-color: #fda4af; box-shadow: none; }
    .accordion-minus .accordion-button::after { background-size: 1rem; }
    .accordion-minus .accordion-body { padding: 10px; background-color: #fffafb; border: 1px solid #fecdd3; border-top: none; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; max-height: 200px; overflow-y: auto;}
    .row-minus { background-color: #fffbfa !important; border-bottom: 2px solid #ffe4e6 !important; }
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
                    {{-- Dinamis ubah judul header jika Non-Seri --}}
                    <th width="20%">{{ $material->pakai_seri == 1 ? 'No. Surat (SPPM)' : 'Kelompok Gudang' }}</th>
                    <th width="12%">{{ $material->pakai_seri == 1 ? 'Tgl Masuk' : 'Update Terakhir' }}</th>
                    <th width="10%">Lokasi Gudang</th>
                    <th width="25%">Rentang Nomor Seri</th>
                    <th width="10%" class="text-end">Qty Tersedia</th>
                    <th width="18%">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @if($normalStocks->isEmpty() && $totalMinusQty == 0)
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted bg-white">
                            <i class="fa-solid fa-clipboard-list fs-2 mb-2 opacity-25"></i>
                            <p class="mb-0 small">Belum ada riwayat penerimaan fisik untuk barang ini.</p>
                        </td>
                    </tr>
                @else
                    {{-- 1. LOOPING STOK NORMAL (POSITIF) --}}
                    @foreach($normalStocks as $index => $detail)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                @if($material->pakai_seri == 1)
                                    <span class="fw-bold text-theme"><i class="fa-solid fa-file-invoice me-1 opacity-50"></i> {{ $detail->no_surat_masuk }}</span>
                                @else
                                    {{-- Tampilan khusus Non-Seri (Menggunakan ikon dus) --}}
                                    <span class="fw-bold text-success"><i class="fa-solid fa-boxes-stacked me-1 opacity-50"></i> {{ $detail->no_surat_masuk }}</span>
                                @endif
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
                                     {{ number_format($detail->qty, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-muted" style="font-size: 0.75rem;">
                                {{ $detail->keterangan ?? '-' }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- 2. BARIS KHUSUS STOK MINUS (SELALU DI BAWAH) --}}
                    @if($totalMinusQty < 0)
                        <tr class="row-minus">
                            <td class="text-center fw-bold text-danger fs-5">*</td>
                            <td>
                                <span class="fw-bold text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i> Data Masuk tidak diketahui</span>
                            </td>
                            <td class="fw-semibold text-muted text-center">-</td>
                            <td class="text-muted text-center">-</td>
                            <td>
                                @if($material->pakai_seri == 1)
                                    @if(count($mergedMinusRanges) == 0)
                                        <span class="text-muted fst-italic">-</span>
                                    @elseif(count($mergedMinusRanges) == 1)
                                        <div class="serial-box bg-white border-danger text-danger">
                                            {!! $formatSeri($mergedMinusRanges[0]['prefix'], $mergedMinusRanges[0]['awal'], $mergedMinusRanges[0]['akhir']) !!}
                                        </div>
                                    @else
                                        {{-- ACCORDION UNTUK MULTI RENTANG SERI MINUS --}}
                                        <div class="accordion accordion-minus accordion-flush" id="accordionMinus">
                                            <div class="accordion-item border-0 bg-transparent">
                                                <h2 class="accordion-header" id="headingMinus">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMinus" aria-expanded="false" aria-controls="collapseMinus">
                                                        <i class="fa-solid fa-layer-group me-2"></i> Terdapat {{ count($mergedMinusRanges) }} Rentang Seri
                                                    </button>
                                                </h2>
                                                <div id="collapseMinus" class="accordion-collapse collapse mt-1" aria-labelledby="headingMinus" data-bs-parent="#accordionMinus">
                                                    <div class="accordion-body shadow-sm">
                                                        <ul class="list-unstyled mb-0 m-0 p-0">
                                                            @foreach($mergedMinusRanges as $mRange)
                                                                <li class="mb-2">
                                                                    <div class="serial-box w-100 bg-white border-danger text-danger text-center">
                                                                        {!! $formatSeri($mRange['prefix'], $mRange['awal'], $mRange['akhir']) !!}
                                                                    </div>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted fst-italic">- Non Seri -</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger fs-6 px-2 py-1">
                                    {{ number_format($totalMinusQty, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="text-danger fw-semibold" style="font-size: 0.75rem;">
                                -
                            </td>
                        </tr>
                    @endif

                @endif
            </tbody>
        </table>
    </div>
</div>

@endsection
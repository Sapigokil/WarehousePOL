@extends('layouts.app')
@section('title', 'Detail SPPM Keluar')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-file-export me-2"></i> Detail Dokumen SPPM Keluar</h4>
        <div class="text-muted small mt-1">Mode Read-Only untuk melihat rincian barang keluar</div>
    </div>
    <a href="{{ route('outbounds.index') }}" class="btn btn-sm btn-light border fw-bold px-3 shadow-sm">
        <i class="fa-solid fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card shadow-sm border-0 mb-4" style="border-radius: 8px;">
    <div class="card-body p-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="35%" class="text-muted fw-semibold">No. SPPM</td>
                        <td width="5%">:</td>
                        <td class="fw-bold text-dark">{{ $outbound->sppm_no }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Tanggal SPPM</td>
                        <td>:</td>
                        <td class="fw-bold text-dark">{{ \Carbon\Carbon::parse($outbound->sppm_date)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Tujuan Pengiriman</td>
                        <td>:</td>
                        <td class="fw-bold text-dark">
                            <i class="fa-solid fa-map-location-dot text-danger opacity-75 me-1"></i> {{ $outbound->destination->name ?? '-' }}
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="35%" class="text-muted fw-semibold">Info Penerima</td>
                        <td width="5%">:</td>
                        <td class="fw-bold text-dark">
                            @if($outbound->nama_bamat)
                                {{ $outbound->nama_bamat }} <span class="text-muted fw-normal">({{ $outbound->pangkat ?? '-' }})</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Status Dokumen</td>
                        <td>:</td>
                        <td>
                            @if($outbound->status == 'completed')
                                <span class="badge bg-success">FINAL</span>
                            @else
                                <span class="badge bg-secondary">DRAFT</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Pembaruan Terakhir</td>
                        <td>:</td>
                        <td>
                            <span class="text-dark fw-semibold">{{ $outbound->updated_at->format('d M Y, H:i') }}</span>
                            <span class="text-muted small">oleh {{ $outbound->updater->name ?? 'Sistem' }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if($outbound->keterangan)
        <div class="bg-light p-3 rounded border">
            <div class="text-muted fw-semibold small mb-1">Catatan / Keterangan:</div>
            <div class="text-dark" style="font-size: 0.9rem;">{{ $outbound->keterangan }}</div>
        </div>
        @endif
    </div>
</div>

<div class="card shadow-sm border-0" style="border-radius: 8px;">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-list-check me-2 text-danger"></i> Rincian Seluruh Barang Keluar Berdasarkan Master</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-nowrap" style="font-size: 0.9rem;">
                <thead class="bg-light">
                    <tr>
                        <th width="40%">Nama Barang / Varian</th>
                        <th width="15%" class="text-center">Total Harga</th>
                        <th width="15%" class="text-center">Jumlah Keluar</th>
                        <th width="30%">Info Seri Fisik Terpotong</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $formatSeri = function($prefix, $start, $end) {
                            if (is_null($start) && is_null($end)) return '-';
                            $padAndDot = function($num) {
                                $s = str_pad($num ?? 0, 9, '0', STR_PAD_LEFT);
                                return substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3);
                            };
                            $s_formatted = $padAndDot($start);
                            $e_formatted = $padAndDot($end);
                            $p = $prefix ? "<span class='text-danger fw-bold me-1'>{$prefix}</span>" : '';
                            return "{$p}<span class='fw-bold'>{$s_formatted}</span> s/d <span class='fw-bold'>{$e_formatted}</span>";
                        };

                        // Ekstrak Parent dan Child
                        $parents = $outbound->details->filter(function($d) {
                            return is_null($d->material->parent_id);
                        })->sortBy(function($d) {
                            return $d->material->nomor_urut ?? 9999;
                        });

                        $childrenGrouped = $outbound->details->filter(function($d) {
                            return !is_null($d->material->parent_id);
                        })->groupBy('material.parent_id');
                    @endphp

                    @forelse($parents as $parentDetail)
                        @php
                            $isParentHeader = $parentDetail->material->children()->count() > 0;
                        @endphp
                        
                        @if($isParentHeader)
                            {{-- BARIS INDUK (PARENT HEADER) --}}
                            <tr class="bg-light">
                                <td class="fw-bold text-dark text-uppercase" colspan="4">
                                    <i class="fa-solid fa-folder-open text-danger me-2 opacity-75"></i> {{ $parentDetail->material->name }}
                                </td>
                            </tr>
                            
                            {{-- RENDER ANAK-ANAKNYA --}}
                            @if(isset($childrenGrouped[$parentDetail->material_id]))
                                @php
                                    $sortedChildren = $childrenGrouped[$parentDetail->material_id]->sortBy(function($c) {
                                        return $c->material->nomor_urut ?? 9999;
                                    });
                                @endphp
                                @foreach($sortedChildren as $childDetail)
                                    <tr>
                                        <td class="fw-semibold">
                                            <span style="margin-left: 1.5rem;"><i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-2 opacity-50"></i></span>
                                            {{ $childDetail->material->name ?? '-' }}
                                            <span class="text-muted fw-normal ms-1">({{ $childDetail->material->satuan ?? '-' }})</span>
                                        </td>
                                        <td class="text-center text-muted">
                                            {{ $childDetail->harga_total > 0 ? 'Rp ' . number_format($childDetail->harga_total, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-center fw-bold text-primary">
                                            {{ $childDetail->target_qty > 0 ? number_format($childDetail->target_qty, 0, ',', '.') : '-' }}
                                        </td>
                                        <td>
                                            @if($outbound->status == 'completed' && $childDetail->material->pakai_seri == 1 && $childDetail->target_qty > 0)
                                                @php
                                                    $outStocks = App\Models\OutStock::whereHas('outLog', function($q) use ($outbound) {
                                                        $q->where('out_sppm_id', $outbound->id);
                                                    })->whereHas('stock', function($q) use ($childDetail) {
                                                        $q->where('material_id', $childDetail->material_id);
                                                    })->get();
                                                @endphp
                                                @forelse($outStocks as $st)
                                                    @if($st->seri_awal || $st->seri_akhir)
                                                        <span class="d-inline-block text-muted me-2 mb-1" style="font-size: 0.7rem; background:#f8fafc; border: 1px solid #e2e8f0; border-radius:4px; padding:2px 6px;">
                                                            {!! $formatSeri($st->prefix, $st->seri_awal, $st->seri_akhir) !!} 
                                                            <span class="ms-1 fw-bold text-dark">({{ $st->qty_keluar }} pcs)</span>
                                                        </span>
                                                    @endif
                                                @empty
                                                    <span class="text-muted fst-italic small">-</span>
                                                @endforelse
                                            @elseif($outbound->status != 'completed')
                                                <span class="text-muted fst-italic small">Belum terpotong (Draft)</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @else
                            {{-- BARIS STANDALONE --}}
                            <tr>
                                <td class="fw-semibold">
                                    <i class="fa-solid fa-cube text-muted me-2 opacity-25"></i>
                                    {{ $parentDetail->material->name ?? '-' }}
                                    <span class="text-muted fw-normal ms-1">({{ $parentDetail->material->satuan ?? '-' }})</span>
                                </td>
                                <td class="text-center text-muted">
                                    {{ $parentDetail->harga_total > 0 ? 'Rp ' . number_format($parentDetail->harga_total, 0, ',', '.') : '-' }}
                                </td>
                                <td class="text-center fw-bold text-primary">
                                    {{ $parentDetail->target_qty > 0 ? number_format($parentDetail->target_qty, 0, ',', '.') : '-' }}
                                </td>
                                <td>
                                    @if($outbound->status == 'completed' && $parentDetail->material->pakai_seri == 1 && $parentDetail->target_qty > 0)
                                        @php
                                            $outStocks = App\Models\OutStock::whereHas('outLog', function($q) use ($outbound) {
                                                $q->where('out_sppm_id', $outbound->id);
                                            })->whereHas('stock', function($q) use ($parentDetail) {
                                                $q->where('material_id', $parentDetail->material_id);
                                            })->get();
                                        @endphp
                                        @forelse($outStocks as $st)
                                            @if($st->seri_awal || $st->seri_akhir)
                                                <span class="d-inline-block text-muted me-2 mb-1" style="font-size: 0.7rem; background:#f8fafc; border: 1px solid #e2e8f0; border-radius:4px; padding:2px 6px;">
                                                    {!! $formatSeri($st->prefix, $st->seri_awal, $st->seri_akhir) !!} 
                                                    <span class="ms-1 fw-bold text-dark">({{ $st->qty_keluar }} pcs)</span>
                                                </span>
                                            @endif
                                        @empty
                                            <span class="text-muted fst-italic small">-</span>
                                        @endforelse
                                    @elseif($outbound->status != 'completed')
                                        <span class="text-muted fst-italic small">Belum terpotong (Draft)</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted fst-italic">Tidak ada rincian barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
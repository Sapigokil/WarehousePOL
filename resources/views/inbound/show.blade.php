@extends('layouts.app')
@section('title', 'Detail SPPM Inbound')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0 text-theme"><i class="fa-solid fa-file-lines me-2"></i> Detail Dokumen SPPM</h4>
        <div class="text-muted small mt-1">Mode Read-Only untuk melihat rincian manifes masuk</div>
    </div>
    <a href="{{ route('inbound.index') }}" class="btn btn-sm btn-light border fw-bold px-3 shadow-sm"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
</div>

<div class="card shadow-sm border-0 mb-4" style="border-radius: 8px;">
    <div class="card-body p-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="35%" class="text-muted fw-semibold">No. SPPM</td>
                        <td width="5%">:</td>
                        <td class="fw-bold text-dark">{{ $inbound->sppm_no }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Tanggal SPPM</td>
                        <td>:</td>
                        <td class="fw-bold text-dark">{{ \Carbon\Carbon::parse($inbound->sppm_date)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Kategori Materiil</td>
                        <td>:</td>
                        <td class="fw-bold text-dark">
                            <span class="badge bg-secondary bg-opacity-10 text-dark border px-2 py-1">{{ $inbound->category->name ?? '-' }}</span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="35%" class="text-muted fw-semibold">Lokasi Gudang</td>
                        <td width="5%">:</td>
                        <td class="fw-bold text-dark">{{ $inbound->warehouse->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Status Penerimaan</td>
                        <td>:</td>
                        <td>
                            @if($inbound->status == 'completed')
                                <span class="badge bg-success">SELESAI</span>
                            @elseif($inbound->status == 'partial')
                                <span class="badge bg-warning text-dark">PARSIAL</span>
                            @else
                                <span class="badge bg-secondary">TUNDA</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold">Pembaruan Terakhir</td>
                        <td>:</td>
                        <td>
                            <span class="text-dark fw-semibold">{{ $inbound->updated_at->format('d M Y, H:i') }}</span>
                            <span class="text-muted small">oleh {{ $inbound->updater->name ?? 'Sistem' }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if($inbound->notes)
        <div class="bg-light p-3 rounded border">
            <div class="text-muted fw-semibold small mb-1">Catatan / Keterangan Manifes:</div>
            <div class="text-dark" style="font-size: 0.9rem;">{{ $inbound->notes }}</div>
        </div>
        @endif
    </div>
</div>

{{-- FITUR BARU: AUTO-PREVIEW LAMPIRAN PDF / GAMBAR --}}
@if($inbound->file_lampiran)
<div class="card shadow-sm border-0 mb-4" style="border-radius: 8px;">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-file-pdf me-2 text-danger"></i> Preview Scan Fisik SPPM</h6>
        <a href="{{ asset('storage/' . $inbound->file_lampiran) }}" target="_blank" class="btn btn-sm btn-outline-primary fw-bold px-3">
            <i class="fa-solid fa-external-link-alt me-1"></i> Buka Fullscreen
        </a>
    </div>
    <div class="card-body p-0 bg-light text-center" style="border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; overflow: hidden;">
        @php
            $extension = strtolower(pathinfo($inbound->file_lampiran, PATHINFO_EXTENSION));
            $fileUrl = asset('storage/' . $inbound->file_lampiran);
        @endphp

        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
            <img src="{{ $fileUrl }}" alt="Lampiran SPPM" class="img-fluid m-3 shadow-sm border" style="max-height: 600px; object-fit: contain;">
        @elseif($extension === 'pdf')
            <iframe src="{{ $fileUrl }}" width="100%" height="600px" style="border: none;"></iframe>
        @else
            <div class="p-5 text-muted">
                <i class="fa-solid fa-file fs-1 mb-3 opacity-50"></i>
                <p class="mb-0">Format file <strong>.{{ $extension }}</strong> tidak mendukung preview otomatis.<br>Silakan klik tombol <strong>Buka Fullscreen</strong> untuk mengunduh/melihatnya.</p>
            </div>
        @endif
    </div>
</div>
@endif

<div class="card shadow-sm border-0" style="border-radius: 8px;">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-box-open me-2 text-theme"></i> Rincian Seluruh Barang Berdasarkan Master</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 text-nowrap" style="font-size: 0.9rem;">
                <thead class="bg-light">
                    <tr>
                        <th width="40%">Nama Barang / Varian</th>
                        <th width="15%" class="text-center">QTY Diterima</th>
                        <th width="15%" class="text-end">Harga Satuan</th>
                        <th width="15%" class="text-end">Total Harga</th>
                        <th width="15%">Info Seri Fisik</th>
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
                            $p = $prefix ? "<span class='text-primary fw-bold me-1'>{$prefix}</span>" : '';
                            return "{$p}<span class='fw-bold'>{$s_formatted}</span> s/d <span class='fw-bold'>{$e_formatted}</span>";
                        };

                        // Ekstrak Parent dan Child
                        $parents = $inbound->details->filter(function($d) {
                            return is_null($d->material->parent_id);
                        })->sortBy(function($d) {
                            return $d->material->nomor_urut ?? 9999;
                        });

                        $childrenGrouped = $inbound->details->filter(function($d) {
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
                                <td class="fw-bold text-dark text-uppercase" colspan="5">
                                    <i class="fa-solid fa-folder-open text-theme me-2 opacity-75"></i> {{ $parentDetail->material->name }}
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
                                        <td class="text-center fw-bold text-primary">{{ $childDetail->target_qty > 0 ? number_format($childDetail->target_qty, 0, ',', '.') : '-' }}</td>
                                        <td class="text-end text-muted">{{ $childDetail->harga_satuan > 0 ? 'Rp ' . number_format($childDetail->harga_satuan, 0, ',', '.') : '-' }}</td>
                                        <td class="text-end fw-semibold text-secondary">{{ $childDetail->harga_total > 0 ? 'Rp ' . number_format($childDetail->harga_total, 0, ',', '.') : '-' }}</td>
                                        <td>
                                            @if($childDetail->material && $childDetail->material->pakai_seri == 1 && ($childDetail->sppm_serial_start || $childDetail->sppm_serial_end))
                                                <small class="bg-white border rounded px-2 py-1">
                                                    {!! $formatSeri($childDetail->sppm_serial_prefix, $childDetail->sppm_serial_start, $childDetail->sppm_serial_end) !!}
                                                </small>
                                            @else
                                                <span class="text-muted fst-italic">-</span>
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
                                <td class="text-center fw-bold text-primary">{{ $parentDetail->target_qty > 0 ? number_format($parentDetail->target_qty, 0, ',', '.') : '-' }}</td>
                                <td class="text-end text-muted">{{ $parentDetail->harga_satuan > 0 ? 'Rp ' . number_format($parentDetail->harga_satuan, 0, ',', '.') : '-' }}</td>
                                <td class="text-end fw-semibold text-secondary">{{ $parentDetail->harga_total > 0 ? 'Rp ' . number_format($parentDetail->harga_total, 0, ',', '.') : '-' }}</td>
                                <td>
                                    @if($parentDetail->material && $parentDetail->material->pakai_seri == 1 && ($parentDetail->sppm_serial_start || $parentDetail->sppm_serial_end))
                                        <small class="bg-white border rounded px-2 py-1">
                                            {!! $formatSeri($parentDetail->sppm_serial_prefix, $parentDetail->sppm_serial_start, $parentDetail->sppm_serial_end) !!}
                                        </small>
                                    @else
                                        <span class="text-muted fst-italic">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted fst-italic">Tidak ada rincian barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
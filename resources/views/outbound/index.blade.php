@extends('layouts.app')
@section('title', 'Transaksi Barang Keluar')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; background: linear-gradient(135deg, #e11d48, #be123c); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.15; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }
    
    .table-dense { width: 100%; border-collapse: collapse; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .table-dense thead { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .table-dense thead th { color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 15px; font-weight: 700; vertical-align: middle; }
    .table-dense tbody tr.main-row { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
    .table-dense tbody tr.main-row:hover { background-color: #f8fafc; }
    .table-dense td { padding: 10px 15px; vertical-align: middle; color: #334155; font-size: 0.85rem; }
    
    .status-badge { font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.5px; }
    .accordion-toggle { cursor: pointer; text-decoration: none; color: inherit; display: block; width: 100%; text-align: left; background: none; border: none; padding: 0; }
    .accordion-toggle i.fa-chevron-right { transition: transform 0.2s ease; font-size: 0.8rem; color: #94a3b8; }
    .accordion-toggle:not(.collapsed) i.fa-chevron-right { transform: rotate(90deg); }
    
    .nested-table-container { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .nested-table { width: 100%; font-size: 0.8rem; background: #fff; }
    .nested-table th { font-weight: 700; text-transform: uppercase; font-size: 0.7rem; color: #64748b; border-bottom: 1px solid #e2e8f0; padding: 10px; }
    .nested-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
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
        
        $p = $prefix ? "<span class='text-danger fw-bold me-1'>{$prefix}</span>" : '';
        return "{$p}<span class='fw-bold'>{$s_formatted}</span> s/d <span class='fw-bold'>{$e_formatted}</span>";
    };
@endphp

<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-truck-fast header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-truck-fast me-2"></i> Pengeluaran / Barang Keluar</h4>
        <p class="mb-0 text-white-50 small">Kelola dokumen Surat Perintah Pengiriman Materiil (SPPM) Keluar.</p>
    </div>
    <div class="header-content">
        <a href="{{ route('outbounds.create') }}" class="btn btn-light fw-bold text-danger shadow-sm px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-plus me-1"></i> SPPM Keluar Baru
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('outbounds.index') }}" class="d-flex align-items-center w-100">
        <div class="me-3 d-flex align-items-center">
            <select name="limit" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="width: 70px;">
                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
        <div class="flex-grow-1"></div>
        <div class="input-group input-group-sm shadow-sm" style="width: 300px;">
            <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Cari No SPPM / Tujuan..." value="{{ $search }}">
            <button class="btn btn-white border-0 bg-white px-3" type="submit"><i class="fa-solid fa-magnifying-glass text-muted"></i></button>
        </div>
    </form>
</div>

<div class="table-responsive shadow-sm" style="border-radius: 8px; background: white;">
    <table class="table-dense">
        <thead>
            <tr>
                <th width="25%">No Dokumen SPPM</th>
                <th width="20%">Tujuan Pengiriman</th>
                <th width="15%">Tgl Dokumen</th>
                <th width="15%">Pembaruan Terakhir</th>
                <th width="10%" class="text-center">Status</th>
                <th width="15%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($outbounds as $sppm)
            <tr class="main-row">
                <td>
                    <button class="accordion-toggle collapsed text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSppm{{ $sppm->id }}" aria-expanded="false">
                        <i class="fa-solid fa-chevron-right me-2"></i>
                        <i class="fa-solid fa-file-export text-danger me-2 opacity-75"></i> <span class="fw-bold text-dark">{{ $sppm->sppm_no }}</span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border ms-2" style="font-size: 0.7rem;">{{ $sppm->details->count() }} Item</span>
                    </button>
                </td>
                <td class="fw-bold text-secondary">
                    <i class="fa-solid fa-map-location-dot me-1 opacity-50"></i> {{ $sppm->destination->name ?? 'Tidak Diketahui' }}
                </td>
                <td class="fw-semibold">
                    {{ \Carbon\Carbon::parse($sppm->sppm_date)->format('d M Y') }}
                </td>
                <td>
                    <span class="text-dark d-block fw-semibold" style="font-size: 0.8rem;">{{ $sppm->updated_at->diffForHumans() }}</span>
                    <span class="text-muted d-block mt-0.5" style="font-size: 0.75rem;"><i class="fa-solid fa-user-pen me-1 opacity-50"></i>{{ $sppm->updater->name ?? 'Sistem' }}</span>
                </td>
                <td class="text-center">
                    @if($sppm->status == 'completed')
                        <span class="status-badge bg-success bg-opacity-10 text-success border border-success">FINAL</span>
                    @else
                        <span class="status-badge bg-secondary bg-opacity-10 text-secondary border border-secondary">DRAFT</span>
                    @endif
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center align-items-center flex-nowrap gap-1">
                        @if($sppm->status == 'completed')
                            <!-- TOMBOL CETAK NEW TAB -->
                            <a href="{{ route('outbounds.print', $sppm->id) }}" target="_blank" class="btn btn-sm btn-info text-white border-0 shadow-sm rounded-1 px-2 py-0.5" title="Cetak SPPM">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            <a href="{{ route('outbounds.edit', $sppm->id) }}" class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-0.5" title="Lihat Data">
                                <i class="fa-solid fa-eye text-primary"></i>
                            </a>
                        @else
                            <a href="{{ route('outbounds.edit', $sppm->id) }}" class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-0.5" title="Edit Draft">
                                <i class="fa-solid fa-pen text-danger"></i>
                            </a>
                        @endif

                        <form action="{{ route('outbounds.destroy', $sppm->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('{{ $sppm->status == 'completed' ? 'Yakin membatalkan SPPM Final ini? Seluruh pemotongan stok & nomor seri akan dikembalikan ke gudang secara utuh.' : 'Yakin menghapus draft ini?' }}');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-0.5" title="Batalkan & Hapus Data">
                                <i class="fa-solid fa-trash text-danger"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <tr class="border-0">
                <td colspan="6" class="p-0 border-0">
                    <div class="collapse" id="collapseSppm{{ $sppm->id }}">
                        <div class="nested-table-container px-4 py-3">
                            <h6 class="fw-bold text-danger mb-2" style="font-size: 0.8rem;"><i class="fa-solid fa-list-check me-1"></i> RINCIAN BARANG KELUAR</h6>
                            
                            <div class="table-responsive bg-white border rounded">
                                <table class="table nested-table mb-0 text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang / Varian</th>
                                            <th class="text-center">Total Harga</th>
                                            <th class="text-center text-primary border-start">Jumlah Keluar</th>
                                            <th class="border-start">Potongan Nomor Seri (Fisik)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sppm->details as $detail)
                                            <tr>
                                                <td class="fw-semibold align-middle">
                                                    {{ $detail->material->name }} <span class="text-muted fw-normal ms-1">({{ $detail->material->satuan }})</span>
                                                </td>
                                                <td class="text-center text-muted align-middle">
                                                    Rp {{ number_format($detail->harga_total, 0, ',', '.') }}
                                                </td>
                                                <td class="text-center fw-bold text-primary bg-primary bg-opacity-10 border-start align-middle">
                                                    {{ number_format($detail->target_qty, 0, ',', '.') }}
                                                </td>
                                                
                                                <td class="border-start align-middle">
                                                    @if($sppm->status == 'completed' && $detail->material->pakai_seri == 1)
                                                        @php
                                                            $outStocks = App\Models\OutStock::whereHas('outLog', function($q) use ($sppm) {
                                                                $q->where('out_sppm_id', $sppm->id);
                                                            })->whereHas('stock', function($q) use ($detail) {
                                                                $q->where('material_id', $detail->material_id);
                                                            })->get();
                                                        @endphp
                                                        @foreach($outStocks as $st)
                                                            @if($st->seri_awal || $st->seri_akhir)
                                                                <span class="d-inline-block text-muted me-2 mb-1" style="font-size: 0.65rem; background:#f8fafc; border: 1px solid #e2e8f0; border-radius:4px; padding:2px 6px;">
                                                                    {!! $formatSeri($st->prefix, $st->seri_awal, $st->seri_akhir) !!} 
                                                                    <span class="ms-1 fw-bold text-dark">({{ $st->qty_keluar }} pcs)</span>
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    @elseif($sppm->status != 'completed')
                                                        <span class="text-muted fst-italic small">Belum terpotong (Draft)</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted bg-white">
                    <i class="fa-solid fa-file-circle-xmark fs-2 mb-2 opacity-25"></i>
                    <p class="mb-0 small">Belum ada dokumen SPPM Keluar.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-end mt-3">
    {{ $outbounds->links('pagination::bootstrap-5') }}
</div>

@endsection
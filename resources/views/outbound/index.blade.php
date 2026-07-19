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

    /* Modal Print Styling Kustom & Responsif */
    .custom-print-modal { max-width: 85%; }
    .print-toolbar { background-color: #f1f5f9; color: #334155; padding: 15px 20px; border-radius: 8px 8px 0 0; border-bottom: 1px solid #cbd5e1; }
    .iframe-container { background-color: #e2e8f0; padding: 20px; overflow-y: auto; height: 80vh; border-radius: 0 0 8px 8px; }
    .print-iframe { width: 100%; height: 100%; border: none; background: transparent; }
    
    /* Pengaturan input dropdown pada toolbar */
    .toolbar-select { background-color: #ffffff; color: #334155; border: 1px solid #cbd5e1; font-weight: 600; }
    .toolbar-select:focus { background-color: #ffffff; color: #334155; border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.2); }
    
    /* Tombol Kustom Toolbar Independen */
    .btn-toolbar-custom { font-weight: 700; padding: 0.4rem 1rem; border-radius: 6px; transition: all 0.2s ease-in-out; display: inline-flex; align-items: center; justify-content: center; }
    .btn-copy-custom { background-color: #475569; color: #ffffff; border: 1px solid #475569; }
    .btn-copy-custom:hover { background-color: #334155; color: #ffffff; }
    .btn-pdf-custom { background-color: #dc2626; color: #ffffff; border: 1px solid #dc2626; }
    .btn-pdf-custom:hover { background-color: #b91c1c; color: #ffffff; }
    .btn-print-custom { background-color: #2563eb; color: #ffffff; border: 1px solid #2563eb; }
    .btn-print-custom:hover { background-color: #1d4ed8; color: #ffffff; }
    .btn-close-custom { background-color: #ffffff; color: #475569; border: 1px solid #cbd5e1; }
    .btn-close-custom:hover { background-color: #f8fafc; color: #0f172a; }

    /* Responsivitas Layar Kecil (Mobile & Tablet) */
    @media (max-width: 992px) {
        .custom-print-modal { max-width: 98%; margin: 10px auto; }
        .print-toolbar { flex-direction: column; gap: 15px; align-items: stretch !important; text-align: center; }
        .print-toolbar .toolbar-controls, .print-toolbar .toolbar-actions { display: flex; flex-wrap: wrap; justify-content: center; width: 100%; gap: 10px; }
        .btn-toolbar-custom { padding: 0.4rem 0.6rem; font-size: 0.85rem; }
    }
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
                            <button type="button" class="btn btn-sm btn-info text-white border-0 shadow-sm rounded-1 px-2 py-0.5 btn-open-print" data-url="{{ route('outbounds.print', $sppm->id) }}" title="Cetak / Preview SPPM">
                                <i class="fa-solid fa-print"></i>
                            </button>
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

<!-- MODAL PREVIEW & CETAK -->
<div class="modal fade" id="printModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog custom-print-modal modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent shadow-none">
            
            <!-- Toolbar Control Flex/Responsive -->
            <div class="print-toolbar d-flex align-items-center shadow">
                <div class="toolbar-controls d-flex align-items-center gap-3">
                    <h5 class="m-0 fw-bold d-none d-md-block"><i class="fa-solid fa-print me-2 text-primary"></i> Pratinjau</h5>
                    
                    <select id="ctrl-size" class="form-select form-select-sm shadow-none toolbar-select" style="width: 80px;">
                        <option value="A4">A4</option>
                        <option value="F4">F4</option>
                    </select>

                    <select id="ctrl-orientation" class="form-select form-select-sm shadow-none toolbar-select" style="width: 110px;">
                        <option value="portrait">Portrait</option>
                        <option value="landscape">Landscape</option>
                    </select>
                </div>

                <div class="toolbar-actions d-flex gap-2 ms-auto">
                    <button id="btn-copy" class="btn btn-toolbar-custom btn-copy-custom"><i class="fa-regular fa-copy me-1"></i> Copy Text</button>
                    <button id="btn-pdf" class="btn btn-toolbar-custom btn-pdf-custom"><i class="fa-solid fa-file-pdf me-1"></i> Save PDF</button>
                    <button id="btn-print" class="btn btn-toolbar-custom btn-print-custom"><i class="fa-solid fa-print me-1"></i> Print</button>
                    <button type="button" class="btn btn-toolbar-custom btn-close-custom" data-bs-dismiss="modal"><i class="fa-solid fa-times me-1"></i> Tutup</button>
                </div>
            </div>
            
            <!-- Iframe Container -->
            <div class="iframe-container shadow">
                <div class="text-center text-secondary mt-5 d-none" id="print-loader">
                    <div class="spinner-border mb-2" role="status"></div>
                    <p class="fw-semibold">Memuat Dokumen...</p>
                </div>
                <iframe id="print-iframe" class="print-iframe" src=""></iframe>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const printModalEl = document.getElementById('printModal');
        const printModal = new bootstrap.Modal(printModalEl);
        const iframe = document.getElementById('print-iframe');
        const loader = document.getElementById('print-loader');
        
        const ctrlSize = document.getElementById('ctrl-size');
        const ctrlOrientation = document.getElementById('ctrl-orientation');

        // Buka Modal & Set iframe src
        document.querySelectorAll('.btn-open-print').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.getAttribute('data-url');
                iframe.classList.add('d-none');
                loader.classList.remove('d-none');
                
                iframe.src = url;
                printModal.show();
            });
        });

        // Hapus src ketika modal ditutup
        printModalEl.addEventListener('hidden.bs.modal', function () {
            iframe.src = '';
        });

        // Saat Iframe selesai dimuat
        iframe.onload = function() {
            if (iframe.src !== window.location.href && iframe.src !== '') {
                loader.classList.add('d-none');
                iframe.classList.remove('d-none');
                updateIframeLayout(); // Aplikasikan setting kertas default
            }
        };

        // Fungsi Kirim Pesan ke Iframe untuk Rubah Layout
        function updateIframeLayout() {
            const size = ctrlSize.value;
            const orientation = ctrlOrientation.value;
            
            iframe.contentWindow.postMessage({
                action: 'changeLayout',
                size: size,
                orientation: orientation
            }, '*');
        }

        ctrlSize.addEventListener('change', updateIframeLayout);
        ctrlOrientation.addEventListener('change', updateIframeLayout);

        // Aksi Tombol Cetak
        document.getElementById('btn-print').addEventListener('click', function() {
            iframe.contentWindow.postMessage({ action: 'print' }, '*');
        });

        // Aksi Tombol Save PDF
        document.getElementById('btn-pdf').addEventListener('click', function() {
            const btn = this;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Proses...';
            btn.disabled = true;

            iframe.contentWindow.postMessage({ action: 'savePdf', size: ctrlSize.value, orientation: ctrlOrientation.value }, '*');

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 3000);
        });

        // Aksi Tombol Copy
        document.getElementById('btn-copy').addEventListener('click', function() {
            iframe.contentWindow.postMessage({ action: 'copyText' }, '*');
        });

        // Terima notifikasi balasan dari iframe jika copy berhasil
        window.addEventListener('message', function(event) {
            if (event.data && event.data.status === 'copied') {
                alert('Teks dokumen berhasil disalin ke Clipboard!');
            }
        });
    });
</script>
@endpush
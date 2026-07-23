@extends('layouts.app')
@section('title', 'Materiel Masuk')

@push('styles')
<style>
    /* Header Banner Styling */
    .header-banner {
        border-radius: 10px;
        padding: 25px;
        color: white;
        margin-bottom: 20px;
        position: relative; 
        overflow: hidden; 
    }
    .header-banner-icon {
        position: absolute;
        right: -2%;
        top: 50%;
        transform: translateY(-50%);
        font-size: 10rem;
        color: #ffffff;
        opacity: 0.15; 
        pointer-events: none;
        z-index: 1;
    }
    .header-content {
        position: relative;
        z-index: 2;
    }

    /* Tata Letak Tabel Kompak & Berbobot */
    .table-dense {
        width: 100%;
        border-collapse: collapse;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
    }
    .table-dense thead {
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-dense thead th {
        color: #475569;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 15px;
        font-weight: 700;
        vertical-align: middle;
    }
    .table-dense tbody tr.main-row {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }
    .table-dense tbody tr.main-row:hover {
        background-color: #f8fafc;
    }
    .table-dense td {
        padding: 10px 15px;
        vertical-align: middle;
        color: #334155;
        font-size: 0.9rem;
    }

    /* Styling Fitur Accordion (Collapse) */
    .accordion-toggle { cursor: pointer; text-decoration: none; display: flex; align-items: center; width: 100%; border: none; background: transparent; padding: 0; }
    .accordion-toggle .fa-chevron-right { transition: transform 0.2s ease; font-size: 0.75rem; color: #94a3b8; }
    .accordion-toggle:not(.collapsed) .fa-chevron-right { transform: rotate(90deg); color: var(--primary-color); }
    
    .nested-table-container { background-color: #fafbfc; border-bottom: 1px solid #e2e8f0; box-shadow: inset 0 3px 6px -3px rgba(0,0,0,0.05); border-left: 3px solid var(--primary-color); }
    .nested-table { margin: 0; background: transparent; width: 100%; }
    .nested-table th { font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase; padding: 8px 15px; border-bottom: 1px solid #e2e8f0; background-color: #f1f5f9; }
    .nested-table td { padding: 8px 15px; color: #334155; font-size: 0.85rem; border: none; border-bottom: 1px dashed #e2e8f0; vertical-align: middle;}
    .nested-table tr:last-child td { border-bottom: none; }
    
    .status-badge { font-size: 0.7rem; padding: 4px 8px; border-radius: 4px; font-weight: 700; letter-spacing: 0.5px; }
</style>
@endpush

@section('content')

<div class="header-banner header-banner-theme d-flex justify-content-between align-items-center shadow-sm">
    <i class="fa-solid fa-truck-ramp-box header-banner-icon"></i>
    
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-signature me-2"></i> Materiel Masuk</h4>
        <p class="mb-0 text-white-50 small">Pencatatan Materiel Masuk berdasarkan SPPM</p>
    </div>
    <div class="header-content d-flex gap-2">
        @canany(['Setting Menu', 'Warehouse Menu'])
        <button class="btn btn-sm btn-success fw-bold shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
            <i class="fa-solid fa-file-excel me-1"></i> Import SPPM
        </button>
        @endcanany
        <a href="{{ route('inbound.create') }}" class="btn btn-sm btn-light fw-bold text-theme shadow-sm px-3 py-2">
            <i class="fa-solid fa-plus me-1"></i> Input SPPM Baru
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-2" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form method="GET" action="{{ route('inbound.index') }}" class="mb-3">
    <div class="row g-2 align-items-center bg-white p-2 rounded shadow-sm border border-light">
        <div class="col-12 col-md-auto d-flex align-items-center">
            <span class="text-muted small me-2 fw-semibold d-none d-md-inline">Tampil:</span>
            <select name="limit" class="form-select form-select-sm shadow-none bg-light" onchange="this.form.submit()" style="width: 70px;">
                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                <option value="all" {{ $limit == 'all' ? 'selected' : '' }}>All</option>
            </select>
        </div>
        
        <div class="col-12 col-md-auto">
            <select name="category_id" class="form-select form-select-sm shadow-none bg-light" onchange="this.form.submit()" style="min-width: 150px;">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (isset($category_id) && $category_id == $category->id) ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-md-auto">
            <select name="bulan" class="form-select form-select-sm shadow-none bg-light" onchange="this.form.submit()">
                <option value="">-- Semua Bulan --</option>
                @php
                    $months = [
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', 
                        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', 
                        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                    ];
                @endphp
                @foreach($months as $num => $name)
                    <option value="{{ $num }}" {{ (isset($bulan) && $bulan == $num) ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-md-auto">
            <select name="tahun" class="form-select form-select-sm shadow-none bg-light" onchange="this.form.submit()">
                <option value="">-- Semua Tahun --</option>
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ (isset($tahun) && $tahun == $y) ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>

        <div class="col flex-grow-1"></div>

        <div class="col-12 col-md-auto">
            <div class="input-group input-group-sm" style="min-width: 280px;">
                <input type="text" name="search" class="form-control shadow-none bg-light" placeholder="Cari SPPM, Nama, Kode, Seri..." value="{{ $search }}">
                <button class="btn btn-dark px-3 shadow-none" type="submit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </div>
    </div>
</form>

<div class="table-responsive shadow-sm" style="border-radius: 8px;">
    <table class="table-dense">
        <thead>
            <tr>
                <th width="32%">No. SPPM / Dokumen</th>
                <th width="15%">Kategori</th>
                <th width="13%">Tgl Dokumen</th>
                <th width="20%">Pembaruan Terakhir</th>
                <th width="12%" class="text-center">Status</th>
                <th width="8%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Helper visual format angka seri 9 digit dengan titik ribuan dan prefix
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
            @endphp

            @forelse($sppms as $sppm)
            <tr class="main-row">
                <td>
                    <button class="accordion-toggle collapsed text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSppm{{ $sppm->id }}" aria-expanded="false">
                        <i class="fa-solid fa-chevron-right me-2"></i>
                        <i class="fa-solid fa-file-invoice text-theme me-2 opacity-75"></i> <span class="fw-bold text-dark">{{ $sppm->sppm_no }}</span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border ms-2" style="font-size: 0.7rem;">{{ $sppm->details->count() }} Item</span>
                    </button>
                </td>
                <td>
                    <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.75rem;">{{ $sppm->category->name ?? '-' }}</span>
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
                        <span class="status-badge bg-success bg-opacity-10 text-success border border-success">SELESAI</span>
                    @elseif($sppm->status == 'partial')
                        <span class="status-badge bg-warning bg-opacity-10 text-warning border border-warning">PARSIAL</span>
                    @else
                        <span class="status-badge bg-secondary bg-opacity-10 text-secondary border border-secondary">TUNDA</span>
                    @endif
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center align-items-center flex-nowrap gap-1">
                        <a href="{{ route('inbound.edit', $sppm->id) }}" class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-0.5" style="font-size: 0.8rem;" title="Input / Ubah Realisasi">
                            <i class="fa-solid fa-pen text-theme"></i>
                        </a>
                        <form action="{{ route('inbound.destroy', $sppm->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Yakin menghapus dokumen SPPM ini beserta seluruh riwayat penerimaannya?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-0.5" style="font-size: 0.8rem;" title="Hapus Data">
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
                            <div class="d-flex justify-content-between align-items-end mb-2">
                                <h6 class="fw-bold text-theme m-0" style="font-size: 0.8rem;"><i class="fa-solid fa-clock-rotate-left me-1"></i> RIWAYAT KEDATANGAN & KELENGKAPAN BARANG</h6>
                                @if($sppm->status != 'completed')
                                    <a href="{{ route('inbound.edit', $sppm->id) }}" class="btn btn-sm btn-dark" style="font-size: 0.7rem;"><i class="fa-solid fa-truck-loading me-1"></i> Input Gelombang Masuk</a>
                                @endif
                            </div>
                            <div class="table-responsive bg-white border rounded">
                                <table class="table nested-table mb-0 text-nowrap">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang / Varian</th>
                                            <th class="text-center text-primary">Data SPPM</th>
                                            
                                            @foreach($sppm->logs as $log)
                                                <th class="text-center border-start">
                                                    Tahap {{ $log->batch_number }}<br>
                                                    <span class="text-muted fw-normal" style="font-size: 0.6rem;">{{ \Carbon\Carbon::parse($log->receive_date)->format('d/m/Y') }}</span>
                                                </th>
                                            @endforeach
                                            
                                            <th class="text-center border-start bg-light text-dark">Total Masuk</th>
                                            <th class="text-center text-danger">Kekurangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($sppm->details as $detail)
                                            @php
                                                $totalMasuk = 0;
                                            @endphp
                                            <tr>
                                                <td class="fw-semibold">
                                                    <span class="d-block">{{ $detail->material->name }} <span class="text-muted fw-normal ms-1">({{ $detail->material->satuan }})</span></span>
                                                    
                                                    @if($detail->material->pakai_seri == 1)
                                                        @if($detail->sppm_serial_start || $detail->sppm_serial_end)
                                                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                                                <i class="fa-solid fa-tags me-1 opacity-75"></i>Seri: {!! $formatSeri($detail->sppm_serial_prefix, $detail->sppm_serial_start, $detail->sppm_serial_end) !!}
                                                            </small>
                                                        @endif
                                                    @endif
                                                </td>
                                                
                                                <td class="text-center fw-bold text-primary bg-primary bg-opacity-10 align-middle">{{ number_format($detail->target_qty, 0, ',', '.') }}</td>
                                                
                                                @foreach($sppm->logs as $log)
                                                    @php
                                                        $stock = $log->stocks->where('material_id', $detail->material_id)->first();
                                                        $qty = $stock ? $stock->qty_received : 0;
                                                        $totalMasuk += $qty;
                                                    @endphp
                                                    <td class="text-center border-start text-muted align-middle">
                                                        <span class="d-block {{ $qty > 0 ? 'fw-bold text-dark' : '' }}">{{ $qty > 0 ? number_format($qty, 0, ',', '.') : '-' }}</span>
                                                        
                                                        @if($stock && ($stock->serial_start || $stock->serial_end))
                                                            <small class="text-muted d-block mt-1" style="font-size: 0.65rem; background:#f8fafc; border-radius:4px; padding:2px;">
                                                                {!! $formatSeri($stock->serial_prefix, $stock->serial_start, $stock->serial_end) !!}
                                                            </small>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                
                                                @php
                                                    $sisa = $detail->target_qty - $totalMasuk;
                                                @endphp
                                                <td class="text-center border-start bg-light fw-bold text-dark align-middle">{{ number_format($totalMasuk, 0, ',', '.') }}</td>
                                                <td class="text-center fw-bold align-middle {{ $sisa > 0 ? 'text-danger bg-danger bg-opacity-10' : 'text-success' }}">
                                                    {{ $sisa > 0 ? number_format($sisa, 0, ',', '.') : 'LENGKAP' }}
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
                    <p class="mb-0 small">Belum ada dokumen yang diregistrasikan.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        @if($limit === 'all')
            Menampilkan seluruh <strong>{{ $sppms->total() }}</strong> dokumen
        @else
            Menampilkan <strong>{{ $sppms->firstItem() ?? 0 }}</strong> sampai <strong>{{ $sppms->lastItem() ?? 0 }}</strong> dari <strong>{{ $sppms->total() }}</strong> dokumen
        @endif
    </div>
    <div>
        @if($limit !== 'all')
            {{ $sppms->links('pagination::bootstrap-5') }}
        @endif
    </div>
</div>

{{-- Modal Import Excel --}}
@canany(['Setting Menu', 'Warehouse Menu'])
<div class="modal fade" id="modalImportExcel" tabindex="-1" aria-labelledby="modalImportExcelLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h6 class="modal-title fw-bold text-success" id="modalImportExcelLabel">
                    <i class="fa-solid fa-file-import me-2"></i> Import Database SPPM
                </h6>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4 bg-white text-start">
                
                <!-- 1. Form Unduh Template (Berubah Berdasarkan Kategori) -->
                <form action="{{ route('inbound.template') }}" method="GET" class="mb-4 p-3 bg-light rounded border border-success border-opacity-25">
                    <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">1. Unduh Format Template</h6>
                    <p class="text-muted small mb-2">Pilih kategori untuk menyesuaikan jumlah kolom barang secara otomatis.</p>
                    
                    <div class="mb-3">
                        <select name="category_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Kategori Komoditas --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-sm btn-outline-success fw-bold w-100">
                        <i class="fa-solid fa-download me-1"></i> Download Template Excel
                    </button>
                </form>

                <!-- 2. Form Unggah File (Save As CSV) -->
                <form action="{{ route('inbound.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.85rem;">2. Unggah File Template (Wajib .CSV)</h6>
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="fa-solid fa-circle-info me-1"></i> Setelah selesai mengisi di Excel, pastikan Anda menyimpannya dengan format <b>Save As -> CSV (Comma delimited)</b>.
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted" style="font-size: 0.75rem; font-weight:bold;">KATEGORI FILE YANG DIUNGGAH</label>
                            <select name="category_id" class="form-select form-select-sm" required>
                                <option value="">-- Pastikan Sama Dengan Template --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <input type="file" name="excel_file" class="form-control" accept=".csv" required>
                    </div>
            </div>
            
            <div class="modal-footer border-0 bg-light py-2 gap-2">
                <button type="button" class="btn btn-sm btn-light border fw-bold px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-success px-4 fw-bold shadow-sm" style="border-radius: 6px;">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Mulai Import
                </button>
            </div>
            </form>
        </div>
    </div>
</div>
@endcanany
@endsection
@extends('layouts.app')
@section('title', 'Daftar Gudang')

@push('styles')
<style>
    /* 1. Header Banner Styling */
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

    /* 2. Tata Letak Tabel Kompak & Berbobot (Dense Design) */
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
    .table-dense tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s ease;
    }
    .table-dense tbody tr:hover {
        background-color: #f1f5f9;
    }
    .table-dense td {
        padding: 10px 15px;
        vertical-align: middle;
        color: #334155;
        font-size: 0.9rem;
    }

    /* 3. Form Input Styling */
    .modal-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .modal-custom-input {
        background-color: #f8fafc;
        border: 1px solid #cbd5e1 !important;
        border-radius: 6px;
        padding: 10px 15px;
        font-size: 0.95rem;
        color: #334155;
        transition: all 0.2s ease-in-out;
    }
    .modal-custom-input:focus {
        background-color: #ffffff;
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); 
    }

    /* 4. Kursor Drag and Drop */
    .drag-handle {
        cursor: grab;
        color: #94a3b8;
    }
    .drag-handle:hover {
        color: #475569;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
    .sortable-ghost {
        opacity: 0.4;
        background-color: #e2e8f0;
    }
</style>
@endpush

@section('content')

<div class="header-banner header-banner-theme d-flex justify-content-between align-items-center shadow-sm">
    <i class="fa-solid fa-warehouse header-banner-icon"></i>
    
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-warehouse me-2"></i> Daftar Gudang</h4>
        <p class="mb-0 text-white-50 small">Kelola lokasi fisik penyimpanan, kode identitas, serta penanggung jawab area gudang.</p>
    </div>
    <div class="header-content">
        <button class="btn btn-sm btn-light fw-bold text-theme shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalAddWarehouse">
            <i class="fa-solid fa-plus me-1"></i> Tambah Gudang
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-2" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i> Periksa kembali isian Anda. Data gagal diproses.
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('warehouses.index') }}" class="d-flex align-items-center w-100">
        <div class="me-3 d-flex align-items-center">
            <span class="text-muted small me-2 fw-semibold">Tampilkan:</span>
            <select name="limit" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="width: 70px; border-radius: 6px;">
                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $limit == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        
        <div class="flex-grow-1"></div>

        <div class="input-group input-group-sm shadow-sm" style="width: 300px; border-radius: 6px; overflow: hidden;">
            <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Cari nama, kode, lokasi..." value="{{ $search }}">
            <button class="btn btn-white border-0 bg-white px-3" type="submit">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
            </button>
        </div>
    </form>
</div>

<div class="table-responsive shadow-sm" style="border-radius: 8px;">
    <table class="table-dense">
        <thead>
            <tr>
                <th width="10%" class="text-center text-nowrap">No Urut</th>
                <th width="15%">Kode Gudang</th>
                <th width="25%">Nama Gudang</th>
                <th width="20%">Lokasi Wilayah</th>
                <th width="20%">Keterangan</th>
                <th width="10%" class="text-center text-nowrap">Aksi</th>
            </tr>
        </thead>
        <tbody id="sortable-warehouses">
            @forelse($warehouses as $warehouse)
            <tr data-id="{{ $warehouse->id }}">
                <td class="text-center fw-bold text-muted text-nowrap">
                    <i class="fa-solid fa-grip-vertical me-2 drag-handle" title="Tarik untuk memindahkan baris"></i>
                    {{ $warehouse->nomor_urut }}
                </td>
                <td>
                    <span class="badge bg-light text-secondary border px-2 py-1"><i class="fa-solid fa-barcode text-muted me-1"></i>{{ $warehouse->code }}</span>
                </td>
                <td class="fw-bold text-dark">
                    {{ $warehouse->name }}
                </td>
                <td>
                    <i class="fa-solid fa-location-dot text-muted me-1" style="font-size: 0.85rem;"></i>{{ $warehouse->lokasi }}
                </td>
                <td class="text-muted small">
                    {{ Str::limit($warehouse->keterangan, 30, '...') ?? '-' }}
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center align-items-center flex-nowrap gap-1">
                        <button class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-1" data-bs-toggle="modal" data-bs-target="#modalEditWarehouse{{ $warehouse->id }}" title="Ubah">
                            <i class="fa-solid fa-pen text-theme"></i>
                        </button>
                        <form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Yakin ingin menghapus data lokasi gudang ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-1" title="Hapus">
                                <i class="fa-solid fa-trash text-danger"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>

            <div class="modal fade" id="modalEditWarehouse{{ $warehouse->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered border-0">
                    <div class="modal-content border-0 shadow-lg">
                        <form action="{{ route('warehouses.update', $warehouse->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-header border-0 bg-light py-3">
                                <h6 class="modal-title fw-bold text-theme m-0"><i class="fa-solid fa-pen-to-square me-2"></i> Ubah Data Gudang</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 bg-white text-start">
                                <div class="mb-3">
                                    <label class="form-label modal-label">Kode Gudang</label>
                                    <input type="text" name="code" class="form-control modal-custom-input" value="{{ old('code', $warehouse->code) }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label modal-label">Nama Gudang</label>
                                    <input type="text" name="name" class="form-control modal-custom-input" value="{{ old('name', $warehouse->name) }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label modal-label">Lokasi Wilayah/Alamat</label>
                                    <input type="text" name="lokasi" class="form-control modal-custom-input" value="{{ old('lokasi', $warehouse->lokasi) }}" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label modal-label">Keterangan Opsional</label>
                                    <textarea name="keterangan" class="form-control modal-custom-input" rows="3">{{ old('keterangan', $warehouse->keterangan) }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer border-0 bg-light py-2">
                                <button type="submit" class="btn px-4 fw-bold shadow-sm btn-theme" style="border-radius: 6px;">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <tr>
                <td colspan="6" class="text-center py-5 text-muted bg-white">
                    <i class="fa-solid fa-folder-open fs-2 mb-2 opacity-25"></i>
                    <p class="mb-0 small">Belum ada lokasi gudang fisik terdaftar.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        Menampilkan <strong>{{ $warehouses->firstItem() ?? 0 }}</strong> sampai <strong>{{ $warehouses->lastItem() ?? 0 }}</strong> dari <strong>{{ $warehouses->total() }}</strong> total data
    </div>
    <div>
        {{ $warehouses->links('pagination::bootstrap-5') }}
    </div>
</div>

<div class="modal fade" id="modalAddWarehouse" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered border-0">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('warehouses.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 bg-light py-3">
                    <h6 class="modal-title fw-bold text-theme m-0"><i class="fa-solid fa-square-plus me-2"></i> Tambah Gudang Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white text-start">
                    <div class="mb-3">
                        <label class="form-label modal-label">Kode Gudang</label>
                        <input type="text" name="code" class="form-control modal-custom-input" placeholder="Contoh: WH-SMG01" value="{{ old('code') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label modal-label">Nama Gudang</label>
                        <input type="text" name="name" class="form-control modal-custom-input" placeholder="Contoh: Gudang Utama Transit" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label modal-label">Lokasi Wilayah/Alamat</label>
                        <input type="text" name="lokasi" class="form-control modal-custom-input" placeholder="Contoh: Kawasan Industri Semarang" value="{{ old('lokasi') }}" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label modal-label">Keterangan Opsional</label>
                        <textarea name="keterangan" class="form-control modal-custom-input" placeholder="Catatan tambahan spesifikasi kapasitas atau akses gudang..." rows="3">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="submit" class="btn px-4 fw-bold shadow-sm btn-theme" style="border-radius: 6px;">Simpan Gudang</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.getElementById('sortable-warehouses');
        
        if (tbody) {
            new Sortable(tbody, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function (evt) {
                    let order = [];
                    tbody.querySelectorAll('tr[data-id]').forEach(function(row) {
                        order.push(row.getAttribute('data-id'));
                    });

                    fetch('{{ route("warehouses.reorder") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ order: order })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            location.reload(); 
                        } else {
                            alert('Gagal memperbarui urutan.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            });
        }
    });
</script>
@endpush
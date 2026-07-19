@extends('layouts.app')
@section('title', 'Daftar Penerima / Tujuan')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.15; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }

    .table-dense { width: 100%; border-collapse: collapse; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .table-dense thead { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .table-dense thead th { color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 15px; font-weight: 700; vertical-align: middle; }
    .table-dense tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
    .table-dense tbody tr:hover { background-color: #f1f5f9; }
    .table-dense td { padding: 10px 15px; vertical-align: middle; color: #334155; font-size: 0.9rem; }

    .modal-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .modal-custom-input { background-color: #f8fafc; border: 1px solid #cbd5e1 !important; border-radius: 6px; padding: 10px 15px; font-size: 0.95rem; color: #334155; transition: all 0.2s ease-in-out; }
    .modal-custom-input:focus { background-color: #ffffff; border-color: var(--primary-color) !important; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }

    .drag-handle { cursor: grab; color: #94a3b8; }
    .drag-handle:hover { color: #475569; }
    .drag-handle:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; background-color: #e2e8f0; }
</style>
@endpush

@section('content')

<div class="header-banner header-banner-theme d-flex justify-content-between align-items-center shadow-sm">
    <i class="fa-solid fa-map-location-dot header-banner-icon"></i>
    
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-map-location-dot me-2"></i> Daftar Penerima (Destinasi)</h4>
        <p class="mb-0 text-white-50 small">Kelola target, instansi, atau pihak yang akan menjadi tujuan pengiriman stok (Outbound).</p>
    </div>
    <div class="header-content">
        <button class="btn btn-sm btn-light fw-bold text-theme shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalAddDestination">
            <i class="fa-solid fa-plus me-1"></i> Tambah Penerima
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
    <form method="GET" action="{{ route('destinations.index') }}" class="d-flex align-items-center w-100">
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

        <div class="input-group input-group-sm shadow-sm" style="width: 350px; border-radius: 6px; overflow: hidden;">
            <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Cari instansi, pejabat, atau keterangan..." value="{{ $search }}">
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
                <th width="8%" class="text-center text-nowrap">No Urut</th>
                <th width="25%">Instansi / Kesatuan</th>
                <th width="35%">Personel / Pejabat Penerima</th>
                <th width="20%">Keterangan</th>
                <th width="12%" class="text-center text-nowrap">Aksi</th>
            </tr>
        </thead>
        <tbody id="sortable-destinations">
            @forelse($destinations as $destination)
            <tr data-id="{{ $destination->id }}">
                <td class="text-center fw-bold text-muted text-nowrap">
                    <i class="fa-solid fa-grip-vertical me-2 drag-handle" title="Tarik untuk memindahkan baris"></i>
                    {{ $destination->nomor_urut }}
                </td>
                <td class="fw-bold text-theme align-middle">
                    <i class="fa-regular fa-building me-1 opacity-75"></i> {{ $destination->name }}
                </td>
                <td class="align-middle">
                    @if($destination->nama)
                        <span class="d-block fw-bold text-dark">{{ $destination->nama }}</span>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            @if($destination->pangkat_nrp) 
                                <span class="fw-semibold">{{ $destination->pangkat_nrp }}</span> 
                            @endif
                            @if($destination->pangkat_nrp && $destination->jabatan) | @endif
                            @if($destination->jabatan) 
                                <span>{{ $destination->jabatan }}</span> 
                            @endif
                        </div>
                    @else
                        <span class="text-muted fst-italic small">Belum ada data personel</span>
                    @endif
                </td>
                <td class="text-muted small align-middle">
                    {{ Str::limit($destination->keterangan, 50, '...') ?? '-' }}
                </td>
                <td class="text-center align-middle">
                    <div class="d-flex justify-content-center align-items-center flex-nowrap gap-1">
                        <button class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-1" data-bs-toggle="modal" data-bs-target="#modalEditDestination{{ $destination->id }}" title="Ubah">
                            <i class="fa-solid fa-pen text-theme"></i>
                        </button>
                        <form action="{{ route('destinations.destroy', $destination->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Yakin ingin menghapus data penerima ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-1" title="Hapus">
                                <i class="fa-solid fa-trash text-danger"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>

            <!-- MODAL UBAH PENERIMA -->
            <div class="modal fade" id="modalEditDestination{{ $destination->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered border-0">
                    <div class="modal-content border-0 shadow-lg">
                        <form action="{{ route('destinations.update', $destination->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-header border-0 bg-light py-3">
                                <h6 class="modal-title fw-bold text-theme m-0"><i class="fa-solid fa-pen-to-square me-2"></i> Ubah Data Penerima</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 bg-white text-start">
                                <div class="mb-3">
                                    <label class="form-label modal-label">Instansi / Kesatuan <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control modal-custom-input" value="{{ old('name', $destination->name) }}" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label modal-label">Nama Lengkap Personel</label>
                                        <input type="text" name="nama" class="form-control modal-custom-input" placeholder="Cth: Budi Santoso" value="{{ old('nama', $destination->nama) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label modal-label">Pangkat / NRP</label>
                                        <input type="text" name="pangkat_nrp" class="form-control modal-custom-input" placeholder="Cth: IPTU / 85010123" value="{{ old('pangkat_nrp', $destination->pangkat_nrp) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label modal-label">Jabatan</label>
                                        <input type="text" name="jabatan" class="form-control modal-custom-input" placeholder="Cth: Kasubbag Log" value="{{ old('jabatan', $destination->jabatan) }}">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label modal-label">Keterangan Opsional</label>
                                    <textarea name="keterangan" class="form-control modal-custom-input" rows="2">{{ old('keterangan', $destination->keterangan) }}</textarea>
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
                <td colspan="5" class="text-center py-5 text-muted bg-white">
                    <i class="fa-solid fa-users-slash fs-2 mb-2 opacity-25"></i>
                    <p class="mb-0 small">Belum ada daftar penerima / destinasi terdaftar.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        Menampilkan <strong>{{ $destinations->firstItem() ?? 0 }}</strong> sampai <strong>{{ $destinations->lastItem() ?? 0 }}</strong> dari <strong>{{ $destinations->total() }}</strong> total data
    </div>
    <div>
        {{ $destinations->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- MODAL TAMBAH PENERIMA BARU -->
<div class="modal fade" id="modalAddDestination" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered border-0">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('destinations.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 bg-light py-3">
                    <h6 class="modal-title fw-bold text-theme m-0"><i class="fa-solid fa-square-plus me-2"></i> Tambah Penerima Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white text-start">
                    <div class="mb-3">
                        <label class="form-label modal-label">Instansi / Kesatuan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control modal-custom-input" placeholder="Cth: Divisi Operasional Polda" value="{{ old('name') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label modal-label">Nama Lengkap Personel</label>
                            <input type="text" name="nama" class="form-control modal-custom-input" placeholder="Cth: Budi Santoso" value="{{ old('nama') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">Pangkat / NRP</label>
                            <input type="text" name="pangkat_nrp" class="form-control modal-custom-input" placeholder="Cth: IPTU / 85010123" value="{{ old('pangkat_nrp') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control modal-custom-input" placeholder="Cth: Kasubbag Log" value="{{ old('jabatan') }}">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label modal-label">Keterangan Opsional</label>
                        <textarea name="keterangan" class="form-control modal-custom-input" placeholder="Alamat atau kontak tambahan..." rows="2">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="submit" class="btn px-4 fw-bold shadow-sm btn-theme" style="border-radius: 6px;">Simpan Data</button>
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
        const tbody = document.getElementById('sortable-destinations');
        
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

                    fetch('{{ route("destinations.reorder") }}', {
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
                    .catch(error => console.error('Error:', error));
                }
            });
        }
    });
</script>
@endpush
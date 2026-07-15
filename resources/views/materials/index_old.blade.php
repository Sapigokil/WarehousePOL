@extends('layouts.app')
@section('title', 'Daftar Barang')

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

    /* Styling Fitur Accordion (Collapse) */
    .accordion-toggle { cursor: pointer; text-decoration: none; display: flex; align-items: center; }
    .accordion-toggle .fa-chevron-right { transition: transform 0.2s ease; font-size: 0.75rem; color: #94a3b8; }
    .accordion-toggle:not(.collapsed) .fa-chevron-right { transform: rotate(90deg); color: var(--primary-color); }
    
    .nested-table-container { background-color: #fafbfc; border-bottom: 1px solid #e2e8f0; box-shadow: inset 0 3px 6px -3px rgba(0,0,0,0.05); }
    .nested-table { margin: 0; background: transparent; }
    .nested-table td { padding: 8px 15px; color: #64748b; font-size: 0.85rem; border: none; border-bottom: 1px dashed #e2e8f0; }
    .nested-table tr:last-child td { border-bottom: none; }

    /* Form Input Styling */
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

    .option-box { border: 2px solid #e2e8f0; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.2s ease; }
    .option-box:hover { border-color: #cbd5e1; background-color: #f8fafc; }
    .option-box.active { border-color: var(--primary-color); background-color: rgba(var(--primary-color-rgb), 0.05); }
    
    .table-variants { min-width: 850px; }
    .table-variants th { font-size: 0.75rem; color: #64748b; font-weight: 700; vertical-align: middle; }
    .table-variants td { vertical-align: middle; }
</style>
@endpush

@section('content')

<div class="header-banner header-banner-theme d-flex justify-content-between align-items-center shadow-sm">
    <i class="fa-solid fa-box header-banner-icon"></i>
    
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-box me-2"></i> Daftar Barang</h4>
        <p class="mb-0 text-white-50 small">Registrasikan data hierarki produk, penentuan kategori, jenis satuan, serta konfigurasi seri.</p>
    </div>
    <div class="header-content">
        <button class="btn btn-sm btn-light fw-bold text-theme shadow-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalAddMaterial">
            <i class="fa-solid fa-plus me-1"></i> Tambah Barang Baru
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
        <i class="fa-solid fa-triangle-exclamation me-2"></i> Gagal menyimpan data. Pastikan seluruh isian form terisi lengkap.
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('materials.index') }}" class="d-flex align-items-center w-100">
        <div class="me-3 d-flex align-items-center">
            <span class="text-muted small me-2 fw-semibold">Tampilkan:</span>
            <select name="limit" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="width: 70px; border-radius: 6px;">
                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
        
        <div class="me-3">
            <select name="category_id" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="border-radius: 6px; min-width: 180px;">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (isset($category_id) && $category_id == $category->id) ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-grow-1"></div>

        <div class="input-group input-group-sm shadow-sm" style="width: 300px; border-radius: 6px; overflow: hidden;">
            <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Cari nama barang..." value="{{ $search }}">
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
                <th width="40%">Nama Barang / Materiel</th>
                <th width="15%">Kategori</th>
                <th width="15%">Satuan</th>
                <th width="10%" class="text-center text-nowrap">Min. Stok</th>
                <th width="10%" class="text-center text-nowrap">Pelacakan Seri</th>
                <th width="10%" class="text-center text-nowrap">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($materials as $material)
            
            <tr>
                <td>
                    @if($material->satuan == '-')
                        <button class="btn btn-link text-dark p-0 accordion-toggle collapsed w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaterial{{ $material->id }}" aria-expanded="false">
                            <i class="fa-solid fa-chevron-right me-2"></i>
                            <i class="fa-regular fa-folder-open text-theme me-1"></i> <span class="fw-bold">{{ $material->name }}</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border ms-2" style="font-size: 0.7rem;">{{ $material->children->count() }} Varian</span>
                        </button>
                    @else
                        <span class="fw-bold text-dark ps-4"><i class="fa-solid fa-box text-theme opacity-75 me-1"></i>{{ $material->name }}</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.75rem;">{{ $material->category->name ?? '-' }}</span>
                </td>
                <td class="fw-semibold">
                    {{ $material->satuan }}
                </td>
                <td class="text-center fw-semibold">
                    {{ $material->satuan == '-' ? '-' : $material->minimal_stok }}
                </td>
                <td class="text-center">
                    @if($material->satuan == '-')
                        <span class="text-muted" style="font-size: 0.75rem;">Kelompok Induk</span>
                    @elseif($material->pakai_seri)
                        <span class="badge bg-success shadow-sm px-2 py-0.5" style="font-size: 0.75rem;"><i class="fa-solid fa-arrow-down-1-9 me-1"></i>Seri</span>
                    @else
                        <span class="text-muted" style="font-size: 0.75rem;">Tanpa Seri</span>
                    @endif
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center align-items-center flex-nowrap gap-1">
                        <button class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-0.5" style="font-size: 0.8rem;" data-bs-toggle="modal" data-bs-target="#modalEditMaterial{{ $material->id }}" title="Ubah Data">
                            <i class="fa-solid fa-pen text-theme"></i>
                        </button>
                        <form action="{{ route('materials.destroy', $material->id) }}" method="POST" class="m-0 p-0" onsubmit="return confirm('Yakin menghapus data ini? Semua varian di dalamnya akan ikut terhapus.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light border shadow-none rounded-1 px-2 py-0.5" style="font-size: 0.8rem;" title="Hapus">
                                <i class="fa-solid fa-trash text-danger"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>

            @if($material->satuan == '-')
            <tr>
                <td colspan="6" class="p-0 border-0">
                    <div class="collapse" id="collapseMaterial{{ $material->id }}">
                        <div class="nested-table-container px-5 py-2">
                            <table class="table nested-table w-100 mb-2 mt-1">
                                <tbody>
                                    @foreach($material->children as $child)
                                    <tr>
                                        <td width="40%"><i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-2 opacity-50"></i> {{ $child->name }}</td>
                                        <td width="15%"><span class="badge bg-white text-secondary border px-2 py-0.5" style="font-size: 0.7rem;">{{ $child->category->name ?? '-' }}</span></td>
                                        <td width="15%">{{ $child->satuan }}</td>
                                        <td width="10%" class="text-center">{{ $child->minimal_stok }}</td>
                                        <td width="10%" class="text-center">
                                            @if($child->pakai_seri)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-0.5" style="font-size: 0.7rem;">Seri</span>
                                            @else
                                                <span class="text-muted" style="font-size: 0.7rem;">Tanpa Seri</span>
                                            @endif
                                        </td>
                                        <td width="10%"></td> </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
            @endif

            <div class="modal fade" id="modalEditMaterial{{ $material->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down {{ $material->satuan == '-' ? 'modal-xl' : 'modal-lg' }} border-0">
                    <div class="modal-content border-0 shadow-lg">
                        <form action="{{ route('materials.update', $material->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-header border-0 bg-light py-3">
                                <h6 class="modal-title fw-bold text-theme m-0"><i class="fa-solid fa-pen-to-square me-2"></i> Ubah Master Data Barang</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 bg-white text-start">
                                
                                @if($material->satuan == '-')
                                    <input type="hidden" name="tipe_input" value="kelompok">
                                    <div class="p-3 bg-light rounded border mb-4">
                                        <h6 class="fw-bold text-theme mb-3">A. Identitas Induk Kelompok</h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label modal-label">Nama Induk Barang</label>
                                                <input type="text" name="parent_name" class="form-control modal-custom-input" value="{{ old('parent_name', $material->name) }}" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label modal-label">Berlaku untuk Kategori</label>
                                                <select name="material_category_id" class="form-select modal-custom-input" required>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" {{ $material->material_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label modal-label">Keterangan Kelompok</label>
                                            <textarea name="parent_keterangan" class="form-control modal-custom-input" rows="2">{{ old('parent_keterangan', $material->keterangan) }}</textarea>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold text-theme m-0">B. Rincian Manajemen Varian / Anak Barang</h6>
                                        <button type="button" class="btn btn-sm btn-dark fw-bold" onclick="addEditVariantRow({{ $material->id }})"><i class="fa-solid fa-plus me-1"></i> Tambah Baris Varian</button>
                                    </div>
                                    
                                    <div class="table-responsive border rounded bg-white">
                                        <table class="table table-bordered mb-0 table-variants">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th width="40%">Nama Varian / Jenis</th>
                                                    <th width="20%">Satuan</th>
                                                    <th width="15%">Min. Stok</th>
                                                    <th width="15%">Lacak Seri?</th>
                                                    <th width="10%" class="text-center">Hapus</th>
                                                </tr>
                                            </thead>
                                            <tbody id="edit-variant-container-{{ $material->id }}">
                                                @foreach($material->children as $index => $child)
                                                <tr>
                                                    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $child->id }}">
                                                    <td><input type="text" name="variants[{{ $index }}][name]" class="form-control form-control-sm" value="{{ $child->name }}" required></td>
                                                    <td>
                                                        <select name="variants[{{ $index }}][satuan]" class="form-select form-select-sm" required>
                                                            <option value="LBR" {{ $child->satuan == 'LBR' ? 'selected' : '' }}>LBR</option>
                                                            <option value="BUKU" {{ $child->satuan == 'BUKU' ? 'selected' : '' }}>BUKU</option>
                                                            <option value="PCS" {{ $child->satuan == 'PCS' ? 'selected' : '' }}>PCS</option>
                                                            <option value="PACK" {{ $child->satuan == 'PACK' ? 'selected' : '' }}>PACK</option>
                                                            <option value="BOX" {{ $child->satuan == 'BOX' ? 'selected' : '' }}>BOX</option>
                                                            <option value="RIM" {{ $child->satuan == 'RIM' ? 'selected' : '' }}>RIM</option>
                                                        </select>
                                                    </td>
                                                    <td><input type="number" name="variants[{{ $index }}][minimal_stok]" class="form-control form-control-sm" value="{{ $child->minimal_stok }}" required></td>
                                                    <td>
                                                        <select name="variants[{{ $index }}][pakai_seri]" class="form-select form-select-sm" required>
                                                            <option value="0" {{ $child->pakai_seri == 0 ? 'selected' : '' }}>TIDAK</option>
                                                            <option value="1" {{ $child->pakai_seri == 1 ? 'selected' : '' }}>YA</option>
                                                        </select>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-light border text-danger" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <input type="hidden" name="tipe_input" value="tunggal">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label modal-label">Nama Barang</label>
                                            <input type="text" name="name" class="form-control modal-custom-input" value="{{ old('name', $material->name) }}" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label modal-label">Kategori</label>
                                            <select name="material_category_id" class="form-select modal-custom-input" required>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ $material->material_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label modal-label">Satuan Ukur</label>
                                            <select name="satuan" class="form-select modal-custom-input" required>
                                                <option value="LBR" {{ $material->satuan == 'LBR' ? 'selected' : '' }}>LBR</option>
                                                <option value="BUKU" {{ $material->satuan == 'BUKU' ? 'selected' : '' }}>BUKU</option>
                                                <option value="PCS" {{ $material->satuan == 'PCS' ? 'selected' : '' }}>PCS</option>
                                                <option value="PACK" {{ $material->satuan == 'PACK' ? 'selected' : '' }}>PACK</option>
                                                <option value="BOX" {{ $material->satuan == 'BOX' ? 'selected' : '' }}>BOX</option>
                                                <option value="RIM" {{ $material->satuan == 'RIM' ? 'selected' : '' }}>RIM</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label modal-label">Batas Minimal Stok</label>
                                            <input type="number" name="minimal_stok" class="form-control modal-custom-input" value="{{ old('minimal_stok', $material->minimal_stok) }}" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label modal-label">Lacak Nomor Seri?</label>
                                            <select name="pakai_seri" class="form-select modal-custom-input" required>
                                                <option value="0" {{ $material->pakai_seri == 0 ? 'selected' : '' }}>TIDAK</option>
                                                <option value="1" {{ $material->pakai_seri == 1 ? 'selected' : '' }}>YA</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label modal-label">Keterangan Spesifikasi</label>
                                        <textarea name="keterangan" class="form-control modal-custom-input" rows="3">{{ old('keterangan', $material->keterangan) }}</textarea>
                                    </div>
                                @endif

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
                    <p class="mb-0 small">Belum ada komoditas data barang terdaftar.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        Menampilkan <strong>{{ $materials->firstItem() ?? 0 }}</strong> sampai <strong>{{ $materials->lastItem() ?? 0 }}</strong> dari <strong>{{ $materials->total() }}</strong> baris data utama
    </div>
    <div>
        {{ $materials->links('pagination::bootstrap-5') }}
    </div>
</div>

<div class="modal fade" id="modalAddMaterial" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down modal-xl border-0">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('materials.store') }}" method="POST" id="formHybridMaterial">
                @csrf
                <div class="modal-header border-0 bg-light py-3">
                    <h6 class="modal-title fw-bold text-theme m-0"><i class="fa-solid fa-square-plus me-2"></i> Daftarkan Barang Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white text-start">
                    
                    <label class="form-label modal-label mb-3">Tipe Input Barang</label>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <label class="w-100">
                                <input type="radio" name="tipe_input" value="tunggal" class="d-none mode-selector" checked>
                                <div class="option-box active text-center" id="box-tunggal">
                                    <i class="fa-solid fa-box fs-3 mb-2 text-theme"></i>
                                    <h6 class="fw-bold m-0 text-dark">Barang Tunggal</h6>
                                    <span class="small text-muted">Satu barang biasa (Tanpa varian)</span>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-6">
                            <label class="w-100">
                                <input type="radio" name="tipe_input" value="kelompok" class="d-none mode-selector">
                                <div class="option-box text-center" id="box-kelompok">
                                    <i class="fa-solid fa-boxes-stacked fs-3 mb-2 text-theme"></i>
                                    <h6 class="fw-bold m-0 text-dark">Barang Berkelompok</h6>
                                    <span class="small text-muted">Satu nama induk dengan beberapa varian isi</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <div id="section-tunggal">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label modal-label">Nama Barang</label>
                                <input type="text" name="name" id="name_tunggal" class="form-control modal-custom-input" placeholder="Contoh: Blanko Elektronik BPKB">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label modal-label">Kategori</label>
                                <select name="material_category_id" id="cat_tunggal" class="form-select modal-custom-input">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label modal-label">Satuan Ukur</label>
                                <select name="satuan" id="satuan_tunggal" class="form-select modal-custom-input">
                                    <option value="LBR">LBR</option>
                                    <option value="BUKU">BUKU</option>
                                    <option value="PCS">PCS</option>
                                    <option value="PACK">PACK</option>
                                    <option value="BOX">BOX</option>
                                    <option value="RIM">RIM</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label modal-label">Minimal Stok</label>
                                <input type="number" name="minimal_stok" id="min_tunggal" class="form-control modal-custom-input" value="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label modal-label">Lacak Nomor Seri?</label>
                                <select name="pakai_seri" id="seri_tunggal" class="form-select modal-custom-input">
                                    <option value="0" selected>TIDAK</option>
                                    <option value="1">YA</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label modal-label">Keterangan Opsional</label>
                            <textarea name="keterangan" class="form-control modal-custom-input" rows="2"></textarea>
                        </div>
                    </div>

                    <div id="section-kelompok" class="d-none">
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <h6 class="fw-bold text-theme mb-3">A. Identitas Induk Kelompok</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label modal-label">Nama Induk Barang</label>
                                    <input type="text" name="parent_name" id="name_kelompok" class="form-control modal-custom-input" placeholder="Contoh: Kartu Induk E-BPKB" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label modal-label">Berlaku untuk Kategori</label>
                                    <select name="material_category_id" id="cat_kelompok" class="form-select modal-custom-input" disabled>
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label modal-label">Keterangan Kelompok Opsional</label>
                                <textarea name="parent_keterangan" id="ket_kelompok" class="form-control modal-custom-input" rows="2" placeholder="Catatan kelompok komoditas..." disabled></textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-theme m-0">B. Rincian Varian / Anak Barang</h6>
                            <button type="button" class="btn btn-sm btn-dark fw-bold" onclick="addVariantRow()"><i class="fa-solid fa-plus me-1"></i> Tambah Baris Varian</button>
                        </div>
                        
                        <div class="table-responsive border rounded-3 pb-1">
                            <table class="table table-bordered mb-0 table-variants bg-white">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40%">Nama Varian / Jenis</th>
                                        <th width="20%">Satuan</th>
                                        <th width="15%">Min. Stok</th>
                                        <th width="15%">Lacak Seri?</th>
                                        <th width="10%" class="text-center">Hapus</th>
                                    </tr>
                                </thead>
                                <tbody id="variant-container">
                                    <tr>
                                        <td><input type="text" name="variants[0][name]" class="form-control form-control-sm" placeholder="Contoh: Warna Hijau (Mobil Penumpang)" disabled required></td>
                                        <td>
                                            <select name="variants[0][satuan]" class="form-select form-select-sm" disabled required>
                                                <option value="LBR">LBR</option>
                                                <option value="BUKU">BUKU</option>
                                                <option value="PCS">PCS</option>
                                                <option value="PACK">PACK</option>
                                                <option value="BOX">BOX</option>
                                                <option value="RIM">RIM</option>
                                            </select>
                                        </td>
                                        <td><input type="number" name="variants[0][minimal_stok]" class="form-control form-control-sm" value="0" disabled required></td>
                                        <td>
                                            <select name="variants[0][pakai_seri]" class="form-select form-select-sm" disabled required>
                                                <option value="0" selected>TIDAK</option>
                                                <option value="1">YA</option>
                                            </select>
                                        </td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-light border text-muted disabled"><i class="fa-solid fa-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="submit" class="btn px-4 fw-bold shadow-sm btn-theme" style="border-radius: 6px;">Simpan Barang</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let variantIndex = 1;

    // Logika Pengalihan Mode Radio Form Tambah
    document.querySelectorAll('.mode-selector').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('box-tunggal').classList.remove('active');
            document.getElementById('box-kelompok').classList.remove('active');
            
            if(this.value === 'tunggal') {
                document.getElementById('box-tunggal').classList.add('active');
                document.getElementById('section-tunggal').classList.remove('d-none');
                document.getElementById('section-kelompok').classList.add('d-none');
                
                document.getElementById('name_tunggal').disabled = false;
                document.getElementById('cat_tunggal').disabled = false;
                document.getElementById('satuan_tunggal').disabled = false;
                
                document.getElementById('name_kelompok').disabled = true;
                document.getElementById('cat_kelompok').disabled = true;
                document.getElementById('ket_kelompok').disabled = true;
                document.querySelectorAll('#variant-container input, #variant-container select').forEach(el => el.disabled = true);
                
            } else {
                document.getElementById('box-kelompok').classList.add('active');
                document.getElementById('section-tunggal').classList.add('d-none');
                document.getElementById('section-kelompok').classList.remove('d-none');
                
                document.getElementById('name_tunggal').disabled = true;
                document.getElementById('cat_tunggal').disabled = true;
                document.getElementById('satuan_tunggal').disabled = true;
                
                document.getElementById('name_kelompok').disabled = false;
                document.getElementById('cat_kelompok').disabled = false;
                document.getElementById('ket_kelompok').disabled = false;
                document.querySelectorAll('#variant-container input, #variant-container select').forEach(el => el.disabled = false);
            }
        });
    });

    // Tambah baris baru Form Tambah
    function addVariantRow() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="variants[${variantIndex}][name]" class="form-control form-control-sm" placeholder="Nama varian..." required></td>
            <td>
                <select name="variants[${variantIndex}][satuan]" class="form-select form-select-sm" required>
                    <option value="LBR">LBR</option>
                    <option value="BUKU">BUKU</option>
                    <option value="PCS">PCS</option>
                    <option value="PACK">PACK</option>
                    <option value="BOX">BOX</option>
                    <option value="RIM">RIM</option>
                </select>
            </td>
            <td><input type="number" name="variants[${variantIndex}][minimal_stok]" class="form-control form-control-sm" value="0" required></td>
            <td>
                <select name="variants[${variantIndex}][pakai_seri]" class="form-select form-select-sm" required>
                    <option value="0" selected>TIDAK</option>
                    <option value="1">YA</option>
                </select>
            </td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-light border text-danger" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
        `;
        document.getElementById('variant-container').appendChild(tr);
        variantIndex++;
    }

    // Tambah baris baru Form Edit Kelompok
    function addEditVariantRow(parentId) {
        const container = document.getElementById('edit-variant-container-' + parentId);
        const index = Date.now(); 
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="variants[${index}][name]" class="form-control form-control-sm" placeholder="Nama varian tambahan..." required></td>
            <td>
                <select name="variants[${index}][satuan]" class="form-select form-select-sm" required>
                    <option value="LBR">LBR</option>
                    <option value="BUKU">BUKU</option>
                    <option value="PCS">PCS</option>
                    <option value="PACK">PACK</option>
                    <option value="BOX">BOX</option>
                    <option value="RIM">RIM</option>
                </select>
            </td>
            <td><input type="number" name="variants[${index}][minimal_stok]" class="form-control form-control-sm" value="0" required></td>
            <td>
                <select name="variants[${index}][pakai_seri]" class="form-select form-select-sm" required>
                    <option value="0" selected>TIDAK</option>
                    <option value="1">YA</option>
                </select>
            </td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-light border text-danger" onclick="this.closest('tr').remove()"><i class="fa-solid fa-trash"></i></button></td>
        `;
        container.appendChild(tr);
    }
</script>
@endpush
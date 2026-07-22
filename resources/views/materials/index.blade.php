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
    .table-dense td {
        padding: 10px 15px;
        vertical-align: middle;
        color: #334155;
        font-size: 0.9rem;
    }

    /* Penanda Handle Drag & Drop */
    .drag-handle {
        cursor: move;
        color: #cbd5e1;
        transition: color 0.15s ease;
    }
    .drag-handle:hover {
        color: var(--primary-color);
    }
    
    /* Efek bayangan saat baris digeser */
    .sortable-chosen {
        background-color: #f1f5f9 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .sortable-ghost {
        opacity: 0.4;
        background-color: #e2e8f0 !important;
    }

    /* Styling Fitur Accordion (Collapse) */
    .accordion-toggle { cursor: pointer; text-decoration: none; display: flex; align-items: center; width: 100%; border: none; background: transparent; padding: 0; }
    .accordion-toggle .fa-chevron-right { transition: transform 0.2s ease; font-size: 0.75rem; color: #94a3b8; }
    .accordion-toggle:not(.collapsed) .fa-chevron-right { transform: rotate(90deg); color: var(--primary-color); }
    
    .nested-table-container { background-color: #fafbfc; border-bottom: 1px solid #e2e8f0; box-shadow: inset 0 3px 6px -3px rgba(0,0,0,0.05); border-left: 3px solid var(--primary-color); }
    .nested-table { margin: 0; background: transparent; width: 100%; }
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
    .table-variants th { font-size: 0.75rem; color: #64748b; font-weight: 700; vertical-align: middle; background-color: #f8fafc;}
    .table-variants td { vertical-align: middle; }
</style>
@endpush

@section('content')

<div class="header-banner header-banner-theme d-flex justify-content-between align-items-center shadow-sm">
    <i class="fa-solid fa-box header-banner-icon"></i>
    
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-box me-2"></i> Daftar Barang</h4>
        <p class="mb-0 text-white-50 small">Registrasikan data hierarki produk, penentuan kategori, jenis satuan, serta konfigurasi urutan.</p>
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
            <select name="limit" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="width: 80px; border-radius: 6px;">
                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                <option value="all" {{ $limit == 'all' ? 'selected' : '' }}>ALL</option>
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
            <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Cari nama atau kode barang..." value="{{ $search }}">
            <button class="btn btn-white border-0 bg-white px-3" type="submit">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
            </button>
        </div>
    </form>
</div>

<div class="table-responsive shadow-sm" style="border-radius: 8px;">
    <table class="table-dense" id="table-sortable-container">
        <thead>
            <tr>
                <th width="5%" class="text-center">Geser</th>
                <th width="8%" class="text-center">No Urut</th>
                <th width="12%">Code</th>
                <th width="20%">Nama Barang / Materiel</th>
                <th width="15%">Kategori</th>
                <th width="12%">Satuan</th>
                <th width="10%" class="text-center">Min. Stok</th>
                <th width="10%" class="text-center">Pelacakan Seri</th>
                <th width="8%" class="text-center">Aksi</th>
            </tr>
        </thead>
        
        @forelse($materials as $material)
        <tbody class="material-group-wrapper" data-id="{{ $material->id }}" data-category="{{ $material->material_category_id }}">
            <tr class="bg-white" style="border-bottom: 1px solid #f1f5f9;">
                <td class="text-center">
                    <i class="fa-solid fa-grip-vertical drag-handle"></i>
                </td>
                <td class="text-center fw-bold text-muted item-nomor-urut">
                    {{ $material->nomor_urut ?? '-' }}
                </td>
                <td>
                    <span class="text-secondary fw-semibold">{{ $material->code ?? '-' }}</span>
                </td>
                <td>
                    @if($material->satuan == '-')
                        <button class="accordion-toggle collapsed text-start" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaterial{{ $material->id }}" aria-expanded="false">
                            <i class="fa-solid fa-chevron-right me-2"></i>
                            <i class="fa-regular fa-folder-open text-theme me-1"></i> <span class="fw-bold text-dark">{{ $material->name }}</span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border ms-2" style="font-size: 0.7rem;">{{ $material->children->count() }} Varian</span>
                        </button>
                    @else
                        <span class="fw-bold text-dark ps-3"><i class="fa-solid fa-box text-theme opacity-75 me-1"></i>{{ $material->name }}</span>
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

            @if($material->satuan == '-' && $material->children->count() > 0)
            <tr class="border-0">
                <td colspan="9" class="p-0 border-0">
                    <div class="collapse" id="collapseMaterial{{ $material->id }}">
                        <div class="nested-table-container px-5 py-2">
                            <table class="nested-table">
                                <tbody>
                                    @foreach($material->children as $child)
                                    <tr>
                                        <td width="45%"><i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-2 opacity-50"></i> {{ $child->name }}</td>
                                        <td width="15%"><span class="badge bg-white text-secondary border px-2 py-0.5" style="font-size: 0.7rem;">{{ $child->category->name ?? '-' }}</span></td>
                                        <td width="15%">{{ $child->satuan }}</td>
                                        <td width="15%" class="text-center">{{ $child->minimal_stok }}</td>
                                        <td width="10%" class="text-center">
                                            @if($child->pakai_seri)
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-0.5" style="font-size: 0.7rem;">Seri</span>
                                            @else
                                                <span class="text-muted" style="font-size: 0.7rem;">Tanpa Seri</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
            @endif
        </tbody>
        @empty
        <tbody>
            <tr>
                <td colspan="9" class="text-center py-5 text-muted bg-white">
                    <i class="fa-solid fa-folder-open fs-2 mb-2 opacity-25"></i>
                    <p class="mb-0 small">Belum ada komoditas data barang terdaftar.</p>
                </td>
            </tr>
        </tbody>
        @endforelse
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        @if($limit === 'all')
            Menampilkan seluruh <strong>{{ $materials->total() }}</strong> kelompok data utama
        @else
            Menampilkan <strong>{{ $materials->firstItem() ?? 0 }}</strong> sampai <strong>{{ $materials->lastItem() ?? 0 }}</strong> dari <strong>{{ $materials->total() }}</strong> kelompok data utama
        @endif
    </div>
    <div>
        @if($limit !== 'all')
            {{ $materials->links('pagination::bootstrap-5') }}
        @endif
    </div>
</div>

<!-- ========================================================================================= -->
<!-- KUMPULAN MODAL EDIT DATA -->
<!-- ========================================================================================= -->
@foreach($materials as $material)
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
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label modal-label">Code Induk Kelompok (Opsional)</label>
                                        <input type="text" name="parent_code" class="form-control modal-custom-input" value="{{ old('parent_code', $material->code) }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5 mb-3">
                                        <label class="form-label modal-label">Nama Induk Barang</label>
                                        <input type="text" name="parent_name" class="form-control modal-custom-input" value="{{ old('parent_name', $material->name) }}" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label modal-label">Berlaku untuk Kategori</label>
                                        <select name="material_category_id" class="form-select modal-custom-input" required>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ $material->material_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label modal-label">Nomor Urut Kategori</label>
                                        <input type="number" name="nomor_urut" class="form-control modal-custom-input" value="{{ $material->nomor_urut }}" min="1" required>
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
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fa-solid fa-grip-vertical child-drag-handle me-2 text-muted" style="cursor: move;"></i>
                                                    <input type="text" name="variants[{{ $index }}][name]" class="form-control form-control-sm" value="{{ $child->name }}" required>
                                                </div>
                                            </td>
                                            <td>
                                                <select name="variants[{{ $index }}][satuan]" class="form-select form-select-sm" required>
                                                    <option value="LBR" {{ $child->satuan == 'LBR' ? 'selected' : '' }}>LBR</option>
                                                    <option value="BUKU" {{ $child->satuan == 'BUKU' ? 'selected' : '' }}>BUKU</option>
                                                    <option value="SET" {{ $child->satuan == 'SET' ? 'selected' : '' }}>SET</option>
                                                    <option value="PCS" {{ $child->satuan == 'PCS' ? 'selected' : '' }}>PCS</option>
                                                    <option value="PASANG" {{ $child->satuan == 'PASANG' ? 'selected' : '' }}>PASANG</option>
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
                                <div class="col-md-12 mb-3">
                                    <label class="form-label modal-label">Code Barang (Opsional)</label>
                                    <input type="text" name="code" class="form-control modal-custom-input" value="{{ old('code', $material->code) }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label modal-label">Nama Barang</label>
                                    <input type="text" name="name" class="form-control modal-custom-input" value="{{ old('name', $material->name) }}" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label modal-label">Kategori</label>
                                    <select name="material_category_id" class="form-select modal-custom-input" required>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" {{ $material->material_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label modal-label">Nomor Urut Kategori</label>
                                    <input type="number" name="nomor_urut" class="form-control modal-custom-input" value="{{ $material->nomor_urut }}" min="1" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label modal-label">Satuan Ukur</label>
                                    <select name="satuan" class="form-select modal-custom-input" required>
                                        <option value="LBR" {{ $material->satuan == 'LBR' ? 'selected' : '' }}>LBR</option>
                                        <option value="BUKU" {{ $material->satuan == 'BUKU' ? 'selected' : '' }}>BUKU</option>
                                        <option value="SET" {{ $material->satuan == 'SET' ? 'selected' : '' }}>SET</option>
                                        <option value="PCS" {{ $material->satuan == 'PCS' ? 'selected' : '' }}>PCS</option>
                                        <option value="PASANG" {{ $material->satuan == 'PASANG' ? 'selected' : '' }}>PASANG</option>
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

                            {{-- HANYA TAMPILKAN JIKA BUKAN CHILD (TIDAK MEMILIKI PARENT) --}}
                            @if(is_null($material->parent_id))
                            <div class="row custom-attribute-row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label modal-label">Material Utama?</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input radio-ismain" type="radio" name="ismain" id="edit_ismain_0_{{ $material->id }}" value="0" {{ $material->ismain == 0 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="edit_ismain_0_{{ $material->id }}">Tidak</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input radio-ismain" type="radio" name="ismain" id="edit_ismain_1_{{ $material->id }}" value="1" {{ $material->ismain == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="edit_ismain_1_{{ $material->id }}">Ya</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3 wrapper-jmlxinduk" style="{{ $material->ismain == 1 ? 'display: none;' : '' }}">
                                    <label class="form-label modal-label">Jumlah Mengikuti Induk?</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input radio-jmlxinduk" type="radio" name="jmlxinduk" id="edit_jmlxinduk_0_{{ $material->id }}" value="0" {{ $material->jmlxinduk == 0 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="edit_jmlxinduk_0_{{ $material->id }}">Tidak</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input radio-jmlxinduk" type="radio" name="jmlxinduk" id="edit_jmlxinduk_1_{{ $material->id }}" value="1" {{ $material->jmlxinduk == 1 ? 'checked' : '' }}>
                                            <label class="form-check-label" for="edit_jmlxinduk_1_{{ $material->id }}">Ya</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="mb-2">
                                <label class="form-label modal-label">Keterangan Spesifikasi</label>
                                <textarea name="keterangan" class="form-control modal-custom-input" rows="3">{{ old('keterangan', $material->keterangan) }}</textarea>
                            </div>
                        @endif

                    </div>
                    <div class="modal-footer border-0 bg-light py-2 gap-2">
                        <button type="submit" class="btn btn-theme px-4 fw-bold shadow-sm" style="border-radius: 6px;">SIMPAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<!-- ========================================================================================= -->
<!-- JENDELA POP-UP MODAL TAMBAH BARANG HYBRID -->
<!-- ========================================================================================= -->
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

                    <!-- SEKSI BARANG TUNGGAL -->
                    <div id="section-tunggal">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label modal-label">Code Barang (Opsional)</label>
                                <input type="text" name="code" id="code_tunggal" class="form-control modal-custom-input" placeholder="Contoh: BRG-001">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label modal-label">Nama Barang</label>
                                <input type="text" name="name" id="name_tunggal" class="form-control modal-custom-input" placeholder="Contoh: Blanko Elektronik BPKB">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label modal-label">Kategori</label>
                                <select name="material_category_id" id="cat_tunggal" class="form-select modal-custom-input">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label modal-label">No Urut Kategori (Opsional)</label>
                                <input type="number" name="nomor_urut" id="urut_tunggal" class="form-control modal-custom-input" placeholder="Otomatis terakhir" min="1">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label modal-label">Satuan Ukur</label>
                                <select name="satuan" id="satuan_tunggal" class="form-select modal-custom-input">
                                    <option value="LBR">LBR</option>
                                    <option value="BUKU">BUKU</option>
                                    <option value="SET">SET</option>
                                    <option value="PCS">PCS</option>
                                    <option value="PASANG">PASANG</option>
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

                        <!-- TAMBAHAN MATERIAL UTAMA DAN JUMLAH MENGIKUTI INDUK DI SINI -->
                        <div class="row custom-attribute-row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label modal-label">Material Utama?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input radio-ismain" type="radio" name="ismain" id="add_ismain_0" value="0" checked>
                                        <label class="form-check-label" for="add_ismain_0">Tidak</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input radio-ismain" type="radio" name="ismain" id="add_ismain_1" value="1">
                                        <label class="form-check-label" for="add_ismain_1">Ya</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3 wrapper-jmlxinduk">
                                <label class="form-label modal-label">Jumlah Mengikuti Induk?</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input radio-jmlxinduk" type="radio" name="jmlxinduk" id="add_jmlxinduk_0" value="0" checked>
                                        <label class="form-check-label" for="add_jmlxinduk_0">Tidak</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input radio-jmlxinduk" type="radio" name="jmlxinduk" id="add_jmlxinduk_1" value="1">
                                        <label class="form-check-label" for="add_jmlxinduk_1">Ya</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label modal-label">Keterangan Opsional</label>
                            <textarea name="keterangan" class="form-control modal-custom-input" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- SEKSI BARANG BERKELOMPOK -->
                    <div id="section-kelompok" class="d-none">
                        <div class="p-3 bg-light rounded-3 border mb-4">
                            <h6 class="fw-bold text-theme mb-3">A. Identitas Induk Kelompok</h6>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label modal-label">Code Induk Kelompok (Opsional)</label>
                                    <input type="text" name="parent_code" id="code_kelompok" class="form-control modal-custom-input" placeholder="Contoh: K-BPKB" disabled>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <label class="form-label modal-label">Nama Induk Barang</label>
                                    <input type="text" name="parent_name" id="name_kelompok" class="form-control modal-custom-input" placeholder="Contoh: Kartu Induk E-BPKB" disabled>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label modal-label">Berlaku untuk Kategori</label>
                                    <select name="material_category_id" id="cat_kelompok" class="form-select modal-custom-input" disabled>
                                        <option value="">-- Pilih Kategori --</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label modal-label">No Urut Kategori (Opsional)</label>
                                    <input type="number" name="nomor_urut" id="urut_kelompok" class="form-control modal-custom-input" placeholder="Otomatis terakhir" min="1" disabled>
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
                                                <option value="SET">SET</option>
                                                <option value="PCS">PCS</option>
                                                <option value="PASANG">PASANG</option>
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
                <div class="modal-footer border-0 bg-light py-2 gap-2">
                    <button type="submit" name="submit_action" value="save_new" class="btn btn-outline-dark px-4 fw-bold shadow-sm" style="border-radius: 6px;">SIMPAN & BARU</button>
                    <button type="submit" name="submit_action" value="save" class="btn btn-theme px-4 fw-bold shadow-sm" style="border-radius: 6px;">SIMPAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tableContainer = document.getElementById('table-sortable-container');
        
        if (tableContainer) {
            new Sortable(tableContainer, {
                draggable: '.material-group-wrapper',
                handle: '.drag-handle',
                animation: 200,
                
                onEnd: function (evt) {
                    const draggedNode = evt.item;
                    const draggedCategoryId = draggedNode.getAttribute('data-category');
                    
                    const allNodes = Array.from(tableContainer.querySelectorAll('.material-group-wrapper'));
                    const siblingNodes = allNodes.filter(node => node.getAttribute('data-category') === draggedCategoryId && node !== draggedNode);
                    
                    if (siblingNodes.length > 0) {
                        const firstSibling = siblingNodes[0];
                        const lastSibling = siblingNodes[siblingNodes.length - 1];
                        
                        const draggedIndex = allNodes.indexOf(draggedNode);
                        const firstSiblingIndex = allNodes.indexOf(firstSibling);
                        const lastSiblingIndex = allNodes.indexOf(lastSibling);
                        
                        if (draggedIndex < firstSiblingIndex) {
                            firstSibling.parentNode.insertBefore(draggedNode, firstSibling);
                        } else if (draggedIndex > lastSiblingIndex) {
                            lastSibling.parentNode.insertBefore(draggedNode, lastSibling.nextSibling);
                        }
                    }
                    
                    recalculateSequenceAndSave();
                }
            });
        }

        @if(session('keep_modal_open'))
            const modalAdd = new bootstrap.Modal(document.getElementById('modalAddMaterial'));
            modalAdd.show();
            
            @if(session('old_tipe_input') == 'kelompok')
                document.querySelector('input[name="tipe_input"][value="kelompok"]').click();
            @endif
        @endif
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            const variantContainer = this.querySelector('[id^="edit-variant-container-"]');
            if (variantContainer && !variantContainer.classList.contains('sortable-child-initialized')) {
                new Sortable(variantContainer, {
                    animation: 150,
                    handle: '.child-drag-handle',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen'
                });
                variantContainer.classList.add('sortable-child-initialized');
            }
        });
    });

    function recalculateSequenceAndSave() {
        const tableContainer = document.getElementById('table-sortable-container');
        const categories = {};
        const globalPayload = [];

        tableContainer.querySelectorAll('.material-group-wrapper').forEach(node => {
            const catId = node.getAttribute('data-category');
            const itemId = node.getAttribute('data-id');
            
            if (!categories[catId]) {
                categories[catId] = [];
            }
            categories[catId].push({ id: itemId, element: node });
        });

        Object.keys(categories).forEach(catId => {
            categories[catId].forEach((item, index) => {
                const urutBaru = index + 1;
                const textUrut = item.element.querySelector('.item-nomor-urut');
                if (textUrut) {
                    textUrut.textContent = urutBaru;
                }
                globalPayload.push({ id: item.id, nomor_urut: urutBaru });
            });
        });

        fetch("{{ route('materials.reorder') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ items: globalPayload })
        })
        .then(response => response.json())
        .then(data => {
            if(data.status !== 'success') {
                alert('Gagal menyelaraskan urutan data ke server.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    let variantIndex = 1;

    document.querySelectorAll('.mode-selector').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('box-tunggal').classList.remove('active');
            document.getElementById('box-kelompok').classList.remove('active');
            
            if(this.value === 'tunggal') {
                document.getElementById('box-tunggal').classList.add('active');
                document.getElementById('section-tunggal').classList.remove('d-none');
                document.getElementById('section-kelompok').classList.add('d-none');
                
                document.getElementById('name_tunggal').disabled = false;
                document.getElementById('code_tunggal').disabled = false;
                document.getElementById('cat_tunggal').disabled = false;
                document.getElementById('satuan_tunggal').disabled = false;
                document.getElementById('urut_tunggal').disabled = false;
                
                document.getElementById('name_kelompok').disabled = true;
                document.getElementById('code_kelompok').disabled = true;
                document.getElementById('cat_kelompok').disabled = true;
                document.getElementById('ket_kelompok').disabled = true;
                document.getElementById('urut_kelompok').disabled = true;
                document.querySelectorAll('#variant-container input, #variant-container select').forEach(el => el.disabled = true);
                
            } else {
                document.getElementById('box-kelompok').classList.add('active');
                document.getElementById('section-tunggal').classList.add('d-none');
                document.getElementById('section-kelompok').classList.remove('d-none');
                
                document.getElementById('name_tunggal').disabled = true;
                document.getElementById('code_tunggal').disabled = true;
                document.getElementById('cat_tunggal').disabled = true;
                document.getElementById('satuan_tunggal').disabled = true;
                document.getElementById('urut_tunggal').disabled = true;
                
                document.getElementById('name_kelompok').disabled = false;
                document.getElementById('code_kelompok').disabled = false;
                document.getElementById('cat_kelompok').disabled = false;
                document.getElementById('ket_kelompok').disabled = false;
                document.getElementById('urut_kelompok').disabled = false;
                document.querySelectorAll('#variant-container input, #variant-container select').forEach(el => el.disabled = false);
            }
        });
    });

    function addVariantRow() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="variants[${variantIndex}][name]" class="form-control form-control-sm" placeholder="Nama varian..." required></td>
            <td>
                <select name="variants[${variantIndex}][satuan]" class="form-select form-select-sm" required>
                    <option value="LBR">LBR</option>
                    <option value="BUKU">BUKU</option>
                    <option value="SET">SET</option>
                    <option value="PCS">PCS</option>
                    <option value="PASANG">PASANG</option>
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

    function addEditVariantRow(parentId) {
        const container = document.getElementById('edit-variant-container-' + parentId);
        const index = Date.now(); 
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-grip-vertical child-drag-handle me-2 text-muted" style="cursor: move;"></i>
                    <input type="text" name="variants[${index}][name]" class="form-control form-control-sm" placeholder="Nama varian tambahan..." required>
                </div>
            </td>
            <td>
                <select name="variants[${index}][satuan]" class="form-select form-select-sm" required>
                    <option value="LBR">LBR</option>
                    <option value="BUKU">BUKU</option>
                    <option value="SET">SET</option>
                    <option value="PCS">PCS</option>
                    <option value="PASANG">PASANG</option>
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

    // Script Penanganan Tampil / Sembunyikan 'Jumlah Mengikuti Induk'
    document.addEventListener('change', function(e) {
        if (e.target.matches('.radio-ismain')) {
            // Mencari container baris (.custom-attribute-row) tempat radio ini berada
            let container = e.target.closest('.custom-attribute-row');
            if(container) {
                let wrapperJmlx = container.querySelector('.wrapper-jmlxinduk');
                let radioJmlxTidak = container.querySelector('.radio-jmlxinduk[value="0"]');
                
                if (e.target.value === '1') {
                    // Jika Material Utama = Ya, sembunyikan kolom "Jumlah Mengikuti Induk"
                    wrapperJmlx.style.display = 'none';
                    // Paksa value-nya menjadi Tidak (0)
                    if (radioJmlxTidak) radioJmlxTidak.checked = true;
                } else {
                    // Jika Material Utama = Tidak, tampilkan kembali
                    wrapperJmlx.style.display = 'block';
                }
            }
        }
    });
</script>
@endpush
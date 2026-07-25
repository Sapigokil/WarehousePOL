@extends('layouts.app')
@section('title', 'Mapping Kolom SIMAK')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; background: linear-gradient(135deg, #475569, #334155); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.15; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }
    
    /* Drag & Drop Styles */
    .list-group-item { cursor: grab; transition: background-color 0.2s; border: 1px solid #e2e8f0; margin-bottom: 6px !important; }
    .list-group-item:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; background-color: #f1f5f9; }
    
    /* Parent Header (Non Draggable) */
    .parent-header { cursor: default !important; pointer-events: none; border: none; border-bottom: 1px solid #e2e8f0; background-color: transparent !important; padding-left: 0; margin-bottom: 8px !important; }
    
    /* Indikator Terpakai (Kiri) */
    .bg-mapped { background-color: #f0fdf4 !important; border-color: #bbf7d0 !important; }
    
    /* Sticky Left Panel - Diubah ke 120px agar turun melewati navbar */
    .sticky-left { position: sticky; top: 120px; z-index: 10; }
    .unmapped-container { height: calc(100vh - 220px); overflow-y: auto; background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 10px; }
    .mapped-container { min-height: 400px; border-radius: 8px; }
    
    /* Style Grup / Blok SIMAK Kanan */
    .simak-group { background: white; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .simak-group-header { background: #f1f5f9; padding: 6px 12px; border-bottom: 1px solid #e2e8f0; border-radius: 6px 6px 0 0; display: flex; align-items: center; justify-content: space-between; cursor: grab; }
    .simak-group-header:active { cursor: grabbing; }
    .simak-group-body { padding: 8px; min-height: 45px; background: #ffffff; border-radius: 0 0 6px 6px; }
    
    .btn-delete-group { color: #ef4444; background: none; border: none; padding: 0; margin-left: 10px; }
    .btn-delete-group:hover { color: #dc2626; }
</style>
<!-- Load SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
@endpush

@section('content')

<!-- Header Banner -->
<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-cogs header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-table-columns me-2"></i> Mapping Kolom SIMAK</h4>
        <p class="mb-0 text-white-50 small">Atur urutan (Kiri ke Kanan) dan gabungkan materiil ke dalam kolom laporan SIMAK.</p>
    </div>
    <div class="header-content d-flex gap-2">
        <button type="button" class="btn btn-danger fw-bold shadow-sm px-3 py-2 border border-light" onclick="resetAll()" style="border-radius: 8px; background-color: #ef4444;">
            <i class="fa-solid fa-rotate-left me-1"></i> Reset Semua
        </button>
        <button type="button" class="btn btn-light fw-bold text-dark shadow-sm px-4 py-2" onclick="submitMapping()" style="border-radius: 8px;">
            <i class="fa-solid fa-floppy-disk me-1 text-primary"></i> Simpan Konfigurasi
        </button>
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
        <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- AREA KIRI: Hirarki Materiil (Clone Source) -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-3 sticky-left">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-list-check me-2 text-muted"></i> Daftar Materiil</h6>
                <small class="text-muted" style="font-size: 0.7rem;">Tarik item materiil (bukan folder) ke panel kanan.</small>
            </div>
            <div class="card-body p-3">
                <div id="unmappedArea" class="unmapped-container list-group">
                    @foreach($allGrouped as $categoryName => $parents)
                        
                        <!-- HIRARKI 1: Kategori (Paling Kiri, Non-Draggable) dengan warna background jelas -->
                        <div class="category-header fw-bold p-2 mb-2 mt-3 rounded-2 shadow-sm" style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; border-left: 4px solid #0284c7; font-size: 0.75rem; letter-spacing: 0.5px;">
                            <i class="fa-solid fa-tags me-2 opacity-75" style="color: #0284c7;"></i> {{ strtoupper($categoryName) }}
                        </div>
                        
                        @foreach($parents as $parent)
                            
                            @if($parent->children_list->count() > 0)
                                <!-- HIRARKI 2 (Jika punya anak): Parent sebagai Header Label -->
                                <div class="list-group-item parent-header fw-bold text-dark mt-2" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-folder-open text-warning me-2"></i> {{ $parent->name }}
                                </div>
                                
                                <!-- HIRARKI 3: Child (Draggable, bergeser ke kanan) -->
                                @foreach($parent->children_list as $child)
                                    @php $isMapped = in_array($child->id, $mappedIds); @endphp
                                    <div class="list-group-item sortable-item draggable-child ms-4 rounded-2 shadow-sm bg-white {{ $isMapped ? 'bg-mapped' : '' }}" data-id="{{ $child->id }}" style="border-left: 3px solid #cbd5e1;">
                                        <div class="d-flex justify-content-between align-items-center py-1 head-wrapper">
                                            <div class="text-dark fw-semibold" style="font-size: 0.8rem;">
                                                <i class="fa-solid fa-grip-vertical text-muted me-2 opacity-50"></i> {{ $child->name }}
                                            </div>
                                            <i class="fa-solid fa-circle-check text-warning check-icon fs-5 {{ $isMapped ? '' : 'd-none' }}"></i>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <!-- HIRARKI 2 (Jika TIDAK punya anak): Parent bisa ditarik -->
                                @php $isMapped = in_array($parent->id, $mappedIds); @endphp
                                <div class="list-group-item sortable-item draggable-child rounded-2 shadow-sm bg-white mt-1 {{ $isMapped ? 'bg-mapped' : '' }}" data-id="{{ $parent->id }}">
                                    <div class="d-flex justify-content-between align-items-center py-1 head-wrapper">
                                        <div class="text-dark fw-bold" style="font-size: 0.8rem;">
                                            <i class="fa-solid fa-grip-vertical text-muted me-2 opacity-50"></i> {{ $parent->name }}
                                        </div>
                                        <i class="fa-solid fa-circle-check text-warning check-icon fs-5 {{ $isMapped ? '' : 'd-none' }}"></i>
                                    </div>
                                </div>
                            @endif
                            
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- AREA KANAN: Susunan Kolom SIMAK -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <!-- Header Kanan diubah ke 120px agar turun melewati navbar -->
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center" style="position: sticky; top: 80px; z-index: 11; border-radius: 8px 8px 0 0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-layer-group me-2 text-teal" style="color: #0d9488;"></i> Susunan Kolom (Kiri ke Kanan)</h6>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="addGroup()">
                    <i class="fa-solid fa-plus me-1"></i> Tambah Kolom
                </button>
            </div>
            <div class="card-body p-3 bg-light">
                <div id="mappedArea" class="mapped-container">
                    
                    @foreach($mappedGroups as $label => $materials)
                        <div class="simak-group">
                            <div class="simak-group-header">
                                <div class="d-flex align-items-center w-100">
                                    <i class="fa-solid fa-arrows-up-down text-muted me-3"></i>
                                    <input type="text" class="form-control form-control-sm fw-bold group-label" value="{{ $label }}" placeholder="NAMA HEADER (Misal: SIM)" style="max-width: 300px;">
                                </div>
                                <button type="button" class="btn-delete-group" onclick="deleteGroup(this)" title="Hapus Kolom">
                                    <i class="fa-solid fa-circle-xmark fs-5"></i>
                                </button>
                            </div>
                            <div class="simak-group-body list-group">
                                @foreach($materials as $mat)
                                    <!-- Render ulang yang sudah di map (Individual) -->
                                    <div class="list-group-item sortable-item draggable-child rounded-2 shadow-sm bg-white" data-id="{{ $mat->id }}">
                                        <div class="d-flex justify-content-between align-items-center py-1 head-wrapper">
                                            <div class="text-dark fw-semibold" style="font-size: 0.8rem;">
                                                <i class="fa-solid fa-grip-vertical text-muted me-2 opacity-50"></i> 
                                                @if($mat->parent_id) 
                                                    <span class="text-muted small me-1">[{{ $mat->parent->name ?? 'Child' }}]</span> 
                                                @endif
                                                {{ $mat->name }}
                                            </div>
                                            <button type="button" class="btn btn-sm text-danger p-0 m-0 btn-remove-item" onclick="removeItem(this)" title="Keluarkan Item"><i class="fa-solid fa-times fs-5"></i></button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                </div>
                <!-- Area kosong jika tidak ada grup -->
                <div id="emptyGroupMessage" class="text-center py-4 text-muted {{ count($mappedGroups) > 0 ? 'd-none' : '' }}">
                    <i class="fa-solid fa-diagram-project fs-3 mb-2 opacity-25"></i>
                    <p class="mb-0 small">Belum ada kolom. Klik tombol "Tambah Kolom" di atas.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for Submission -->
<form id="mappingForm" action="{{ route('settings.reports.simak.store') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="mapping_data" id="mappingDataInput">
</form>

@endsection

@push('scripts')
<script>
    // Fungsi UI: Memperbarui centang hijau dan centang kuning di Panel Kiri
    function updateLeftUI() {
        let usedIds = [];
        // Ambil semua data-id individual yang ada di kotak sebelah kanan
        document.querySelectorAll('#mappedArea .draggable-child').forEach(el => {
            usedIds.push(el.getAttribute('data-id'));
        });
        
        // Loop kotak di sebelah kiri
        document.querySelectorAll('#unmappedArea .draggable-child').forEach(el => {
            let mId = el.getAttribute('data-id');
            let icon = el.querySelector('.check-icon');
            if(usedIds.includes(mId)) {
                el.classList.add('bg-mapped');
                if(icon) icon.classList.remove('d-none');
            } else {
                el.classList.remove('bg-mapped');
                if(icon) icon.classList.add('d-none');
            }
        });
    }

    // 1. Inisialisasi Sortable untuk Area Kiri (Unmapped)
    const unmappedArea = document.getElementById('unmappedArea');
    new Sortable(unmappedArea, {
        group: {
            name: 'shared',
            pull: 'clone', // Saat didrag, dia membuat duplikat, tidak menghilangkan yang asli
            put: false     // Area kiri tidak bisa menerima barang dari kanan
        },
        animation: 150,
        sort: false, 
        filter: '.category-header, .parent-header', // Header Kategori dan Header Parent tidak bisa di-drag
        preventOnFilter: false
    });

    // 2. Inisialisasi Sortable untuk Susunan Grup/Blok (Area Kanan Vertikal)
    const mappedArea = document.getElementById('mappedArea');
    new Sortable(mappedArea, {
        animation: 150,
        handle: '.simak-group-header', // Memindah kolom hanya lewat headernya
        ghostClass: 'sortable-ghost'
    });

    // 3. Fungsi Dropzone Kanan (Tempat Menampung Clone)
    function initGroupDropzones() {
        const dropzones = document.querySelectorAll('.simak-group-body');
        dropzones.forEach(zone => {
            if (!zone.sortableInstance) {
                zone.sortableInstance = new Sortable(zone, {
                    group: 'shared',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onAdd: function (evt) {
                        let itemEl = evt.item;
                        
                        // Manipulasi DOM pada barang Clone (Barang yang mendarat di kanan)
                        if(evt.pullMode === 'clone') {
                            itemEl.classList.remove('bg-mapped');
                            itemEl.classList.remove('ms-4'); // Hapus indentasi child jika ada
                            itemEl.classList.add('bg-white');
                            itemEl.style.borderLeft = ''; // Reset custom border
                            
                            // Ganti icon centang dengan tombol X
                            let checkIcon = itemEl.querySelector('.check-icon');
                            if(checkIcon) checkIcon.remove();
                            
                            let headerDiv = itemEl.querySelector('.head-wrapper');
                            let removeBtn = document.createElement('button');
                            removeBtn.type = 'button';
                            removeBtn.className = 'btn btn-sm text-danger p-0 m-0 btn-remove-item';
                            removeBtn.title = "Keluarkan Item";
                            removeBtn.innerHTML = '<i class="fa-solid fa-times fs-5"></i>';
                            removeBtn.onclick = function() { removeItem(this); };
                            
                            headerDiv.appendChild(removeBtn);
                        }
                        
                        updateLeftUI();
                    },
                    onRemove: function() {
                        updateLeftUI();
                    }
                });
            }
        });
    }

    initGroupDropzones();

    // 4. Fungsi Menambah Grup Baru
    function addGroup() {
        document.getElementById('emptyGroupMessage').classList.add('d-none');
        
        const groupHTML = `
            <div class="simak-group">
                <div class="simak-group-header">
                    <div class="d-flex align-items-center w-100">
                        <i class="fa-solid fa-arrows-up-down text-muted me-3"></i>
                        <input type="text" class="form-control form-control-sm fw-bold group-label" placeholder="NAMA HEADER (Misal: SIM)" style="max-width: 300px;">
                    </div>
                    <button type="button" class="btn-delete-group" onclick="deleteGroup(this)" title="Hapus Kolom">
                        <i class="fa-solid fa-circle-xmark fs-5"></i>
                    </button>
                </div>
                <div class="simak-group-body list-group">
                    <!-- Barang didrop kesini -->
                </div>
            </div>
        `;
        mappedArea.insertAdjacentHTML('beforeend', groupHTML);
        initGroupDropzones(); 
    }

    // 5. Menghapus 1 Barang dari Panel Kanan
    function removeItem(btn) {
        btn.closest('.draggable-child').remove();
        updateLeftUI();
    }

    // 6. Menghapus 1 Kolom Grup Penuh
    function deleteGroup(btn) {
        if(confirm('Hapus kolom SIMAK ini? (Materiil di dalamnya otomatis batal ter-mapping)')) {
            btn.closest('.simak-group').remove();
            updateLeftUI();
            
            if(mappedArea.children.length === 0) {
                document.getElementById('emptyGroupMessage').classList.remove('d-none');
            }
        }
    }

    // 7. Tombol Reset Semua
    function resetAll() {
        if(confirm('PERINGATAN: Apakah Anda yakin ingin mereset seluruh konfigurasi kolom SIMAK?')) {
            mappedArea.innerHTML = '';
            document.getElementById('emptyGroupMessage').classList.remove('d-none');
            updateLeftUI();
        }
    }

    // 8. Kumpulkan Data (Individual IDs) dan Submit
    function submitMapping() {
        const groups = document.querySelectorAll('.simak-group');
        const mappingData = [];

        groups.forEach(group => {
            const labelInput = group.querySelector('.group-label').value.trim();
            const items = group.querySelectorAll('.list-group-item');
            
            // Ambil "data-id"
            const materialIds = Array.from(items).map(item => item.getAttribute('data-id'));
            
            if(labelInput !== "" || materialIds.length > 0) {
                mappingData.push({
                    label: labelInput,
                    materials: materialIds
                });
            }
        });

        document.getElementById('mappingDataInput').value = JSON.stringify(mappingData);
        document.getElementById('mappingForm').submit();
    }
</script>
@endpush
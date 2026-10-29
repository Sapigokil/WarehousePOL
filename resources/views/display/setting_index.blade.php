@extends('layouts.app')

@section('title', 'Pengaturan Display Monitor')

@push('styles')
<style>
    .form-card { background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; }
    .table-display th { background-color: #f8fafc; color: #475569; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
    .table-display td { padding: 8px 12px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .modal-scrollable { max-height: 400px; overflow-y: auto; }
    .cursor-move { cursor: grab; }
    .cursor-move:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; background-color: #e0f2fe !important; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-desktop text-theme me-2"></i> Pengaturan Tampilan Display Monitor
        </h5>
        <small class="text-muted">Kelola daftar material, custom label, pilihan tema layar, dan konfigurasi live monitor.</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-dark fw-bold px-3 shadow-sm" id="btnLaunchMonitor">
            <i class="fa-solid fa-up-right-from-square me-1"></i> Buka Display Monitor
        </button>
        <button type="button" class="btn btn-sm btn-theme fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahDisplayMaterial">
            <i class="fa-solid fa-plus me-1"></i> Tambah Material
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 py-2 mb-3" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- KARTU PENGATURAN GLOBAL & PILIHAN TEMA --}}
<div class="form-card shadow-sm p-3 mb-4">
    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
        <i class="fa-solid fa-sliders text-primary me-2"></i> Konfigurasi Global Screen & Tema Display
    </h6>
    <form action="{{ route('settings.display.update_global') }}" method="POST">
        @csrf
        @method('PUT')
        
        {{-- BARIS 1: PILIHAN TEMA --}}
        <div class="row g-3 mb-3 border-bottom pb-3">
            <div class="col-md-6">
                <label class="form-label small fw-bold text-muted mb-1">Pilihan Tema Layout Monitor</label>
                <select name="display_theme" class="form-select fw-bold border-primary">
                    @foreach($availableThemes as $themeKey => $themeLabel)
                        <option value="{{ $themeKey }}" {{ $settings['display_theme'] == $themeKey ? 'selected' : '' }}>
                            {{ $themeLabel }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted d-block mt-1">Tema yang dipilih akan otomatis digunakan saat rute <code>/display/live</code> dibuka.</small>
            </div>
        </div>

        {{-- BARIS 2: KONFIGURASI PARAMETER TAMPILAN --}}
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Refresh Data (Detik)</label>
                <input type="number" name="refresh_interval" class="form-control form-control-sm fw-bold" value="{{ $settings['refresh_interval'] }}" min="5" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Item Per Halaman</label>
                <input type="number" name="items_per_page" class="form-control form-control-sm fw-bold" value="{{ $settings['items_per_page'] }}" min="1" max="24" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">Auto-Slide (Detik)</label>
                <input type="number" name="slide_interval" class="form-control form-control-sm fw-bold" value="{{ $settings['slide_interval'] }}" min="3" required>
            </div>
            <div class="col-md-3 d-flex align-items-center justify-content-between pt-3">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="auto_slide" id="chkAutoSlide" {{ $settings['auto_slide'] == '1' ? 'checked' : '' }}>
                    <label class="form-check-label small fw-bold text-dark" for="chkAutoSlide">Auto-Slide</label>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary fw-bold px-3">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Setting
                </button>
            </div>
        </div>
    </form>
</div>

{{-- TABEL DAFTAR MATERIAL DISPLAY WITH DRAG & DROP --}}
<div class="form-card shadow-sm p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted"><i class="fa-solid fa-hand-pointer me-1"></i> Tarik icon <i class="fa-solid fa-grip-vertical mx-1"></i> untuk menggeser dan merubah urutan material secara otomatis.</small>
    </div>
    <form action="{{ route('settings.display.update_materials_bulk') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="table-responsive">
            <table class="table-display w-100">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">#</th>
                        <th width="8%" class="text-center">No. Urut</th>
                        <th width="26%">Material</th>
                        <th width="32%">Custom Label (Tampilan Monitor)</th>
                        <th width="15%">Kategori</th>
                        <th width="8%" class="text-center">Status</th>
                        <th width="7%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="sortable-materials">
                    @forelse($displayMaterials as $index => $item)
                        @php
                            $mat = $item->material;
                            $formattedMaterialName = '-';
                            if ($mat) {
                                $formattedMaterialName = $mat->parent 
                                    ? $mat->parent->name . ' - ' . $mat->name 
                                    : $mat->name;
                            }
                        @endphp
                        <tr data-id="{{ $item->id }}">
                            <td class="text-center cursor-move text-secondary pe-0">
                                <i class="fa-solid fa-grip-vertical fs-6"></i>
                            </td>
                            <td class="text-center">
                                {{-- LABEL TEKS KONTINER & HIDDEN INPUT UNTUK FORM --}}
                                <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-bold label-urut" style="font-size: 0.85rem;">{{ $item->nomor_urut }}</span>
                                <input type="hidden" name="items[{{ $item->id }}][nomor_urut]" class="input-urut" value="{{ $item->nomor_urut }}">
                            </td>
                            <td>
                                <strong class="text-dark d-block" style="font-size: 0.85rem;">{{ $formattedMaterialName }}</strong>
                                <small class="text-muted" style="font-size: 0.75rem;">Satuan: {{ $item->material->satuan ?? '-' }} | Rate: {{ $item->material->rate_usage ?? 0 }}/bln</small>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $item->id }}][custom_label]" class="form-control form-control-sm fw-semibold" value="{{ $item->custom_label }}" placeholder="Gunakan nama asli jika kosong...">
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.75rem;">
                                    {{ $item->material->category->name ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input class="form-check-input" type="checkbox" name="items[{{ $item->id }}][is_active]" value="1" {{ $item->is_active ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-light text-danger border shadow-none px-2 py-0.5" onclick="if(confirm('Keluarkan material ini dari daftar display monitor?')) { document.getElementById('form-delete-display-{{ $item->id }}').submit(); }" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted bg-white">
                                <i class="fa-solid fa-desktop fs-2 mb-2 opacity-25"></i>
                                <p class="mb-0 small">Belum ada material yang didaftarkan ke display monitor. Klik <strong>Tambah Material</strong> di atas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($displayMaterials->count() > 0)
            <div class="d-flex justify-content-end mt-3 border-top pt-3">
                <button type="submit" class="btn btn-theme fw-bold px-4 shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Urutan & Custom Label
                </button>
            </div>
        @endif
    </form>
</div>

{{-- Hidden Form Delete --}}
@foreach($displayMaterials as $item)
    <form id="form-delete-display-{{ $item->id }}" action="{{ route('settings.display.destroy_material', $item->id) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endforeach

<!-- MODAL TAMBAH MATERIAL TO DISPLAY -->
<div class="modal fade" id="modalTambahDisplayMaterial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('settings.display.store_materials') }}" method="POST">
                @csrf
                <div class="modal-header bg-light border-bottom-0">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-list-check text-theme me-2"></i> Pilih Material Untuk Display Monitor
                    </h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Pilih material untuk ditambahkan ke daftar layar monitor. Material dikelompokkan berdasarkan kategori.
                    </p>
                    
                    <div class="mb-3">
                        <input type="text" id="filterModalMaterial" class="form-control form-control-sm" placeholder="Cari nama material atau kategori...">
                    </div>

                    <div class="border rounded p-3 modal-scrollable bg-light" id="modalMaterialList">
                        @forelse($availableMaterials as $categoryName => $materials)
                            <div class="category-group-block mb-3">
                                <div class="fw-bold text-theme mb-2 pb-1 border-bottom border-primary border-opacity-25 d-flex align-items-center justify-content-between" style="font-size: 0.85rem;">
                                    <span><i class="fa-solid fa-folder-open me-2"></i>{{ $categoryName }}</span>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border fw-normal" style="font-size: 0.7rem;">{{ $materials->count() }} Material</span>
                                </div>

                                @foreach($materials as $mat)
                                    @php
                                        $modalMatName = $mat->parent ? $mat->parent->name . ' - ' . $mat->name : $mat->name;
                                    @endphp
                                    <div class="material-item-row mb-2" data-name="{{ strtolower($modalMatName) }}" data-category="{{ strtolower($categoryName) }}">
                                        <div class="form-check bg-white border rounded p-2 ps-4 d-flex align-items-center">
                                            <input class="form-check-input me-2" type="checkbox" name="material_ids[]" value="{{ $mat->id }}" id="chk_disp_mat_{{ $mat->id }}">
                                            <label class="form-check-label w-100" for="chk_disp_mat_{{ $mat->id }}" style="cursor: pointer; font-size: 0.85rem;">
                                                <strong class="text-dark d-block">{{ $modalMatName }}</strong>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                    Satuan: {{ $mat->satuan }} | Rate Usage: {{ $mat->rate_usage ?? 0 }}/bln
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <span class="small">Seluruh material aktif sudah ditambahkan ke daftar display monitor.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-sm btn-light border fw-bold px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-theme fw-bold px-4">Tambahkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Library SortableJS untuk Drag and Drop --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Init Sortable JS pada Tabel Material Display
        const sortableEl = document.getElementById('sortable-materials');
        if (sortableEl) {
            new Sortable(sortableEl, {
                handle: '.cursor-move',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    // Update label teks dan hidden input nomor urut sesuai posisi baris baru
                    const rows = sortableEl.querySelectorAll('tr');
                    rows.forEach((row, index) => {
                        const newUrut = index + 1;
                        const labelUrut = row.querySelector('.label-urut');
                        const inputUrut = row.querySelector('.input-urut');

                        if (labelUrut) {
                            labelUrut.textContent = newUrut;
                        }
                        if (inputUrut) {
                            inputUrut.value = newUrut;
                        }
                    });
                }
            });
        }
    });

    // Live Search Filter di Dalam Modal
    document.getElementById('filterModalMaterial')?.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        
        document.querySelectorAll('.category-group-block').forEach(group => {
            let hasVisible = false;
            
            group.querySelectorAll('.material-item-row').forEach(row => {
                const name = row.dataset.name;
                const category = row.dataset.category;
                
                if (name.includes(query) || category.includes(query)) {
                    row.style.display = '';
                    hasVisible = true;
                } else {
                    row.style.display = 'none';
                }
            });

            if (hasVisible) {
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        });
    });

    // LAUNCH MONITOR MEMAKSA OPEN NEW POPUP WINDOW
    document.getElementById('btnLaunchMonitor')?.addEventListener('click', async function () {
        const targetUrl = "{{ route('display.live') }}";

        let targetLeft = 0;
        let targetTop = 0;
        let targetWidth = window.screen.availWidth || 1280;
        let targetHeight = window.screen.availHeight || 720;

        if ('getScreenDetails' in window) {
            try {
                const screenDetails = await window.getScreenDetails();
                const secondaryScreen = screenDetails.screens.find(screen => screen !== screenDetails.currentScreen) 
                                     || screenDetails.screens.find(screen => !screen.isPrimary);

                if (secondaryScreen) {
                    targetLeft = secondaryScreen.availLeft;
                    targetTop = secondaryScreen.availTop;
                    targetWidth = secondaryScreen.availWidth;
                    targetHeight = secondaryScreen.availHeight;
                }
            } catch (err) {
                console.warn("Izin multi-screen ditolak, menggunakan dimensi layar utama:", err);
            }
        }

        const windowFeatures = `popup=yes,left=${targetLeft},top=${targetTop},width=${targetWidth},height=${targetHeight},menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=no`;
        
        const monitorWindow = window.open(targetUrl, '_blank', windowFeatures);
        if (monitorWindow) {
            monitorWindow.focus();
        }
    });
</script>
@endpush
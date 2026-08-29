@extends('layouts.app')
@section('title', 'Mapping Laporan Terima Keluar')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; background: linear-gradient(135deg, #475569, #1e293b); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.10; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }
    
    .table-mapping { width: 100%; border-collapse: collapse; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .table-mapping thead { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .table-mapping thead th { color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 15px; font-weight: 700; vertical-align: middle; text-align: center;}
    .table-mapping tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
    .table-mapping tbody tr:hover { background-color: #f8fafc; }
    .table-mapping td { padding: 8px 15px; vertical-align: middle; color: #334155; font-size: 0.85rem; }
    
    .select-sm-custom { font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; border: 1px solid #cbd5e1; font-weight: 600; width: 100%; }
    .select-sm-custom:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,0.1); }
    
    .field-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
    .custom-input { background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 15px; font-size: 0.95rem; color: #334155; transition: all 0.2s ease-in-out; width: 100%; }
    .custom-input:focus { background-color: #ffffff; border-color: var(--primary-color, #2563eb); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); outline: none; }

    /* Custom Tabs */
    .custom-tabs .nav-link { color: #64748b; font-weight: 600; border: none; border-bottom: 3px solid transparent; padding: 12px 25px; transition: all 0.2s; border-radius: 0; background: white;}
    .custom-tabs .nav-link:hover { color: #3b82f6; border-bottom-color: #bfdbfe; }
    .custom-tabs .nav-link.active { color: #1e40af; border-bottom-color: #1e40af; background: #f8fafc; }

    /* Sortable Styles */
    .sortable-item { background: white; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 15px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    .sortable-handle { background: #f1f5f9; padding: 12px 15px; cursor: grab; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; }
    .sortable-handle:active { cursor: grabbing; }
    .sortable-ghost { opacity: 0.4; border: 2px dashed #3b82f6; }

    /* Toast Alert Notification */
    #ajaxToast { position: fixed; bottom: 20px; right: 20px; z-index: 1055; display: none; min-width: 250px; }
</style>
@endpush

@section('content')

<!-- Script library SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-cogs header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-link me-2 text-warning"></i> Mapping Laporan Terima Keluar</h4>
        <p class="mb-0 text-white-50 small">Atur atribut TNKB, TCKB, dan Judul SBST pada masing-masing materiil.</p>
    </div>
    <div class="header-content d-flex gap-2">
        <a href="{{ route('report.inout.index') }}" class="btn btn-light fw-bold text-dark shadow-sm px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Laporan
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-2" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- ============================================== -->
<!-- AREA STACKED: SETTINGS PENANDATANGAN           -->
<!-- ============================================== -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
        <h6 class="fw-bold text-dark"><i class="fa-solid fa-signature me-2 text-primary"></i> Pengaturan Pejabat Penandatangan</h6>
    </div>
    <div class="card-body px-4 pb-4 pt-3">
        <form action="{{ route('report.inout.settings.signature') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="field-label">Jabatan (Header)</label>
                    <input type="text" name="Jabatan_tnkb_ttd" class="form-control custom-input" value="{{ $signatureSettings['Jabatan_tnkb_ttd'] ?? '' }}" placeholder="Cth: KASI FASMAT SBST">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="field-label">Nama Lengkap & Gelar</label>
                    <input type="text" name="Nama_tnkb_ttd" class="form-control custom-input" value="{{ $signatureSettings['Nama_tnkb_ttd'] ?? '' }}" placeholder="Cth: MUNAWARRAH, S.H., S.I.K., M.H.">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="field-label">Pangkat / NRP</label>
                    <input type="text" name="pangkatnrp_tnkb_ttd" class="form-control custom-input" value="{{ $signatureSettings['pangkatnrp_tnkb_ttd'] ?? '' }}" placeholder="Cth: KOMPOL NRP 88031152">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-2">
                <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm" style="border-radius: 6px;">
                    <i class="fa-solid fa-save me-2"></i> Simpan Penandatangan
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ============================================== -->
<!-- AREA TABBED: MAPPING DATA (Satu Form Besar)    -->
<!-- ============================================== -->
<div class="card border-0 shadow-sm rounded-3">
    
    <!-- Header Tab & Filter -->
    <div class="card-header bg-white border-bottom-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <ul class="nav nav-tabs custom-tabs" id="mappingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-tnkb-tab" data-bs-toggle="tab" data-bs-target="#tab-tnkb" type="button" role="tab">
                    <i class="fa-solid fa-car-side me-2"></i> Konfigurasi TNKB & TCKB
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-sbst-tab" data-bs-toggle="tab" data-bs-target="#tab-sbst" type="button" role="tab">
                    <i class="fa-solid fa-id-card me-2"></i> Konfigurasi SBST
                </button>
            </li>
        </ul>
        
        <div class="d-flex align-items-center mb-2">
            <form method="GET" action="{{ route('report.inout.settings') }}" class="d-flex align-items-center gap-2">
                <label class="fw-bold text-secondary mb-0" style="font-size: 0.85rem;">Filter Kategori:</label>
                <select name="category_id" class="form-select form-select-sm border-secondary" onchange="this.form.submit()" style="min-width: 200px;">
                    <option value="">-- Semua Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ strtoupper($cat->name) }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- FORM UTAMA BUNGKUS KEDUA TAB -->
    <form action="{{ route('report.inout.settings.update') }}" method="POST">
        @csrf
        <div class="card-body p-4 bg-light">
            <div class="tab-content" id="mappingTabsContent">
                
                <!-- ================== TAB 1: TNKB ================== -->
                <div class="tab-pane fade show active" id="tab-tnkb" role="tabpanel">
                    <div class="table-responsive shadow-sm" style="border-radius: 8px; background: white;">
                        <table class="table-mapping">
                            <thead>
                                <tr>
                                    <th class="text-start" width="40%">Nama Barang / Materiil</th>
                                    <th width="20%">Jenis Laporan</th>
                                    <th width="20%">Kelompok</th>
                                    <th width="20%">Listrik / EV</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($structuredData as $categoryName => $catData)
                                    <tr style="background-color: #f1f5f9;">
                                        <td colspan="4" class="text-start fw-bold text-primary text-uppercase border-bottom border-primary border-opacity-25" style="padding: 12px 15px;">
                                            <i class="fa-solid fa-layer-group me-2 opacity-75"></i> KATEGORI: {{ $categoryName }}
                                        </td>
                                    </tr>

                                    @foreach($catData['items'] as $row)
                                        @php $material = $row['item']; $isChild = $row['is_child']; @endphp
                                        <tr>
                                            <td class="text-start {{ $isChild ? 'ps-5' : 'ps-3' }}">
                                                @if($isChild)
                                                    <div class="text-dark">
                                                        <i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-2 opacity-50"></i> {{ $material->name }}
                                                    </div>
                                                @else
                                                    <div class="fw-bold text-dark">
                                                        <i class="fa-regular fa-folder-open text-warning me-2"></i> {{ $material->name }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center border-start">
                                                <select name="mappings[{{ $material->id }}][tnkb_rpt]" class="select-sm-custom {{ $material->tnkb_rpt > 0 ? 'bg-primary bg-opacity-10 text-primary border-primary' : 'bg-white text-secondary' }}">
                                                    <option value="0" {{ $material->tnkb_rpt == 0 || is_null($material->tnkb_rpt) ? 'selected' : '' }}>0 - Skip / Abaikan</option>
                                                    <option value="1" {{ $material->tnkb_rpt == 1 ? 'selected' : '' }}>1 - Laporan TNKB</option>
                                                    <option value="2" {{ $material->tnkb_rpt == 2 ? 'selected' : '' }}>2 - Laporan TCKB</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <select name="mappings[{{ $material->id }}][tnkb_r]" class="select-sm-custom">
                                                    <option value="">-- Kosong --</option>
                                                    <option value="R2" {{ $material->tnkb_r == 'R2' ? 'selected' : '' }}>R2 / R3</option>
                                                    <option value="R4" {{ $material->tnkb_r == 'R4' ? 'selected' : '' }}>R4 / Lebih</option>
                                                </select>
                                            </td>
                                            <td class="text-center border-end">
                                                <select name="mappings[{{ $material->id }}][tnkb_ev]" class="select-sm-custom">
                                                    <option value="0" {{ $material->tnkb_ev == 0 ? 'selected' : '' }}>0 - Non Listrik</option>
                                                    <option value="1" {{ $material->tnkb_ev == 1 ? 'selected' : '' }}>1 - Listrik (EV)</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-folder-open fs-2 mb-3 opacity-25 d-block"></i> Tidak ada materiil.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================== TAB 2: SBST (DRAG & DROP) ================== -->
                <div class="tab-pane fade" id="tab-sbst" role="tabpanel">
                    <div class="alert alert-info border-0 shadow-sm small fw-bold">
                        <i class="fa-solid fa-info-circle me-2"></i> Tips: Anda dapat menarik (drag) dan melepas (drop) baris <strong>Kategori</strong> ke atas atau ke bawah untuk mengatur urutan tabel laporan SBST. Urutan akan tersimpan secara otomatis.
                    </div>
                    
                    <div id="sbst-sortable-list">
                        @forelse($structuredData as $categoryName => $catData)
                            <!-- Container individual kategori -->
                            <div class="sortable-item" data-id="{{ $catData['cat_id'] }}">
                                <!-- Area Handle (Gagang Tarikan) -->
                                <div class="sortable-handle fw-bold text-success text-uppercase">
                                    <i class="fa-solid fa-grip-vertical text-muted me-3 fs-5"></i> 
                                    <i class="fa-solid fa-layer-group me-2"></i> KATEGORI: {{ $categoryName }}
                                </div>
                                
                                <!-- Isi Material (Tabel) -->
                                <table class="table-mapping border-0">
                                    <thead>
                                        <tr>
                                            <th class="text-start" width="50%">Nama Barang / Materiil</th>
                                            <th class="text-start bg-success bg-opacity-10 text-success" width="50%">Input Judul Laporan SBST</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($catData['items'] as $row)
                                            @php $material = $row['item']; $isChild = $row['is_child']; @endphp
                                            <tr>
                                                <td class="text-start {{ $isChild ? 'ps-5' : 'ps-3' }}">
                                                    @if($isChild)
                                                        <div class="text-dark">
                                                            <i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-2 opacity-50"></i> {{ $material->name }}
                                                        </div>
                                                    @else
                                                        <div class="fw-bold text-dark">
                                                            <i class="fa-regular fa-folder-open text-warning me-2"></i> {{ $material->name }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="text-center bg-success bg-opacity-10">
                                                    <input type="text" name="mappings[{{ $material->id }}][sbst_judul]" class="form-control" style="font-size: 0.8rem; padding: 4px 8px; border-color: #cbd5e1;" value="{{ $material->sbst_judul ?? '' }}" placeholder="Kosongkan jika skip">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @empty
                            <div class="text-center py-5 text-muted bg-white shadow-sm rounded-3">
                                <i class="fa-solid fa-folder-open fs-2 mb-3 opacity-25 d-block"></i> Tidak ada materiil.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- Tombol Simpan (Berlaku untuk seluruh tab) -->
            @if(count($structuredData) > 0)
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                    <i class="fa-solid fa-save me-2"></i> Simpan Semua Perubahan
                </button>
            </div>
            @endif

        </div>
    </form>
</div>

<!-- ============================================== -->
<!-- TOAST NOTIFIKASI AJAX AUTO-SAVE                -->
<!-- ============================================== -->
<div id="ajaxToast" class="toast align-items-center text-white bg-success border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
        <div class="toast-body fw-bold">
            <i class="fa-solid fa-check-circle me-2"></i> <span id="toastMessage">Urutan Tersimpan!</span>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="document.getElementById('ajaxToast').style.display='none'"></button>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var el = document.getElementById('sbst-sortable-list');
        
        // Inisialisasi SortableJS
        if(el) {
            var sortable = Sortable.create(el, {
                handle: '.sortable-handle', // Hanya bisa ditarik lewat area gagang
                animation: 150, // Animasi pergerakan mulus (ms)
                ghostClass: 'sortable-ghost', // Class CSS saat item sedang melayang ditarik
                
                // Trigger Event saat selesai ditarik (drop)
                onEnd: function (evt) {
                    var items = el.querySelectorAll('.sortable-item');
                    var orderedIds = [];
                    
                    // Kumpulkan urutan ID kategori yang baru
                    items.forEach(function(item) {
                        orderedIds.push(item.getAttribute('data-id'));
                    });

                    // Eksekusi AJAX Auto-Save
                    fetch("{{ route('report.inout.settings.reorder') }}", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ order: orderedIds })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            // Tampilkan notifikasi toast hijau sukses
                            var toast = document.getElementById('ajaxToast');
                            toast.style.display = 'block';
                            setTimeout(function(){ toast.style.display = 'none'; }, 3000); // Hilang dalam 3 detik
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("Gagal menyimpan urutan. Silakan refresh halaman.");
                    });
                }
            });
        }
    });
</script>
@endpush

@endsection
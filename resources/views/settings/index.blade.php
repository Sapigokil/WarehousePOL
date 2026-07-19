@extends('layouts.app')
@section('title', 'Pengaturan Global Aplikasi')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 25px; position: relative; overflow: hidden; background: linear-gradient(135deg, #1e293b, #0f172a); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.10; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }

    .setting-card { background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); height: 100%; }
    .setting-card-header { background-color: #f8fafc; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; border-radius: 10px 10px 0 0; font-weight: 700; color: #334155; display: flex; align-items: center; gap: 10px; }
    .setting-card-body { padding: 25px 20px; }

    .field-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
    .custom-input { background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 15px; font-size: 0.95rem; color: #334155; transition: all 0.2s ease-in-out; width: 100%; }
    .custom-input:focus { background-color: #ffffff; border-color: var(--primary-color, #2563eb); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); outline: none; }
    
    .radio-card-wrapper { display: flex; gap: 10px; }
    
    /* Tambahan agar opsi mode barang masuk bersusun ke bawah */
    .radio-card-vertical { flex-direction: column; }
    .radio-card-vertical .radio-card label { display: flex; align-items: center; text-align: left; padding: 12px 15px; }
    .radio-card-vertical .radio-card .icon { margin-bottom: 0; margin-right: 15px; font-size: 1.5rem; }
    
    .radio-card { flex: 1; position: relative; }
    .radio-card input[type="radio"] { position: absolute; opacity: 0; }
    .radio-card label { display: block; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s; background: #fff; text-align: center; color: #64748b; height: 100%; }
    .radio-card input[type="radio"]:checked + label { border-color: var(--primary-color, #2563eb); background-color: #eff6ff; color: #1e40af; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1); }
    .radio-card .icon { font-size: 1.3rem; margin-bottom: 8px; display: block; }
    .radio-card .title { font-weight: 700; font-size: 0.85rem; display: block; margin-bottom: 2px; color: inherit; }
    .radio-card .desc { font-size: 0.7rem; color: #94a3b8; line-height: 1.3; margin: 0; }
    .radio-card input[type="radio"]:checked + label .desc { color: #60a5fa; }
    
    .info-box { background-color: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 15px; display: flex; gap: 15px; align-items: flex-start; }
    .info-box-icon { font-size: 1.5rem; color: #94a3b8; }
    .info-box-text h6 { margin: 0 0 5px 0; font-weight: 700; color: #475569; font-size: 0.9rem; }
    .info-box-text p { margin: 0; font-size: 0.8rem; color: #64748b; line-height: 1.4; }
</style>
@endpush

@section('content')
<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-gears header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-sliders me-2 text-info"></i> Pengaturan Global</h4>
        <p class="mb-0 text-white-50 small">Kelola preferensi aplikasi, keamanan akses, dan konfigurasi dokumen.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-3" role="alert">
        <i class="fa-solid fa-circle-check me-2 fs-5 align-middle"></i> <span class="align-middle">{{ session('success') }}</span>
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger shadow-sm border-0 py-2 d-flex align-items-center" role="alert">
        <i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i>
        <div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    </div>
@endif

<form action="{{ route('settings.update') }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="row g-4">
        
        <!-- KOLOM 1: Preferensi Sistem -->
        <div class="col-lg-4">
            <div class="setting-card">
                <div class="setting-card-header">
                    <i class="fa-solid fa-desktop text-primary fs-5"></i> Preferensi Sistem
                </div>
                <div class="setting-card-body">
                    
                    <div class="mb-4">
                        <label class="field-label">Tema Warna Aplikasi</label>
                        <div class="input-group shadow-sm" style="border-radius: 6px; overflow:hidden;">
                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-palette text-muted"></i></span>
                            <select name="app_theme" id="theme-selector" class="form-select custom-input border-0" required>
                                <option value="light-blue" {{ $data['app_theme'] == 'light-blue' ? 'selected' : '' }}>Light Blue (Terang & Bersih)</option>
                                <option value="ocean-blue" {{ $data['app_theme'] == 'ocean-blue' ? 'selected' : '' }}>Ocean Blue (Biru Laut)</option>
                                <option value="modern-blue" {{ $data['app_theme'] == 'modern-blue' ? 'selected' : '' }}>Modern Blue (Elegan)</option>
                                <option value="navy-blue" {{ $data['app_theme'] == 'navy-blue' ? 'selected' : '' }}>Navy Blue (Gelap & Profesional)</option>
                            </select>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <div class="mb-2">
                        <label class="field-label">Sistem Kedatangan Barang Masuk</label>
                        <div class="radio-card-wrapper radio-card-vertical">
                            
                            <div class="radio-card">
                                <input type="radio" name="inbound_mode" id="mode_1" value="mode-1" {{ $data['inbound_mode'] == 'mode-1' ? 'checked' : '' }}>
                                <label for="mode_1">
                                    <i class="fa-solid fa-box icon"></i>
                                    <div>
                                        <span class="title">Mode 1: Langsung (Tetap)</span>
                                        <p class="desc">1 Tahap. Opsi parsial form disembunyikan.</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="radio-card">
                                <input type="radio" name="inbound_mode" id="mode_2" value="mode-2" {{ $data['inbound_mode'] == 'mode-2' ? 'checked' : '' }}>
                                <label for="mode_2">
                                    <i class="fa-solid fa-boxes-stacked icon"></i>
                                    <div>
                                        <span class="title">Mode 2: Parsial (Tetap)</span>
                                        <p class="desc">Bertahap. Opsi parsial form disembunyikan.</p>
                                    </div>
                                </label>
                            </div>

                            <div class="radio-card">
                                <input type="radio" name="inbound_mode" id="mode_3" value="mode-3" {{ $data['inbound_mode'] == 'mode-3' ? 'checked' : '' }}>
                                <label for="mode_3">
                                    <i class="fa-solid fa-hand-pointer icon"></i>
                                    <div>
                                        <span class="title">Mode 3: Pilih Manual</span>
                                        <p class="desc">User dapat memilih di form Barang Masuk.</p>
                                    </div>
                                </label>
                            </div>

                        </div>
                    </div>

                    <!-- Input Max Batch (Akan di-hide/show oleh JavaScript) -->
                    <div class="mt-4" id="max_batch_container" style="display: none;">
                        <label class="field-label">Batas Maksimal Batch (Parsial)</label>
                        <div class="input-group shadow-sm" style="border-radius: 6px; overflow:hidden;">
                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-layer-group text-muted"></i></span>
                            <input type="number" name="max_batch" class="form-control custom-input border-0" value="{{ old('max_batch', $data['max_batch']) }}" min="2" placeholder="Contoh: 5">
                        </div>
                        <small class="text-muted" style="font-size: 0.7rem;">Maksimal pemecahan tahap penerimaan barang untuk 1 SPPM.</small>
                    </div>

                </div>
            </div>
        </div>

        <!-- KOLOM 2: Keamanan & Sesi Akses -->
        <div class="col-lg-4">
            <div class="setting-card">
                <div class="setting-card-header">
                    <i class="fa-solid fa-shield-halved text-success fs-5"></i> Keamanan & Sesi
                </div>
                <div class="setting-card-body">
                    <div class="mb-4">
                        <label class="field-label">Izinkan Double Login (Multi Device)</label>
                        <div class="radio-card-wrapper">
                            <div class="radio-card">
                                <input type="radio" name="allow_double_login" id="dl_1" value="1" {{ $data['allow_double_login'] == '1' ? 'checked' : '' }}>
                                <label for="dl_1">
                                    <i class="fa-solid fa-check-double icon text-success"></i>
                                    <span class="title">Diizinkan</span>
                                    <span class="desc">Bisa login di HP & PC bersamaan.</span>
                                </label>
                            </div>
                            <div class="radio-card">
                                <input type="radio" name="allow_double_login" id="dl_0" value="0" {{ $data['allow_double_login'] == '0' ? 'checked' : '' }}>
                                <label for="dl_0">
                                    <i class="fa-solid fa-ban icon text-danger"></i>
                                    <span class="title">Diblokir</span>
                                    <span class="desc">Akun lama ter-logout otomatis.</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 my-4">

                    <div class="mb-3">
                        <label class="field-label">Waktu Auto Logout (Idle Timeout)</label>
                        <div class="input-group shadow-sm" style="border-radius: 6px; overflow:hidden;">
                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-clock-rotate-left text-muted"></i></span>
                            <input type="number" name="login_timeout" class="form-control custom-input border-0 text-center fw-bold" value="{{ old('login_timeout', $data['login_timeout']) }}" min="1" required>
                            <span class="input-group-text bg-light border-0 text-muted fw-bold">Menit</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.7rem;">Sistem akan me-logout otomatis jika tidak ada aktivitas halaman melebihi waktu ini.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM 3: Pengaturan Cetak -->
        <div class="col-lg-4">
            <div class="setting-card">
                <div class="setting-card-header">
                    <i class="fa-solid fa-file-signature text-danger fs-5"></i> Penandatangan Surat
                </div>
                <div class="setting-card-body">
                    <div class="info-box mb-4">
                        <i class="fa-solid fa-circle-info info-box-icon"></i>
                        <div class="info-box-text">
                            <p>Data penandatangan otomatis muncul pada Footer dokumen cetak.</p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="field-label">Nama Pejabat</label>
                        <div class="input-group shadow-sm" style="border-radius: 6px; overflow:hidden;">
                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-user-tie text-muted"></i></span>
                            <input type="text" name="signatory_name" class="form-control custom-input border-0" value="{{ old('signatory_name', $data['signatory_name']) }}" placeholder="Cth: ENDRO SUSILO" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="field-label">Pangkat / NRP</label>
                        <div class="input-group shadow-sm" style="border-radius: 6px; overflow:hidden;">
                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-id-badge text-muted"></i></span>
                            <input type="text" name="signatory_nrp" class="form-control custom-input border-0" value="{{ old('signatory_nrp', $data['signatory_nrp']) }}" placeholder="Cth: BRIPKA / 86041391">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="field-label">Nama Jabatan</label>
                        <div class="input-group shadow-sm" style="border-radius: 6px; overflow:hidden;">
                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-sitemap text-muted"></i></span>
                            <input type="text" name="signatory_position" class="form-control custom-input border-0" value="{{ old('signatory_position', $data['signatory_position']) }}" placeholder="Cth: DIREKTUR" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tombol Aksi Simpan -->
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary fw-bold px-5 py-2 shadow-sm" style="border-radius: 8px;">
                <i class="fa-solid fa-save me-2"></i> SIMPAN PENGATURAN
            </button>
        </div>

    </div>
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Logika Live Preview Tema
        const themeSelector = document.getElementById('theme-selector');
        if (themeSelector) {
            themeSelector.addEventListener('change', function() {
                const newTheme = this.value;
                document.body.className = document.body.className.replace(/\btheme-[a-zA-Z0-9-]+\b/g, '');
                document.body.classList.add('theme-' + newTheme);
            });
        }

        // Logika Toggle Visibilitas Input Max Batch
        const inboundRadios = document.querySelectorAll('input[name="inbound_mode"]');
        const maxBatchContainer = document.getElementById('max_batch_container');

        function toggleMaxBatch() {
            const selectedMode = document.querySelector('input[name="inbound_mode"]:checked').value;
            if (selectedMode === 'mode-2' || selectedMode === 'mode-3') {
                maxBatchContainer.style.display = 'block';
                // Jika tidak ada isian, kembalikan ke default 5
                const input = maxBatchContainer.querySelector('input');
                if(!input.value) input.value = 5;
            } else {
                maxBatchContainer.style.display = 'none';
            }
        }

        inboundRadios.forEach(radio => {
            radio.addEventListener('change', toggleMaxBatch);
        });

        // Jalankan saat pertama kali halaman dimuat
        toggleMaxBatch();
    });
</script>
@endpush
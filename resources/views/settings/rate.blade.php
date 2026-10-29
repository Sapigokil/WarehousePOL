@extends('layouts.app')
@section('title', 'Pengaturan Rate Usage & Daya Tahan Stok')

@push('styles')
<style>
    .form-card { background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; }
    .table-rate { width: 100%; border-collapse: collapse; }
    .table-rate th { background-color: #f8fafc; color: #475569; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
    .table-rate td { padding: 8px 12px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    .input-rate { max-width: 110px; font-weight: 600; text-align: center; }
    .modal-scrollable { max-height: 400px; overflow-y: auto; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-chart-line text-theme me-2"></i> Pengaturan Rate Usage & Estimasi Daya Tahan Stok
        </h5>
        <small class="text-muted">Kelola tingkat penggunaan bulanan untuk memprediksi ketahanan stok gudang.</small>
    </div>
    <div class="d-flex gap-2">
        @if(!$isAutoGenerate)
            <a href="{{ route('settings.rate.index', array_merge(request()->all(), ['auto_generate' => 1])) }}" class="btn btn-sm btn-outline-primary fw-bold shadow-sm">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto Generate (1 Tahun)
            </a>
        @else
            <a href="{{ route('settings.rate.index', request()->except('auto_generate')) }}" class="btn btn-sm btn-outline-secondary fw-bold shadow-sm">
                <i class="fa-solid fa-xmark me-1"></i> Tutup Auto Generate
            </a>
        @endif

        <button type="button" class="btn btn-sm btn-theme fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahMaterial">
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

<div class="form-card shadow-sm p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <form action="{{ route('settings.rate.index') }}" method="GET" class="d-flex gap-2 col-12 col-md-4">
            @if($isAutoGenerate)
                <input type="hidden" name="auto_generate" value="1">
            @endif
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Cari material..." value="{{ $search ?? '' }}">
                <button type="submit" class="btn btn-light border"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>
    </div>

    <form action="{{ route('settings.rate.update_bulk') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="table-responsive">
            <table class="table-rate">
                <thead>
                    <tr>
                        <th width="4%" class="text-center">No</th>
                        <th width="30%">Nama Barang / Materiel</th>
                        <th width="12%">Kategori</th>
                        <th width="12%" class="text-center">Stok Gudang</th>
                        <th width="15%" class="text-center">Rate Usage / Bln</th>
                        
                        {{-- KOLOM AUTO GENERATE (DITAMPILKAN SAAT MODE AUTO GENERATE AKTIF) --}}
                        @if($isAutoGenerate)
                            <th width="15%" class="text-center bg-info bg-opacity-10 text-primary border-bottom border-primary">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto Generate
                            </th>
                        @endif

                        <th width="12%" class="text-center">Daya Tahan</th>
                        <th width="6%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rateMaterials as $index => $material)
                        @php
                            $stok = $material->current_stock ?? 0;
                            $rate = $material->rate_usage ?? 0;
                            
                            $dayaTahanText = '-';
                            $badgeClass = 'bg-secondary';
                            
                            if ($stok <= 0) {
                                $dayaTahanText = 'Stok Habis';
                                $badgeClass = 'bg-danger';
                            } elseif ($rate <= 0) {
                                $dayaTahanText = 'Tanpa Estimasi';
                                $badgeClass = 'bg-light text-muted border';
                            } else {
                                $months = round($stok / $rate, 1);
                                if ($months < 2) {
                                    $badgeClass = 'bg-danger';
                                } elseif ($months <= 5) {
                                    $badgeClass = 'bg-warning text-dark';
                                } else {
                                    $badgeClass = 'bg-success';
                                }
                                $dayaTahanText = $months . ' Bulan';
                            }
                        @endphp
                        <tr>
                            <td class="text-center fw-bold text-muted" style="font-size: 0.85rem;">{{ $index + 1 }}</td>
                            <td>
                                <span class="fw-bold text-dark d-block" style="font-size: 0.85rem;">{{ $material->name }}</span>
                                @if($material->parent)
                                    <small class="text-muted"><i class="fa-solid fa-folder me-1"></i>Induk: {{ $material->parent->name }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border px-2 py-0.5" style="font-size: 0.75rem;">
                                    {{ $material->category->name ?? '-' }}
                                </span>
                            </td>
                            <td class="text-center fw-bold text-primary">
                                {{ number_format($stok, 0, ',', '.') }} {{ $material->satuan }}
                            </td>
                            <td class="text-center">
                                <div class="input-group input-group-sm mx-auto input-rate">
                                    <input type="number" name="rates[{{ $material->id }}]" class="form-control text-center fw-bold text-dark" value="{{ $rate }}" min="0" placeholder="0">
                                    <span class="input-group-text bg-light text-muted px-1" style="font-size: 0.7rem;">/bln</span>
                                </div>
                            </td>

                            {{-- VALUE KOLOM AUTO GENERATE DENGAN TOMBOL X (BATAL) --}}
                            @if($isAutoGenerate)
                                <td class="text-center bg-info bg-opacity-10">
                                    <div class="d-flex align-items-center justify-content-center gap-1 auto-gen-container">
                                        <input type="hidden" name="auto_rates[{{ $material->id }}]" class="auto-rate-val" value="{{ $material->auto_rate }}">
                                        <span class="badge bg-primary px-2 py-1 auto-rate-badge" style="font-size: 0.75rem;">
                                            {{ number_format($material->auto_rate, 0, ',', '.') }} / bln
                                        </span>
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1 btn-cancel-auto" onclick="cancelAutoRate(this)" title="Batalkan Auto Generate baris ini">
                                            <i class="fa-solid fa-circle-xmark fs-6"></i>
                                        </button>
                                    </div>
                                </td>
                            @endif

                            <td class="text-center">
                                <span class="badge {{ $badgeClass }} px-2 py-1" style="font-size: 0.75rem;">
                                    {{ $dayaTahanText }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-light text-danger border shadow-none px-2 py-0.5" onclick="if(confirm('Keluarkan material ini dari daftar pemantauan Rate Usage?')) { document.getElementById('form-delete-{{ $material->id }}').submit(); }" title="Hapus dari daftar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAutoGenerate ? 8 : 7 }}" class="text-center py-5 text-muted bg-white">
                                <i class="fa-solid fa-chart-line fs-2 mb-2 opacity-25"></i>
                                <p class="mb-0 small">Belum ada material yang dipantau. Klik tombol <strong>Tambah Material</strong> di atas untuk menambahkan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rateMaterials->count() > 0)
            <div class="d-flex justify-content-end mt-3 border-top pt-3">
                <button type="submit" class="btn btn-theme fw-bold px-4 shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan Rate
                </button>
            </div>
        @endif
    </form>
</div>

{{-- Hidden Form Delete --}}
@foreach($rateMaterials as $material)
    <form id="form-delete-{{ $material->id }}" action="{{ route('settings.rate.remove', $material->id) }}" method="POST" class="d-none">
        @csrf
        @method('DELETE')
    </form>
@endforeach

<!-- MODAL TAMBAH MATERIAL -->
<div class="modal fade" id="modalTambahMaterial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('settings.rate.apply_selection') }}" method="POST">
                @csrf
                <div class="modal-header bg-light border-bottom-0">
                    <h6 class="modal-title fw-bold text-dark">
                        <i class="fa-solid fa-list-check text-theme me-2"></i> Pilih Material Pemantauan Rate Usage
                    </h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Centang material yang ingin dipantau tingkat penggunaannya. Material yang dicentang akan diberi nilai default <code>0</code>, sedangkan yang didecentang akan dikosongkan (<code>NULL</code>).
                    </p>
                    
                    <div class="mb-3">
                        <input type="text" id="filterModalMaterial" class="form-control form-control-sm" placeholder="Cari nama material atau kategori...">
                    </div>

                    <div class="border rounded p-3 modal-scrollable bg-light" id="modalMaterialList">
                        @forelse($allMaterials->groupBy(fn($item) => $item->category->name ?? 'Tanpa Kategori') as $categoryName => $materials)
                            <div class="category-group-block mb-3">
                                {{-- Header Kategori --}}
                                <div class="fw-bold text-theme mb-2 pb-1 border-bottom border-primary border-opacity-25 d-flex align-items-center justify-content-between" style="font-size: 0.85rem;">
                                    <span><i class="fa-solid fa-folder-open me-2"></i>{{ $categoryName }}</span>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border fw-normal" style="font-size: 0.7rem;">{{ $materials->count() }} Material</span>
                                </div>

                                {{-- Daftar Material 1 Kolom --}}
                                @foreach($materials as $mat)
                                    @php $isChecked = !is_null($mat->rate_usage); @endphp
                                    <div class="material-item-row mb-2" data-name="{{ strtolower($mat->name) }}" data-category="{{ strtolower($categoryName) }}">
                                        <div class="form-check bg-white border rounded p-2 ps-4 d-flex align-items-center">
                                            <input class="form-check-input me-2" type="checkbox" name="selected_materials[]" value="{{ $mat->id }}" id="chk_mat_{{ $mat->id }}" {{ $isChecked ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="chk_mat_{{ $mat->id }}" style="cursor: pointer; font-size: 0.85rem;">
                                                <strong class="text-dark d-block">{{ $mat->name }}</strong>
                                                <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                    Satuan: {{ $mat->satuan }}
                                                </small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <span class="small">Tidak ada data material yang ditemukan.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-sm btn-light border fw-bold px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-theme fw-bold px-4">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // FUNGSI BATALKAN AUTO GENERATE PER BARIS (UBAH JADI NULL)
    function cancelAutoRate(btn) {
        const container = btn.closest('.auto-gen-container');
        if (container) {
            const hiddenInput = container.querySelector('.auto-rate-val');
            if (hiddenInput) {
                hiddenInput.value = ''; // Kosongkan agar backend mengabaikan replace
            }
            container.innerHTML = '<span class="text-muted" style="font-size: 0.75rem;">(Batal)</span>';
        }
    }

    // Live Search Filter di Dalam Modal (Support Grouping Kategori)
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

            // Sembunyikan header kategori jika seluruh material di dalamnya tidak cocok dengan kata kunci
            if (hasVisible) {
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        });
    });
</script>
@endpush
@extends('layouts.app')
@section('title', 'Penyesuaian Laporan')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; background: linear-gradient(135deg, #475569, #1e293b); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.1; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }
    .form-label { font-weight: bold; font-size: 0.85rem; color: #475569; }
</style>
@endpush

@section('content')

<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-sliders header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-wrench me-2"></i> Penyesuaian Angka Laporan</h4>
        <p class="mb-0 text-white-50 small">Modifikasi angka laporan tanpa mengubah integritas stok fisik di gudang.</p>
    </div>
    <div class="header-content d-flex gap-2">
        <button type="button" onclick="window.close()" class="btn btn-light fw-bold shadow-sm px-4 py-2" style="border-radius: 8px;">
            <i class="fa-solid fa-xmark me-1"></i> Tutup Tab
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm py-3 fw-bold" role="alert">
        <i class="fa-solid fa-circle-check me-2 fs-5"></i>{{ session('success') }}
        <button type="button" class="btn-close pb-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- KOLOM KIRI: FORM INPUT YANG SEKALIGUS MENJADI FILTER -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom pt-3 pb-2 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-primary mb-0"><i class="fa-solid fa-plus me-2"></i>Penyesuaian Baru</h6>
                @if($filterTab || $filterMonth || $filterBucketKey)
                    <a href="{{ route('report.adjustments.index', ['year' => $year]) }}" class="badge bg-danger text-decoration-none">
                        <i class="fa-solid fa-xmark me-1"></i> Hapus Filter
                    </a>
                @endif
            </div>
            <div class="card-body p-4 bg-light">
                <form action="{{ route('report.adjustments.store') }}" method="POST" id="createForm">
                    @csrf
                    <input type="hidden" name="year" id="current_year" value="{{ $year }}">

                    <div class="mb-3">
                        <label class="form-label">Tipe Modul Laporan</label>
                        <select name="tab_type" id="tab_type" class="form-select border-secondary" required onchange="toggleTargets('create'); applyFilter();">
                            <option value="">-- Pilih Tipe --</option>
                            <option value="tnkb" {{ $filterTab == 'tnkb' ? 'selected' : '' }}>Tab 1: TNKB & TCKB</option>
                            <option value="sbst" {{ $filterTab == 'sbst' ? 'selected' : '' }}>Tab 2: SBST</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bulan Target</label>
                        <select name="month" id="month" class="form-select border-secondary" required onchange="applyFilter()">
                            <option value="">-- Pilih Bulan --</option>
                            @foreach($monthsName as $num => $name)
                                <option value="{{ $num }}" {{ $filterMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Area (Tabel / Kategori)</label>
                        
                        <!-- Pilihan TNKB -->
                        <select name="bucket_key_tnkb" id="target_tnkb" class="form-select border-secondary {{ $filterTab == 'tnkb' ? '' : 'd-none' }}" onchange="applyFilter()">
                            <option value="">-- Pilih Kolom TNKB --</option>
                            @foreach($tnkbTargets as $key => $label)
                                <option value="{{ $key }}" {{ $filterBucketKey == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>

                        <!-- Pilihan SBST -->
                        <select name="bucket_key_sbst" id="target_sbst" class="form-select border-secondary {{ $filterTab == 'sbst' ? '' : 'd-none' }}" onchange="applyFilter()">
                            <option value="">-- Pilih Kategori SBST --</option>
                            @foreach($sbstMaterials as $sbst)
                                <option value="sbst_{{ $sbst->id }}" {{ $filterBucketKey == 'sbst_'.$sbst->id ? 'selected' : '' }}>{{ strtoupper($sbst->sbst_judul) }}</option>
                            @endforeach
                        </select>

                        <input type="hidden" name="bucket_key" id="bucket_key_final" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kolom Transaksi / Target Penyesuaian</label>
                        <select name="transaction_type" class="form-select border-secondary" required>
                            <option value="in">Kolom PENERIMAAN (IN)</option>
                            <option value="out">Kolom PENDISTRIBUSIAN (OUT)</option>
                            <option value="sisa_awal">Kolom SISA AWAL (Bulan Lalu)</option>
                            <option value="sisa_gudang">Kolom SISA GUDANG (Bulan Ini)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nilai Penyesuaian (QTY)</label>
                        <input type="number" name="qty_adjustment" class="form-control border-secondary fw-bold text-center" placeholder="Contoh: 500 atau -500" required>
                        <small class="text-muted">Gunakan minus (-) untuk mengurangi hasil.</small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Keterangan / Alasan (Opsional)</label>
                        <input type="text" name="keterangan" class="form-control border-secondary" placeholder="Tulis alasan penyesuaian...">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm" onclick="setFinalBucketKey('create')">
                        <i class="fa-solid fa-save me-2"></i> Simpan Penyesuaian
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: DAFTAR DATA & RESET -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-bottom pt-3 pb-3 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fa-solid fa-list me-2"></i>Daftar Penyesuaian 
                        @if($filterMonth) Bulan {{ $monthsName[$filterMonth] }} @endif 
                    </h6>
                    
                    <div class="d-flex align-items-center gap-2">
                        <form method="GET" action="{{ route('report.adjustments.index') }}" class="m-0" id="yearFilterForm">
                            <!-- Pertahankan filter kiri saat user mengganti tahun -->
                            @if($filterTab) <input type="hidden" name="tab_type" value="{{ $filterTab }}"> @endif
                            @if($filterMonth) <input type="hidden" name="month" value="{{ $filterMonth }}"> @endif
                            @if($filterBucketKey) <input type="hidden" name="bucket_key" value="{{ $filterBucketKey }}"> @endif
                            
                            <select name="year" class="form-select form-select-sm border-secondary fw-bold" onchange="document.getElementById('yearFilterForm').submit()" style="width: 100px;">
                                @foreach($years as $yr)
                                    <option value="{{ $yr }}" {{ $year == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small">
                            <tr>
                                <th class="ps-4">BULAN</th>
                                <th>TAB MODUL</th>
                                <th>TARGET KOLOM</th>
                                <th>TRANSAKSI / TARGET</th>
                                <th class="text-center">NILAI (+/-)</th>
                                <th>KETERANGAN</th>
                                <th class="pe-4 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($adjustments as $adj)
                                <tr>
                                    <td class="ps-4 fw-bold">{{ $monthsName[$adj->month] }}</td>
                                    <td>
                                        @if($adj->tab_type == 'tnkb')
                                            <span class="badge bg-primary">TNKB/TCKB</span>
                                        @else
                                            <span class="badge bg-success">SBST</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold text-dark">
                                        @if($adj->tab_type == 'tnkb')
                                            {{ $tnkbTargets[$adj->bucket_key] ?? $adj->bucket_key }}
                                        @else
                                            @php
                                                $sbstId = str_replace('sbst_', '', $adj->bucket_key);
                                                $sbstName = $sbstMaterials->where('id', $sbstId)->first()->sbst_judul ?? 'SBST (Terhapus)';
                                            @endphp
                                            {{ strtoupper($sbstName) }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($adj->transaction_type == 'in')
                                            <span class="text-success fw-bold"><i class="fa-solid fa-arrow-down me-1"></i>PENERIMAAN</span>
                                        @elseif($adj->transaction_type == 'out')
                                            <span class="text-danger fw-bold"><i class="fa-solid fa-arrow-up me-1"></i>PENDISTRIBUSIAN</span>
                                        @elseif($adj->transaction_type == 'sisa_awal')
                                            <span class="text-primary fw-bold"><i class="fa-solid fa-hourglass-start me-1"></i>SISA AWAL</span>
                                        @elseif($adj->transaction_type == 'sisa_gudang')
                                            <span class="text-info fw-bold text-dark"><i class="fa-solid fa-hourglass-end me-1"></i>SISA GUDANG</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($adj->qty_adjustment > 0)
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success fs-6 px-2 py-1">+{{ number_format($adj->qty_adjustment, 0, ',', '.') }}</span>
                                        @else
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger fs-6 px-2 py-1">{{ number_format($adj->qty_adjustment, 0, ',', '.') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-muted small">{{ $adj->keterangan ?? '-' }}</td>
                                    <td class="pe-4 text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <!-- Tombol Edit Modal -->
                                            <button type="button" class="btn btn-sm btn-outline-warning" title="Edit Data" 
                                                onclick="openEditModal('{{ route('report.adjustments.update', $adj->id) }}', {{ $adj->month }}, '{{ $adj->tab_type }}', '{{ $adj->bucket_key }}', '{{ $adj->transaction_type }}', {{ $adj->qty_adjustment }}, '{{ $adj->keterangan }}')">
                                                <i class="fa-solid fa-edit"></i>
                                            </button>

                                            <!-- Tombol Hapus -->
                                            <form action="{{ route('report.adjustments.destroy', $adj->id) }}" method="POST" onsubmit="return confirm('Hapus data penyesuaian ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-filter-circle-xmark fs-2 mb-3 opacity-25 d-block"></i>
                                        Belum ada data penyesuaian yang sesuai dengan kriteria filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if($adjustments->count() > 0 && empty($filterTab) && empty($filterMonth) && empty($filterBucketKey))
            <div class="card-footer bg-white border-top p-4 d-flex justify-content-end">
                <form action="{{ route('report.adjustments.reset', $year) }}" method="POST" onsubmit="return confirm('PERHATIAN! Apakah Anda yakin ingin MENGHAPUS SEMUA data penyesuaian di Tahun {{ $year }}? Data yang terhapus tidak bisa dikembalikan.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger fw-bold shadow-sm">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i> Reset Semua Penyesuaian Tahun {{ $year }}
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- MODAL EDIT PENYESUAIAN                         -->
<!-- ============================================== -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold" id="editModalLabel"><i class="fa-solid fa-edit me-2"></i>Edit Penyesuaian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4 bg-light">
                    
                    <div class="mb-3">
                        <label class="form-label">Tipe Modul Laporan</label>
                        <select name="tab_type" id="edit_tab_type" class="form-select border-secondary" required onchange="toggleTargets('edit')">
                            <option value="tnkb">Tab 1: TNKB & TCKB</option>
                            <option value="sbst">Tab 2: SBST</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bulan Target</label>
                        <select name="month" id="edit_month" class="form-select border-secondary" required>
                            @foreach($monthsName as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Area (Tabel / Kategori)</label>
                        
                        <!-- Pilihan TNKB Edit -->
                        <select id="edit_target_tnkb" class="form-select border-secondary d-none">
                            <option value="">-- Pilih Kolom TNKB --</option>
                            @foreach($tnkbTargets as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <!-- Pilihan SBST Edit -->
                        <select id="edit_target_sbst" class="form-select border-secondary d-none">
                            <option value="">-- Pilih Kategori SBST --</option>
                            @foreach($sbstMaterials as $sbst)
                                <option value="sbst_{{ $sbst->id }}">{{ strtoupper($sbst->sbst_judul) }}</option>
                            @endforeach
                        </select>

                        <input type="hidden" name="bucket_key" id="edit_bucket_key_final" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kolom Transaksi / Target Penyesuaian</label>
                        <select name="transaction_type" id="edit_transaction_type" class="form-select border-secondary" required>
                            <option value="in">Kolom PENERIMAAN (IN)</option>
                            <option value="out">Kolom PENDISTRIBUSIAN (OUT)</option>
                            <option value="sisa_awal">Kolom SISA AWAL (Bulan Lalu)</option>
                            <option value="sisa_gudang">Kolom SISA GUDANG (Bulan Ini)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nilai Penyesuaian (QTY)</label>
                        <input type="number" name="qty_adjustment" id="edit_qty_adjustment" class="form-control border-secondary fw-bold text-center" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Keterangan / Alasan (Opsional)</label>
                        <input type="text" name="keterangan" id="edit_keterangan" class="form-control border-secondary">
                    </div>

                </div>
                <div class="modal-footer border-0 bg-white">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning fw-bold" onclick="setFinalBucketKey('edit')">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // MENGATUR TAMPILAN AREA TARGET (Create & Edit)
    function toggleTargets(mode) {
        var prefix = mode === 'edit' ? 'edit_' : '';
        var type = document.getElementById(prefix + 'tab_type').value;
        var tnkb = document.getElementById(prefix + 'target_tnkb');
        var sbst = document.getElementById(prefix + 'target_sbst');

        if (type === 'tnkb') {
            tnkb.classList.remove('d-none');
            tnkb.required = true;
            sbst.classList.add('d-none');
            sbst.required = false;
        } else if (type === 'sbst') {
            sbst.classList.remove('d-none');
            sbst.required = true;
            tnkb.classList.add('d-none');
            tnkb.required = false;
        } else {
            tnkb.classList.add('d-none');
            sbst.classList.add('d-none');
        }
    }

    // MENGISI INPUT HIDDEN SAAT FORM DISUBMIT
    function setFinalBucketKey(mode) {
        var prefix = mode === 'edit' ? 'edit_' : '';
        var type = document.getElementById(prefix + 'tab_type').value;
        var finalKey = document.getElementById(prefix + 'bucket_key_final');
        
        if (type === 'tnkb') {
            finalKey.value = document.getElementById(prefix + 'target_tnkb').value;
        } else if (type === 'sbst') {
            finalKey.value = document.getElementById(prefix + 'target_sbst').value;
        }
    }

    // MENGIRIM VARIABEL KE MODAL EDIT
    function openEditModal(actionUrl, month, tab_type, bucket_key, transaction_type, qty, keterangan) {
        document.getElementById('editForm').action = actionUrl;
        
        document.getElementById('edit_month').value = month;
        document.getElementById('edit_tab_type').value = tab_type;
        document.getElementById('edit_transaction_type').value = transaction_type;
        document.getElementById('edit_qty_adjustment').value = qty;
        document.getElementById('edit_keterangan').value = keterangan;

        toggleTargets('edit');

        if (tab_type === 'tnkb') {
            document.getElementById('edit_target_tnkb').value = bucket_key;
        } else {
            document.getElementById('edit_target_sbst').value = bucket_key;
        }

        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    }

    // ========================================================
    // AUTO FILTER & RELOAD SAAT DROPDOWN FORM KIRI DIGANTI
    // ========================================================
    function applyFilter() {
        var year = document.getElementById('current_year').value;
        var tab_type = document.getElementById('tab_type').value;
        var month = document.getElementById('month').value;
        
        var bucket_key = '';
        if (tab_type === 'tnkb') {
            bucket_key = document.getElementById('target_tnkb').value;
        } else if (tab_type === 'sbst') {
            bucket_key = document.getElementById('target_sbst').value;
        }

        var url = "{{ route('report.adjustments.index') }}?year=" + year;
        
        if (tab_type) url += "&tab_type=" + tab_type;
        if (month) url += "&month=" + month;
        if (bucket_key) url += "&bucket_key=" + bucket_key;

        // Redirect instan untuk memfilter tabel kanan
        window.location.href = url;
    }
</script>
@endpush
@endsection
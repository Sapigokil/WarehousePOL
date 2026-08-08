@extends('layouts.app')
@section('title', isset($outbound) ? (isset($isReadonly) && $isReadonly ? 'Detail SPPM Keluar' : 'Kelola SPPM Keluar') : 'Input SPPM Keluar Baru')

@push('styles')
<style>
    .form-card { background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; }
    .form-header-title { font-size: 0.9rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
    .field-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: block; }
    .custom-input { background-color: #f8fafc; border: 1px solid #cbd5e1 !important; border-radius: 6px; padding: 8px 12px; font-size: 0.9rem; color: #334155; }
    .custom-input:focus { background-color: #ffffff; border-color: var(--primary-color) !important; box-shadow: none; }
    
    .table-form { width: 100%; border-collapse: collapse; }
    .table-form th { background-color: #f8fafc; color: #475569; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 10px 10px; border-bottom: 2px solid #e2e8f0; vertical-align: middle;}
    .table-form td { padding: 6px 10px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }
    
    .text-letter-span { font-size: 0.7rem; font-weight: 600; color: #4b5563; text-transform: uppercase; background-color: #f3f4f6; padding: 2px 8px; border-radius: 6px; display: flex; align-items: center; min-height: 31px; border: 1px dashed #cbd5e1; word-wrap: break-word; }
    .text-price-total { font-size: 0.8rem; font-weight: 700; color: var(--primary-color); }

    /* Efek visual transparan untuk input yang terkunci di mode Show */
    .readonly-overlay input, .readonly-overlay select { pointer-events: none; background-color: #f1f5f9; opacity: 0.8; }
</style>
@endpush

@section('content')
@php
    // Cek dengan isset untuk mencegah undefined variable error
    $isCompleted = isset($outbound) && $outbound->status === 'completed';
    $readOnlyMode = isset($isReadonly) && $isReadonly;
    // Terapkan default false jika variabel dari controller tidak dikirim
    $isLocked = $isCompleted || $readOnlyMode ? true : false;

    // BACA PENGATURAN ALLOW MINUS STOCK DARI DATABASE
    $allowMinusStock = \App\Models\Setting::where('key', 'allow_minus_stock')->value('value') == '1';

    // Helper untuk mengubah angka menjadi huruf di sisi server (PHP)
    $terbilang = function($n) use (&$terbilang) {
        $bil = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        $n = (int)$n;
        if ($n <= 0) return "-";
        if ($n < 12) return $bil[$n];
        if ($n < 20) return $terbilang($n - 10) . " belas";
        if ($n < 100) return $terbilang(floor($n / 10)) . " puluh " . ($bil[$n % 10] === "" ? "" : " " . $bil[$n % 10]);
        if ($n < 200) return "seratus " . ($n - 100 === 0 ? "" : $terbilang($n - 100));
        if ($n < 1000) return $terbilang(floor($n / 100)) . " ratus " . ($n % 100 === 0 ? "" : " " . $terbilang($n % 100));
        if ($n < 2000) return "seribu " . ($n - 1000 === 0 ? "" : $terbilang($n - 1000));
        if ($n < 1000000) return $terbilang(floor($n / 1000)) . " ribu " . ($n % 1000 === 0 ? "" : " " . $terbilang($n % 1000));
        if ($n < 1000000000) return $terbilang(floor($n / 1000000)) . " juta " . ($n % 1000000 === 0 ? "" : " " . $terbilang($n % 1000000));
        return "";
    };

    // Helper untuk memformat visual Nomor Seri (Prefix merah + titik)
    $formatSeriVisual = function($prefix, $start, $end) {
        if (is_null($start) && is_null($end)) return '-';
        $padAndDot = function($num) {
            $s = str_pad($num ?? 0, 9, '0', STR_PAD_LEFT);
            return substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3);
        };
        $s_formatted = $padAndDot($start);
        $e_formatted = $padAndDot($end);
        
        $p = $prefix ? "<span class='text-danger fw-bold'>{$prefix}.</span>" : '';
        return "{$p}<span class='fw-bold'>{$s_formatted}</span> <span class='fw-normal mx-1'>s/d</span> <span class='fw-bold'>{$e_formatted}</span>";
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-file-export text-danger me-2"></i>
            {{ isset($outbound) ? ($readOnlyMode ? 'Detail Dokumen Keluar' : 'Kelola Dokumen Keluar') : 'Registrasi SPPM Keluar' }}
            
            @if(isset($outbound))
                @if($isCompleted)
                    <span class="badge bg-success ms-2 fs-6 align-middle"><i class="fa-solid fa-check me-1"></i> FINAL</span>
                @else
                    <span class="badge bg-secondary ms-2 fs-6 align-middle"><i class="fa-solid fa-pen-ruler me-1"></i> DRAFT</span>
                @endif
                
                @if($readOnlyMode)
                    <span class="badge bg-info ms-1 fs-6 align-middle"><i class="fa-solid fa-eye me-1"></i> Read-Only</span>
                @endif
            @endif
        </h5>
        
        @if($allowMinusStock && !$isLocked)
            <small class="text-warning fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> Peringatan: Mode Transaksi Stok Minus Sedang Aktif.</small>
        @endif
    </div>
    <div class="d-flex gap-2">
        @if($isCompleted || $readOnlyMode)
            <a href="{{ route('outbounds.print', $outbound->id) }}" target="_blank" class="btn btn-sm btn-info text-white fw-bold shadow-sm px-3"><i class="fa-solid fa-print me-1"></i> Cetak SPPM</a>
        @endif
        <a href="{{ route('outbounds.index') }}" class="btn btn-sm btn-light border fw-semibold px-3"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
    </div>
</div>

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

<form action="{{ isset($outbound) && !$readOnlyMode ? route('outbounds.update', $outbound->id) : route('outbounds.store') }}" method="POST" id="formMainOutbound">
    @csrf
    @if(isset($outbound) && !$readOnlyMode) @method('PUT') @endif

    <div class="row {{ $readOnlyMode ? 'readonly-overlay' : '' }}">
        <!-- HEADER -->
        <div class="col-12 mb-3">
            <div class="form-card p-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h6 class="form-header-title m-0"><i class="fa-solid fa-folder me-1"></i> Informasi Dokumen Keluar</h6>
                </div>
                
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Nomor SPPM Keluar</label>
                        <input type="text" name="sppm_no" class="form-control custom-input w-100" value="{{ old('sppm_no', $outbound->sppm_no ?? $generatedSppm ?? '') }}" required placeholder="Contoh: SPPM/001/X/2026/DITLANTAS" {{ $isLocked ? 'readonly' : '' }}>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Tgl Surat Keluar</label>
                        <input type="date" name="sppm_date" class="form-control custom-input w-100" value="{{ old('sppm_date', $outbound->sppm_date ?? date('Y-m-d')) }}" required {{ $isLocked ? 'readonly' : '' }}>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Tujuan Distribusi (Penerima)</label>
                        @if($isLocked)
                            <input type="text" class="form-control custom-input w-100" value="{{ $outbound->destination->name ?? '-' }}" readonly>
                            <input type="hidden" name="destination_id" value="{{ $outbound->destination_id ?? '' }}">
                        @else
                            <select name="destination_id" class="form-select custom-input w-100" required>
                                <option value="">-- Pilih Tujuan --</option>
                                @foreach($destinations as $dest)
                                    <option value="{{ $dest->id }}" {{ old('destination_id', $outbound->destination_id ?? '') == $dest->id ? 'selected' : '' }}>{{ $dest->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Kategori Komoditas</label>
                        @if($isLocked)
                            <input type="text" class="form-control custom-input w-100" value="{{ $outbound->details->first()->material->category->name ?? '-' }}" readonly>
                        @else
                            <select id="category-selector" class="form-select custom-input w-100" required>
                                <option value="">-- Pilih --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (isset($selectedCategoryId) && $selectedCategoryId == $category->id) ? 'selected' : '' }}>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-12 col-sm-12 col-md-8 col-xl-4 mb-3">
                        <label class="field-label">Keterangan SPPM (Umum)</label>
                        <input type="text" name="keterangan" class="form-control custom-input w-100" value="{{ old('keterangan', $outbound->keterangan ?? '') }}" placeholder="Catatan pengiriman..." {{ $isLocked ? 'readonly' : '' }}>
                    </div>
                    
                    <!-- KOTAK INFO PEJABAT BAMAT -->
                    <div id="bamatInfo" class="mt-2 p-3 bg-light border rounded {{ isset($outbound) ? '' : 'd-none' }}">
                        <h6 class="fw-bold mb-2 text-muted" style="font-size: 0.8rem;"><i class="fa-solid fa-address-card me-1"></i> Info Pejabat Bamat</h6>
                        <div class="row small">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Nama:</span>
                                <strong id="infoNama" class="text-dark">{{ $outbound->nama_bamat ?? '-' }}</strong>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Pangkat / NRP:</span>
                                <strong id="infoPangkat" class="text-dark">{{ $outbound->pangkat ?? '-' }}</strong>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Jabatan:</span>
                                <strong id="infoJabatan" class="text-dark">{{ $outbound->jabatan ?? '-' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MATRIX -->
        <div class="col-12 mb-4">
            <div class="form-card shadow-sm overflow-hidden">
                <div class="bg-light px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="form-header-title m-0"><i class="fa-solid fa-table me-1"></i> Matrix Pengeluaran Barang</h6>
                    <span id="loading-indicator" class="spinner-border spinner-border-sm text-theme d-none" role="status"></span>
                </div>
                
                <div class="table-responsive">
                    <table class="table-form">
                        <thead>
                            <tr>
                                <th width="3%" class="text-center">No</th>
                                <th width="{{ $isLocked ? '35%' : '25%' }}">Nama & Kode Materiil</th>
                                <th width="5%" class="text-center">Sat</th>
                                @if(!$isLocked)
                                <th width="10%" class="text-center text-info">Tersedia</th>
                                @endif
                                <th width="10%" class="text-center text-danger">Jumlah<br><small>(Angka)</small></th>
                                <th width="15%">Banyaknya<br><small>(Huruf)</small></th>
                                <th width="12%" class="text-end">Hrg Satuan<br><small>(Rp)</small></th>
                                <th width="15%" class="text-end">Jumlah<br><small>(Rp)</small></th>
                            </tr>
                        </thead>
                        <tbody id="outbound-items-container">
                            <!-- Jika dokumen final / dikunci, langsung render data statis yang sudah tersimpan -->
                            @if($isLocked)
                                @php $parentNo = 1; @endphp
                                @foreach($outbound->details as $idx => $detail)
                                    <tr>
                                        <td class="text-center fw-bold text-muted" style="font-size: 0.85rem;">{{ is_null($detail->material->parent_id) ? $parentNo++ : '' }}</td>
                                        <td>
                                            @if(!is_null($detail->material->parent_id)) <i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-1 opacity-50"></i> @endif
                                            <span class="text-dark d-inline-block fw-semibold" style="font-size: 0.8rem;">{{ $detail->material->name }}</span>
                                            
                                            <!-- Render Serial dari Log OutStock -->
                                            @if($detail->material->pakai_seri == 1 && $detail->target_qty > 0)
                                                @php
                                                    $outStocks = App\Models\OutStock::whereHas('outLog', function($q) use ($outbound){
                                                        $q->where('out_sppm_id', $outbound->id);
                                                    })->whereHas('stock', function($q) use ($detail){
                                                        $q->where('material_id', $detail->material_id);
                                                    })->get();
                                                @endphp
                                                @if($outStocks->count() > 0)
                                                    <div class="mt-1">
                                                        <small class="text-muted"><i class="fa-solid fa-tags"></i> Seri Keluar:</small><br>
                                                        @foreach($outStocks as $st)
                                                            @if($st->seri_awal !== null)
                                                                <span class="badge bg-secondary bg-opacity-10 text-dark border mb-1" style="font-size: 0.7rem; font-weight: normal;">
                                                                    {!! $formatSeriVisual($st->prefix, $st->seri_awal, $st->seri_akhir) !!}
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold text-secondary" style="font-size: 0.8rem;">{{ $detail->material->satuan }}</td>
                                        
                                        <td class="align-middle text-center fw-bold text-danger">
                                            {{ $detail->target_qty > 0 ? number_format($detail->target_qty, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="align-middle">
                                            <span class="text-letter-span">{{ $detail->target_qty > 0 ? $terbilang($detail->target_qty) : '-' }}</span>
                                        </td>
                                        <td class="text-end align-middle fw-bold text-secondary">
                                            {{ $detail->harga_satuan > 0 ? 'Rp ' . number_format($detail->harga_satuan, 0, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-end align-middle">
                                            <span class="text-price-total">
                                                {{ $detail->harga_total > 0 ? 'Rp ' . number_format($detail->harga_total, 0, ',', '.') : '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="8" id="empty-state-row" class="text-center py-5 text-muted bg-white">
                                        <i class="fa-solid fa-arrow-pointer fs-3 mb-2 opacity-50"></i>
                                        <p class="mb-0 small">Silakan tentukan Kategori Komoditas terlebih dahulu untuk menggelar manifes barang.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Tampilkan Tombol Simpan HANYA Jika Bukan Mode ReadOnly & Bukan Final -->
                @if(!$isLocked)
                <div class="bg-light p-3 border-top d-flex justify-content-end gap-2">
                    <button type="submit" name="action_type" value="draft" class="btn btn-outline-secondary fw-bold px-4" style="border-radius: 6px;"><i class="fa-solid fa-pen-ruler me-1"></i> SIMPAN DRAFT</button>
                    <button type="submit" name="action_type" value="final" class="btn btn-theme fw-bold px-4" style="border-radius: 6px;" onclick="return confirm('PENTING: Menyimpan dokumen secara Final akan memotong stok di Gudang dan dokumen tidak dapat diubah lagi. Lanjutkan?');"><i class="fa-solid fa-box-open me-1"></i> SIMPAN & KELUARKAN STOK</button>
                </div>
                @endif
            </div>
        </div>
    </div>
</form>

<!-- MODAL WIZARD SERI (Hanya ditampilkan jika belum dikunci) -->
@if(!$isLocked)
<div class="modal fade" id="modalSeriWizard" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h6 class="modal-title fw-bold text-primary"><i class="fa-solid fa-wand-magic-sparkles me-2"></i> Wizard Pemilihan Seri Stok Gudang</h6>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="d-flex justify-content-between mb-3">
                    <div><span class="text-muted small">Materiil:</span> <strong id="wizard-mat-name" class="d-block text-dark"></strong></div>
                    <div class="text-end"><span class="text-muted small">Target Qty Keluar:</span> <strong id="wizard-target-qty" class="d-block text-danger fs-5">0</strong></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" style="font-size: 0.85rem;">
                        <thead class="bg-light text-center">
                            <tr>
                                <th width="5%">Pilih</th>
                                <th width="45%">Rentang Seri Tersedia di Gudang</th>
                                <th width="20%">Sisa Stok</th>
                                <th width="30%">Qty Diambil</th>
                            </tr>
                        </thead>
                        <tbody id="wizard-stock-list">
                            <!-- Diisi oleh JS -->
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3 py-2 d-flex justify-content-between align-items-center mb-0 border-0 shadow-sm">
                    <strong>Total Diambil: <span id="wizard-total-taken" class="text-primary fs-5">0</span></strong>
                    <strong>Status: <span id="wizard-status" class="text-danger">Kekurangan</span></strong>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <input type="hidden" id="wizard-index">
                <input type="hidden" id="wizard-stocks-data">
                <button type="button" class="btn btn-sm btn-light border fw-bold px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="btn-apply-wizard" class="btn btn-sm btn-primary fw-bold px-4">Terapkan Seri</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if(!$isLocked)
<script>
    const allowMinusStock = {{ $allowMinusStock ? 'true' : 'false' }};
    const categorySelector = document.getElementById('category-selector');
    const itemsContainer = document.getElementById('outbound-items-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    // Injeksi data draft lama (cek isset agar aman)
    const savedTargetData = {!! (isset($outbound) && isset($outbound->details)) ? json_encode($outbound->details->pluck('target_qty', 'material_id')) : '{}' !!};

    function terbilang(n) {
        const bil = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
        n = parseInt(n);
        if (isNaN(n) || n <= 0) return ""; 
        if (n < 12) return bil[n];
        if (n < 20) return terbilang(n - 10) + " belas";
        if (n < 100) return terbilang(Math.floor(n / 10)) + " puluh " + (bil[n % 10] === "" ? "" : " " + bil[n % 10]);
        if (n < 200) return "seratus " + (n - 100 === 0 ? "" : terbilang(n - 100));
        if (n < 1000) return terbilang(Math.floor(n / 100)) + " ratus " + (n % 100 === 0 ? "" : " " + terbilang(n % 100));
        if (n < 2000) return "seribu " + (n - 1000 === 0 ? "" : terbilang(n - 1000));
        if (n < 1000000) return terbilang(Math.floor(n / 1000)) + " ribu " + (n % 1000 === 0 ? "" : " " + terbilang(n % 1000));
        if (n < 1000000000) return terbilang(Math.floor(n / 1000000)) + " juta " + (n % 1000000 === 0 ? "" : " " + terbilang(n % 1000000));
        return "";
    }

    function formatRupiah(angka) {
        if(!angka || angka === 0) return "Rp 0";
        return "Rp " + Math.round(angka).toLocaleString('id-ID');
    }

    function formatSeriVisual(num) {
        let s = num.toString().replace(/\D/g, ''); 
        if (!s) return '';
        s = s.padStart(9, '0');
        return s.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // FUNGSI HELPER: Sinkronisasi Teks, Kalkulasi Harga & Total (FIFO)
    function updateRowValues(idx, qty, inputElement) {
        const letterSpan = document.getElementById(`letter-span-${idx}`);
        if (letterSpan) {
            letterSpan.textContent = (qty > 0) ? terbilang(qty) : '-';
        }

        const stocksDataStr = inputElement.dataset.stocks;
        if (stocksDataStr) {
            try {
                const stocks = JSON.parse(stocksDataStr);
                let maxPrice = 0;
                let sisa = qty;
                
                for (let i = 0; i < stocks.length; i++) {
                    if (sisa <= 0) break;
                    let batch = stocks[i];
                    let stockQty = parseInt(batch.qty) || 0;
                    let stockPrice = parseFloat(batch.harga_satuan || batch.price) || 0;
                    
                    let ambil = Math.min(stockQty, sisa);
                    if (stockPrice > maxPrice) { maxPrice = stockPrice; }
                    sisa -= ambil;
                }
                
                const priceSpan = document.getElementById(`price-span-${idx}`);
                const totalSpan = document.getElementById(`total-span-${idx}`);
                const priceInput = document.getElementById(`price-input-${idx}`);
                const totalInput = document.getElementById(`total-input-${idx}`);
                
                let total = maxPrice * qty;
                
                if (priceSpan) priceSpan.textContent = formatRupiah(maxPrice);
                if (totalSpan) totalSpan.textContent = formatRupiah(total);
                if (priceInput) priceInput.value = maxPrice;
                if (totalInput) totalInput.value = total;
            } catch(e) { console.error("Kalkulasi Harga Gagal", e); }
        }
    }

    // EVENT: Saat Qty diketik manual
    itemsContainer.addEventListener('input', function(e) {
        if (e.target.classList.contains('data-qty-input')) {
            let qty = parseInt(e.target.value) || 0;
            
            // JIKA ALLOW MINUS TIDAK AKTIF, paksa nilai agar tidak melebihi MAX
            if (!allowMinusStock) {
                const max = parseInt(e.target.getAttribute('max')) || 0;
                if (qty > max) {
                    qty = max;
                    e.target.value = max;
                }
            }

            const idx = e.target.dataset.index;
            updateRowValues(idx, qty, e.target);

            // OTOMATISASI: Sinkronkan ke anakan jika ismain diubah
            const isMain = e.target.dataset.ismain;
            if (isMain == "1" || isMain === 1) {
                const selector = '.data-qty-input[data-jmlxinduk="1"]';
                document.querySelectorAll(selector).forEach(childInput => {
                    if (childInput !== e.target) {
                        childInput.value = qty;
                        updateRowValues(childInput.dataset.index, qty, childInput);
                    }
                });
            }
        }
    });

    document.addEventListener("wheel", function(event){
        if(document.activeElement.type === "number"){ document.activeElement.blur(); }
    });

    if (categorySelector) {
        categorySelector.addEventListener('change', function() {
            const categoryId = this.value;
            if (!categoryId) return;

            if(loadingIndicator) loadingIndicator.classList.remove('d-none');

            fetch("{{ url('outbounds/materials-by-category') }}/" + categoryId)
                .then(response => response.json())
                .then(materials => {
                    if(loadingIndicator) loadingIndicator.classList.add('d-none');
                    itemsContainer.innerHTML = '';

                    function buildRow(mat, gIndex, noText, isParent) {
                        const hasChildren = mat.children && mat.children.length > 0;
                        const hasCode = (mat.code && mat.code !== '' && mat.code !== '-');
                        let codeHtml = hasCode ? `<div class="text-muted fw-bold mt-0.5" style="font-size: ${isParent ? '0.8rem' : '0.7rem'};">KODE : ${mat.code}</div>` : '';
                        
                        let stocksData = mat.stocks || mat.fifo_queue || [];
                        let stocksDataString = JSON.stringify(stocksData).replace(/"/g, '&quot;');
                        
                        const isMainVal = (mat.ismain == 1 || mat.is_main == 1) ? 1 : 0;
                        const isJmlxVal = (mat.jmlxinduk == 1 || mat.jml_x_induk == 1) ? 1 : 0;
                        const isHargaVal = (mat.is_harga == 1) ? 1 : 0;
                        
                        const readonlyAttr = (isJmlxVal == 1) ? 'readonly tabindex="-1"' : '';
                        
                        let serialInputHtml = '';
                        if (mat.pakai_seri == 1 && !(isParent && hasChildren)) {
                            serialInputHtml = `
                                <div class="mt-2 serial-inputs-container" data-index="${gIndex}" data-pakaiseri="1">
                                    <div class="d-flex gap-1 mb-1 serial-row">
                                        <input type="text" name="items[${gIndex}][serials][0][prefix]" class="form-control form-control-sm text-center fw-bold" style="max-width:50px; font-size:0.7rem;" placeholder="Pfx">
                                        <input type="text" name="items[${gIndex}][serials][0][start]" class="form-control form-control-sm text-center format-seri" style="font-size:0.7rem;" placeholder="Seri Awal">
                                        <input type="text" name="items[${gIndex}][serials][0][end]" class="form-control form-control-sm text-center format-seri" style="font-size:0.7rem;" placeholder="Seri Akhir">
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-wizard px-2 shadow-sm" data-index="${gIndex}" data-matname="${mat.name}" data-stocks="${stocksDataString}" title="Pilih dari Stok Gudang">
                                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                        }
                        
                        let currentStock = parseInt(mat.current_stock) || 0;
                        const stokStr = currentStock.toLocaleString('id-ID');
                        
                        let stockBadge = currentStock > 0 
                            ? `<span class="badge bg-info bg-opacity-10 text-info border border-info">${stokStr}</span>` 
                            : `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">KOSONG</span>`;

                        // IMPLEMENTASI ALLOW MINUS PADA INPUT QTY
                        let qtyInputAttrs = '';
                        if (currentStock === 0 && !allowMinusStock) {
                            qtyInputAttrs = `max="0" readonly disabled placeholder="0"`;
                        } else {
                            qtyInputAttrs = `min="0" ${!allowMinusStock ? `max="${currentStock}"` : ''} placeholder="0"`;
                        }

                        let html = `
                            <input type="hidden" name="items[${gIndex}][material_id]" value="${mat.id}">
                            <td class="text-center fw-bold text-muted" style="font-size: 0.85rem;">${noText}</td>
                            <td>
                                ${!isParent ? '<i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-1 opacity-50"></i>' : ''}
                                <span class="text-dark d-inline-block ${isParent ? 'fw-bold' : 'fw-semibold'}" style="font-size: ${isParent ? '0.9rem' : '0.8rem'};">${mat.name}</span>
                                ${codeHtml}
                                ${serialInputHtml}
                            </td>
                            <td class="text-center fw-bold text-secondary" style="font-size: 0.8rem;">${mat.satuan || '-'}</td>`;

                        if (isParent && hasChildren) {
                            html += `<td></td><td></td><td></td><td></td><td></td>`;
                        } else {
                            html += `
                            <td class="text-center align-middle">${stockBadge}</td>
                            <td class="align-middle">
                                <input type="number" name="items[${gIndex}][target_qty]" class="form-control form-control-sm text-center fw-bold text-danger data-qty-input" data-index="${gIndex}" data-ismain="${isMainVal}" data-jmlxinduk="${isJmlxVal}" data-isharga="${isHargaVal}" data-stocks="${stocksDataString}" ${qtyInputAttrs} ${readonlyAttr}>
                            </td>
                            <td class="align-middle">
                                <span id="letter-span-${gIndex}" class="text-letter-span">-</span>
                            </td>
                            <td class="text-end align-middle">
                                <span id="price-span-${gIndex}" class="text-secondary fw-bold">Rp 0</span>
                                <input type="hidden" name="items[${gIndex}][harga_satuan]" id="price-input-${gIndex}" value="0">
                            </td>
                            <td class="text-end align-middle">
                                <span id="total-span-${gIndex}" class="text-price-total">Rp 0</span>
                                <input type="hidden" name="items[${gIndex}][harga_total]" id="total-input-${gIndex}" value="0">
                            </td>`;
                        }
                        return html;
                    }

                    let globalIndex = 0;
                    let parentNo = 1;

                    materials.forEach((material) => {
                        let row = document.createElement('tr');
                        row.innerHTML = buildRow(material, globalIndex, (parentNo++), true);
                        itemsContainer.appendChild(row);
                        globalIndex++;

                        if (material.children && material.children.length > 0) {
                            material.children.forEach(child => {
                                let childRow = document.createElement('tr');
                                childRow.innerHTML = buildRow(child, globalIndex, '', false);
                                itemsContainer.appendChild(childRow);
                                globalIndex++;
                            });
                        }
                    });

                    // Trigger data draft lama
                    document.querySelectorAll('.data-qty-input').forEach(input => {
                        const matId = input.closest('tr').querySelector('input[name*="[material_id]"]').value;
                        if (savedTargetData[matId]) {
                            input.value = savedTargetData[matId];
                            input.dispatchEvent(new Event('input'));
                        }
                    });
                })
                .catch(error => {
                    console.error("Fetch Error:", error);
                    if(loadingIndicator) loadingIndicator.classList.add('d-none');
                    alert("Gagal memuat data kategori. Periksa koneksi atau console log.");
                });
        });

        if (categorySelector.value !== '') {
            categorySelector.dispatchEvent(new Event('change'));
        }
    }

    // --- LOGIKA WIZARD SERI STOK ---
    const modalSeriWizardEl = document.getElementById('modalSeriWizard');
    let wizardModal;
    if(modalSeriWizardEl) wizardModal = new bootstrap.Modal(modalSeriWizardEl);

    document.addEventListener('click', function(e) {
        const wizardBtn = e.target.closest('.btn-wizard');
        if(wizardBtn) {
            const idx = wizardBtn.dataset.index;
            const matName = wizardBtn.dataset.matname;
            const stocks = JSON.parse(wizardBtn.dataset.stocks || '[]');
            
            const targetQtyInput = document.querySelector(`.data-qty-input[data-index="${idx}"]`);
            const targetQty = targetQtyInput ? (parseInt(targetQtyInput.value) || 0) : 0;

            if(targetQty <= 0) {
                alert('PENTING: Silakan ketik Target Keluar (Qty) di tabel utama terlebih dahulu sebelum memilih seri stok.');
                return;
            }

            document.getElementById('wizard-mat-name').textContent = matName;
            document.getElementById('wizard-target-qty').textContent = targetQty;
            document.getElementById('wizard-index').value = idx;

            const tbody = document.getElementById('wizard-stock-list');
            tbody.innerHTML = '';

            if(stocks.length === 0) {
                // Modifikasi pesan wizard jika allow_minus aktif
                if (allowMinusStock) {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Gudang kosong. Silakan <strong>ketik manual Seri Awal dan Seri Akhir</strong> di kolom tabel depan untuk mencatat stok minus.</td></tr>`;
                } else {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">Tidak ada data rentang seri tersimpan di gudang.</td></tr>`;
                }
            } else {
                stocks.forEach((st, i) => {
                    const pfx = st.prefix || st.batch_prefix || '';
                    const awl = st.seri_awal ? formatSeriVisual(st.seri_awal) : '';
                    const akh = st.seri_akhir ? formatSeriVisual(st.seri_akhir) : '';
                    const price = parseFloat(st.harga_satuan || st.price) || 0;
                    
                    let displaySeri = '';
                    if (awl !== '' && akh !== '') {
                        const pfxStr = pfx ? `<span class="text-danger fw-bold">${pfx}.</span>` : '';
                        displaySeri = `${pfxStr}<span class="fw-bold text-dark">${awl}</span> <span class="fw-normal text-muted mx-1">s/d</span> <span class="fw-bold text-dark">${akh}</span>`;
                    } else {
                        displaySeri = `<span class="text-muted fst-italic">Tanpa Rentang Seri (Null Database)</span>`;
                    }

                    const priceBadge = price > 0 
                        ? `<span class="badge bg-success bg-opacity-10 text-success border border-success mt-1">Rp ${formatRupiah(price).replace('Rp ', '')}</span>` 
                        : `<span class="badge bg-secondary bg-opacity-10 text-secondary border mt-1" title="Data harga_satuan di tabel stok bernilai 0">Rp 0</span>`;
                    
                    tbody.innerHTML += `
                        <tr>
                            <td class="text-center align-middle">
                                <input class="form-check-input wizard-check border-primary" type="checkbox" value="${i}" id="w_check_${i}" style="transform: scale(1.3);">
                            </td>
                            <td class="align-middle">
                                ${displaySeri}<br>
                                ${priceBadge}
                                <input type="hidden" id="w_pfx_${i}" value="${pfx}">
                                <input type="hidden" id="w_start_${i}" value="${st.seri_awal || ''}">
                                <input type="hidden" id="w_price_${i}" value="${price}">
                            </td>
                            <td class="text-center align-middle fw-bold text-info fs-6" id="w_sisa_${i}">${st.qty}</td>
                            <td class="align-middle">
                                <input type="number" class="form-control text-center fw-bold wizard-qty text-primary" id="w_qty_${i}" max="${st.qty}" min="0" placeholder="Qty" disabled>
                            </td>
                        </tr>
                    `;
                });
            }

            calculateWizardTotal();
            wizardModal.show();
        }
    });

    document.addEventListener('change', function(e) {
        if(e.target.classList.contains('wizard-check')) {
            const i = e.target.value;
            const qtyInput = document.getElementById(`w_qty_${i}`);
            if(e.target.checked) {
                qtyInput.disabled = false;
                const target = parseInt(document.getElementById('wizard-target-qty').textContent) || 0;
                const taken = parseInt(document.getElementById('wizard-total-taken').textContent) || 0;
                const sisaBeli = target - taken;
                const maxStok = parseInt(document.getElementById(`w_sisa_${i}`).textContent) || 0;
                
                if(sisaBeli > 0) { qtyInput.value = Math.min(sisaBeli, maxStok); }
            } else {
                qtyInput.disabled = true;
                qtyInput.value = '';
            }
            calculateWizardTotal();
        }
    });

    document.addEventListener('input', function(e) {
        if(e.target.classList.contains('wizard-qty')) { calculateWizardTotal(); }
    });

    function calculateWizardTotal() {
        let total = 0;
        const target = parseInt(document.getElementById('wizard-target-qty').textContent) || 0;
        
        document.querySelectorAll('.wizard-qty').forEach(input => {
            if(!input.disabled) { total += parseInt(input.value) || 0; }
        });

        const spanTotal = document.getElementById('wizard-total-taken');
        const spanStatus = document.getElementById('wizard-status');
        if(spanTotal) spanTotal.textContent = total;

        if(spanStatus) {
            if(total === target) {
                spanStatus.innerHTML = '<i class="fa-solid fa-check-circle"></i> Sesuai Target';
                spanStatus.className = 'text-success fw-bold';
            } else if(total > target) {
                spanStatus.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Melebihi Target!';
                spanStatus.className = 'text-warning fw-bold';
            } else {
                spanStatus.innerHTML = '<i class="fa-solid fa-xmark-circle"></i> Kurang dari Target';
                spanStatus.className = 'text-danger fw-bold';
            }
        }
    }

    // TERAPKAN WIZARD KE SELURUH MATERIIL BERSERI
    document.getElementById('btn-apply-wizard')?.addEventListener('click', function() {
        const target = parseInt(document.getElementById('wizard-target-qty').textContent) || 0;
        const totalQty = parseInt(document.getElementById('wizard-total-taken').textContent) || 0;

        if(totalQty !== target) {
            // Jika allow minus stock aktif, wizard tidak membatasi pemilihan lebih kecil dari form
            // Tapi jika admin memaksa, tampilkan warning.
            if(!confirm(`Total Qty yang diambil (${totalQty}) tidak sama dengan target form (${target}). Ingin tetap menerapkan rentang ini?`)) {
                return;
            }
        }

        let selectedSerials = [];
        let maxPrice = 0;
        let totalPrice = 0;

        document.querySelectorAll('.wizard-check:checked').forEach(cb => {
            const i = cb.value;
            const qty = parseInt(document.getElementById(`w_qty_${i}`).value) || 0;
            if(qty > 0) {
                const price = parseFloat(document.getElementById(`w_price_${i}`).value) || 0;
                totalPrice += qty * price;
                if(price > maxPrice) maxPrice = price;

                const pfx = document.getElementById(`w_pfx_${i}`).value;
                const startRaw = document.getElementById(`w_start_${i}`).value;
                let endRaw = '';
                
                if(startRaw) {
                    const startNum = parseInt(startRaw, 10);
                    endRaw = startNum + qty - 1;
                }
                
                selectedSerials.push({
                    prefix: pfx,
                    start: startRaw ? formatSeriVisual(startRaw) : '',
                    end: endRaw ? formatSeriVisual(endRaw.toString()) : ''
                });
            }
        });

        if(selectedSerials.length === 0) {
            selectedSerials.push({prefix:'', start:'', end:''}); 
        }

        const originalIdx = document.getElementById('wizard-index').value;
        const targetQtyInput = document.querySelector(`.data-qty-input[data-index="${originalIdx}"]`);

        // Update Target Qty jika ternyata berbeda (Hanya jika admin memilih untuk mengubah)
        if (targetQtyInput && totalQty !== target && totalQty > 0 && !allowMinusStock) {
            targetQtyInput.value = totalQty;
            targetQtyInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // Terapkan Harga secara Final
        if(totalQty > 0) {
            const priceSpan = document.getElementById(`price-span-${originalIdx}`);
            const totalSpan = document.getElementById(`total-span-${originalIdx}`);
            const priceInput = document.getElementById(`price-input-${originalIdx}`);
            const totalInput = document.getElementById(`total-input-${originalIdx}`);
            
            if (priceSpan) priceSpan.textContent = formatRupiah(maxPrice);
            if (totalSpan) totalSpan.textContent = formatRupiah(totalPrice);
            if (priceInput) priceInput.value = maxPrice;
            if (totalInput) totalInput.value = totalPrice;
        }

        // Sebarkan format seri
        const allContainers = document.querySelectorAll('.serial-inputs-container[data-pakaiseri="1"]');
        allContainers.forEach(container => {
            const cIdx = container.dataset.index;
            let newHtml = '';
            
            const currentBtn = container.querySelector('.btn-wizard');
            const btnHtml = currentBtn ? currentBtn.outerHTML : '';

            selectedSerials.forEach((ser, loopIdx) => {
                const appendHtml = (loopIdx === 0 && btnHtml) ? btnHtml : '<div style="width:31px;"></div>'; 
                
                newHtml += `
                <div class="d-flex gap-1 mb-1 serial-row">
                    <input type="text" name="items[${cIdx}][serials][${loopIdx}][prefix]" class="form-control form-control-sm text-center fw-bold" style="max-width:50px; font-size:0.7rem;" placeholder="Pfx" value="${ser.prefix}">
                    <input type="text" name="items[${cIdx}][serials][${loopIdx}][start]" class="form-control form-control-sm text-center format-seri" style="font-size:0.7rem;" placeholder="Seri Awal" value="${ser.start}">
                    <input type="text" name="items[${cIdx}][serials][${loopIdx}][end]" class="form-control form-control-sm text-center format-seri" style="font-size:0.7rem;" placeholder="Seri Akhir" value="${ser.end}">
                    ${appendHtml}
                </div>`;
            });
            
            container.innerHTML = newHtml;
        });

        wizardModal.hide();
    });

    document.addEventListener('focusout', function(e) {
        if(e.target.classList.contains('format-seri')) {
            if(e.target.value) { e.target.value = formatSeriVisual(e.target.value); }
        }
    });

    // --- LOGIKA INFO PEJABAT BAMAT (DESTINATION) ---
    document.addEventListener("DOMContentLoaded", function() {
        const destinationsData = @json($destinations);
        const destinationSelect = document.querySelector('select[name="destination_id"]');
        const infoBox = document.getElementById('bamatInfo');

        function updateBamatInfo(destinationId) {
            if (!destinationId) {
                if(infoBox) infoBox.classList.add('d-none');
                return;
            }
            const dest = destinationsData.find(d => d.id == destinationId);
            if (dest && infoBox) {
                document.getElementById('infoNama').innerText = dest.nama || '-';
                document.getElementById('infoPangkat').innerText = dest.pangkat_nrp || '-';
                document.getElementById('infoJabatan').innerText = dest.jabatan || '-';
                infoBox.classList.remove('d-none'); 
            } else if (infoBox) {
                infoBox.classList.add('d-none'); 
            }
        }
        if(destinationSelect) {
            destinationSelect.addEventListener('change', function() { updateBamatInfo(this.value); });
            if(destinationSelect.value) updateBamatInfo(destinationSelect.value);
        }
    });
</script>
@endif
@endpush
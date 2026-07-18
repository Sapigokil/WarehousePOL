@extends('layouts.app')
@section('title', isset($inbound) ? 'Realisasi Kedatangan SPPM' : 'Input SPPM Baru')

@push('styles')
<style>
    .form-card { background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; }
    .form-header-title { font-size: 0.9rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
    .field-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 5px; display: block; }
    .custom-input { background-color: #f8fafc; border: 1px solid #cbd5e1 !important; border-radius: 6px; padding: 8px 12px; font-size: 0.9rem; color: #334155; }
    .custom-input:focus { background-color: #ffffff; border-color: var(--primary-color) !important; box-shadow: none; }
    .custom-input:disabled { background-color: #e2e8f0; opacity: 0.7; }
    
    .table-form { width: 100%; border-collapse: collapse; }
    .table-form th { background-color: #f8fafc; color: #475569; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 10px 10px; border-bottom: 2px solid #e2e8f0; vertical-align: middle;}
    .table-form td { padding: 6px 10px; vertical-align: top; border-bottom: 1px solid #f1f5f9; }

    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }

    .form-check-input-custom { width: 1.8em; height: 1rem; background-color: #fff; border: 1px solid rgba(0,0,0,.25); border-radius: 2em; cursor: pointer; transition: background-color .15s;}
    .form-check-input-custom:checked { background-color: var(--primary-color); border-color: var(--primary-color); }
    
    .text-letter-span { font-size: 0.7rem; font-weight: 600; color: #4b5563; text-transform: uppercase; background-color: #f3f4f6; padding: 2px 8px; border-radius: 6px; display: flex; align-items: center; min-height: 31px; border: 1px dashed #cbd5e1; word-wrap: break-word; }
    .text-price-total { font-size: 0.8rem; font-weight: 700; color: var(--primary-color); }
    
    .th-real-batch { border-left: 2px solid #e2e8f0; background-color: #fdfdfd; }
    .td-real-batch { border-left: 2px solid #e2e8f0; background-color: #fdfdfd; }
    .th-sisa { border-left: 2px solid #e2e8f0; background-color: #fef2f2; color: #dc2626 !important; }
    .td-sisa { border-left: 2px solid #e2e8f0; background-color: #fef2f2; font-weight: bold; color: #dc2626; text-align: center; vertical-align: middle !important; font-size: 1rem;}
</style>
@endpush

@section('content')

@php
    $maxBatches = 3; 
    $currentBatch = isset($inbound) ? $inbound->logs->count() + 1 : 1;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-file-signature text-theme me-2"></i>
            {{ isset($inbound) ? 'Realisasi Kedatangan SPPM' : 'Registrasi Surat Perintah Pengiriman' }}
        </h5>
    </div>
    <a href="{{ route('inbound.index') }}" class="btn btn-sm btn-light border fw-semibold px-3"><i class="fa-solid fa-arrow-left me-1"></i> Kembali</a>
</div>

@if(session('error'))
    <div class="alert alert-danger shadow-sm border-0 py-2 d-flex align-items-center" role="alert">
        <i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i>
        <div>{{ session('error') }}</div>
    </div>
@endif

<form action="{{ isset($inbound) ? route('inbound.update', $inbound->id) : route('inbound.store') }}" method="POST" id="formMainInbound">
    @csrf
    @if(isset($inbound)) @method('PUT') @endif

    <input type="hidden" name="inbound_mode" id="inbound_mode_value" value="{{ old('inbound_mode', $inboundMode) }}">

    <div class="row">
        <!-- HEADER (INFORMASI DOKUMEN & GUDANG) -->
        <div class="col-12 mb-3">
            <div class="form-card p-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h6 class="form-header-title m-0"><i class="fa-solid fa-folder me-1"></i> Informasi Dokumen</h6>
                    <div class="d-flex align-items-center gap-2 bg-light px-3 py-1 rounded border">
                        <span class="small fw-bold text-secondary text-uppercase" style="font-size: 0.7rem;">Mode Kedatangan Parsial / Bertahap</span>
                        <input type="checkbox" class="form-check-input-custom" id="toggle-mode-checkbox" {{ old('inbound_mode', $inboundMode) === 'mode-2' ? 'checked' : '' }} {{ isset($inbound) && $inbound->status == 'completed' ? 'disabled' : '' }}>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Nomor SPPM</label>
                        <input type="text" name="sppm_no" class="form-control custom-input w-100" value="{{ old('sppm_no', isset($inbound) ? $inbound->sppm_no : '') }}" required {{ isset($inbound) ? 'readonly' : '' }} placeholder="SPPM/001/VI/2026/KORLANTAS">
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Tgl Surat</label>
                        <input type="date" name="sppm_date" class="form-control custom-input w-100" value="{{ old('sppm_date', isset($inbound) ? $inbound->sppm_date : date('Y-m-d')) }}" required {{ isset($inbound) ? 'readonly' : '' }}>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Gudang Tujuan</label>
                        <select name="warehouse_id" id="warehouse-selector" class="form-select custom-input w-100" required {{ isset($inbound) ? 'disabled' : '' }}>
                            @if(count($warehouses) > 0)
                                <option value="">-- Pilih --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ isset($inbound) && $inbound->warehouse_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                @endforeach
                            @else
                                <option value="">-- Kosong --</option>
                            @endif
                            @if(!isset($inbound))
                                <option value="create_new" class="fw-bold text-theme bg-light">&#43; Buat Gudang Baru</option>
                            @endif
                        </select>
                        @if(isset($inbound))
                            <input type="hidden" name="warehouse_id" value="{{ $inbound->warehouse_id }}">
                        @endif
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Kategori Komoditas</label>
                        <select name="material_category_id" id="category-selector" class="form-select custom-input w-100" required {{ isset($inbound) ? 'disabled' : '' }}>
                            <option value="">-- Pilih --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ isset($inbound) && $inbound->material_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @if(isset($inbound))
                            <input type="hidden" name="material_category_id" value="{{ $inbound->material_category_id }}">
                        @endif
                    </div>
                    <div class="col-12 col-sm-12 col-md-8 col-xl-4 mb-3">
                        <label class="field-label">Keterangan SPPM (Umum)</label>
                        <input type="text" name="notes_manifes" class="form-control custom-input w-100" value="{{ old('notes_manifes', isset($inbound) ? $inbound->notes : '') }}" placeholder="Catatan dokumen surat...">
                    </div>
                </div>
                
                <div class="section-realita-date d-none">
                    <div class="row bg-warning bg-opacity-10 border border-warning rounded p-3 mt-2">
                        <div class="col-md-12 mb-2"><span class="fw-bold text-dark"><i class="fa-solid fa-truck-loading me-1"></i> Input Gelombang Fisik (Tahap {{ $currentBatch }})</span></div>
                        <div class="col-12 col-md-3 mb-2 mb-md-0">
                            <label class="field-label">Tgl Masuk Fisik (Tahap {{ $currentBatch }})</label>
                            <input type="date" name="batch_date" class="form-control custom-input w-100 bg-white" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-12 col-md-9">
                            <label class="field-label">Keterangan Khusus Tahap Ini</label>
                            <textarea name="batch_notes" class="form-control custom-input w-100 bg-white" rows="1" placeholder="Tulis kerusakan, kehilangan, dll pada tahap ini..."></textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- SEKSI TABEL MANIFES MATRIX -->
        <div class="col-12 mb-4">
            <div class="form-card shadow-sm overflow-hidden">
                <div class="bg-light px-4 py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="form-header-title m-0"><i class="fa-solid fa-table me-1"></i> Matrix Penerimaan Barang</h6>
                    <span id="loading-indicator" class="spinner-border spinner-border-sm text-theme d-none" role="status"></span>
                </div>
                
                <div class="table-responsive">
                    <table class="table-form">
                        <thead>
                            <tr>
                                <th width="3%" class="text-center">No</th>
                                <th width="20%">Nama & Kode Materiil</th>
                                <th width="7%" class="text-center">Sat</th>
                                <th width="8%" class="text-center">Banyaknya<br><small>(Angka)</small></th>
                                <th width="12%">Banyaknya<br><small>(Huruf)</small></th>
                                <th width="10%" class="text-end">Hrg Satuan<br><small>(Rp)</small></th>
                                <th width="10%" class="text-end">Total<br><small>(Rp)</small></th>
                                
                                @for($b=1; $b<=$maxBatches; $b++)
                                    <th width="10%" class="text-center th-real-batch col-tahap-{{ $b }} d-none">
                                        Tahap {{ $b }}
                                    </th>
                                @endfor
                                
                                <th width="8%" class="text-center th-sisa col-kekurangan d-none">Sisa<br><small>Kurang</small></th>
                                <th width="3%" class="text-center"><i class="fa-solid fa-trash text-danger opacity-75"></i></th>
                            </tr>
                        </thead>
                        <tbody id="inbound-items-container">
                            @if(isset($inbound))
                                @php 
                                    $noUrut = 1; 
                                    $isFirstSerial = true;
                                @endphp
                                @foreach($inbound->details as $index => $detail)
                                @php
                                    $isParent = is_null($detail->material->parent_id);
                                    $hasChildren = $detail->material->children()->count() > 0;
                                    
                                    $fmtSerial = function($val) {
                                        if(!$val) return '';
                                        return number_format($val, 0, '', '.');
                                    };
                                @endphp
                                <tr>
                                    <input type="hidden" name="items[{{ $index }}][material_id]" value="{{ $detail->material_id }}">
                                    
                                    <td class="text-center fw-bold text-muted row-number-span" style="font-size: 0.85rem;">{{ $isParent ? $noUrut++ : '' }}</td>
                                    
                                    <td>
                                        @if(!$isParent) <i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-1 opacity-50"></i> @endif
                                        <span class="text-dark d-inline-block {{ $isParent ? 'fw-bold' : 'fw-semibold' }}" style="font-size: {{ $isParent ? '0.9rem' : '0.8rem' }};">{{ $detail->material->name }}</span>
                                        
                                        @if(!is_null($detail->material->code) && $detail->material->code !== '' && $detail->material->code !== '-')
                                            <div class="text-muted fw-bold mt-0.5" style="font-size: 0.75rem;">KODE : {{ $detail->material->code }}</div>
                                        @endif

                                        @if($detail->material->pakai_seri == 1)
                                            <div class="mt-1">
                                                <small class="text-muted" style="font-size:0.65rem;">
                                                    <i class="fa-solid fa-tags"></i> Seri SPPM:
                                                    @if($isFirstSerial)
                                                        <button type="button" class="btn btn-dark py-0 px-1 btn-copy-serial ms-1" style="font-size: 0.55rem; line-height: 1.2;"><i class="fa-solid fa-copy"></i> Copy Semua</button>
                                                        @php $isFirstSerial = false; @endphp
                                                    @endif
                                                </small>
                                                <div class="d-flex gap-1 mt-1">
                                                    <input type="text" name="items[{{ $index }}][sppm_serial_prefix]" class="form-control form-control-sm text-center sppm-serial-prefix-input fw-bold" data-index="{{ $index }}" style="font-size:0.7rem; padding:2px; max-width:40px;" value="{{ $detail->sppm_serial_prefix }}" placeholder="PFX">
                                                    
                                                    <input type="text" name="items[{{ $index }}][sppm_serial_start]" class="form-control form-control-sm sppm-serial-start-input format-seri" data-index="{{ $index }}" style="font-size:0.7rem; padding:2px;" value="{{ $detail->sppm_serial_start ? str_pad($detail->sppm_serial_start, 9, '0', STR_PAD_LEFT) : '' }}" placeholder="Awal">
                                                    
                                                    <input type="text" name="items[{{ $index }}][sppm_serial_end]" class="form-control form-control-sm sppm-serial-end-input format-seri" data-index="{{ $index }}" style="font-size:0.7rem; padding:2px;" value="{{ $detail->sppm_serial_end ? str_pad($detail->sppm_serial_end, 9, '0', STR_PAD_LEFT) : '' }}" placeholder="Akhir">
                                                </div>
                                            </div>
                                        @endif
                                    </td>
                                    
                                    <td class="text-center fw-bold text-secondary" style="font-size: 0.8rem;">
                                        {{ $detail->material->satuan }}
                                    </td>
                                    
                                    @if($isParent && $hasChildren)
                                        <td></td><td></td><td></td><td></td>
                                        @for($b=1; $b<=$maxBatches; $b++) <td class="col-tahap-{{ $b }} d-none"></td> @endfor
                                        <td class="col-kekurangan d-none"></td>
                                    @else
                                        <td>
                                            <input type="number" name="items[{{ $index }}][target_qty]" id="target_{{ $index }}" class="form-control form-control-sm text-center fw-bold text-primary data-qty-input" data-index="{{ $index }}" value="{{ $detail->target_qty }}" min="0">
                                        </td>
                                        <td>
                                            <span id="letter-span-{{ $index }}" class="text-letter-span">{{ $detail->qty_huruf ?? '-' }}</span>
                                            <input type="hidden" name="items[{{ $index }}][qty_huruf]" id="qty_huruf_hidden_{{ $index }}" value="{{ $detail->qty_huruf }}">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $index }}][harga_satuan]" id="price_{{ $index }}" step="0.01" class="form-control form-control-sm text-end text-secondary data-price-input" data-index="{{ $index }}" value="{{ number_format($detail->harga_satuan, 2, '.', '') }}" placeholder="0.00" min="0">
                                        </td>
                                        <td>
                                            <div class="text-end"><span id="total-span-{{ $index }}" class="text-price-total">Rp {{ number_format($detail->harga_total, 0, ',', '.') }}</span></div>
                                            <input type="hidden" name="items[{{ $index }}][harga_total]" id="harga_total_hidden_{{ $index }}" value="{{ $detail->harga_total }}">
                                        </td>

                                        @php $totalReal = 0; @endphp
                                        @for($b=1; $b<=$maxBatches; $b++)
                                            <td class="td-real-batch col-tahap-{{ $b }} d-none">
                                                @if($b < $currentBatch)
                                                    @php
                                                        $oldLog = $inbound->logs->where('batch_number', $b)->first();
                                                        $oldStock = $oldLog ? $oldLog->stocks->where('material_id', $detail->material_id)->first() : null;
                                                        $qty = $oldStock ? $oldStock->qty_received : 0;
                                                        $totalReal += $qty;
                                                    @endphp
                                                    <div class="text-center fw-bold text-dark">{{ $qty }}</div>
                                                    @if($detail->material->pakai_seri == 1 && $oldStock && $oldStock->serial_start)
                                                        <div class="text-center text-muted mt-1" style="font-size:0.6rem;">{{ str_pad($oldStock->serial_start, 9, '0', STR_PAD_LEFT) }} - {{ str_pad($oldStock->serial_end, 9, '0', STR_PAD_LEFT) }}</div>
                                                    @endif
                                                @elseif($b == $currentBatch)
                                                    @php
                                                        $sisaKurang = $detail->target_qty - $totalReal;
                                                        $defaultInput = $sisaKurang > 0 ? $sisaKurang : 0;
                                                    @endphp
                                                    <input type="number" name="items[{{ $index }}][qty_received]" id="received_{{ $index }}" class="form-control form-control-sm text-center fw-bold border-warning text-warning data-real-input" data-index="{{ $index }}" value="{{ $defaultInput }}" min="0" max="{{ $sisaKurang > 0 ? $sisaKurang : 999999 }}">
                                                    
                                                    @if($detail->material->pakai_seri == 1)
                                                        <div class="d-flex gap-1 mt-1">
                                                            <input type="text" name="items[{{ $index }}][serial_prefix]" class="form-control form-control-sm text-center fw-bold" data-index="{{ $index }}" style="font-size:0.65rem; padding:2px; max-width:35px;" placeholder="PFX">
                                                            <input type="text" name="items[{{ $index }}][serial_start]" class="form-control form-control-sm real-serial-start-input format-seri" data-index="{{ $index }}" style="font-size:0.65rem; padding:2px;" placeholder="Awal">
                                                            <input type="text" name="items[{{ $index }}][serial_end]" class="form-control form-control-sm real-serial-end-input format-seri" data-index="{{ $index }}" style="font-size:0.65rem; padding:2px;" placeholder="Akhir">
                                                        </div>
                                                    @endif
                                                @endif
                                            </td>
                                        @endfor

                                        <td class="td-sisa col-kekurangan d-none">
                                            <span id="sisa-span-{{ $index }}" data-past="{{ $totalReal }}">{{ $detail->target_qty - $totalReal }}</span>
                                        </td>
                                    @endif
                                    
                                    <td class="text-center">
                                        <button type="button" class="btn btn-link text-danger p-0 btn-remove-row" title="Hapus Baris Ini">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="15" id="empty-state-row" class="text-center py-5 text-muted bg-white">
                                        <i class="fa-solid fa-arrow-pointer fs-3 mb-2 opacity-50"></i>
                                        <p class="mb-0 small">Silakan tentukan Kategori Komoditas terlebih dahulu untuk menggelar manifes barang.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="bg-light p-3 border-top d-flex justify-content-end gap-2">
                    @if(!isset($inbound))
                        <button type="submit" name="submit_action" value="save_new" class="btn btn-sm btn-outline-dark fw-bold px-3" style="border-radius: 6px;">SIMPAN & BARU</button>
                    @endif
                    <button type="submit" name="submit_action" value="save" class="btn btn-sm btn-theme fw-bold px-4" style="border-radius: 6px;">SIMPAN</button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- REVISI: MODAL TAMBAH GUDANG BARU -->
<div class="modal fade" id="modalAddWarehouse" tabindex="-1" aria-labelledby="modalAddWarehouseLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold" id="modalAddWarehouseLabel"><i class="fa-solid fa-warehouse me-2 text-theme"></i>Buat Gudang Baru</h6>
                <button type="button" class="btn-close shadow-none btn-cancel-warehouse" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="field-label">Kode Gudang</label>
                        <input type="text" id="new_warehouse_code" class="form-control form-control-sm custom-input w-100" placeholder="Cth: GDG-A">
                    </div>
                    <div class="col-md-8 mb-2">
                        <label class="field-label">Nama Gudang <span class="text-danger">*</span></label>
                        <input type="text" id="new_warehouse_name" class="form-control form-control-sm custom-input w-100" placeholder="Misal: Gudang Utama">
                        <div id="warehouse_error" class="text-danger mt-1 d-none" style="font-size: 0.7rem;">Nama gudang wajib diisi.</div>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="field-label">Lokasi</label>
                        <input type="text" id="new_warehouse_lokasi" class="form-control form-control-sm custom-input w-100" placeholder="Misal: Gedung B, Sayap Kanan">
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="field-label">Keterangan Tambahan</label>
                        <textarea id="new_warehouse_keterangan" class="form-control form-control-sm custom-input w-100" rows="2" placeholder="Catatan internal..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-sm btn-light border fw-bold btn-cancel-warehouse">Batal</button>
                <button type="button" id="btn-save-warehouse" class="btn btn-sm btn-theme fw-bold px-3">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="warehouse-spinner" role="status" aria-hidden="true"></span>
                    Simpan
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const modeValueInput = document.getElementById('inbound_mode_value');
    const toggleModeCheckbox = document.getElementById('toggle-mode-checkbox');
    const categorySelector = document.getElementById('category-selector');
    const itemsContainer = document.getElementById('inbound-items-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    const maxBatches = {{ $maxBatches }};
    const currentBatch = {{ $currentBatch }};
    let debounceTimer;

    document.addEventListener("wheel", function(event){
        if(document.activeElement.type === "number"){ document.activeElement.blur(); }
    });

    // PENGATUR FORMAT ANGKA 9 DIGIT DENGAN TITIK
    function applyVisualFormat(value) {
        let numericVal = value.replace(/\D/g, ''); 
        if (!numericVal) return '';
        let paddedVal = numericVal.padStart(9, '0');
        return paddedVal.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function calculateAutoEndSerial(idx, isRealita) {
        const startName = isRealita ? `items[${idx}][serial_start]` : `items[${idx}][sppm_serial_start]`;
        const endName = isRealita ? `items[${idx}][serial_end]` : `items[${idx}][sppm_serial_end]`;
        const qtyId = isRealita ? `received_${idx}` : `target_${idx}`;

        const qtyInput = document.getElementById(qtyId);
        const startInput = document.querySelector(`input[name="${startName}"]`);
        const endInput = document.querySelector(`input[name="${endName}"]`);

        if (qtyInput && startInput && endInput) {
            const qty = parseInt(qtyInput.value) || 0;
            const startValRaw = startInput.value.replace(/\D/g, '');

            if (qty > 0 && startValRaw !== '') {
                const startNum = parseInt(startValRaw, 10);
                const endNum = startNum + qty - 1;
                
                startInput.value = applyVisualFormat(startNum.toString());
                endInput.value = applyVisualFormat(endNum.toString());
            }
        }
    }

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
        if(angka === 0) return "Rp 0";
        return "Rp " + Math.round(angka).toLocaleString('id-ID');
    }

    function recalculateRowNumbers() {
        let num = 1;
        itemsContainer.querySelectorAll('.row-number-span').forEach(span => {
            if(span.textContent.trim() !== '') { span.textContent = num++; }
        });
    }

    function calculateRowValues(index) {
        const targetInput = document.getElementById(`target_${index}`);
        const priceInput = document.getElementById(`price_${index}`);
        const letterSpan = document.getElementById(`letter-span-${index}`);
        const totalSpan = document.getElementById(`total-span-${index}`);
        const hiddenHuruf = document.getElementById(`qty_huruf_hidden_${index}`);
        const hiddenTotal = document.getElementById(`harga_total_hidden_${index}`);
        const realInput = document.getElementById(`received_${index}`);
        const sisaSpan = document.getElementById(`sisa-span-${index}`);

        if (targetInput) {
            const qty = parseInt(targetInput.value) || 0;
            
            if(priceInput) {
                const price = parseFloat(priceInput.value) || 0;
                const total = qty * price;
                if(totalSpan) totalSpan.textContent = formatRupiah(total);
                if(hiddenTotal) hiddenTotal.value = total;
            }

            const teksHuruf = terbilang(qty);
            if(letterSpan) letterSpan.textContent = teksHuruf || '-';
            if(hiddenHuruf) hiddenHuruf.value = teksHuruf;

            if (modeValueInput.value === 'mode-1' && realInput) { realInput.value = qty; }

            if (sisaSpan) {
                let pastReceived = parseInt(sisaSpan.dataset.past) || 0;
                let currentReal = realInput ? (parseInt(realInput.value) || 0) : 0;
                let sisa = qty - (pastReceived + currentReal);
                sisaSpan.textContent = sisa;
                sisaSpan.style.color = (sisa === 0) ? '#16a34a' : '#dc2626';
            }
        }
    }

    function syncModeColumns() {
        const isChecked = toggleModeCheckbox.checked;
        const mode = isChecked ? 'mode-2' : 'mode-1';
        modeValueInput.value = mode;

        const dateSection = document.querySelector('.section-realita-date');
        const kekuranganCols = document.querySelectorAll('.col-kekurangan');
        const empRow = document.getElementById('empty-state-row');

        if (mode === 'mode-2') {
            if(dateSection) dateSection.classList.remove('d-none');
            kekuranganCols.forEach(col => col.classList.remove('d-none'));
            for(let b=1; b<=maxBatches; b++) {
                if (b <= currentBatch) { document.querySelectorAll(`.col-tahap-${b}`).forEach(col => col.classList.remove('d-none')); } 
                else { document.querySelectorAll(`.col-tahap-${b}`).forEach(col => col.classList.add('d-none')); }
            }
            if(empRow) empRow.setAttribute('colspan', '15');
        } else {
            if(dateSection) dateSection.classList.add('d-none');
            kekuranganCols.forEach(col => col.classList.add('d-none'));
            for(let b=1; b<=maxBatches; b++) { document.querySelectorAll(`.col-tahap-${b}`).forEach(col => col.classList.add('d-none')); }
            if(empRow) empRow.setAttribute('colspan', '15');
        }

        document.querySelectorAll('.data-qty-input').forEach(el => { calculateRowValues(el.dataset.index); });
    }

    if(toggleModeCheckbox) { toggleModeCheckbox.addEventListener('change', syncModeColumns); }

    itemsContainer.addEventListener('input', function(e) {
        if (e.target.classList.contains('data-qty-input') || e.target.classList.contains('data-price-input') || e.target.classList.contains('data-real-input')) {
            const idx = e.target.dataset.index;
            if (e.target.classList.contains('data-real-input')) { e.target.dataset.edited = "true"; }
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                calculateRowValues(idx);
                calculateAutoEndSerial(idx, false);
                calculateAutoEndSerial(idx, true); 
            }, 300);
        }
    });

    itemsContainer.addEventListener('focusout', function(e) {
        if (e.target.classList.contains('format-seri')) {
            const idx = e.target.dataset.index;
            const isRealita = e.target.classList.contains('real-serial-start-input') || e.target.classList.contains('real-serial-end-input');
            
            if (e.target.value) { e.target.value = applyVisualFormat(e.target.value); }
            calculateAutoEndSerial(idx, isRealita);
        }
    });

    itemsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-row') || e.target.closest('.btn-remove-row')) {
            e.preventDefault();
            const row = e.target.closest('tr');
            if (row) {
                row.remove();
                recalculateRowNumbers();
            }
        }

        if (e.target.classList.contains('btn-copy-serial') || e.target.closest('.btn-copy-serial')) {
            e.preventDefault();
            
            const firstPrefix = itemsContainer.querySelector('.sppm-serial-prefix-input');
            const firstStartInput = itemsContainer.querySelector('.sppm-serial-start-input');
            const firstEndInput = itemsContainer.querySelector('.sppm-serial-end-input');

            if (firstStartInput && firstEndInput) {
                const pVal = firstPrefix ? firstPrefix.value : '';
                const sVal = firstStartInput.value;
                const eVal = firstEndInput.value;
                
                const allPrefixes = itemsContainer.querySelectorAll('.sppm-serial-prefix-input');
                const allStarts = itemsContainer.querySelectorAll('.sppm-serial-start-input');
                const allEnds = itemsContainer.querySelectorAll('.sppm-serial-end-input');

                allPrefixes.forEach(input => { input.value = pVal; });
                allStarts.forEach(input => { input.value = sVal; });
                allEnds.forEach(input => { input.value = eVal; });
            }
        }
    });

    if (categorySelector) {
        categorySelector.addEventListener('change', function() {
            const categoryId = this.value;
            if (!categoryId) return;

            if(loadingIndicator) loadingIndicator.classList.remove('d-none');

            fetch("{{ url('inbound/materials-by-category') }}/" + categoryId)
                .then(response => response.json())
                .then(materials => {
                    if(loadingIndicator) loadingIndicator.classList.add('d-none');
                    itemsContainer.innerHTML = '';

                    let firstSerialRendered = false;

                    function buildRow(mat, gIndex, noText, isParent) {
                        const hasChildren = mat.children && mat.children.length > 0;
                        const hasCode = (mat.code && mat.code !== '' && mat.code !== '-');
                        let codeHtml = hasCode ? `<div class="text-muted fw-bold mt-0.5" style="font-size: ${isParent ? '0.8rem' : '0.7rem'};">KODE : ${mat.code}</div>` : '';

                        let sppmSerialHtml = '';
                        let realSerialHtml = '';

                        if(!(isParent && hasChildren)) {
                            if (mat.pakai_seri == 1) {
                                let copyBtnHtml = '';
                                if (!firstSerialRendered) {
                                    copyBtnHtml = `<button type="button" class="btn btn-dark py-0 px-1 btn-copy-serial ms-1" style="font-size: 0.55rem; line-height: 1.2;"><i class="fa-solid fa-copy"></i> Copy Semua</button>`;
                                    firstSerialRendered = true;
                                }
                                sppmSerialHtml = `
                                <div class="mt-1">
                                    <small class="text-muted" style="font-size:0.65rem;">
                                        <i class="fa-solid fa-tags"></i> Seri SPPM: ${copyBtnHtml}
                                    </small>
                                    <div class="d-flex gap-1 mt-1">
                                        <input type="text" name="items[${gIndex}][sppm_serial_prefix]" class="form-control form-control-sm text-center sppm-serial-prefix-input fw-bold" data-index="${gIndex}" style="font-size:0.7rem; padding:2px; max-width:40px;" placeholder="PFX">
                                        <input type="text" name="items[${gIndex}][sppm_serial_start]" class="form-control form-control-sm sppm-serial-start-input format-seri" data-index="${gIndex}" style="font-size:0.7rem; padding:2px;" placeholder="Awal">
                                        <input type="text" name="items[${gIndex}][sppm_serial_end]" class="form-control form-control-sm sppm-serial-end-input format-seri" data-index="${gIndex}" style="font-size:0.7rem; padding:2px;" placeholder="Akhir">
                                    </div>
                                </div>`;

                                realSerialHtml = `
                                <div class="d-flex gap-1 mt-1">
                                    <input type="text" name="items[${gIndex}][serial_prefix]" class="form-control form-control-sm text-center fw-bold" data-index="${gIndex}" style="font-size:0.65rem; padding:2px; max-width:35px;" placeholder="PFX">
                                    <input type="text" name="items[${gIndex}][serial_start]" class="form-control form-control-sm real-serial-start-input format-seri" data-index="${gIndex}" style="font-size:0.65rem; padding:2px;" placeholder="Awal">
                                    <input type="text" name="items[${gIndex}][serial_end]" class="form-control form-control-sm real-serial-end-input format-seri" data-index="${gIndex}" style="font-size:0.65rem; padding:2px;" placeholder="Akhir">
                                </div>`;
                            }
                        }

                        let html = `
                            <input type="hidden" name="items[${gIndex}][material_id]" value="${mat.id}">
                            <td class="text-center fw-bold text-muted row-number-span" style="font-size: 0.85rem;">${noText}</td>
                            <td>
                                ${!isParent ? '<i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-1 opacity-50"></i>' : ''}
                                <span class="text-dark d-inline-block ${isParent ? 'fw-bold' : 'fw-semibold'}" style="font-size: ${isParent ? '0.9rem' : '0.8rem'};">${mat.name}</span>
                                ${codeHtml}
                                ${sppmSerialHtml}
                            </td>
                            <td class="text-center fw-bold text-secondary" style="font-size: 0.8rem;">${mat.satuan}</td>`;

                        if (isParent && hasChildren) {
                            html += `<td></td><td></td><td></td><td></td>`;
                            for(let b=1; b<=maxBatches; b++) { html += `<td class="col-tahap-${b} d-none"></td>`; }
                            html += `<td class="col-kekurangan d-none"></td>`;
                        } else {
                            html += `
                            <td>
                                <input type="number" name="items[${gIndex}][target_qty]" id="target_${gIndex}" class="form-control form-control-sm text-center fw-bold text-primary data-qty-input" data-index="${gIndex}" value="" min="0">
                            </td>
                            <td>
                                <span id="letter-span-${gIndex}" class="text-letter-span">-</span>
                                <input type="hidden" name="items[${gIndex}][qty_huruf]" id="qty_huruf_hidden_${gIndex}" value="">
                            </td>
                            <td>
                                <input type="number" name="items[${gIndex}][harga_satuan]" id="price_${gIndex}" step="0.01" class="form-control form-control-sm text-end text-secondary data-price-input" data-index="${gIndex}" placeholder="0.00" min="0">
                            </td>
                            <td>
                                <div class="text-end"><span id="total-span-${gIndex}" class="text-price-total">Rp 0</span></div>
                                <input type="hidden" name="items[${gIndex}][harga_total]" id="harga_total_hidden_${gIndex}" value="0">
                            </td>`;

                            for(let b=1; b<=maxBatches; b++) {
                                if(b == currentBatch) {
                                    html += `<td class="td-real-batch col-tahap-${b} d-none">
                                        <input type="number" name="items[${gIndex}][qty_received]" id="received_${gIndex}" class="form-control form-control-sm text-center fw-bold border-warning text-warning data-real-input" data-index="${gIndex}" value="" min="0">
                                        ${realSerialHtml}
                                    </td>`;
                                } else {
                                    html += `<td class="td-real-batch col-tahap-${b} d-none"><div class="text-center text-muted">-</div></td>`;
                                }
                            }

                            html += `
                            <td class="td-sisa col-kekurangan d-none">
                                <span id="sisa-span-${gIndex}" data-past="0">0</span>
                            </td>`;
                        }

                        html += `
                            <td class="text-center">
                                <button type="button" class="btn btn-link text-danger p-0 btn-remove-row" title="Hapus Baris Ini">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </td>
                        `;

                        return html;
                    }

                    let globalIndex = 0;
                    let parentNo = 1;

                    materials.forEach((material) => {
                        let row = document.createElement('tr');
                        let currentNoText = true ? (parentNo++) : ''; 
                        row.innerHTML = buildRow(material, globalIndex, currentNoText, true);
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

                    syncModeColumns();
                });
        });
    }

    document.querySelectorAll('.format-seri').forEach(el => {
        if(el.value) { el.value = applyVisualFormat(el.value); }
    });

    document.querySelectorAll('.data-qty-input').forEach(el => { calculateRowValues(el.dataset.index); });
    syncModeColumns();

    document.addEventListener("DOMContentLoaded", function() {
        const warehouseSelector = document.getElementById('warehouse-selector');
        let previousWarehouseValue = warehouseSelector ? warehouseSelector.value : '';
        
        let warehouseModalEl = document.getElementById('modalAddWarehouse');
        let warehouseModal;
        if(warehouseModalEl) {
            warehouseModal = new bootstrap.Modal(warehouseModalEl);
        }

        if (warehouseSelector) {
            warehouseSelector.addEventListener('focus', function() {
                previousWarehouseValue = this.value;
            });

            warehouseSelector.addEventListener('change', function() {
                if (this.value === 'create_new') {
                    warehouseModal.show();
                    this.value = previousWarehouseValue; 
                } else {
                    previousWarehouseValue = this.value;
                }
            });
        }

        const btnCancelList = document.querySelectorAll('.btn-cancel-warehouse');
        btnCancelList.forEach(btn => {
            btn.addEventListener('click', function() {
                warehouseModal.hide();
                document.getElementById('new_warehouse_name').value = '';
                document.getElementById('new_warehouse_code').value = '';
                document.getElementById('new_warehouse_lokasi').value = '';
                document.getElementById('new_warehouse_keterangan').value = '';
                document.getElementById('warehouse_error').classList.add('d-none');
            });
        });

        const btnSaveWarehouse = document.getElementById('btn-save-warehouse');
        if (btnSaveWarehouse) {
            btnSaveWarehouse.addEventListener('click', function() {
                const warehouseCode = document.getElementById('new_warehouse_code').value.trim();
                const warehouseName = document.getElementById('new_warehouse_name').value.trim();
                const warehouseLokasi = document.getElementById('new_warehouse_lokasi').value.trim();
                const warehouseKeterangan = document.getElementById('new_warehouse_keterangan').value.trim();
                
                const errorText = document.getElementById('warehouse_error');
                const spinner = document.getElementById('warehouse-spinner');

                if (warehouseName === '') {
                    errorText.textContent = "Nama gudang tidak boleh kosong.";
                    errorText.classList.remove('d-none');
                    return;
                }

                errorText.classList.add('d-none');
                btnSaveWarehouse.disabled = true;
                spinner.classList.remove('d-none');

                // Payload lengkap
                fetch("{{ route('warehouses.ajax.store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        name: warehouseName,
                        code: warehouseCode,
                        lokasi: warehouseLokasi,
                        keterangan: warehouseKeterangan
                    })
                })
                .then(response => response.json())
                .then(data => {
                    btnSaveWarehouse.disabled = false;
                    spinner.classList.add('d-none');

                    if (data.success) {
                        const newOption = document.createElement('option');
                        newOption.value = data.warehouse.id;
                        newOption.text = data.warehouse.name;

                        const createNewOption = warehouseSelector.querySelector('option[value="create_new"]');
                        if (createNewOption) {
                            warehouseSelector.insertBefore(newOption, createNewOption);
                        } else {
                            warehouseSelector.appendChild(newOption);
                        }

                        warehouseSelector.value = data.warehouse.id;
                        previousWarehouseValue = data.warehouse.id;

                        warehouseModal.hide();
                        document.getElementById('new_warehouse_code').value = '';
                        document.getElementById('new_warehouse_name').value = '';
                        document.getElementById('new_warehouse_lokasi').value = '';
                        document.getElementById('new_warehouse_keterangan').value = '';
                    } else {
                        errorText.textContent = data.message || "Gagal menyimpan gudang.";
                        errorText.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    btnSaveWarehouse.disabled = false;
                    spinner.classList.add('d-none');
                    errorText.textContent = "Terjadi kesalahan. Pastikan koneksi atau inputan Anda benar (tidak duplikat).";
                    errorText.classList.remove('d-none');
                });
            });
        }
    });

</script>
@endpush
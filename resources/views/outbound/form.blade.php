@extends('layouts.app')
@section('title', isset($outbound) ? 'Kelola SPPM Keluar' : 'Input SPPM Keluar Baru')

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
</style>
@endpush

@section('content')
@php
    $isCompleted = isset($outbound) && $outbound->status === 'completed';
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-0">
            <i class="fa-solid fa-file-export text-danger me-2"></i>
            {{ isset($outbound) ? 'Kelola Dokumen Keluar' : 'Registrasi SPPM Keluar' }}
            @if(isset($outbound))
                @if($isCompleted)
                    <span class="badge bg-success ms-2 fs-6 align-middle"><i class="fa-solid fa-check me-1"></i> FINAL</span>
                @else
                    <span class="badge bg-secondary ms-2 fs-6 align-middle"><i class="fa-solid fa-pen-ruler me-1"></i> DRAFT</span>
                @endif
            @endif
        </h5>
    </div>
    <div class="d-flex gap-2">
        @if($isCompleted)
            <a href="#" class="btn btn-sm btn-info text-white fw-bold shadow-sm px-3"><i class="fa-solid fa-print me-1"></i> Cetak SPPM</a>
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

<form action="{{ isset($outbound) ? route('outbounds.update', $outbound->id) : route('outbounds.store') }}" method="POST" id="formMainOutbound">
    @csrf
    @if(isset($outbound)) @method('PUT') @endif

    <div class="row">
        <!-- HEADER -->
        <div class="col-12 mb-3">
            <div class="form-card p-4 shadow-sm">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h6 class="form-header-title m-0"><i class="fa-solid fa-folder me-1"></i> Informasi Dokumen Keluar</h6>
                </div>
                
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Nomor SPPM Keluar</label>
                        <input type="text" name="sppm_no" class="form-control custom-input w-100" value="{{ old('sppm_no', $outbound->sppm_no ?? '') }}" required placeholder="Contoh: OUT/001/2026" {{ $isCompleted ? 'readonly' : '' }}>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Tgl Surat Keluar</label>
                        <input type="date" name="sppm_date" class="form-control custom-input w-100" value="{{ old('sppm_date', $outbound->sppm_date ?? date('Y-m-d')) }}" required {{ $isCompleted ? 'readonly' : '' }}>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-xl-2 mb-3">
                        <label class="field-label">Tujuan Distribusi (Penerima)</label>
                        @if($isCompleted)
                            <input type="text" class="form-control custom-input w-100" value="{{ $outbound->destination->name }}" readonly>
                            <input type="hidden" name="destination_id" value="{{ $outbound->destination_id }}">
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
                        @if($isCompleted)
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
                        <input type="text" name="keterangan" class="form-control custom-input w-100" value="{{ old('keterangan', $outbound->keterangan ?? '') }}" placeholder="Catatan pengiriman..." {{ $isCompleted ? 'readonly' : '' }}>
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
                                <th width="25%">Nama & Kode Materiil</th>
                                <th width="5%" class="text-center">Sat</th>
                                <th width="10%" class="text-center text-info">Tersedia</th>
                                <th width="10%" class="text-center text-danger">Target Keluar<br><small>(Angka)</small></th>
                                <th width="15%">Banyaknya<br><small>(Huruf)</small></th>
                                <th width="12%" class="text-end">Hrg Satuan<br><small>(Rp)</small></th>
                                <th width="15%" class="text-end">Jumlah<br><small>(Rp)</small></th>
                            </tr>
                        </thead>
                        <tbody id="outbound-items-container">
                            <!-- Jika dokumen final, langsung render data statis yang sudah tersimpan -->
                            @if($isCompleted)
                                @php $parentNo = 1; @endphp
                                @foreach($outbound->details as $idx => $detail)
                                    <tr>
                                        <td class="text-center fw-bold text-muted" style="font-size: 0.85rem;">{{ is_null($detail->material->parent_id) ? $parentNo++ : '' }}</td>
                                        <td>
                                            @if(!is_null($detail->material->parent_id)) <i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-1 opacity-50"></i> @endif
                                            <span class="text-dark d-inline-block fw-semibold" style="font-size: 0.8rem;">{{ $detail->material->name }}</span>
                                            
                                            <!-- Render Serial dari Log OutStock -->
                                            @if($detail->material->pakai_seri == 1)
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
                                                                <span class="badge bg-secondary bg-opacity-10 text-dark border fw-bold mb-1" style="font-size: 0.65rem;">
                                                                    {{ $st->prefix }} {{ str_pad($st->seri_awal, 9, '0', STR_PAD_LEFT) }} s/d {{ str_pad($st->seri_akhir, 9, '0', STR_PAD_LEFT) }}
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif

                                        </td>
                                        <td class="text-center fw-bold text-secondary" style="font-size: 0.8rem;">{{ $detail->material->satuan }}</td>
                                        <td class="text-center align-middle"><span class="badge bg-secondary">-</span></td>
                                        <td class="align-middle text-center fw-bold text-danger">{{ number_format($detail->target_qty, 0, ',', '.') }}</td>
                                        <td class="align-middle">
                                            <span class="text-letter-span">{{ $detail->target_qty > 0 ? '-' : '-' }}</span> <!-- You can call terbilang here if PHP helper exists -->
                                        </td>
                                        <td class="text-end align-middle fw-bold text-secondary">Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                                        <td class="text-end align-middle"><span class="text-price-total">Rp {{ number_format($detail->harga_total, 0, ',', '.') }}</span></td>
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

                @if(!$isCompleted)
                <div class="bg-light p-3 border-top d-flex justify-content-end gap-2">
                    <button type="submit" name="action_type" value="draft" class="btn btn-outline-secondary fw-bold px-4" style="border-radius: 6px;"><i class="fa-solid fa-pen-ruler me-1"></i> SIMPAN DRAFT</button>
                    <button type="submit" name="action_type" value="final" class="btn btn-theme fw-bold px-4" style="border-radius: 6px;" onclick="return confirm('PENTING: Menyimpan dokumen secara Final akan langsung memotong stok di Gudang dan dokumen tidak dapat diubah lagi. Lanjutkan?');"><i class="fa-solid fa-box-open me-1"></i> SIMPAN & KELUARKAN STOK</button>
                </div>
                @endif
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
@if(!$isCompleted)
<script>
    const categorySelector = document.getElementById('category-selector');
    const itemsContainer = document.getElementById('outbound-items-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    // Injeksi data draft lama
    const savedTargetData = {!! isset($outbound) ? json_encode($outbound->details->pluck('target_qty', 'material_id')) : '{}' !!};

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
        let s = num.toString().padStart(9, '0');
        return s.substring(0,3) + '.' + s.substring(3,6) + '.' + s.substring(6,9);
    }

    itemsContainer.addEventListener('input', function(e) {
        if (e.target.classList.contains('data-qty-input')) {
            const qty = parseInt(e.target.value) || 0;
            const idx = e.target.dataset.index;
            const tr = e.target.closest('tr');
            
            const letterSpan = document.getElementById(`letter-span-${idx}`);
            if (letterSpan) {
                letterSpan.textContent = (qty > 0) ? terbilang(qty) : '-';
            }

            const previewContainer = tr.querySelector('.serial-preview-container');
            if (previewContainer) {
                const prefix = previewContainer.dataset.prefix;
                const startSeriRaw = previewContainer.dataset.start;
                const previewText = previewContainer.querySelector('.serial-preview-text');
                
                if (qty > 0 && startSeriRaw && startSeriRaw !== 'null') {
                    const startNum = parseInt(startSeriRaw, 10);
                    const endNum = startNum + qty - 1;
                    
                    const pfxHtml = (prefix && prefix !== 'null') ? `<span class="text-danger fw-bold me-1">${prefix}</span>` : '';
                    previewText.innerHTML = `${pfxHtml}${formatSeriVisual(startNum)} <span class="fw-normal text-muted mx-1">s/d</span> ${formatSeriVisual(endNum)}`;
                } else {
                    previewText.innerHTML = '-';
                }
            }

            const fifoDataStr = e.target.dataset.fifo;
            if (fifoDataStr) {
                const fifoQueue = JSON.parse(fifoDataStr);
                let maxPrice = 0;
                let sisa = qty;
                
                for (let i = 0; i < fifoQueue.length; i++) {
                    if (sisa <= 0) break;
                    let batch = fifoQueue[i];
                    let ambil = Math.min(batch.qty, sisa);
                    if (parseFloat(batch.price) > maxPrice) {
                        maxPrice = parseFloat(batch.price);
                    }
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
                        
                        let serialPreviewHtml = '';
                        if (mat.pakai_seri == 1 && !(isParent && hasChildren)) {
                            let prefixData = mat.next_prefix || '';
                            let startData = mat.next_seri || '';
                            serialPreviewHtml = `
                                <div class="mt-1 text-muted serial-preview-container" data-prefix="${prefixData}" data-start="${startData}" style="font-size: 0.7rem;">
                                    <i class="fa-solid fa-tags opacity-50 me-1"></i> Estimasi Seri: <span class="serial-preview-text fw-bold text-dark">-</span>
                                </div>
                            `;
                        }
                        
                        const stokStr = mat.current_stock.toLocaleString('id-ID');
                        let stockBadge = mat.current_stock > 0 
                            ? `<span class="badge bg-info bg-opacity-10 text-info border border-info">${stokStr}</span>` 
                            : `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger">KOSONG</span>`;

                        let fifoDataString = JSON.stringify(mat.fifo_queue || []).replace(/"/g, '&quot;');

                        let html = `
                            <input type="hidden" name="items[${gIndex}][material_id]" value="${mat.id}">
                            <td class="text-center fw-bold text-muted" style="font-size: 0.85rem;">${noText}</td>
                            <td>
                                ${!isParent ? '<i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-1 opacity-50"></i>' : ''}
                                <span class="text-dark d-inline-block ${isParent ? 'fw-bold' : 'fw-semibold'}" style="font-size: ${isParent ? '0.9rem' : '0.8rem'};">${mat.name}</span>
                                ${codeHtml}
                                ${serialPreviewHtml}
                            </td>
                            <td class="text-center fw-bold text-secondary" style="font-size: 0.8rem;">${mat.satuan}</td>`;

                        if (isParent && hasChildren) {
                            html += `<td></td><td></td><td></td><td></td><td></td>`;
                        } else {
                            html += `
                            <td class="text-center align-middle">${stockBadge}</td>
                            <td class="align-middle">
                                <input type="number" name="items[${gIndex}][target_qty]" class="form-control form-control-sm text-center fw-bold text-danger data-qty-input" data-index="${gIndex}" data-fifo="${fifoDataString}" min="0" max="${mat.current_stock}" ${mat.current_stock == 0 ? 'readonly disabled placeholder="0"' : 'placeholder="0"'}>
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

                    // Trigger input agar nilai draft lama terisi (Pembaruan Nomor Seri Otomatis)
                    document.querySelectorAll('.data-qty-input').forEach(input => {
                        const matId = input.closest('tr').querySelector('input[name*="[material_id]"]').value;
                        if (savedTargetData[matId]) {
                            input.value = savedTargetData[matId];
                            input.dispatchEvent(new Event('input'));
                        }
                    });
                });
        });

        // Trigger AJAX on page load if editing Draft
        if (categorySelector.value !== '') {
            categorySelector.dispatchEvent(new Event('change'));
        }
    }
</script>
@endif
@endpush
@extends('layouts.app')
@section('title', 'Lacak Nomor Seri')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 25px; position: relative; overflow: hidden; background: linear-gradient(135deg, #1e293b, #0f172a); }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.10; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }

    .tracking-card { background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; }
    .tracking-card-header { background-color: #f8fafc; padding: 15px 25px; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #334155; }
    
    .field-label { font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
    .custom-input { background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 15px; font-size: 0.95rem; color: #334155; transition: all 0.2s ease-in-out; width: 100%; }
    .custom-input:focus { background-color: #ffffff; border-color: var(--primary-color, #2563eb); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); outline: none; }

    /* Radio Button Pencarian */
    .search-type-wrapper { display: flex; gap: 15px; margin-bottom: 20px; }
    .search-type-card { flex: 1; position: relative; }
    .search-type-card input[type="radio"] { position: absolute; opacity: 0; }
    .search-type-card label { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s; background: #fff; color: #64748b; font-weight: 700; font-size: 0.9rem; }
    .search-type-card input[type="radio"]:checked + label { border-color: var(--primary-color, #2563eb); background-color: #eff6ff; color: #1e40af; box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.1); }

    /* Hasil Pencarian */
    .status-badge { font-size: 0.75rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; }
    
    /* Timeline Vertical - DIBUAT LEBIH RAPAT */
    .timeline { position: relative; max-width: 100%; margin: 0 auto; padding: 5px 0; }
    .timeline::after { content: ''; position: absolute; width: 3px; background-color: #e2e8f0; top: 0; bottom: 0; left: 24px; border-radius: 4px; }
    
    .timeline-item { padding: 5px 0 15px 55px; position: relative; background: inherit; width: 100%; }
    .timeline-item::after { content: ''; position: absolute; width: 18px; height: 18px; right: auto; background-color: white; border: 3px solid var(--primary-color, #2563eb); border-radius: 50%; top: 12px; left: 16px; z-index: 1; }
    .timeline-item.outbound::after { border-color: #dc2626; }
    .timeline-item.inbound::after { border-color: #16a34a; }

    .timeline-content { padding: 12px 15px; background: #f8fafc; position: relative; border-radius: 6px; border: 1px solid #e2e8f0; }
    .timeline-date { font-size: 0.75rem; font-weight: 700; color: #94a3b8; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
    .timeline-title { font-size: 0.95rem; font-weight: 700; color: #334155; margin-bottom: 5px; }
    
    .timeline-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #cbd5e1; }
    .detail-item span { display: block; font-size: 0.7rem; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 0px; }
    .detail-item strong { display: block; font-size: 0.85rem; color: #1e293b; }
    
    .sppm-link:hover { text-decoration: underline !important; }
    
    /* Styling Format Seri */
    .serial-box { background-color: #f8fafc; border: 1px dashed #cbd5e1; padding: 4px 8px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; font-weight: bold; color: #475569; display: inline-block;}
    
    /* Accordion Custom */
    .tracking-accordion .accordion-button { padding: 12px 18px; background-color: #ffffff; color: #334155; border: 1px solid #e2e8f0; border-radius: 6px !important; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.2s; }
    .tracking-accordion .accordion-button:not(.collapsed) { background-color: #f8fafc; border-color: #cbd5e1; box-shadow: none; border-bottom-left-radius: 0 !important; border-bottom-right-radius: 0 !important; }
    .tracking-accordion .accordion-body { background-color: #ffffff; border: 1px solid #e2e8f0; border-top: none; border-bottom-left-radius: 6px; border-bottom-right-radius: 6px; padding: 15px 20px; }
    .tracking-accordion .accordion-item { border: none; background: transparent; margin-bottom: 10px; }
</style>
@endpush

@section('content')

@php
    // Helper Format Seri (Titik tiap 3 digit)
    $formatSeri = function($prefix, $start, $end = null) {
        $padAndDot = function($num) {
            $s = str_pad($num ?? 0, 9, '0', STR_PAD_LEFT);
            return substr($s, 0, 3) . '.' . substr($s, 3, 3) . '.' . substr($s, 6, 3);
        };
        
        $p = $prefix ? "<span class='text-danger fw-bold'>{$prefix}.</span>" : '';
        $s_formatted = $padAndDot($start);
        
        if ($end === null || $start == $end) {
            return "{$p}<span class='fw-bold'>{$s_formatted}</span>";
        }
        
        $e_formatted = $padAndDot($end);
        return "{$p}<span class='fw-bold'>{$s_formatted}</span> <span class='text-muted mx-1'>s/d</span> {$p}<span class='fw-bold'>{$e_formatted}</span>";
    };
@endphp

<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-satellite-dish header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-magnifying-glass-location me-2 text-info"></i> Lacak Nomor Seri</h4>
        <p class="mb-0 text-white-50 small">Pantau status, lokasi gudang, dan riwayat distribusi materiil utama berdasarkan nomor seri.</p>
    </div>
</div>

<div class="row">
    <!-- PANEL PENCARIAN (ATAS) -->
    <div class="col-12 mb-4">
        <div class="tracking-card">
            <div class="tracking-card-header">
                <i class="fa-solid fa-filter me-2"></i> Panel Pencarian Materiil
            </div>
            <div class="p-3 bg-white">
                <form action="{{ route('tracking.search') }}" method="GET" class="row align-items-end g-3">
                    
                    <input type="hidden" name="search_type" value="single">

                    <div class="col-md-4">
                        <label class="field-label">Jenis Materiil</label>
                        <select name="category_id" class="form-select custom-input" required>
                            <option value="">-- Pilih Jenis --</option>
                            @if(isset($categories))
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (isset($searchData['category_id']) && $searchData['category_id'] == $category->id) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="field-label">Kode</label>
                        <input type="text" name="prefix" class="form-control custom-input text-center fw-bold" placeholder="Cth: J" value="{{ $searchData['prefix'] ?? '' }}">
                    </div>

                    <div class="col-md-3">
                        <label class="field-label">Nomor Seri</label>
                        <input type="number" name="seri_awal" class="form-control custom-input text-center fw-bold text-primary" placeholder="Cth: 12345" required value="{{ $searchData['seri_awal'] ?? '' }}">
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-theme flex-grow-1 fw-bold py-2 shadow-sm rounded-3">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> CARI
                        </button>
                        
                        @if(isset($searchData))
                            <a href="{{ route('tracking.index') }}" class="btn btn-light fw-bold py-2 border rounded-3 text-secondary" title="Reset Pencarian">
                                <i class="fa-solid fa-rotate-left"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- KOLOM HASIL PENCARIAN (BAWAH) -->
    <div class="col-12">
        @if(isset($searchData))
            
            @if(count($singleResults) > 0)
                <div class="accordion tracking-accordion" id="accordionTracking">
                    @foreach($singleResults as $index => $result)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $index }}">
                                <button class="accordion-button collapsed d-flex justify-content-between align-items-center w-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="false" aria-controls="collapse{{ $index }}">
                                    
                                    <div class="d-flex flex-column align-items-start">
                                        <div class="text-muted fw-bold mb-1" style="font-size: 0.7rem; text-transform: uppercase;">Materiil Ditemukan</div>
                                        <h6 class="fw-bold mb-2 text-dark">{{ strtoupper($result['stock']->material->name ?? 'MATERIIL TIDAK DIKETAHUI') }}</h6>
                                        <div class="serial-box bg-white border">
                                            <i class="fa-solid fa-barcode me-2 text-secondary"></i> {!! $formatSeri($result['prefix'], $searchData['seri_awal']) !!}
                                        </div>
                                    </div>

                                    <div class="text-end ms-auto me-3">
                                        @if($result['status'] == 'available')
                                            <div class="status-badge bg-success bg-opacity-10 text-success border border-success mb-1">
                                                <i class="fa-solid fa-box-check"></i> TERSEDIA
                                            </div>
                                            <div class="text-muted fw-bold" style="font-size: 0.7rem;"><i class="fa-solid fa-warehouse me-1"></i> {{ strtoupper($result['stock']->warehouse->name ?? '-') }}</div>
                                        @else
                                            <div class="status-badge bg-danger bg-opacity-10 text-danger border border-danger mb-1">
                                                <i class="fa-solid fa-truck"></i> DIDISTRIBUSIKAN
                                            </div>
                                            <div class="text-muted fw-bold" style="font-size: 0.7rem;"><i class="fa-solid fa-building-shield me-1"></i> {{ strtoupper($result['outStock']->outLog->outSppm->destination->name ?? '-') }}</div>
                                        @endif
                                    </div>
                                </button>
                            </h2>
                            
                            <div id="collapse{{ $index }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $index }}" data-bs-parent="#accordionTracking">
                                <div class="accordion-body">
                                    <h6 class="fw-bold text-secondary mb-3 border-bottom pb-2" style="font-size: 0.85rem;"><i class="fa-solid fa-clock-rotate-left me-2"></i> Jejak Riwayat (Timeline)</h6>
                                    
                                    <div class="timeline">
                                        <!-- Titik Kedatangan (Inbound) -->
                                        <div class="timeline-item inbound">
                                            <div class="timeline-content shadow-sm">
                                                <div class="timeline-date"><i class="fa-regular fa-calendar me-1"></i> 
                                                    {{ \Carbon\Carbon::parse($result['inStock']->log->receive_date ?? $result['stock']->tgl_masuk ?? $result['stock']->created_at)->translatedFormat('d F Y') }}
                                                </div>
                                                <div class="timeline-title text-success"><i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Masuk Gudang (Penerimaan)</div>
                                                
                                                <div class="timeline-details">
                                                    <div class="detail-item">
                                                        <span>No. SPPM Masuk</span>
                                                        <strong>
                                                            @if(isset($result['inStock']->log->sppm->id))
                                                                <a href="{{ url('inbounds/' . $result['inStock']->log->sppm->id . '/edit') }}" target="_blank" class="text-decoration-none text-success fw-bold sppm-link">
                                                                    {{ $result['inStock']->log->sppm->sppm_no }} <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.75rem;"></i>
                                                                </a>
                                                            @else
                                                                {{ $result['stock']->no_surat_masuk ?? '-' }}
                                                            @endif
                                                        </strong>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span>Gudang Penyimpanan</span>
                                                        <strong>{{ $result['stock']->warehouse->name ?? '-' }}</strong>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span>Tahap / Batch</span>
                                                        <strong>{{ isset($result['inStock']->log->batch_number) ? 'Tahap ' . $result['inStock']->log->batch_number : '-' }}</strong>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span>Keterangan Gudang</span>
                                                        <strong>{{ $result['inStock']->log->notes ?? $result['stock']->keterangan ?? '-' }}</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Titik Pengeluaran (Outbound) -->
                                        @if($result['outStock'])
                                            <div class="timeline-item outbound">
                                                <div class="timeline-content shadow-sm">
                                                    <div class="timeline-date"><i class="fa-regular fa-calendar me-1"></i> {{ \Carbon\Carbon::parse($result['outStock']->created_at)->translatedFormat('d F Y - H:i') }} WIB</div>
                                                    <div class="timeline-title text-danger"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Pengeluaran (Distribusi)</div>
                                                    
                                                    <div class="timeline-details">
                                                        <div class="detail-item">
                                                            <span>No. SPPM Keluar</span>
                                                            <strong>
                                                                @if(isset($result['outStock']->outLog->outSppm->id))
                                                                    <a href="{{ url('outbounds/' . $result['outStock']->outLog->outSppm->id . '/edit') }}" target="_blank" class="text-decoration-none text-danger fw-bold sppm-link">
                                                                        {{ $result['outStock']->outLog->outSppm->sppm_no }} <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.75rem;"></i>
                                                                    </a>
                                                                @else
                                                                    -
                                                                @endif
                                                            </strong>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span>Tujuan Distribusi</span>
                                                            <strong>{{ strtoupper($result['outStock']->outLog->outSppm->destination->name ?? '-') }}</strong>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span>Total Seri Keluar</span>
                                                            <strong>{{ number_format($result['outStock']->qty_keluar ?? 0, 0, ',', '.') }} Lbr</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- STATE TIDAK DITEMUKAN (SINGLE) -->
                <div class="d-flex align-items-center justify-content-center border rounded bg-white shadow-sm" style="min-height: 350px;">
                    <div class="text-center text-muted opacity-75">
                        <i class="fa-solid fa-boxes-packing" style="font-size: 5rem; margin-bottom: 15px;"></i>
                        <h5 class="fw-bold">Data Tidak Ditemukan</h5>
                        @php
                            $paddedSeri = str_pad($searchData['seri_awal'], 9, '0', STR_PAD_LEFT);
                            $dottedSeri = substr($paddedSeri, 0, 3) . '.' . substr($paddedSeri, 3, 3) . '.' . substr($paddedSeri, 6, 3);
                        @endphp
                        <p class="mb-0">Nomor seri dengan kode <strong>{{ empty($searchData['prefix']) ? 'TANPA KODE' : $searchData['prefix'] }}</strong> dan urutan <strong>{{ $dottedSeri }}</strong> tidak tercatat di sistem.</p>
                    </div>
                </div>
            @endif

        @else
            <!-- STATE KOSONG / AWAL -->
            <div class="d-flex align-items-center justify-content-center border rounded bg-white shadow-sm" style="min-height: 350px;">
                <div class="text-center text-muted opacity-50">
                    <i class="fa-solid fa-satellite-dish" style="font-size: 6rem; margin-bottom: 15px;"></i>
                    <h5 class="fw-bold">Pelacakan Siap Digunakan</h5>
                    <p class="mb-0">Masukkan Jenis Materiil, Kode (jika ada), dan Nomor Seri di panel atas.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSingle = document.getElementById('type_single');
        const typeRange = document.getElementById('type_range');
        const rangeEndContainer = document.getElementById('range_end_container');
        const labelSerialStart = document.getElementById('label_serial_start');
        
        const inputSerialEnd = document.querySelector('input[name="seri_akhir"]');

        function toggleSearchType() {
            if (typeRange.checked) {
                rangeEndContainer.style.display = 'block';
                labelSerialStart.textContent = 'Nomor Seri Awal';
                inputSerialEnd.setAttribute('required', 'required');
            } else {
                rangeEndContainer.style.display = 'none';
                labelSerialStart.textContent = 'Nomor Seri';
                inputSerialEnd.removeAttribute('required');
                
                if(!inputSerialEnd.value) inputSerialEnd.value = '';
            }
        }

        typeSingle.addEventListener('change', toggleSearchType);
        typeRange.addEventListener('change', toggleSearchType);

        toggleSearchType();
    });
</script>
@endpush
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
    .status-badge { font-size: 0.8rem; font-weight: 800; padding: 6px 12px; border-radius: 6px; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; }
    
    /* Timeline Vertical */
    .timeline { position: relative; max-width: 100%; margin: 0 auto; padding: 20px 0; }
    .timeline::after { content: ''; position: absolute; width: 4px; background-color: #e2e8f0; top: 0; bottom: 0; left: 31px; border-radius: 4px; }
    
    .timeline-item { padding: 10px 0 30px 70px; position: relative; background: inherit; width: 100%; }
    .timeline-item::after { content: ''; position: absolute; width: 24px; height: 24px; right: auto; background-color: white; border: 4px solid var(--primary-color, #2563eb); border-radius: 50%; top: 12px; left: 21px; z-index: 1; }
    .timeline-item.outbound::after { border-color: #dc2626; }
    .timeline-item.inbound::after { border-color: #16a34a; }

    .timeline-content { padding: 20px; background: #f8fafc; position: relative; border-radius: 8px; border: 1px solid #e2e8f0; }
    .timeline-date { font-size: 0.8rem; font-weight: 700; color: #94a3b8; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
    .timeline-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: 10px; }
    
    .timeline-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #cbd5e1; }
    .detail-item span { display: block; font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 2px; }
    .detail-item strong { display: block; font-size: 0.9rem; color: #1e293b; }
    
    /* Hover link style */
    .sppm-link:hover { text-decoration: underline !important; }
</style>
@endpush

@section('content')
<div class="header-banner shadow-sm d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-satellite-dish header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-magnifying-glass-location me-2 text-info"></i> Lacak Nomor Seri</h4>
        <p class="mb-0 text-white-50 small">Pantau status, lokasi gudang, dan riwayat distribusi materiil berdasarkan nomor seri.</p>
    </div>
</div>

<div class="row">
    <!-- KOLOM PENCARIAN -->
    <div class="col-lg-4 mb-4">
        <div class="tracking-card">
            <div class="tracking-card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-filter me-2"></i> Panel Pencarian</span>
            </div>
            <div class="p-4">
                <form action="{{ route('tracking.search') }}" method="GET">
                    
                    <div class="search-type-wrapper">
                        <div class="search-type-card">
                            <input type="radio" name="search_type" id="type_single" value="single" {{ (isset($searchData['search_type']) && $searchData['search_type'] == 'single') || !isset($searchData['search_type']) ? 'checked' : '' }}>
                            <label for="type_single"><i class="fa-solid fa-crosshairs"></i> Single</label>
                        </div>
                        <div class="search-type-card">
                            <input type="radio" name="search_type" id="type_range" value="range" {{ isset($searchData['search_type']) && $searchData['search_type'] == 'range' ? 'checked' : '' }}>
                            <label for="type_range"><i class="fa-solid fa-layer-group"></i> Rentang (Range)</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="field-label">Kategori Materiil</label>
                        <select name="category_id" class="form-select custom-input">
                            <option value="">-- Semua Kategori --</option>
                            @if(isset($categories))
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (isset($searchData['category_id']) && $searchData['category_id'] == $category->id) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="field-label">Prefix (Awalan)</label>
                        <input type="text" name="prefix" class="form-control custom-input" placeholder="Cth: PFX (Kosongkan jika tidak ada)" value="{{ $searchData['prefix'] ?? '' }}">
                    </div>

                    <div class="mb-3">
                        <label class="field-label" id="label_serial_start">Nomor Seri</label>
                        <input type="number" name="seri_awal" class="form-control custom-input" placeholder="Cth: 12345" required value="{{ $searchData['seri_awal'] ?? '' }}">
                    </div>

                    <div class="mb-4" id="range_end_container" style="display: none;">
                        <label class="field-label">Nomor Seri Akhir</label>
                        <input type="number" name="seri_akhir" class="form-control custom-input" placeholder="Cth: 12400" value="{{ $searchData['seri_akhir'] ?? '' }}">
                    </div>

                    <button type="submit" class="btn btn-theme w-100 fw-bold py-2 shadow-sm rounded-3">
                        <i class="fa-solid fa-magnifying-glass me-2"></i> CARI DATA
                    </button>
                    
                    @if(isset($searchData))
                        <a href="{{ route('tracking.index') }}" class="btn btn-light w-100 fw-bold py-2 mt-2 border rounded-3 text-secondary">
                            <i class="fa-solid fa-rotate-left me-2"></i> RESET
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <!-- KOLOM HASIL PENCARIAN -->
    <div class="col-lg-8">
        @if(isset($searchData))
            
            @if($searchData['search_type'] == 'single')
                
                @if($singleResult)
                    <!-- HASIL UNTUK SINGLE SEARCH -->
                    <div class="tracking-card mb-4 {{ $singleResult['status'] == 'available' ? 'border-success' : 'border-danger' }} border-2">
                        <div class="p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted fw-bold mb-1" style="font-size: 0.8rem; text-transform: uppercase;">Materiil Ditemukan</div>
                                <h4 class="fw-bold mb-0 text-dark">{{ strtoupper($singleResult['stock']->material->name ?? 'MATERIIL TIDAK DIKETAHUI') }}</h4>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark border px-2 py-1"><i class="fa-solid fa-barcode me-1"></i> SERI: <span class="text-primary">{{ $singleResult['stock']->prefix ?? '' }}{{ str_pad($searchData['seri_awal'], 9, '0', STR_PAD_LEFT) }}</span></span>
                                </div>
                            </div>
                            <div class="text-end">
                                @if($singleResult['status'] == 'available')
                                    <div class="status-badge bg-success bg-opacity-10 text-success border border-success mb-2">
                                        <i class="fa-solid fa-box-check"></i> TERSEDIA DI GUDANG
                                    </div>
                                    <div class="text-muted fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-warehouse me-1"></i> {{ strtoupper($singleResult['stock']->warehouse->name ?? '-') }}</div>
                                @else
                                    <div class="status-badge bg-danger bg-opacity-10 text-danger border border-danger mb-2">
                                        <i class="fa-solid fa-truck"></i> DIDISTRIBUSIKAN
                                    </div>
                                    <div class="text-muted fw-bold" style="font-size: 0.75rem;"><i class="fa-solid fa-building-shield me-1"></i> {{ strtoupper($singleResult['outStock']->outLog->outSppm->destination->name ?? '-') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="tracking-card p-4">
                        <h6 class="fw-bold text-secondary mb-4 border-bottom pb-2"><i class="fa-solid fa-clock-rotate-left me-2"></i> Jejak Riwayat (Timeline)</h6>
                        
                        <div class="timeline">
                            <!-- Titik Kedatangan (Inbound) -->
                            <div class="timeline-item inbound">
                                <div class="timeline-content shadow-sm">
                                    <div class="timeline-date"><i class="fa-regular fa-calendar me-1"></i> 
                                        {{ \Carbon\Carbon::parse($singleResult['inStock']->log->receive_date ?? $singleResult['stock']->tgl_masuk ?? $singleResult['stock']->created_at)->translatedFormat('d F Y') }}
                                    </div>
                                    <div class="timeline-title text-success"><i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Masuk Gudang (Penerimaan)</div>
                                    
                                    <div class="timeline-details">
                                        <div class="detail-item">
                                            <span>No. SPPM Masuk</span>
                                            <strong>
                                                @if(isset($singleResult['inStock']->log->sppm->id))
                                                    <a href="{{ route('inbound.edit', $singleResult['inStock']->log->sppm->id) }}" target="_blank" class="text-decoration-none text-success fw-bold sppm-link">
                                                        {{ $singleResult['inStock']->log->sppm->sppm_no }} <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.75rem;"></i>
                                                    </a>
                                                @else
                                                    {{ $singleResult['stock']->no_surat_masuk ?? '-' }}
                                                @endif
                                            </strong>
                                        </div>
                                        <div class="detail-item">
                                            <span>Gudang Penyimpanan</span>
                                            <strong>{{ $singleResult['stock']->warehouse->name ?? '-' }}</strong>
                                        </div>
                                        <div class="detail-item">
                                            <span>Tahap / Batch</span>
                                            <strong>{{ isset($singleResult['inStock']->log->batch_number) ? 'Tahap ' . $singleResult['inStock']->log->batch_number : '-' }}</strong>
                                        </div>
                                        <div class="detail-item">
                                            <span>Keterangan Gudang</span>
                                            <strong>{{ $singleResult['inStock']->log->notes ?? $singleResult['stock']->keterangan ?? '-' }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Titik Pengeluaran (Outbound) -->
                            @if($singleResult['outStock'])
                                <div class="timeline-item outbound">
                                    <div class="timeline-content shadow-sm">
                                        <div class="timeline-date"><i class="fa-regular fa-calendar me-1"></i> {{ \Carbon\Carbon::parse($singleResult['outStock']->created_at)->translatedFormat('d F Y - H:i') }} WIB</div>
                                        <div class="timeline-title text-danger"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Pengeluaran (Distribusi)</div>
                                        
                                        <div class="timeline-details">
                                            <div class="detail-item">
                                                <span>No. SPPM Keluar</span>
                                                <strong>
                                                    @if(isset($singleResult['outStock']->outLog->outSppm->id))
                                                        <!-- MENGGUNAKAN FUNGSI URL() -->
                                                        <a href="{{ url('outbounds/' . $singleResult['outStock']->outLog->outSppm->id . '/edit') }}" target="_blank" class="text-decoration-none text-danger fw-bold sppm-link">
                                                            {{ $singleResult['outStock']->outLog->outSppm->sppm_no }} <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.75rem;"></i>
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </strong>
                                            </div>
                                            <div class="detail-item">
                                                <span>Tujuan Distribusi</span>
                                                <strong>{{ strtoupper($singleResult['outStock']->outLog->outSppm->destination->name ?? '-') }}</strong>
                                            </div>
                                            <div class="detail-item">
                                                <span>Total Seri Keluar (Satu Tarikan)</span>
                                                <strong>{{ number_format($singleResult['outStock']->qty_keluar ?? 0, 0, ',', '.') }} Lembar</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                @else
                    <!-- STATE TIDAK DITEMUKAN (SINGLE) -->
                    <div class="d-flex align-items-center justify-content-center h-100" style="min-height: 400px;">
                        <div class="text-center text-muted opacity-75">
                            <i class="fa-solid fa-boxes-packing" style="font-size: 5rem; margin-bottom: 15px;"></i>
                            <h5 class="fw-bold">Data Tidak Ditemukan</h5>
                            <p class="mb-0">Nomor seri <strong>{{ $searchData['prefix'] ?? '' }}{{ str_pad($searchData['seri_awal'], 9, '0', STR_PAD_LEFT) }}</strong> tidak tercatat di dalam sistem.</p>
                        </div>
                    </div>
                @endif
            
            @else
                
                @if(count($rangeResults) > 0)
                    <!-- HASIL UNTUK RANGE SEARCH -->
                    <div class="tracking-card p-4 text-center pb-2">
                        <i class="fa-solid fa-table-list fs-1 text-primary mb-3 opacity-75"></i>
                        <h5 class="fw-bold">Matriks Pencarian Rentang (Range)</h5>
                        <p class="text-muted mb-4">
                            Menampilkan pemecahan blok seri dari 
                            <strong class="text-dark">{{ $searchData['prefix'] ?? '' }}{{ str_pad($searchData['seri_awal'], 9, '0', STR_PAD_LEFT) }}</strong> 
                            s/d 
                            <strong class="text-dark">{{ $searchData['prefix'] ?? '' }}{{ str_pad($searchData['seri_akhir'], 9, '0', STR_PAD_LEFT) }}</strong>
                        </p>
                        
                        <div class="table-responsive border rounded bg-white text-start">
                            <table class="table table-hover table-bordered mb-0" style="font-size: 0.85rem;">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="text-center py-3">Blok Seri Awal</th>
                                        <th class="text-center py-3">Blok Seri Akhir</th>
                                        <th class="text-center py-3">Jumlah</th>
                                        <th class="py-3">Status / Lokasi Terkini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rangeResults as $res)
                                    <tr>
                                        <td class="text-center align-middle fw-bold">{{ str_pad($res['start'], 9, '0', STR_PAD_LEFT) }}</td>
                                        <td class="text-center align-middle fw-bold">{{ str_pad($res['end'], 9, '0', STR_PAD_LEFT) }}</td>
                                        <td class="text-center align-middle">{{ number_format($res['qty'], 0, ',', '.') }} Lembar</td>
                                        <td class="align-middle">
                                            @if($res['status'] == 'available')
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fa-solid fa-box-check me-1"></i> TERSEDIA</span>
                                                <div class="text-muted mt-1" style="font-size: 0.7rem;"><i class="fa-solid fa-warehouse"></i> {{ strtoupper($res['warehouse']) }}</div>
                                            @else
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="fa-solid fa-truck me-1"></i> DIDISTRIBUSIKAN</span>
                                                <div class="text-muted mt-1" style="font-size: 0.7rem;">
                                                    <i class="fa-solid fa-building-shield"></i> {{ strtoupper($res['destination']) }} <br>
                                                    
                                                    @if($res['sppm_id'])
                                                        <!-- MENGGUNAKAN FUNGSI URL() -->
                                                        <a href="{{ url('outbounds/' . $res['sppm_id'] . '/edit') }}" target="_blank" class="text-decoration-none text-danger opacity-75 sppm-link">
                                                            ({{ $res['sppm_no'] }} <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>)
                                                        </a>
                                                    @else
                                                        <span class="opacity-75">({{ $res['sppm_no'] }})</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <!-- STATE TIDAK DITEMUKAN (RANGE) -->
                    <div class="d-flex align-items-center justify-content-center h-100" style="min-height: 400px;">
                        <div class="text-center text-muted opacity-75">
                            <i class="fa-solid fa-boxes-packing" style="font-size: 5rem; margin-bottom: 15px;"></i>
                            <h5 class="fw-bold">Data Tidak Ditemukan</h5>
                            <p class="mb-0">Tidak ada satupun nomor seri di dalam rentang tersebut yang tercatat di dalam sistem.</p>
                        </div>
                    </div>
                @endif

            @endif

        @else
            <!-- STATE KOSONG / AWAL -->
            <div class="d-flex align-items-center justify-content-center h-100" style="min-height: 400px;">
                <div class="text-center text-muted opacity-50">
                    <i class="fa-solid fa-satellite-dish" style="font-size: 6rem; margin-bottom: 15px;"></i>
                    <h5 class="fw-bold">Pelacakan Siap Digunakan</h5>
                    <p class="mb-0">Pilih jenis pencarian dan masukkan detail seri di panel sebelah kiri.</p>
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
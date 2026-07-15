@extends('layouts.app')
@section('title', 'Stok Gudang')

@push('styles')
<style>
    .header-banner { border-radius: 12px; padding: 30px; color: white; margin-bottom: 25px; position: relative; overflow: hidden; }
    .header-banner-icon { position: absolute; right: 0%; top: 50%; transform: translateY(-50%); font-size: 12rem; color: #ffffff; opacity: 0.15; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }
    .table-modern { border-collapse: separate; border-spacing: 0 8px; width: 100%; }
    .table-modern thead th { border-bottom: none; color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; padding: 10px 15px; font-weight: 700; }
    .table-modern tbody tr { box-shadow: 0 2px 10px rgba(0,0,0,0.03); transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .table-modern tbody tr:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    .table-modern tbody tr:nth-child(odd) td { background-color: #ffffff; }
    .table-modern tbody tr:nth-child(even) td { background-color: rgba(0,0,0,0.02); }
    .table-modern td { border: none; padding: 15px; vertical-align: middle; color: #444; }
    .table-modern td:first-child { border-radius: 8px 0 0 8px; }
    .table-modern td:last-child { border-radius: 0 8px 8px 0; }
    .modal-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .modal-custom-input { background-color: #f8fafc; border: 1px solid #cbd5e1 !important; border-radius: 6px; padding: 10px 15px; font-size: 0.95rem; color: #334155; transition: all 0.2s ease-in-out; }
    .modal-custom-input:focus { background-color: #ffffff; border-color: var(--primary-color) !important; box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.05); }
    
    .seri-box { background: #f1f5f9; border: 1px dashed #cbd5e1; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85rem; letter-spacing: 1px; }
</style>
@endpush

@section('content')

<div class="header-banner header-banner-theme d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-layer-group header-banner-icon"></i>
    <div class="header-content">
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-layer-group me-2"></i> Stok Aktual</h3>
        <p class="mb-0 text-white-50">Pantau posisi barang, rentang nomor seri, dan lokasi fisik secara real-time.</p>
    </div>
    {{-- <div class="header-content text-end">
        <span class="badge bg-warning text-dark mb-2 shadow-sm"><i class="fa-solid fa-triangle-exclamation me-1"></i> Mode Penyesuaian Manual Aktif</span><br>
        <button class="btn btn-light fw-bold text-theme shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddStock">
            <i class="fa-solid fa-plus me-1"></i> Input Koreksi Stok
        </button>
    </div> --}}
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i> Gagal menyimpan data. Pastikan format isian sudah benar.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('stocks.index') }}" class="d-flex align-items-center w-100">
        <div class="me-3 d-flex align-items-center">
            <span class="text-muted small me-2 fw-semibold">Tampilkan:</span>
            <select name="limit" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="width: 70px; border-radius: 6px;">
                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
        
        <div class="me-2">
            <select name="category_id" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="border-radius: 6px; min-width: 150px;">
                <option value="">-- Semua Kategori --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="me-2">
            <select name="warehouse_id" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="border-radius: 6px; min-width: 150px;">
                <option value="">-- Semua Gudang --</option>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" {{ $warehouse_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-grow-1"></div>
        
        <div class="input-group input-group-sm shadow-sm" style="width: 300px; border-radius: 6px; overflow: hidden;">
            <input type="text" name="search" class="form-control border-0 px-3" placeholder="No. Surat, Barang, No. Seri..." value="{{ $search }}">
            <button class="btn btn-white border-0 bg-white px-3" type="submit"><i class="fa-solid fa-magnifying-glass text-muted"></i></button>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table-modern">
        <thead>
            <tr>
                <th width="12%">No. Surat / Tgl</th>
                <th width="20%">Barang & Kategori</th>
                <th width="15%">Lokasi Gudang</th>
                <th width="15%" class="text-center">Rentang Seri</th>
                <th width="10%" class="text-center">Kuantitas</th>
                <th width="15%" class="text-end">Nilai Total</th>
                <th width="13%" class="text-center">Status / Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stocks as $stock)
            <tr>
                <td>
                    <span class="fw-bold text-dark d-block">{{ $stock->no_surat_masuk }}</span>
                    <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i>{{ \Carbon\Carbon::parse($stock->tgl_masuk)->format('d M Y') }}</small>
                </td>
                <td>
                    <div class="fw-bold text-dark">{{ $stock->material?->name ?? '- Data Terhapus -' }}</div>
                    <small class="text-muted">{{ $stock->material?->kode_barang ?? '-' }} | {{ $stock->material?->category?->name ?? '-' }}</small>
                </td>
                <td class="text-muted fw-semibold">
                    <i class="fa-solid fa-location-dot me-1"></i>{{ $stock->warehouse?->name ?? '- Gudang Dihapus -' }}
                </td>
                <td class="text-center">
                    @if($stock->seri_awal && $stock->seri_akhir)
                        <div class="seri-box d-inline-block">
                            {{ $stock->seri_awal }}<br><small class="text-muted">s/d</small><br>{{ $stock->seri_akhir }}
                        </div>
                    @else
                        <span class="badge bg-light text-secondary border">Non-Seri</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="fs-5 fw-bold {{ $stock->qty > 0 ? 'text-theme' : 'text-danger' }}">{{ number_format($stock->qty, 0, ',', '.') }}</span>
                    <small class="text-muted d-block">{{ $stock->material?->satuan ?? '-' }}</small>
                </td>
                <td class="text-end">
                    <span class="fw-bold text-dark">Rp {{ number_format($stock->total_harga, 0, ',', '.') }}</span>
                    <small class="text-muted d-block">@ Rp {{ number_format($stock->harga_satuan, 0, ',', '.') }}</small>
                </td>
                <td class="text-center">
                    <div class="mb-2">
                        @if($stock->status == 'Tersedia' && $stock->qty > 0)
                            <span class="badge bg-success">Tersedia</span>
                        @else
                            <span class="badge bg-secondary">{{ $stock->status }}</span>
                        @endif
                    </div>
                    <button class="btn btn-sm btn-white bg-white text-theme shadow-sm rounded-circle me-1" style="width: 32px; height: 32px; border: 1px solid #e0e0e0;" data-bs-toggle="modal" data-bs-target="#modalEditStock{{ $stock->id }}" title="Koreksi"><i class="fa-solid fa-pen"></i></button>
                    <form action="{{ route('stocks.destroy', $stock->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus baris stok ini secara permanen?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-white bg-white text-danger shadow-sm rounded-circle" style="width: 32px; height: 32px; border: 1px solid #e0e0e0;" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>

            <div class="modal fade" id="modalEditStock{{ $stock->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg border-0">
                    <div class="modal-content border-0 shadow-lg">
                        <form action="{{ route('stocks.update', $stock->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-header border-0 bg-light">
                                <h5 class="modal-title fw-bold text-theme"><i class="fa-solid fa-pen-to-square me-2"></i> Koreksi Data Stok</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 bg-white text-start">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label modal-label">No. Surat Masuk</label>
                                        <input type="text" name="no_surat_masuk" class="form-control modal-custom-input" value="{{ old('no_surat_masuk', $stock->no_surat_masuk) }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label modal-label">Tgl Masuk</label>
                                        <input type="date" name="tgl_masuk" class="form-control modal-custom-input" value="{{ old('tgl_masuk', $stock->tgl_masuk) }}" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label modal-label">Barang / Materiel</label>
                                        <select name="material_id" class="form-select modal-custom-input" required>
                                            @foreach($materials as $material)
                                                <option value="{{ $material->id }}" {{ $stock->material_id == $material->id ? 'selected' : '' }}>{{ $material->name }} ({{ $material->kode_barang }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label modal-label">Lokasi Gudang</label>
                                        <select name="warehouse_id" class="form-select modal-custom-input" required>
                                            @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}" {{ $stock->warehouse_id == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label modal-label">No. Seri Awal (Opsional)</label>
                                        <input type="text" name="seri_awal" class="form-control modal-custom-input" value="{{ old('seri_awal', $stock->seri_awal) }}">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label modal-label">No. Seri Akhir (Opsional)</label>
                                        <input type="text" name="seri_akhir" class="form-control modal-custom-input" value="{{ old('seri_akhir', $stock->seri_akhir) }}">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label modal-label">Kuantitas (Qty)</label>
                                        <input type="number" name="qty" class="form-control modal-custom-input" value="{{ old('qty', $stock->qty) }}" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label modal-label">Harga Satuan (Rp)</label>
                                        <input type="number" name="harga_satuan" class="form-control modal-custom-input" value="{{ old('harga_satuan', intval($stock->harga_satuan)) }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label modal-label">Status Fisik</label>
                                        <select name="status" class="form-select modal-custom-input" required>
                                            <option value="Tersedia" {{ $stock->status == 'Tersedia' ? 'selected' : '' }}>Tersedia</option>
                                            <option value="Habis" {{ $stock->status == 'Habis' ? 'selected' : '' }}>Habis</option>
                                            <option value="Dipecah" {{ $stock->status == 'Dipecah' ? 'selected' : '' }}>Dipecah (Split)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label modal-label">Keterangan Koreksi</label>
                                    <textarea name="keterangan" class="form-control modal-custom-input" rows="2">{{ old('keterangan', $stock->keterangan) }}</textarea>
                                </div>
                            </div>
                            <div class="modal-footer border-0 bg-light">
                                <button type="submit" class="btn px-4 fw-bold shadow-sm btn-theme" style="border-radius: 8px;">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted bg-white" style="border-radius: 8px;">
                    <i class="fa-solid fa-box-open fs-1 mb-3 opacity-25"></i>
                    <p class="mb-0">Belum ada saldo persediaan barang di gudang.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">Menampilkan <strong>{{ $stocks->firstItem() ?? 0 }}</strong> - <strong>{{ $stocks->lastItem() ?? 0 }}</strong> dari <strong>{{ $stocks->total() }}</strong> baris</div>
    <div>{{ $stocks->links('pagination::bootstrap-5') }}</div>
</div>

<div class="modal fade" id="modalAddStock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg border-0">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('stocks.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold text-theme"><i class="fa-solid fa-square-plus me-2"></i> Input Stok Penyesuaian Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white text-start">
                    <div class="alert alert-warning border-0 small py-2 mb-4"><i class="fa-solid fa-circle-info me-2"></i><strong>Perhatian:</strong> Gunakan form ini hanya untuk penyesuaian saldo awal atau koreksi fisik. Penerimaan reguler harus melalui menu Barang Masuk.</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">No. Surat Masuk / Bukti</label>
                            <input type="text" name="no_surat_masuk" class="form-control modal-custom-input" placeholder="Contoh: OPNAME-001" value="{{ old('no_surat_masuk') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">Tgl Masuk / Tgl Opname</label>
                            <input type="date" name="tgl_masuk" class="form-control modal-custom-input" value="{{ old('tgl_masuk', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">Barang / Materiel</label>
                            <select name="material_id" class="form-select modal-custom-input" required>
                                <option value="">-- Pilih Barang --</option>
                                @foreach($materials as $material)
                                    <option value="{{ $material->id }}">{{ $material->name }} ({{ $material->kode_barang }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">Lokasi Gudang</label>
                            <select name="warehouse_id" class="form-select modal-custom-input" required>
                                <option value="">-- Pilih Gudang --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">No. Seri Awal (Bila ada)</label>
                            <input type="text" name="seri_awal" class="form-control modal-custom-input" placeholder="Contoh: 374501" value="{{ old('seri_awal') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">No. Seri Akhir (Bila ada)</label>
                            <input type="text" name="seri_akhir" class="form-control modal-custom-input" placeholder="Contoh: 375500" value="{{ old('seri_akhir') }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">Kuantitas Fisik (Qty)</label>
                            <input type="number" name="qty" class="form-control modal-custom-input" placeholder="Contoh: 1000" value="{{ old('qty') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label modal-label">Harga Satuan Pokok (Rp)</label>
                            <input type="number" name="harga_satuan" class="form-control modal-custom-input" placeholder="Contoh: 50000" value="{{ old('harga_satuan', 0) }}">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label modal-label">Keterangan Opsional</label>
                        <textarea name="keterangan" class="form-control modal-custom-input" rows="2" placeholder="Catatan hasil opname atau koreksi...">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="submit" class="btn px-4 fw-bold shadow-sm btn-theme" style="border-radius: 8px;">Simpan Stok Masuk</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
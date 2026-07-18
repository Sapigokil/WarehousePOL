@extends('layouts.app')
@section('title', 'Buku Besar Stok Gudang')

@push('styles')
<style>
    .header-banner { border-radius: 10px; padding: 25px; color: white; margin-bottom: 20px; position: relative; overflow: hidden; }
    .header-banner-icon { position: absolute; right: -2%; top: 50%; transform: translateY(-50%); font-size: 10rem; color: #ffffff; opacity: 0.15; pointer-events: none; z-index: 1; }
    .header-content { position: relative; z-index: 2; }

    .table-dense { width: 100%; border-collapse: collapse; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
    .table-dense thead { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .table-dense thead th { color: #475569; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 15px; font-weight: 700; vertical-align: middle; }
    .table-dense tbody tr { border-bottom: 1px solid #f1f5f9; transition: background-color 0.15s ease; }
    .table-dense tbody tr:hover { background-color: #f8fafc; }
    .table-dense td { padding: 8px 15px; vertical-align: middle; color: #334155; font-size: 0.9rem; }
    
    .row-category td { background-color: #f1f5f9; color: #475569; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .qty-badge { font-size: 0.85rem; font-weight: 700; padding: 4px 10px; border-radius: 6px; }
</style>
@endpush

@section('content')

<div class="header-banner header-banner-theme d-flex justify-content-between align-items-center shadow-sm">
    <i class="fa-solid fa-boxes-stacked header-banner-icon"></i>
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-boxes-stacked me-2"></i> Stok Keseluruhan Gudang</h4>
        <p class="mb-0 text-white-50 small">Pantau ketersediaan barang secara global berdasarkan kategori dan varian materiil.</p>
    </div>
</div>

<!-- Area Filter -->
<div class="d-flex gap-2 mb-3">
    <form method="GET" action="{{ route('stocks.index') }}" class="d-flex gap-2 align-items-center w-100">
        <select name="category_id" class="form-select form-select-sm shadow-none" style="width: 200px;" onchange="this.form.submit()">
            <option value="">-- Semua Kategori --</option>
            @foreach($allCategories as $cat)
                <option value="{{ $cat->id }}" {{ $category_filter == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <div class="input-group input-group-sm shadow-sm flex-grow-1" style="max-width: 400px;">
            <input type="text" name="search" class="form-control border-0" placeholder="Cari Nama, Kode, SPPM, atau No. Seri..." value="{{ $search }}">
            <button class="btn btn-white bg-white border-0" type="submit"><i class="fa-solid fa-search"></i></button>
        </div>
        
        @if($search || $category_filter)
            <a href="{{ route('stocks.index') }}" class="btn btn-sm btn-light border shadow-sm px-3 text-secondary" title="Reset Filter Pencarian">
                <i class="fa-solid fa-rotate-left me-1"></i> Reset
            </a>
        @endif
    </form>
</div>

{{-- <div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('stocks.index') }}" class="d-flex align-items-center w-100">
        <div class="input-group input-group-sm shadow-sm" style="max-width: 400px; border-radius: 6px; overflow: hidden;">
            <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Cari Nama Barang atau Kode..." value="{{ $search }}">
            <button class="btn btn-white border-0 bg-white px-3" type="submit"><i class="fa-solid fa-magnifying-glass text-muted"></i></button>
        </div>
    </form>
</div> --}}

<div class="table-responsive shadow-sm" style="border-radius: 8px;">
    <table class="table-dense">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="45%">Nama Barang & Kode</th>
                <th width="15%" class="text-center">Satuan</th>
                <th width="20%" class="text-end">Total Stok Fisik</th>
                <th width="15%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody id="sortable-warehouses">
            @forelse($categories as $category)
                @if($category->materials->count() > 0)
                    <tr class="row-category">
                        <td colspan="5"><i class="fa-solid fa-layer-group me-2 opacity-75"></i> {{ $category->name }}</td>
                    </tr>
                    
                    @php $noUrut = 1; @endphp
                    @foreach($category->materials as $parent)
                        @php 
                            $parentQty = $stockTotals[$parent->id] ?? 0; 
                            $hasChildren = $parent->children->count() > 0;
                        @endphp
                        
                        @if($hasChildren)
                            <!-- PARENT DENGAN CHILD (NAMA TIDAK BISA DIKLIK) -->
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $noUrut++ }}</td>
                                <td class="fw-bold text-dark">{{ $parent->name }}</td>
                                <td></td><td></td><td></td>
                            </tr>

                            @foreach($parent->children as $child)
                                @php $childQty = $stockTotals[$child->id] ?? 0; @endphp
                                <tr class="bg-light bg-opacity-25">
                                    <td class="text-center"></td>
                                    <td class="ps-4 text-secondary">
                                        <i class="fa-solid fa-turn-up fa-rotate-90 text-muted me-2 opacity-50"></i>
                                        <!-- CHILD BISA DIKLIK -->
                                        <a href="{{ route('stocks.show', $child->id) }}" target="_blank" class="text-decoration-none text-secondary">
                                            {{ $child->name }} 
                                        </a>
                                        @if($child->code) <small class="text-muted ms-2">[{{ $child->code }}]</small> @endif
                                    </td>
                                    <td class="text-center fw-semibold text-muted">{{ $child->satuan }}</td>
                                    <td class="text-end">
                                        <!-- STYLE QTY SAMA DENGAN PARENT TANPA CHILD -->
                                        <span class="qty-badge {{ $childQty > 0 ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary' }}">
                                            {{ number_format($childQty, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('stocks.show', $child->id) }}" target="_blank" class="btn btn-sm btn-white border fw-bold text-theme" style="font-size: 0.75rem;">
                                            <i class="fa-solid fa-eye me-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <!-- PARENT TANPA CHILD (NAMA BISA DIKLIK) -->
                            <tr>
                                <td class="text-center fw-bold text-muted">{{ $noUrut++ }}</td>
                                <td class="fw-bold text-dark">
                                    <a href="{{ route('stocks.show', $parent->id) }}" target="_blank" class="text-decoration-none text-dark">
                                        {{ $parent->name }}
                                    </a>
                                </td>
                                <td class="text-center fw-semibold text-muted">{{ $parent->satuan }}</td>
                                <td class="text-end">
                                    <span class="qty-badge {{ $parentQty > 0 ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary' }}">
                                        {{ number_format($parentQty, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('stocks.show', $parent->id) }}" target="_blank" class="btn btn-sm btn-light border fw-bold text-theme" style="font-size: 0.75rem;">
                                        <i class="fa-solid fa-eye me-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                @endif
            @empty
                <tr><td colspan="5" class="text-center py-5">Data tidak ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
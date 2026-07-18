@extends('layouts.app')
@section('title', 'Realisasi Fisik Barang Keluar')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark"><i class="fa-solid fa-box-open text-danger me-2"></i> Realisasi Fisik (Potong Stok)</h4>
            <p class="text-muted small mb-0">SPPM No: <strong>{{ $outbound->sppm_no }}</strong> | Tujuan: <strong>{{ $outbound->destination->name }}</strong></p>
        </div>
        <a href="{{ route('outbounds.index') }}" class="btn btn-sm btn-light border fw-bold text-secondary px-3"><i class="fa-solid fa-arrow-left me-1"></i> Batal</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger shadow-sm border-0 mb-4">
            <ul class="mb-0">
                @foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('outbounds.update', $outbound->id) }}" method="POST">
        @csrf @method('PUT')
        
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
            <div class="card-header bg-light border-bottom py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small mb-1">Tanggal Keluar (Fisik) <span class="text-danger">*</span></label>
                        <input type="date" name="tgl_keluar" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small mb-1">Keterangan / Bukti Surat Jalan</label>
                        <input type="text" name="keterangan_log" class="form-control" placeholder="Contoh: Ekspedisi JNE Resi 12345...">
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="bg-white">
                        <tr>
                            <th class="text-muted small">Nama Barang</th>
                            <th class="text-muted small text-center">Stok Tersedia (Fisik)</th>
                            <th class="text-muted small text-center">Kekurangan SPPM</th>
                            <th class="text-muted small text-center" style="width: 200px;">Jumlah Akan Dikeluarkan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($outbound->details as $detail)
                            @php
                                $totalSudahKeluar = App\Models\OutStock::whereHas('outLog', function($q) use ($outbound) {
                                    $q->where('out_sppm_id', $outbound->id);
                                })->whereHas('stock', function($q) use ($detail) {
                                    $q->where('material_id', $detail->material_id);
                                })->sum('qty_keluar');
                                
                                $sisaTarget = $detail->target_qty - $totalSudahKeluar;
                                $stokGudang = $stockTotals[$detail->material_id] ?? 0;
                                $maxInput = min($sisaTarget, $stokGudang);
                            @endphp
                            
                            <tr>
                                <td class="align-middle fw-semibold text-dark">
                                    {{ $detail->material->name }}
                                    <div class="text-muted fw-normal" style="font-size: 0.75rem;">Target Awal: {{ $detail->target_qty }} {{ $detail->material->satuan }}</div>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge {{ $stokGudang > 0 ? 'bg-info bg-opacity-10 text-info border border-info' : 'bg-danger bg-opacity-10 text-danger border border-danger' }}">
                                        {{ number_format($stokGudang, 0, ',', '.') }} Fisik
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    @if($sisaTarget > 0)
                                        <span class="text-danger fw-bold">{{ number_format($sisaTarget, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Selesai</span>
                                    @endif
                                </td>
                                <td class="align-middle px-3 py-3">
                                    @if($sisaTarget > 0)
                                        <input type="number" name="items[{{ $detail->id }}][qty_keluar]" class="form-control form-control-lg text-center fw-bold" placeholder="0" min="0" max="{{ $maxInput }}" {{ $stokGudang == 0 ? 'readonly disabled' : '' }}>
                                        @if($stokGudang == 0)
                                            <small class="text-danger d-block mt-1" style="font-size: 0.65rem;">Stok Kosong</small>
                                        @endif
                                    @else
                                        <input type="hidden" name="items[{{ $detail->id }}][qty_keluar]" value="0">
                                        <div class="text-center text-muted fst-italic">Fulfilled</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-end py-3">
                <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm"><i class="fa-solid fa-cut me-1"></i> Proses Potong Stok & Nomor Seri</button>
            </div>
        </div>
    </form>
</div>
@endsection
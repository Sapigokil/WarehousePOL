@extends('layouts.app')
@section('title', 'System Logs & Audit Trail')

@push('styles')
<style>
    .header-banner {
        border-radius: 10px;
        padding: 25px;
        color: white;
        margin-bottom: 20px;
        position: relative; 
        overflow: hidden; 
    }
    .header-banner-icon {
        position: absolute;
        right: -2%;
        top: 50%;
        transform: translateY(-50%);
        font-size: 10rem;
        color: #ffffff;
        opacity: 0.15; 
        pointer-events: none;
        z-index: 1;
    }
    .header-content {
        position: relative;
        z-index: 2;
    }

    .table-dense {
        width: 100%;
        border-collapse: collapse;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
    }
    .table-dense thead {
        background-color: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-dense thead th {
        color: #475569;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 15px;
        font-weight: 700;
        vertical-align: middle;
    }
    .table-dense td {
        padding: 10px 15px;
        vertical-align: middle;
        color: #334155;
        font-size: 0.85rem;
    }

    .log-payload-box {
        background-color: #f8fafc;
        color: #334155;
        border: 1px solid #e2e8f0;
        padding: 15px;
        border-radius: 8px;
        font-size: 0.85rem;
        max-height: 350px;
        overflow-y: auto;
    }
    
    .log-list {
        list-style-type: none;
        padding-left: 0;
        margin-bottom: 0;
    }
    
    .log-list li {
        margin-bottom: 6px;
        padding-bottom: 6px;
        border-bottom: 1px dashed #cbd5e1;
    }
    
    .log-list li:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
</style>
@endpush

@section('content')

<div class="header-banner bg-secondary d-flex justify-content-between align-items-center shadow-sm">
    <i class="fa-solid fa-server header-banner-icon"></i>
    
    <div class="header-content">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left me-2"></i> System Logs & Audit Trail</h4>
        <p class="mb-0 text-white-50 small">Pusat pemantauan seluruh riwayat aktivitas, manipulasi data, dan rekam jejak pengguna.</p>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('sislogs.index') }}" class="d-flex align-items-center w-100">
        <div class="me-3 d-flex align-items-center">
            <span class="text-muted small me-2 fw-semibold">Tampilkan:</span>
            <select name="limit" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="width: 80px; border-radius: 6px;">
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $limit == 100 ? 'selected' : '' }}>100</option>
                <option value="all" {{ $limit == 'all' ? 'selected' : '' }}>ALL</option>
            </select>
        </div>
        
        <div class="me-3">
            <select name="action" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="border-radius: 6px; min-width: 180px;">
                <option value="">-- Semua Aksi --</option>
                @foreach($availableActions as $act)
                    <option value="{{ $act }}" {{ $action == $act ? 'selected' : '' }}>{{ strtoupper($act) }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-grow-1"></div>

        <div class="input-group input-group-sm shadow-sm" style="width: 300px; border-radius: 6px; overflow: hidden;">
            <input type="text" name="search" class="form-control border-0 px-3 py-2" placeholder="Cari user, tabel, atau IP..." value="{{ $search }}">
            <button class="btn btn-white border-0 bg-white px-3" type="submit">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
            </button>
        </div>
    </form>
</div>

<div class="table-responsive shadow-sm" style="border-radius: 8px;">
    <table class="table-dense">
        <thead>
            <tr>
                <th width="15%">Waktu Kejadian</th>
                <th width="15%">Pengguna</th>
                <th width="10%" class="text-center">Aksi</th>
                <th width="15%">Modul / Tabel</th>
                <th width="10%" class="text-center">Record ID</th>
                <th width="15%">Informasi IP</th>
                <th width="10%" class="text-center">Detail</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
            <tr>
                <td>
                    <span class="fw-bold">{{ $log->created_at->format('d M Y') }}</span><br>
                    <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                </td>
                <td>
                    <span class="fw-bold text-dark">{{ $log->username ?? 'Sistem' }}</span>
                </td>
                <td class="text-center">
                    @if(strtolower($log->action) == 'created' || strtolower($log->action) == 'import')
                        <span class="badge bg-success px-2 py-1"><i class="fa-solid fa-plus me-1"></i> {{ strtoupper($log->action) }}</span>
                    @elseif(strtolower($log->action) == 'updated')
                        <span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-pen me-1"></i> {{ strtoupper($log->action) }}</span>
                    @elseif(strtolower($log->action) == 'deleted')
                        <span class="badge bg-danger px-2 py-1"><i class="fa-solid fa-trash me-1"></i> {{ strtoupper($log->action) }}</span>
                    @else
                        <span class="badge bg-secondary px-2 py-1">{{ strtoupper($log->action) }}</span>
                    @endif
                </td>
                <td class="fw-semibold text-secondary">
                    {{ strtoupper($log->table_name) }}
                </td>
                <td class="text-center fw-bold">
                    {{ $log->record_id ?? '-' }}
                </td>
                <td>
                    <small class="d-block fw-bold"><i class="fa-solid fa-network-wired me-1 text-muted"></i> {{ $log->ip_address ?? '-' }}</small>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-light border shadow-none" data-bs-toggle="modal" data-bs-target="#modalLogDetail{{ $log->id }}">
                        <i class="fa-solid fa-eye text-theme"></i> Rincian
                    </button>
                </td>
            </tr>

            <!-- Modal Detail Log -->
            <div class="modal fade" id="modalLogDetail{{ $log->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-light border-0 py-3">
                            <h6 class="modal-title fw-bold text-theme m-0"><i class="fa-solid fa-file-code me-2"></i> Rincian Aktivitas Data</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 bg-white text-start">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-bold text-muted small text-uppercase">Aktor</label>
                                    <div class="fw-bold">{{ $log->username ?? 'Sistem' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold text-muted small text-uppercase">Browser / Perangkat</label>
                                    <div class="small">{{ $log->user_agent ?? '-' }}</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <!-- DATA LAMA -->
                                @if(!empty($log->old_values))
                                <div class="col-md-6 mb-3">
                                    <label class="fw-bold text-danger small text-uppercase mb-2"><i class="fa-solid fa-minus-circle me-1"></i> Data Sebelumnya (Dihapus/Diubah)</label>
                                    <div class="log-payload-box border-danger border-opacity-25 bg-danger bg-opacity-10">
                                        <ul class="log-list text-danger">
                                            @foreach($log->old_values as $key => $val)
                                                <li>
                                                    <strong>{{ $key }}:</strong> 
                                                    {{ is_array($val) ? json_encode($val) : $val }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @endif

                                <!-- DATA BARU -->
                                @if(!empty($log->new_values))
                                <div class="col-md-{{ empty($log->old_values) ? '12' : '6' }} mb-3">
                                    <label class="fw-bold text-success small text-uppercase mb-2"><i class="fa-solid fa-plus-circle me-1"></i> Data Baru / Ditambahkan</label>
                                    <div class="log-payload-box border-success border-opacity-25 bg-success bg-opacity-10">
                                        <ul class="log-list text-success">
                                            @foreach($log->new_values as $key => $val)
                                                <li>
                                                    <strong>{{ $key }}:</strong> 
                                                    {{ is_array($val) ? json_encode($val) : $val }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer border-0 bg-light py-2">
                            <button type="button" class="btn btn-sm btn-dark px-4 fw-bold" data-bs-dismiss="modal">TUTUP</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Modal -->
            @empty
            <tr>
                <td colspan="7" class="text-center py-5 text-muted bg-white">
                    <i class="fa-solid fa-clock-rotate-left fs-2 mb-2 opacity-25"></i>
                    <p class="mb-0 small">Belum ada rekam jejak aktivitas yang tercatat di sistem.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        @if($limit === 'all')
            Menampilkan seluruh <strong>{{ $logs->total() }}</strong> log aktivitas
        @else
            Menampilkan <strong>{{ $logs->firstItem() ?? 0 }}</strong> sampai <strong>{{ $logs->lastItem() ?? 0 }}</strong> dari <strong>{{ $logs->total() }}</strong> log aktivitas
        @endif
    </div>
    <div>
        @if($limit !== 'all')
            {{ $logs->links('pagination::bootstrap-5') }}
        @endif
    </div>
</div>

@endsection
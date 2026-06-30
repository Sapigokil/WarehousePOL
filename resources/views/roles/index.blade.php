@extends('layouts.app')
@section('title', 'Role & Permission')

@push('styles')
<style>
    /* 1. Header Banner Styling */
    .header-banner {
        background: linear-gradient(135deg, #01a9ac 0%, #01898c 100%);
        border-radius: 12px;
        padding: 30px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(1, 169, 172, 0.2);
        position: relative;
        overflow: hidden;
    }
    
    .header-banner-icon {
        position: absolute;
        right: -2%;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14rem;
        color: #ffffff;
        opacity: 0.15;
        pointer-events: none;
        z-index: 1;
    }

    .header-content {
        position: relative;
        z-index: 2;
    }

    /* 2. Desain Matriks Padat (Gaya Excel) */
    .table-excel {
        width: 100%;
        border-collapse: collapse; 
        background-color: #ffffff;
        font-size: 0.85rem; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .table-excel th, .table-excel td {
        border: 1px solid #d3d3d3; 
        padding: 6px 10px; 
        vertical-align: middle;
    }
    
    /* Sticky Header */
    .table-excel thead th {
        position: sticky;
        top: 70px; 
        z-index: 10;
        background-color: #f1f3f5; 
        color: #333;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: inset 0 -1px 0 #d3d3d3, inset 0 1px 0 #d3d3d3; 
    }
    
    .table-excel tbody tr:hover {
        background-color: #e8f0fe; 
    }
    
    .table-excel tbody tr:nth-child(even) {
        background-color: #f8f9fa; 
    }

    /* Styling Visual Checkbox Padat */
    .matrix-checkbox {
        width: 1rem;
        height: 1rem;
        cursor: pointer;
        margin: 0;
        display: block;
    }
    
    /* Penanda khusus untuk kolom readonly administrator */
    .col-readonly {
        background-color: #f1f3f5 !important; 
    }

    /* Baris Group Header Spesifik */
    .table-excel tr.group-header:hover {
        background-color: #d9e2ec !important; /* Matikan efek hover biru pada baris group */
    }
    .table-excel tr.group-header td {
        background-color: #d9e2ec !important;
        color: #102a43 !important;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding-top: 10px !important;
        padding-bottom: 10px !important;
    }
</style>
@endpush

@section('content')

<div class="header-banner d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-shield-halved header-banner-icon"></i>
    
    <div class="header-content">
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-user-lock me-2"></i> Matriks Hak Akses</h3>
        <p class="mb-0 text-white-50">Atur izin secara detail untuk setiap posisi kerja atau role di sistem.</p>
    </div>
    <div class="header-content">
        <button class="btn btn-light fw-bold text-primary shadow-sm" style="color: #01a9ac !important;" data-bs-toggle="modal" data-bs-target="#modalAddRole">
            <i class="fa-solid fa-plus me-1"></i> Tambah Role Baru
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ route('roles.sync') }}" method="POST">
    @csrf
    <div class="table-responsive" style="overflow-x: visible;">
        <table class="table-excel text-center">
            <thead>
                <tr>
                    <th class="text-start" width="30%">
                        <i class="fa-solid fa-key me-1"></i> Nama Permission
                    </th>
                    @foreach($roles as $role)
                        <th>
                            {{ strtoupper(str_replace('_', ' ', $role->name)) }}
                            @if(!in_array($role->name, ['administrator']))
                                <br>
                                <button type="button" class="btn btn-link text-danger p-0 mt-1" style="font-size: 0.7rem; text-decoration: none;" onclick="confirmDeleteRole({{ $role->id }})">
                                    <i class="fa-solid fa-trash me-1"></i>Hapus
                                </button>
                            @else
                                <br><span class="badge bg-secondary mt-1" style="font-size: 0.6rem;">SISTEM INTI</span>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php
                    // Mengelompokkan data berdasarkan kata pertama dari nama permission
                    $groupedPermissions = $permissions->groupBy(function($perm) {
                        return explode(' ', $perm->name)[0];
                    });
                @endphp

                @foreach($groupedPermissions as $groupName => $perms)
                    <tr class="group-header">
                        <td colspan="{{ count($roles) + 1 }}" class="text-start">
                            <i class="fa-solid fa-layer-group me-2"></i> MODUL: {{ $groupName }}
                        </td>
                    </tr>
                    
                    @foreach($perms as $permission)
                    <tr>
                        <td class="text-start fw-bold" style="color: #444; padding-left: 25px;">
                            <i class="fa-solid fa-angle-right me-2 text-muted" style="font-size: 0.7rem;"></i>{{ $permission->name }}
                            @if($permission->label)
                                <br><small class="text-muted fw-normal" style="font-size: 0.7rem; margin-left: 17px;">{{ $permission->label }}</small>
                            @endif
                        </td>
                        
                        @foreach($roles as $role)
                            <td class="{{ $role->name === 'administrator' ? 'col-readonly' : '' }}">
                                @if($role->name === 'administrator')
                                    <div class="d-flex justify-content-center">
                                        <input class="form-check-input matrix-checkbox" type="checkbox" checked disabled style="cursor: not-allowed; opacity: 0.5;">
                                    </div>
                                @else
                                    <div class="d-flex justify-content-center">
                                        <input class="form-check-input matrix-checkbox border-secondary" type="checkbox" name="permissions[{{ $role->id }}][]" value="{{ $permission->name }}" {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4 mb-5 text-end">
        <button type="submit" class="btn text-white px-5 py-2 fw-bold shadow-sm" style="background-color: #01a9ac; border-radius: 8px;">
            <i class="fa-solid fa-floppy-disk me-2"></i> Simpan Matriks
        </button>
    </div>
</form>

<div class="modal fade" id="modalAddRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog border-0">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold" style="color: #01a9ac;"><i class="fa-solid fa-plus-circle me-2"></i> Tambah Role Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold uppercase">Nama Role</label>
                        <input type="text" name="name" class="form-control bg-light border-0" placeholder="Contoh: Supervisor Gudang" required>
                        <div class="form-text text-muted mt-2 small">Sistem akan secara otomatis mengubahnya menjadi format standar (huruf kecil dan spasi diubah menjadi underscore).</div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="submit" class="btn text-white px-4 fw-bold shadow-sm" style="background-color: #01a9ac; border-radius: 8px;">Simpan Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="delete-role-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
    function confirmDeleteRole(roleId) {
        if (confirm('Yakin ingin menghapus Role ini secara permanen? Semua pengguna yang memiliki Role ini akan kehilangan hak aksesnya.')) {
            let form = document.getElementById('delete-role-form');
            form.action = '/roles/' + roleId;
            form.submit();
        }
    }
</script>
@endpush
@endsection
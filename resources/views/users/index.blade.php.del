@extends('layouts.app')
@section('title', 'Daftar Pengguna')

@push('styles')
<style>
    /* 1. Pengaturan Master Color Background Layout */
    body {
        background-color: #f4f7fa !important; /* Warna abu-abu kebiruan lembut untuk memisahkan bg layout dengan data */
    }

    /* 2. Header Banner Styling (Dengan Watermark Icon) */
    .header-banner {
        background: linear-gradient(135deg, #01a9ac 0%, #01898c 100%);
        border-radius: 12px;
        padding: 30px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(1, 169, 172, 0.2);
        position: relative; /* Wajib untuk menahan icon watermark */
        overflow: hidden; /* Agar bagian icon yang besar terpotong rapi */
    }
    
    /* Ikon Raksasa sebagai Penghias Banner */
    .header-banner-icon {
        position: absolute;
        right: 0%;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12rem;
        color: #ffffff;
        opacity: 0.15; /* Transparansi untuk efek watermark */
        pointer-events: none;
        z-index: 1;
    }

    /* Memastikan teks dan tombol tetap bisa diklik/di atas icon */
    .header-content {
        position: relative;
        z-index: 2;
    }

    /* 3. Desain Opsi 3: Soft Striped Card */
    .table-modern {
        border-collapse: separate;
        border-spacing: 0 8px; /* Memberikan jarak atas-bawah antar baris */
        width: 100%;
    }
    .table-modern thead th {
        border-bottom: none;
        color: #888;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 10px 15px;
        font-weight: 700;
    }
    .table-modern tbody tr {
        box-shadow: 0 2px 10px rgba(0,0,0,0.03); /* Bayangan sangat halus */
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .table-modern tbody tr:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    /* Pewarnaan Belang Klasik-Modern */
    .table-modern tbody tr:nth-child(odd) td {
        background-color: #ffffff; /* Putih bersih, kontras dengan bg layout */
    }
    .table-modern tbody tr:nth-child(even) td {
        background-color: #eef4f9; /* Biru sangat pudar agar belangnya terasa lembut */
    }
    .table-modern td {
        border: none;
        padding: 15px;
        vertical-align: middle;
        color: #444;
    }
    
    /* Melengkungkan sudut pada awal dan akhir sel setiap baris */
    .table-modern td:first-child { border-radius: 8px 0 0 8px; }
    .table-modern td:last-child { border-radius: 0 8px 8px 0; }
</style>
@endpush

@section('content')

<div class="header-banner d-flex justify-content-between align-items-center">
    <i class="fa-solid fa-users-gear header-banner-icon"></i>
    
    <div class="header-content">
        <h3 class="fw-bold mb-1"><i class="fa-solid fa-users-gear me-2"></i> Manajemen Pengguna</h3>
        <p class="mb-0 text-white-50">Kelola daftar akun, tetapkan hak akses, dan pantau aktivitas pengguna sistem.</p>
    </div>
    <div class="header-content">
        <button class="btn btn-light fw-bold text-primary shadow-sm" style="color: #01a9ac !important;" data-bs-toggle="modal" data-bs-target="#modalAddUser">
            <i class="fa-solid fa-plus me-1"></i> Tambah Pengguna
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" action="{{ route('users.index') }}" class="d-flex align-items-center w-100">
        <div class="me-3 d-flex align-items-center">
            <span class="text-muted small me-2 fw-semibold">Tampilkan:</span>
            <select name="limit" class="form-select form-select-sm shadow-sm border-0 bg-white" onchange="this.form.submit()" style="width: 70px; border-radius: 6px;">
                <option value="10" {{ $limit == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ $limit == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $limit == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $limit == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>
        
        <div class="flex-grow-1"></div>

        <div class="input-group input-group-sm shadow-sm" style="width: 300px; border-radius: 6px; overflow: hidden;">
            <input type="text" name="search" class="form-control border-0 px-3" placeholder="Cari nama, username, email..." value="{{ $search }}">
            <button class="btn btn-white border-0 bg-white px-3" type="submit">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
            </button>
        </div>
    </form>
</div>

<div class="table-responsive">
    <table class="table-modern">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="30%">Nama & Email</th>
                <th width="20%">Username</th>
                <th width="25%">Role Akses</th>
                <th width="20%" class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $key => $user)
            <tr>
                <td class="text-center fw-bold text-muted">
                    {{ $users->firstItem() + $key }}
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=e0f2f1&color=01a9ac&rounded=true" alt="Avatar" width="40" height="40" class="me-3 shadow-sm">
                        <div>
                            <div class="fw-bold text-dark">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-white text-dark border shadow-sm"><i class="fa-solid fa-at text-muted me-1"></i>{{ $user->username }}</span></td>
                <td>
                    @foreach($user->roles as $role)
                        <span class="badge" style="background-color: #01a9ac;">{{ strtoupper(str_replace('_', ' ', $role->name)) }}</span>
                    @endforeach
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-white bg-white text-primary shadow-sm rounded-circle me-1" style="width: 34px; height: 34px; border: 1px solid #e0e0e0;" data-bs-toggle="modal" data-bs-target="#modalEditUser{{ $user->id }}" title="Edit">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-white bg-white text-danger shadow-sm rounded-circle" style="width: 34px; height: 34px; border: 1px solid #e0e0e0;" title="Hapus">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>

            <div class="modal fade" id="modalEditUser{{ $user->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog border-0">
                    <div class="modal-content border-0 shadow-lg">
                        <form action="{{ route('users.update', $user->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-header border-0 bg-light">
                                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Pengguna</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold uppercase">Nama Lengkap</label>
                                    <input type="text" name="name" class="form-control bg-light border-0" value="{{ $user->name }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold uppercase">Username</label>
                                    <input type="text" name="username" class="form-control bg-light border-0" value="{{ $user->username }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold uppercase">Email</label>
                                    <input type="email" name="email" class="form-control bg-light border-0" value="{{ $user->email }}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold uppercase">Password <span class="text-secondary fw-normal">(Kosongkan jika tidak diubah)</span></label>
                                    <input type="password" name="password" class="form-control bg-light border-0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold uppercase">Role Akses</label>
                                    <select name="role" class="form-select bg-light border-0" required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                {{ strtoupper(str_replace('_', ' ', $role->name)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer border-0 bg-light">
                                <button type="submit" class="btn text-white px-4 fw-bold shadow-sm" style="background-color: #01a9ac; border-radius: 8px;">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <tr>
                <td colspan="5" class="text-center py-5 text-muted bg-white" style="border-radius: 8px;">
                    <i class="fa-solid fa-folder-open fs-1 mb-3 opacity-25"></i>
                    <p class="mb-0">Data pengguna tidak ditemukan.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted small">
        Menampilkan <strong>{{ $users->firstItem() ?? 0 }}</strong> sampai <strong>{{ $users->lastItem() ?? 0 }}</strong> dari <strong>{{ $users->total() }}</strong> total data
    </div>
    <div>
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>

<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog border-0">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold" style="color: #01a9ac;"><i class="fa-solid fa-user-plus me-2"></i> Tambah Pengguna Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold uppercase">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold uppercase">Username</label>
                        <input type="text" name="username" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold uppercase">Email</label>
                        <input type="email" name="email" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold uppercase">Password</label>
                        <input type="password" name="password" class="form-control bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold uppercase">Role Akses</label>
                        <select name="role" class="form-select bg-light border-0" required>
                            <option value="">-- Pilih Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ strtoupper(str_replace('_', ' ', $role->name)) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="submit" class="btn text-white px-4 fw-bold shadow-sm" style="background-color: #01a9ac; border-radius: 8px;">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
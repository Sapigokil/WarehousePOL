<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset Cache Permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Definisi Permission Warehouse (Ditambahkan Inbound & Outbound Edit/Delete)
        $permissions = [
            '01. Dashboard' => [
                'Dashboard Menu' => 'Akses Halaman Dashboard Utama',
            ],
            '02. Master Data' => [
                'Warehouse Menu' => 'Akses Menu Master Warehouse & Barang',
                'Warehouse Create' => 'Menambah Master Data',
                'Warehouse Edit' => 'Mengubah Master Data',
                'Warehouse Delete' => 'Menghapus Master Data',
            ],
            '03. Inbound (Masuk)' => [
                'Inbound Menu' => 'Akses Menu Barang Masuk',
                'Inbound Create' => 'Membuat Dokumen Inbound',
                'Inbound Edit' => 'Mengubah Dokumen Inbound',
                'Inbound Delete' => 'Menghapus Dokumen Inbound',
                'Inbound Approve' => 'Menyetujui Dokumen Inbound',
            ],
            '04. Outbound (Keluar)' => [
                'Outbound Menu' => 'Akses Menu Barang Keluar / Distribusi',
                'Outbound Create' => 'Membuat Dokumen Outbound',
                'Outbound Edit' => 'Mengubah Dokumen Outbound',
                'Outbound Delete' => 'Menghapus Dokumen Outbound',
                'Outbound Approve' => 'Menyetujui Dokumen Outbound',
            ],
            '05. Laporan & Analitik' => [
                'Report Menu' => 'Akses Menu Laporan & Tracking Seri',
            ],
            '06. Pengaturan Sistem' => [
                'Setting Menu' => 'Akses Pengaturan Global Sistem',
                'User Menu' => 'Akses Manajemen Pengguna, Role & Permission',
            ],
        ];

        $allPermissionNames = [];

        // 3. Masukkan Permission ke Database
        foreach ($permissions as $group => $perms) {
            foreach ($perms as $permName => $permLabel) {
                Permission::updateOrCreate(
                    ['name' => $permName],
                    [
                        'group_name' => $group, 
                        'label'      => $permLabel
                    ]
                );
                $allPermissionNames[] = $permName;
            }
        }

        // Cleanup permission usang
        Permission::whereNotIn('name', $allPermissionNames)->delete();

        // 4. Buat Roles
        $roleDev = Role::firstOrCreate(['name' => 'developer']);
        $roleDev->syncPermissions(Permission::all());

        $roleAdmin = Role::firstOrCreate(['name' => 'admin_gudang']);
        $roleAdmin->syncPermissions(Permission::all());

        $roleStaf = Role::firstOrCreate(['name' => 'staf_gudang']);
        $roleStaf->syncPermissions([
            'Dashboard Menu',
            'Warehouse Menu',
            'Inbound Menu', 'Inbound Create', 'Inbound Edit',
            'Outbound Menu', 'Outbound Create', 'Outbound Edit',
            'Report Menu'
        ]);

        // 5. Buat Akun Spesial dengan Full Authority
        $dev = User::firstOrCreate(
            ['username' => 'dev.campus'], 
            [
                'name'      => 'System Developer', 
                'email'     => 'campus@dev.id',
                'password'  => Hash::make('campussolusi26#'), 
            ]
        );
        $dev->syncRoles([$roleDev]);

        $admin = User::firstOrCreate(
            ['username' => 'admin'], 
            [
                'name'      => 'Admin Gudang', 
                'email'     => 'admin@gudang.local',
                'password'  => Hash::make('password123'), 
            ]
        );
        $admin->syncRoles([$roleAdmin]);

        $this->command->info('Warehouse Role & Permission berhasil disinkronisasi!');
    }
}
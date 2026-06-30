<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index()
    {
        // Ambil semua role KECUALI developer agar tidak tampil di UI
        $roles = Role::where('name', '!=', 'developer')->orderBy('id')->get();
        $permissions = Permission::orderBy('id')->get();

        return view('roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name'
        ]);

        // Spatie menggunakan huruf kecil dan tanpa spasi (menggunakan underscore)
        $roleName = strtolower(str_replace(' ', '_', $request->name));

        Role::create(['name' => $roleName]);

        return redirect()->route('roles.index')->with('success', 'Role baru berhasil ditambahkan ke dalam kolom.');
    }

    public function sync(Request $request)
    {
        // Data request berupa array: permissions[role_id][] = permission_name
        $permissionsData = $request->input('permissions', []);

        // Kita abaikan role developer dan administrator dari proses update ini
        // karena hak akses mereka terkunci secara otomatis
        $roles = Role::whereNotIn('name', ['developer', 'administrator'])->get();

        foreach ($roles as $role) {
            // Ambil array permission dari input untuk role ini, atau array kosong jika tidak ada centang
            $rolePermissions = isset($permissionsData[$role->id]) ? $permissionsData[$role->id] : [];
            
            // Sinkronisasi permission
            $role->syncPermissions($rolePermissions);
        }

        return redirect()->route('roles.index')->with('success', 'Matriks hak akses berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        // Proteksi role default
        if (in_array($role->name, ['developer', 'administrator'])) {
            return redirect()->route('roles.index')->with('error', 'Role inti sistem tidak dapat dihapus.');
        }

        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus dari sistem.');
    }
}
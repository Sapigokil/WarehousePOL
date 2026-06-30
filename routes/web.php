<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Area Dashboard 
Route::middleware(['auth', 'single.session', 'update.last.seen'])->group(function () {
    
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Manajemen User Group
    Route::middleware(['can:User Menu'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        
        // Rute khusus untuk menyimpan data matriks permission
        Route::post('roles/sync', [RoleController::class, 'sync'])->name('roles.sync');
        Route::resource('roles', RoleController::class)->except(['show', 'edit', 'update']);
    });
});
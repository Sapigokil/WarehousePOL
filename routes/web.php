<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\MaterialCategoryController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\InboundController;
USe App\Http\Controllers\DestinationController;
use App\Http\Controllers\ReportController;

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
    
    /* ==============================================
       UTAMA
       ============================================== */
    Route::middleware(['can:Dashboard Menu'])->group(function () {
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
    });

    /* ==============================================
       OPERASIONAL
       ============================================== */
    Route::middleware(['can:Inbound Menu'])->group(function () {
        Route::get('inbound/materials-by-category/{category_id}', [App\Http\Controllers\InboundController::class, 'getMaterialsByCategory'])->name('inbound.materials-by-category');
        Route::get('inbound/template-import', [\App\Http\Controllers\InboundController::class, 'downloadTemplate'])->name('inbound.template');
        Route::post('inbound/import-excel', [\App\Http\Controllers\InboundController::class, 'importExcel'])->name('inbound.import');
    
        Route::resource('inbound', App\Http\Controllers\InboundController::class);
        // Tambahkan di dalam route group yang sesuai
        Route::post('/warehouses/ajax-store', [\App\Http\Controllers\InboundController::class, 'storeWarehouseAjax'])->name('warehouses.ajax.store');
    });

    Route::middleware(['can:Warehouse Menu'])->group(function () {
        Route::resource('stocks', StockController::class);
    });

    Route::middleware(['can:Outbound Menu'])->group(function () {
        // AJAX Route untuk memanggil material berdasarkan kategori beserta sisa stoknya
        Route::get('outbounds/materials-by-category/{category_id}', [\App\Http\Controllers\OutboundController::class, 'getMaterialsByCategory']);

        Route::resource('outbounds', \App\Http\Controllers\OutboundController::class);
        Route::get('outbounds/{id}/print', [\App\Http\Controllers\OutboundController::class, 'print'])->name('outbounds.print');
        
    });

    /* ==============================================
       ANALITIK
       ============================================== */
    Route::middleware(['can:Report Menu'])->group(function () {
        Route::get('reports.mutation', [ReportController::class, 'mutation'])->name('reports.mutation');
        Route::get('reports.mutation/export', [ReportController::class, 'exportMutation'])->name('reports.mutation.export');

        // Diubah dari 'inbound' menjadi 'inbound-history' agar tidak bentrok
        Route::get('reports.inbound-history', [ReportController::class, 'inbound'])->name('reports.inbound-history');
        Route::get('reports.inbound-history/export', [ReportController::class, 'exportInbound'])->name('reports.inbound-history.export');
        
        // Diubah dari 'outbound' menjadi 'outbound-history' agar lebih konsisten dan aman
        Route::get('reports.outbound-history', [ReportController::class, 'outbound'])->name('reports.outbound-history');
        Route::get('reports.outbound-history/export', [ReportController::class, 'exportOutbound'])->name('reports.outbound-history.export');
        // Menu Tracking Seri
        Route::get('/tracking', [\App\Http\Controllers\TrackingController::class, 'index'])->name('tracking.index');
        Route::get('/tracking/search', [\App\Http\Controllers\TrackingController::class, 'search'])->name('tracking.search');
    });

    /* ==============================================
       SISTEM
       ============================================== */
    // Manajemen User Group
    Route::middleware(['can:User Menu'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
        
        // Rute khusus untuk menyimpan data matriks permission
        Route::post('roles/sync', [RoleController::class, 'sync'])->name('roles.sync');
        Route::resource('roles', RoleController::class)->except(['show', 'edit', 'update']);
    });

    // Pengaturan Global & Warehouse Group
    Route::middleware(['can:Setting Menu'])->group(function () {
        // Menu Pengaturan Global
    Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [\App\Http\Controllers\SettingController::class, 'update'])->name('settings.update');
        Route::post('categories/reorder', [ MaterialCategoryController::class, 'reorder' ])->name('categories.reorder');
        Route::resource('categories', MaterialCategoryController::class);
        Route::post('warehouses/reorder', [WarehouseController::class, 'reorder'])->name('warehouses.reorder');
        Route::resource('warehouses', WarehouseController::class)->except(['show']);
        Route::post('materials/reorder', [MaterialController::class, 'reorder'])->name('materials.reorder');
        Route::resource('materials', MaterialController::class)->except(['show']);
        Route::post('destinations/reorder', [\App\Http\Controllers\DestinationController::class, 'reorder'])->name('destinations.reorder');
        Route::resource('destinations', \App\Http\Controllers\DestinationController::class)->except(['show']);
    });
});
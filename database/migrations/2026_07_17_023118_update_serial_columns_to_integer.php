<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel InDetail
        Schema::table('in_details', function (Blueprint $table) {
            $table->string('sppm_serial_prefix')->nullable()->after('harga_total');
            $table->bigInteger('sppm_serial_start')->nullable()->change();
            $table->bigInteger('sppm_serial_end')->nullable()->change();
        });

        // 2. Tabel InStock (Realisasi Fisik)
        Schema::table('in_stocks', function (Blueprint $table) {
            $table->string('serial_prefix')->nullable()->after('qty_received');
            $table->bigInteger('serial_start')->nullable()->change();
            $table->bigInteger('serial_end')->nullable()->change();
        });

        // 3. Tabel Induk Stocks (Ledger)
        Schema::table('stocks', function (Blueprint $table) {
            $table->string('prefix')->nullable()->after('warehouse_id');
            $table->bigInteger('seri_awal')->nullable()->change();
            $table->bigInteger('seri_akhir')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rollback logika
    }
};
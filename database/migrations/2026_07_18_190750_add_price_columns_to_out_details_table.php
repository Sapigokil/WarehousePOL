<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('out_details', function (Blueprint $table) {
            // Menggunakan decimal agar presisi untuk perhitungan keuangan
            $table->decimal('harga_satuan', 15, 2)->default(0)->after('target_qty');
            $table->decimal('harga_total', 15, 2)->default(0)->after('harga_satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('out_details', function (Blueprint $table) {
            $table->dropColumn(['harga_satuan', 'harga_total']);
        });
    }
};
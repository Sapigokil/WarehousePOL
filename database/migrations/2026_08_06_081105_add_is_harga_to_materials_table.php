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
        Schema::table('materials', function (Blueprint $table) {
            // Menambahkan kolom is_harga dengan nilai default 0 (tidak ada harga)
            // Posisinya diletakkan setelah kolom pakai_seri agar rapi
            $table->tinyInteger('is_harga')->default(0)->after('pakai_seri')->comment('0 = Tidak pakai harga, 1 = Pakai harga');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('is_harga');
        });
    }
};
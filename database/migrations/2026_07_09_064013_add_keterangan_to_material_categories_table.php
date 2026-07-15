<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKeteranganToMaterialCategoriesTable extends Migration
{
    public function up(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            // Menambahkan kolom keterangan setelah kolom nomor_urut (atau name jika nomor_urut belum terdeteksi urutannya)
            $table->text('keterangan')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
}
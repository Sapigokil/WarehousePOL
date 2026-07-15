<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MaterialCategory;

class AddNomorUrutToMaterialCategoriesTable extends Migration
{
    public function up(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            // Menambahkan kolom nomor_urut tepat setelah kolom id
            $table->integer('nomor_urut')->after('id')->nullable();
        });

        // OTOMATISASI DATA EKSISTING:
        // Jika Anda sudah memiliki data kategori di database, kita beri nomor urut bawaan awal agar tidak kosong (null)
        $categories = MaterialCategory::orderBy('id', 'asc')->get();
        $urut = 1;
        foreach ($categories as $category) {
            $category->update(['nomor_urut' => $urut]);
            $urut++;
        }
    }

    public function down(): void
    {
        Schema::table('material_categories', function (Blueprint $table) {
            $table->dropColumn('nomor_urut');
        });
    }
}
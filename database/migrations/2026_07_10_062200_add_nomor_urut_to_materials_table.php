<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Material;

class AddNomorUrutToMaterialsTable extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            // Menambahkan kolom nomor_urut setelah kolom parent_id
            $table->integer('nomor_urut')->after('parent_id')->nullable()->index();
        });

        // OTOMATISASI DATA EKSISTING:
        // Mengisi nomor urut induk yang sudah ada di database agar terisi berurutan per kategori
        $categories = DB::table('material_categories')->pluck('id');
        
        foreach ($categories as $categoryId) {
            $materials = Material::where('material_category_id', $categoryId)
                ->whereNull('parent_id')
                ->orderBy('id', 'asc')
                ->get();
                
            $urut = 1;
            foreach ($materials as $material) {
                $material->update(['nomor_urut' => $urut]);
                $urut++;
            }
        }
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('nomor_urut');
        });
    }
}
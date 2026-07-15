<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialsTable extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            
            // Kolom untuk menampung ID Induk (Nullable karena barang tunggal atau barang induk tidak memiliki parent)
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            
            $table->string('name');
            $table->unsignedBigInteger('material_category_id');
            $table->string('satuan');
            $table->integer('minimal_stok')->default(0);
            $table->boolean('pakai_seri')->default(false); 
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Proteksi integritas data ke tabel kategori
            $table->foreign('material_category_id')
                ->references('id')
                ->on('material_categories')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
}
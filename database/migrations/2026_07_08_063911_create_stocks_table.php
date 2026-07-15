<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStocksTable extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('no_surat_masuk')->index();
            $table->date('tgl_masuk');
            
            // Hanya menyimpan ID berupa angka, tanpa ikatan relasi Foreign Key
            $table->unsignedBigInteger('material_id')->index();
            $table->unsignedBigInteger('warehouse_id')->index();
            
            $table->string('seri_awal')->nullable();
            $table->string('seri_akhir')->nullable();
            $table->integer('qty');
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->string('status')->default('Tersedia'); 
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
}
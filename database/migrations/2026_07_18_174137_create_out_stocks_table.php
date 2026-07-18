<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('out_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('out_log_id')->constrained('out_logs')->onDelete('cascade');
            // Mereferensikan ke tabel stocks (stok fisik yang dipotong)
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('restrict'); 
            $table->integer('qty_keluar');
            $table->string('prefix')->nullable();
            $table->bigInteger('seri_awal')->nullable();
            $table->bigInteger('seri_akhir')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_stocks');
    }
};
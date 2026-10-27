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
        Schema::create('stock_buku', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Master Material
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            
            // Relasi ke Inbound (Bisa NULL jika ini adalah pengeluaran Parentless/Orphan)
            $table->unsignedBigInteger('in_sppm_id')->nullable();
            $table->unsignedBigInteger('in_stock_id')->nullable();
            
            // Relasi ke Outbound (Bisa NULL jika ini adalah Sisa Tersedia atau Inbound Tanpa Seri)
            $table->unsignedBigInteger('out_sppm_id')->nullable();
            $table->unsignedBigInteger('out_stock_id')->nullable();
            
            // Tipe Baris (Untuk kemudahan filter di halaman Detail)
            // Value: 'AVAILABLE', 'USED', 'UNSERIALIZED_IN', 'ORPHAN_OUT'
            $table->string('tipe_baris', 50); 
            
            // Data Seri & Qty
            $table->string('prefix')->nullable();
            $table->bigInteger('seri_awal')->nullable();
            $table->bigInteger('seri_akhir')->nullable();
            $table->integer('qty')->default(0);
            
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Mendefinisikan Foreign Key (Opsional tapi disarankan agar data konsisten)
            $table->foreign('in_sppm_id')->references('id')->on('in_sppms')->nullOnDelete();
            $table->foreign('in_stock_id')->references('id')->on('in_stocks')->nullOnDelete();
            $table->foreign('out_sppm_id')->references('id')->on('out_sppms')->nullOnDelete();
            $table->foreign('out_stock_id')->references('id')->on('out_stocks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_buku');
    }
};
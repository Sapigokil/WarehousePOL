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
        Schema::create('display_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')
                  ->constrained('materials')
                  ->onDelete('cascade');
            
            $table->string('custom_label')->nullable(); // Menggantikan nama material jika diisi
            $table->integer('nomor_urut')->default(0);  // Untuk urutan khusus di monitor
            $table->boolean('is_active')->default(true); // Opsi non-aktifkan sementara
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('display_materials');
    }
};
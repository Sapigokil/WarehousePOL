<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_sppms', function (Blueprint $table) {
            $table->id();
            $table->string('sppm_no')->unique(); // Nomor surat tercatat
            $table->date('sppm_date'); // Tanggal terbit surat
            $table->foreignId('material_category_id')->constrained('material_categories')->onDelete('restrict');
            $table->enum('status', ['pending', 'partial', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_sppms');
    }
};
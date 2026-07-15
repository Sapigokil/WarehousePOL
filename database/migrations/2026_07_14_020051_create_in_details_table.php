<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('in_sppm_id')->constrained('in_sppms')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('restrict');
            $table->integer('target_qty'); // Jumlah yang seharusnya dikirim menurut surat
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_details');
    }
};
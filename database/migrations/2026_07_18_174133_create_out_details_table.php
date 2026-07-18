<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('out_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('out_sppm_id')->constrained('out_sppms')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('restrict');
            $table->integer('target_qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_details');
    }
};
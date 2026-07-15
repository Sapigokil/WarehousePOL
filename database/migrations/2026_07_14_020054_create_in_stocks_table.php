<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('in_log_id')->constrained('in_logs')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('materials')->onDelete('restrict');
            $table->integer('qty_received'); // Kuantitas asli yang datang di gelombang ini
            $table->string('serial_start')->nullable(); // Rentang awal seri (opsional)
            $table->string('serial_end')->nullable(); // Rentang akhir seri (opsional)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_stocks');
    }
};
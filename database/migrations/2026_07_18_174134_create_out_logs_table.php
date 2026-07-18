<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('out_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('out_sppm_id')->constrained('out_sppms')->onDelete('cascade');
            $table->integer('batch_number');
            $table->date('tgl_keluar');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_logs');
    }
};
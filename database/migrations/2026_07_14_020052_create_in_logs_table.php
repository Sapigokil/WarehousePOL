<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('in_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('in_sppm_id')->constrained('in_sppms')->onDelete('cascade');
            $table->integer('batch_number'); // Gelombang ke-1, ke-2, dst.
            $table->date('receive_date'); // Tanggal fisik paket tiba
            $table->string('receiver_name')->nullable(); // Nama penerima paket
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_logs');
    }
};
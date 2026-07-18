<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('out_sppms', function (Blueprint $table) {
            $table->id();
            $table->string('sppm_no')->unique();
            $table->date('sppm_date');
            $table->foreignId('destination_id')->constrained('destinations')->onDelete('restrict');
            $table->enum('status', ['pending', 'partial', 'completed'])->default('pending');
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_sppms');
    }
};
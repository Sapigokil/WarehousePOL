<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_adjustments', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->string('tab_type'); // 'tnkb' atau 'sbst'
            $table->string('bucket_key'); // Target spesifik, misal: 'tnkb_non_ev_R2' atau 'sbst_15'
            $table->string('transaction_type'); // 'in' (Penerimaan) atau 'out' (Pendistribusian)
            $table->integer('qty_adjustment'); // Bisa positif (menambah) atau negatif (mengurangi)
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_adjustments');
    }
};
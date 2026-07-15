<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_details', function (Blueprint $table) {
            // Mengubah ke Decimal (panjang 15 digit, 2 desimal di belakang koma)
            $table->decimal('harga_satuan', 15, 2)->default(0)->change();
            $table->decimal('harga_total', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('in_details', function (Blueprint $table) {
            $table->bigInteger('harga_satuan')->default(0)->change();
            $table->bigInteger('harga_total')->default(0)->change();
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_details', function (Blueprint $table) {
            $table->string('qty_huruf')->nullable()->after('target_qty');
            $table->bigInteger('harga_satuan')->default(0)->after('qty_huruf');
            $table->bigInteger('harga_total')->default(0)->after('harga_satuan');
            $table->string('sppm_serial_start')->nullable()->after('harga_total');
            $table->string('sppm_serial_end')->nullable()->after('sppm_serial_start');
        });
    }

    public function down(): void
    {
        Schema::table('in_details', function (Blueprint $table) {
            $table->dropColumn(['qty_huruf', 'harga_satuan', 'harga_total', 'sppm_serial_start', 'sppm_serial_end']);
        });
    }
};
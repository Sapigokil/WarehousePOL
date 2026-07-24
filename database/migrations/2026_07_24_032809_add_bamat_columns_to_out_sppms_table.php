<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('out_sppms', function (Blueprint $table) {
            $table->string('nama_bamat')->nullable()->after('keterangan');
            $table->string('pangkat')->nullable()->after('nama_bamat');
            $table->string('nrp')->nullable()->after('pangkat');
            $table->string('jabatan')->nullable()->after('nrp');
        });
    }

    public function down(): void
    {
        Schema::table('out_sppms', function (Blueprint $table) {
            $table->dropColumn(['nama_bamat', 'pangkat', 'nrp', 'jabatan']);
        });
    }
};
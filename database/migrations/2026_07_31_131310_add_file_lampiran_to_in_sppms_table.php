<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('in_sppms', function (Blueprint $table) {
            $table->string('file_lampiran')->nullable()->after('notes')->comment('Path lokasi file gambar/pdf lampiran SPPM');
        });
    }

    public function down()
    {
        Schema::table('in_sppms', function (Blueprint $table) {
            $table->dropColumn('file_lampiran');
        });
    }
};
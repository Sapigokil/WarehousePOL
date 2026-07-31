<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('materials', function (Blueprint $table) {
            // Hapus kolom toggle yang lama jika sudah terlanjur ada
            if (Schema::hasColumn('materials', 'sbst_rpt')) {
                $table->dropColumn('sbst_rpt');
            }
            // Tambahkan kolom teks judul
            $table->string('sbst_judul')->nullable()->comment('Kosong: Abaikan, Isi: Tampil di Laporan SBST dengan Judul Ini');
        });
    }

    public function down()
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('sbst_judul');
            $table->boolean('sbst_rpt')->default(0);
        });
    }
};
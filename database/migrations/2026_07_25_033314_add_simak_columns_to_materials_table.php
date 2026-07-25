<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->boolean('is_simak')->default(0)->after('ismain'); // Penanda tampil di SIMAK
            $table->string('simak_label')->nullable()->after('is_simak'); // Nama Header SIMAK
            $table->integer('simak_urut')->nullable()->after('simak_label'); // Urutan Kolom Kiri ke Kanan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            //
        });
    }
};

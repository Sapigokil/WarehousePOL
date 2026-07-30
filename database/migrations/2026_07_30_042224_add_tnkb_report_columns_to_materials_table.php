<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->tinyInteger('tnkb_rpt')->nullable()->default(0)->comment('0/Null: Skip, 1: TNKB, 2: TCKB');
            $table->string('tnkb_r', 5)->nullable()->comment('R2 atau R4');
            $table->boolean('tnkb_ev')->default(0)->comment('0: Non Listrik, 1: Listrik');
        });
    }

    public function down()
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['tnkb_rpt', 'tnkb_r', 'tnkb_ev']);
        });
    }
};
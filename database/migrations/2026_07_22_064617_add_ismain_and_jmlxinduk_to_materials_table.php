<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->integer('ismain')->default(0)->after('pakai_seri');
            $table->integer('jmlxinduk')->default(0)->after('ismain');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['ismain', 'jmlxinduk']);
        });
    }
};
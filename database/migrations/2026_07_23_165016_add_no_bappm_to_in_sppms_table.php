<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('in_sppms', function (Blueprint $table) {
            $table->string('no_bappm')->nullable()->after('sppm_date');
        });
    }

    public function down(): void
    {
        Schema::table('in_sppms', function (Blueprint $table) {
            $table->dropColumn('no_bappm');
        });
    }
};
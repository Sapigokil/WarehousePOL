<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterialCategoriesTable extends Migration
{
    public function up(): void
    {
        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps(); // Menyediakan created_at dan updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_categories');
    }
}
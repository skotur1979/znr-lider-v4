<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_catalog_items', function (Blueprint $table) {
            $table->id();

            $table->string('waste_code', 20)->unique();
            $table->string('name');
            $table->boolean('is_hazardous')->default(false);
            $table->string('record_mark', 20)->nullable(); // npr. N, V1, V2...
            $table->timestamps();

            $table->index('name');
            $table->index('is_hazardous');
            $table->index('record_mark');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_catalog_items');
    }
};
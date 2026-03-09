<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_types', function (Blueprint $table) {
            $table->id();

            $table->string('waste_code', 20);
            $table->string('name');
            $table->boolean('is_hazardous')->default(false);

            $table->softDeletes();
            $table->timestamps();

            $table->unique('waste_code');
            $table->index('name');
            $table->index('is_hazardous');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_types');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Zona 1, Zona 2...
            $table->unsignedInteger('sort_order')->default(0);
            $table->decimal('total_points', 8, 2)->nullable();
            $table->decimal('max_points', 8, 2)->nullable();
            $table->decimal('percentage', 6, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['inspection_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_zones');
    }
};
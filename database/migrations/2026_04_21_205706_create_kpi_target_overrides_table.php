<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_target_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained('kpis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('warning_offset', 15, 4)->nullable();
            $table->timestamps();

            $table->unique(['kpi_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_target_overrides');
    }
};
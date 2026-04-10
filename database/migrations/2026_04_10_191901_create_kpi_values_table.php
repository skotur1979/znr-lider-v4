<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('kpi_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            $table->decimal('value', 18, 4)->nullable();

            $table->boolean('auto_generated')->default(false);
            $table->string('source_label')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['kpi_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_values');
    }
};
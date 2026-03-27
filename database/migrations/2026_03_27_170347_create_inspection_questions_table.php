<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inspection_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inspection_zone_id')
                ->constrained('inspection_zones')
                ->cascadeOnDelete();

            $table->string('section')->nullable();

            $table->text('question');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_questions');
    }
};
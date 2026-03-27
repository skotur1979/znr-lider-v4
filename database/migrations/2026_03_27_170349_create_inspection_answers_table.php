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
        Schema::create('inspection_answers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inspection_zone_id')
                ->constrained('inspection_zones')
                ->cascadeOnDelete();

            $table->foreignId('inspection_question_id')
                ->constrained('inspection_questions')
                ->cascadeOnDelete();

            $table->integer('score')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_answers');
    }
};
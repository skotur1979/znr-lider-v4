<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_template_questions', function (Blueprint $table) {
            $table->id();
            $table->string('section'); // sortiranje, slaganje, sjaj...
            $table->string('code')->nullable();
            $table->text('question');
            $table->unsignedTinyInteger('max_score')->default(5);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_template_questions');
    }
};
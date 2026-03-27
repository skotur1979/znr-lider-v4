<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_zone_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_template_question_id')->constrained()->cascadeOnDelete();

            $table->unsignedTinyInteger('score')->nullable(); // 0-5
            $table->text('note')->nullable();
            $table->string('photo_path')->nullable();

            $table->boolean('action_required')->default(false);
            $table->string('responsible_person')->nullable();
            $table->date('due_date')->nullable();

            $table->string('workflow_status')->default('open'); // open, in_progress, closed, rejected...
            $table->string('finding_status')->default('recommendation'); // ok, recommendation, noncompliance, critical
            $table->foreignId('observation_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->unique(['inspection_zone_id', 'inspection_template_question_id'], 'zone_question_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_zone_answers');
    }
};
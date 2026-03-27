<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspection_findings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();

            $table->string('category')->nullable();
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('finding_status')->default('ok');
            // ok, recommendation, noncompliance, critical

            $table->string('workflow_status')->default('open');
            // open, in_progress, resolved_no_action, converted_to_observation, rejected

            $table->boolean('action_required')->default(false);

            $table->string('five_s_section')->nullable();
            // sort, set_in_order, shine, standardize, sustain, safety

            $table->unsignedTinyInteger('score_value')->nullable(); // 0-5

            $table->string('responsible_person')->nullable();
            $table->date('due_date')->nullable();

            $table->string('photo_path')->nullable();

            $table->foreignId('observation_id')->nullable()->constrained('observations')->nullOnDelete();

            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_findings');
    }
};
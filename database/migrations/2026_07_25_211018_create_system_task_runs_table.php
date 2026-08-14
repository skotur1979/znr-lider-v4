<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_task_runs', function (Blueprint $table) {
            $table->id();

            $table->string('task_key')->unique();
            $table->string('task_name');

            $table->string('status')->default('never_run');

            $table->timestamp('last_started_at')->nullable();
            $table->timestamp('last_finished_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failed_at')->nullable();

            $table->unsignedBigInteger('processed_count')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();

            $table->text('message')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_task_runs');
    }
};

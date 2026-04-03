<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title', 120);
            $table->text('description')->nullable();

            $table->date('due_date');
            $table->boolean('is_done')->default(false);
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'due_date']);
            $table->index(['user_id', 'is_done']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_tasks');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->date('log_date')->nullable();
            $table->string('title')->nullable();
            $table->longText('note');

            $table->string('location')->nullable();

            $table->string('type')->default('note');
            // note, task, observation

            $table->string('status')->default('recorded');
            // recorded, converted, archived

            $table->json('attachments')->nullable();

            $table->nullableMorphs('converted');
            // converted_type, converted_id

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'log_date']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_logs');
    }
};
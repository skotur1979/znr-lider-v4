<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('module')->nullable();
            $table->string('action')->nullable();
            $table->string('record_type')->nullable();
            $table->unsignedBigInteger('record_id')->nullable();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('url')->nullable();
            $table->string('ip_address')->nullable();

            $table->timestamps();

            $table->index(['owner_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['module', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
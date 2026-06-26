<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_alcohol_tests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('test_date');
            $table->string('result', 50)->nullable();
            $table->string('tested_by')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->index(['employee_id', 'test_date']);
            $table->index(['user_id', 'test_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_alcohol_tests');
    }
};
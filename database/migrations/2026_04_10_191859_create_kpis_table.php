<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpis', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('category')->default('ZNR');
            $table->string('unit')->nullable();

            $table->decimal('target_value', 18, 4)->nullable();
            $table->decimal('warning_offset', 18, 4)->nullable();

            $table->enum('direction', ['lower_better', 'higher_better', 'target_value'])->default('lower_better');
            $table->enum('calculation_type', ['manual', 'automatic', 'formula'])->default('manual');

            $table->string('source_key')->nullable();
            $table->text('formula_text')->nullable();
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_dashboard')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpis');
    }
};
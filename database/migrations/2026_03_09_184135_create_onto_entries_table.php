<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onto_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('onto_record_id')
                ->constrained('onto_records')
                ->cascadeOnDelete();

            $table->unsignedInteger('entry_no')->default(1);
            $table->date('entry_date');

            $table->string('entry_type', 20); // input / output
            $table->decimal('input_kg', 12, 2)->default(0);
            $table->decimal('output_kg', 12, 2)->default(0);

            $table->string('method', 100)->nullable();
            $table->decimal('balance_after_kg', 12, 2)->default(0);

            $table->text('note')->nullable();

            $table->foreignId('waste_tracking_form_id')
                ->nullable()
                ->constrained('waste_tracking_forms')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->index('entry_no');
            $table->index('entry_date');
            $table->index('entry_type');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onto_entries');
    }
};

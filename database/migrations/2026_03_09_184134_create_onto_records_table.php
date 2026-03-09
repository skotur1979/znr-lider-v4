<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onto_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('waste_organization_id')
                ->constrained('waste_organizations')
                ->cascadeOnDelete();

            $table->foreignId('waste_organization_location_id')
                ->constrained('waste_organization_locations')
                ->cascadeOnDelete();

            $table->foreignId('waste_type_id')
                ->constrained('waste_types')
                ->cascadeOnDelete();

            $table->year('year');
            $table->string('responsible_person')->nullable();
            $table->date('opening_date')->nullable();
            $table->date('closing_date')->nullable();

            $table->decimal('current_balance_kg', 12, 2)->default(0);
            $table->boolean('is_closed')->default(false);

            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('year');
            $table->index('is_closed');
            $table->index('current_balance_kg');

            $table->unique([
                'waste_organization_location_id',
                'waste_type_id',
                'year',
            ], 'onto_unique_location_waste_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onto_records');
    }
};

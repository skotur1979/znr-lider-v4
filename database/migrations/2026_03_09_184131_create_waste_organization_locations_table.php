<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_organization_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('waste_organization_id')
                ->constrained('waste_organizations')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('unit_code', 20)->nullable();
            $table->string('internal_code', 20)->nullable();
            $table->string('address')->nullable();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->index('name');
            $table->index('unit_code');
            $table->index('internal_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_organization_locations');
    }
};


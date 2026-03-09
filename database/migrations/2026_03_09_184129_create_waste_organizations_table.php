<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('company_name');
            $table->string('oib', 20)->nullable();
            $table->string('nkd_code', 50)->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_details')->nullable();
            $table->string('registered_office')->nullable();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->index('company_name');
            $table->index('oib');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_organizations');
    }
};


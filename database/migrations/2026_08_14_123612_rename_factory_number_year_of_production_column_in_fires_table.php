<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fires', function (Blueprint $table) {
            $table->renameColumn(
                'factory_number/year_of_production',
                'factory_number_year_of_production'
            );
        });
    }

    public function down(): void
    {
        Schema::table('fires', function (Blueprint $table) {
            $table->renameColumn(
                'factory_number_year_of_production',
                'factory_number/year_of_production'
            );
        });
    }
};
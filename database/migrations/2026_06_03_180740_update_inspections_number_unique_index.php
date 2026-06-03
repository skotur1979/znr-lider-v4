<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropUnique('inspections_number_unique');

            $table->unique(['user_id', 'number'], 'inspections_user_id_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropUnique('inspections_user_id_number_unique');

            $table->unique('number', 'inspections_number_unique');
        });
    }
};
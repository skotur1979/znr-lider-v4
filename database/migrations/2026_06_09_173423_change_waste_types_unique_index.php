<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_types', function (Blueprint $table) {
            $table->dropUnique('waste_types_waste_code_unique');
            $table->unique(['user_id', 'waste_code'], 'waste_types_user_id_waste_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('waste_types', function (Blueprint $table) {
            $table->dropUnique('waste_types_user_id_waste_code_unique');
            $table->unique('waste_code', 'waste_types_waste_code_unique');
        });
    }
};
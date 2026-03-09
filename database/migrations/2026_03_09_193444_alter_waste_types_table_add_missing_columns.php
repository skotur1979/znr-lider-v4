<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_types', function (Blueprint $table) {
            $table->string('waste_code', 20)->nullable()->after('id');
            $table->string('name')->nullable()->after('waste_code');
            $table->boolean('is_hazardous')->default(false)->after('name');
            $table->softDeletes();
        });

        Schema::table('waste_types', function (Blueprint $table) {
            $table->unique('waste_code');
            $table->index('name');
            $table->index('is_hazardous');
        });
    }

    public function down(): void
    {
        Schema::table('waste_types', function (Blueprint $table) {
            $table->dropUnique(['waste_code']);
            $table->dropIndex(['name']);
            $table->dropIndex(['is_hazardous']);

            $table->dropColumn([
                'waste_code',
                'name',
                'is_hazardous',
                'deleted_at',
            ]);
        });
    }
};
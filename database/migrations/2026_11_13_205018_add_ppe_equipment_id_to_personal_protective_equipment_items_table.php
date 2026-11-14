<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'personal_protective_equipment_items',
            function (Blueprint $table): void {
                $table
                    ->foreignId('ppe_equipment_id')
                    ->nullable()
                    ->after('personal_protective_equipment_log_id')
                    ->constrained('ppe_equipments')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'personal_protective_equipment_items',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'ppe_equipment_id'
                );
            }
        );
    }
};
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
                $table->softDeletes();

                $table->index(
                    'personal_protective_equipment_log_id',
                    'ppe_items_log_id_index'
                );

                $table
                    ->foreign(
                        'personal_protective_equipment_log_id',
                        'ppe_items_log_id_foreign'
                    )
                    ->references('id')
                    ->on('personal_protective_equipment_logs')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'personal_protective_equipment_items',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'ppe_items_log_id_foreign'
                );

                $table->dropIndex(
                    'ppe_items_log_id_index'
                );

                $table->dropSoftDeletes();
            }
        );
    }
};
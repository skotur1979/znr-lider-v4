<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_values', 'user_id')) {
            Schema::table('kpi_values', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('kpi_id')
                    ->constrained()
                    ->nullOnDelete();
            });
        }

        try {
            DB::statement('ALTER TABLE kpi_values DROP FOREIGN KEY kpi_values_kpi_id_foreign');
        } catch (\Throwable $e) {
            // već uklonjeno ili ne postoji
        }

        try {
            DB::statement('ALTER TABLE kpi_values DROP INDEX kpi_values_kpi_id_month_year_unique');
        } catch (\Throwable $e) {
            // već uklonjeno ili ne postoji
        }

        try {
            DB::statement('ALTER TABLE kpi_values ADD CONSTRAINT kpi_values_kpi_id_foreign FOREIGN KEY (kpi_id) REFERENCES kpis(id) ON DELETE CASCADE');
        } catch (\Throwable $e) {
            // već postoji
        }

        try {
            DB::statement('ALTER TABLE kpi_values ADD UNIQUE kpi_values_unique_user_month (kpi_id, user_id, month, year)');
        } catch (\Throwable $e) {
            // već postoji
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE kpi_values DROP INDEX kpi_values_unique_user_month');
        } catch (\Throwable $e) {
            //
        }

        if (Schema::hasColumn('kpi_values', 'user_id')) {
            Schema::table('kpi_values', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        try {
            DB::statement('ALTER TABLE kpi_values ADD UNIQUE kpi_values_kpi_id_month_year_unique (kpi_id, month, year)');
        } catch (\Throwable $e) {
            //
        }
    }
};
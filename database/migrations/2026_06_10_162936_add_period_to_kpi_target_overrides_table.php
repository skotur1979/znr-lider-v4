<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kpi_target_overrides', 'month')) {
            Schema::table('kpi_target_overrides', function (Blueprint $table) {
                $table->unsignedTinyInteger('month')->nullable()->after('user_id');
            });
        }

        if (! Schema::hasColumn('kpi_target_overrides', 'year')) {
            Schema::table('kpi_target_overrides', function (Blueprint $table) {
                $table->unsignedSmallInteger('year')->nullable()->after('month');
            });
        }

        try {
            Schema::table('kpi_target_overrides', function (Blueprint $table) {
                $table->index(
                    ['kpi_id', 'user_id', 'year', 'month'],
                    'kpi_target_overrides_period_index'
                );
            });
        } catch (\Throwable $e) {
            // Index već postoji ili ga baza ne može dodati - nastavi dalje.
        }
    }

    public function down(): void
    {
        try {
            Schema::table('kpi_target_overrides', function (Blueprint $table) {
                $table->dropIndex('kpi_target_overrides_period_index');
            });
        } catch (\Throwable $e) {
            // Ignoriraj ako index ne postoji.
        }

        if (Schema::hasColumn('kpi_target_overrides', 'year')) {
            Schema::table('kpi_target_overrides', function (Blueprint $table) {
                $table->dropColumn('year');
            });
        }

        if (Schema::hasColumn('kpi_target_overrides', 'month')) {
            Schema::table('kpi_target_overrides', function (Blueprint $table) {
                $table->dropColumn('month');
            });
        }
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Lokalni KPI pripada organizaciji.
         *
         * Ako se owner fizički izbriše,
         * lokalni KPI ne smije postati globalni.
         */
        /*
        * Legacy KPI vrijednosti bez organizacijskog ownera
        * više nisu valjane prema tenant modelu.
        *
        * Brišemo ih prije postavljanja user_id na NOT NULL
        * kako bi migracija bila sigurna i na starijim bazama.
        */
        DB::table('kpi_values')
            ->whereNull('user_id')
            ->delete();
            
        Schema::table('kpis', function (Blueprint $table): void {
            $table->dropForeign([
                'user_id',
            ]);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        /*
         * KPI vrijednost uvijek mora pripadati
         * konkretnoj organizaciji.
         */
        Schema::table(
            'kpi_values',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'user_id',
                ]);

                $table->unsignedBigInteger('user_id')
                    ->nullable(false)
                    ->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'kpi_values',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'user_id',
                ]);

                $table->unsignedBigInteger('user_id')
                    ->nullable()
                    ->change();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        );

        Schema::table('kpis', function (Blueprint $table): void {
            $table->dropForeign([
                'user_id',
            ]);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
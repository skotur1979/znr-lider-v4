<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Provjerava postoji li indeks na tablici.
     */
    private function indexExists(string $indexName): bool
    {
        $result = DB::selectOne(
            '
                SELECT COUNT(*) AS aggregate
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                  AND INDEX_NAME = ?
            ',
            [
                'kpi_target_overrides',
                $indexName,
            ]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }

    public function up(): void
    {
        if (! Schema::hasTable('kpi_target_overrides')) {
            return;
        }

        /*
         * Stari UNIQUE:
         *
         * kpi_id + user_id
         *
         * dopuštao je samo jedan override cilja
         * po KPI-u i organizaciji, bez obzira
         * na mjesec i godinu.
         *
         * Na nekim bazama već je uklonjen,
         * zato prvo provjeravamo postoji li.
         */
        if (
            $this->indexExists(
                'kpi_target_overrides_kpi_id_user_id_unique'
            )
        ) {
            DB::statement(
                '
                    ALTER TABLE `kpi_target_overrides`
                    DROP INDEX `kpi_target_overrides_kpi_id_user_id_unique`
                '
            );
        }

        /*
         * Obični periodični indeks postaje suvišan
         * kada postoji UNIQUE indeks nad istim stupcima.
         */
        if (
            $this->indexExists(
                'kpi_target_overrides_period_index'
            )
        ) {
            DB::statement(
                '
                    ALTER TABLE `kpi_target_overrides`
                    DROP INDEX `kpi_target_overrides_period_index`
                '
            );
        }

        /*
         * Konačno pravilo:
         *
         * jedan KPI
         * + jedna organizacija
         * + jedna godina
         * + jedan mjesec
         *
         * može imati samo jedan override cilja.
         *
         * Na postojećoj server bazi ovaj UNIQUE
         * već postoji, pa ga tada ne stvaramo ponovno.
         */
        if (
            ! $this->indexExists(
                'kpi_target_overrides_period_unique'
            )
        ) {
            DB::statement(
                '
                    ALTER TABLE `kpi_target_overrides`
                    ADD UNIQUE INDEX
                    `kpi_target_overrides_period_unique`
                    (`kpi_id`, `user_id`, `year`, `month`)
                '
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kpi_target_overrides')) {
            return;
        }

        /*
         * U rollbacku uklanjamo UNIQUE koji je
         * definiran ovom normalizacijom.
         */
        if (
            $this->indexExists(
                'kpi_target_overrides_period_unique'
            )
        ) {
            DB::statement(
                '
                    ALTER TABLE `kpi_target_overrides`
                    DROP INDEX `kpi_target_overrides_period_unique`
                '
            );
        }

        /*
         * Vraćamo obični periodični indeks
         * kakav je postojao u ranijoj migraciji.
         */
        if (
            ! $this->indexExists(
                'kpi_target_overrides_period_index'
            )
        ) {
            DB::statement(
                '
                    ALTER TABLE `kpi_target_overrides`
                    ADD INDEX
                    `kpi_target_overrides_period_index`
                    (`kpi_id`, `user_id`, `year`, `month`)
                '
            );
        }

        /*
         * Namjerno NE vraćamo stari UNIQUE:
         *
         * kpi_id + user_id
         *
         * jer nakon korištenja periodičnih ciljeva
         * jedna organizacija može legitimno imati
         * više redova za isti KPI u različitim
         * mjesecima/godinama.
         *
         * Ponovno stvaranje starog UNIQUE-a tada
         * bi moglo srušiti rollback ili blokirati
         * ispravne povijesne podatke.
         */
    }
};

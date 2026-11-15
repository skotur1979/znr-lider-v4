<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * NR-1 je poslovni zapis organizacije i
         * svaki zapis mora imati vlasnika.
         *
         * Ne popravljamo podatke automatski.
         * Ako postoje legacy NULL vrijednosti,
         * migracija se namjerno zaustavlja.
         */
        $nullUserCount = DB::table('night_work_referrals')
            ->whereNull('user_id')
            ->count();

        if ($nullUserCount > 0) {
            throw new \RuntimeException(
                "Night work referrals contain {$nullUserCount} "
                . 'record(s) without user_id. '
                . 'Clean legacy data before running this migration.'
            );
        }

        /*
         * Uklanjamo postojeći FK:
         *
         * user_id nullable
         * ON DELETE SET NULL
         * ON UPDATE RESTRICT
         */
        Schema::table(
            'night_work_referrals',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'night_work_referrals_user_id_foreign'
                );
            }
        );

        /*
         * Ownership više ne smije biti NULL.
         */
        DB::statement(
            'ALTER TABLE night_work_referrals '
            . 'MODIFY user_id BIGINT UNSIGNED NOT NULL'
        );

        /*
         * Ako se organizacijski korisnik trajno
         * obriše, uklanjaju se i njegovi NR-1
         * poslovni zapisi.
         *
         * UPDATE CASCADE zadržava referencijalni
         * integritet ako se ID ikada promijeni.
         */
        Schema::table(
            'night_work_referrals',
            function (Blueprint $table): void {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'night_work_referrals',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'night_work_referrals_user_id_foreign'
                );
            }
        );

        /*
         * Vraćamo originalnu strukturu:
         * user_id nullable.
         */
        DB::statement(
            'ALTER TABLE night_work_referrals '
            . 'MODIFY user_id BIGINT UNSIGNED NULL'
        );

        /*
         * Originalni FK:
         * ON UPDATE RESTRICT
         * ON DELETE SET NULL
         */
        Schema::table(
            'night_work_referrals',
            function (Blueprint $table): void {
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->restrictOnUpdate()
                    ->nullOnDelete();
            }
        );
    }
};
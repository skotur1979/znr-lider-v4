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
         * Broj NR-1 uputnice mora biti jedinstven
         * unutar iste organizacije.
         *
         * NULL ostaje dopušten jer kopirana
         * uputnica namjerno može biti bez broja
         * dok se novi broj ne dodijeli.
         */

        $duplicates = DB::table('night_work_referrals')
            ->select(
                'user_id',
                'referral_number',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('referral_number')
            ->where('referral_number', '<>', '')
            ->groupBy(
                'user_id',
                'referral_number'
            )
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                'Duplicate NR-1 referral numbers exist. '
                . 'Clean duplicate records before running this migration.'
            );
        }

        Schema::table(
            'night_work_referrals',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'user_id',
                        'referral_number',
                    ],
                    'night_work_referrals_user_referral_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'night_work_referrals',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'night_work_referrals_user_referral_unique'
                );
            }
        );
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('medical_referrals')
            ->select(
                'user_id',
                'referral_number',
                DB::raw('COUNT(*) as total')
            )
            ->whereNotNull('referral_number')
            ->where('referral_number', '<>', '')
            ->groupBy('user_id', 'referral_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new \RuntimeException(
                'Medical referrals contain duplicate referral numbers within the same organization.'
            );
        }

        Schema::table('medical_referrals', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'referral_number'],
                'medical_referrals_user_referral_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('medical_referrals', function (Blueprint $table) {
            $table->dropUnique(
                'medical_referrals_user_referral_unique'
            );
        });
    }
};
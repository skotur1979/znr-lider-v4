<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_referrals', function (Blueprint $table) {
            $table->string('form_version')->default('RA1_2026')->after('manual_entry');
        });
    }

    public function down(): void
    {
        Schema::table('medical_referrals', function (Blueprint $table) {
            $table->dropColumn('form_version');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('medical_referrals', 'manual_entry')) {
                $table->boolean('manual_entry')->default(false)->after('employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('medical_referrals', 'manual_entry')) {
                $table->dropColumn('manual_entry');
            }
        });
    }
};
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

            if (! Schema::hasColumn('medical_referrals', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('id');
            }

            if (! Schema::hasColumn('medical_referrals', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete()->after('user_id');
            }

            if (! Schema::hasColumn('medical_referrals', 'lifting_weight')) {
                $table->string('lifting_weight')->nullable()->after('lifting_enabled');
            }

            if (! Schema::hasColumn('medical_referrals', 'carrying_weight')) {
                $table->string('carrying_weight')->nullable()->after('carrying_enabled');
            }

            if (! Schema::hasColumn('medical_referrals', 'pushing_weight')) {
                $table->string('pushing_weight')->nullable()->after('pushing_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medical_referrals', function (Blueprint $table) {
            if (Schema::hasColumn('medical_referrals', 'manual_entry')) {
                $table->dropColumn('manual_entry');
            }

            if (Schema::hasColumn('medical_referrals', 'lifting_weight')) {
                $table->dropColumn('lifting_weight');
            }

            if (Schema::hasColumn('medical_referrals', 'carrying_weight')) {
                $table->dropColumn('carrying_weight');
            }

            if (Schema::hasColumn('medical_referrals', 'pushing_weight')) {
                $table->dropColumn('pushing_weight');
            }

            if (Schema::hasColumn('medical_referrals', 'employee_id')) {
                $table->dropConstrainedForeignId('employee_id');
            }

            if (Schema::hasColumn('medical_referrals', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
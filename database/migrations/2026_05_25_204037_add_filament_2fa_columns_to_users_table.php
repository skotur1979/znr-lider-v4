<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'app_authentication_secret')) {
                $table->text('app_authentication_secret')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'app_authentication_recovery_codes')) {
                $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
            }

            if (! Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('app_authentication_recovery_codes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'app_authentication_secret')) {
                $table->dropColumn('app_authentication_secret');
            }

            if (Schema::hasColumn('users', 'app_authentication_recovery_codes')) {
                $table->dropColumn('app_authentication_recovery_codes');
            }

            if (Schema::hasColumn('users', 'last_activity_at')) {
                $table->dropColumn('last_activity_at');
            }
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (! Schema::hasColumn('users', 'email_2fa_code_hash')) {
                $table->string('email_2fa_code_hash')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'email_2fa_expires_at')) {
                $table->timestamp('email_2fa_expires_at')->nullable()->after('email_2fa_code_hash');
            }

            if (! Schema::hasColumn('users', 'email_2fa_verified_at')) {
                $table->timestamp('email_2fa_verified_at')->nullable()->after('email_2fa_expires_at');
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'email_2fa_code_hash')) {
                $table->dropColumn('email_2fa_code_hash');
            }

            if (Schema::hasColumn('users', 'email_2fa_expires_at')) {
                $table->dropColumn('email_2fa_expires_at');
            }

            if (Schema::hasColumn('users', 'email_2fa_verified_at')) {
                $table->dropColumn('email_2fa_verified_at');
            }

        });
    }
};
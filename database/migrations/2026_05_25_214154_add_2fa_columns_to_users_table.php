<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (! Schema::hasColumn('users', 'google2fa_secret')) {
                $table->text('google2fa_secret')->nullable()->after('password');
            }

            if (! Schema::hasColumn('users', 'google2fa_enabled_at')) {
                $table->timestamp('google2fa_enabled_at')->nullable()->after('google2fa_secret');
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (Schema::hasColumn('users', 'google2fa_secret')) {
                $table->dropColumn('google2fa_secret');
            }

            if (Schema::hasColumn('users', 'google2fa_enabled_at')) {
                $table->dropColumn('google2fa_enabled_at');
            }

        });
    }
};

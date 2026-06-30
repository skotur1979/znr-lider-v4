<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'cookies_version')) {
                $table->string('cookies_version')->nullable()->after('privacy_version');
            }

            if (! Schema::hasColumn('users', 'dpa_version')) {
                $table->string('dpa_version')->nullable()->after('cookies_version');
            }

            if (! Schema::hasColumn('users', 'security_version')) {
                $table->string('security_version')->nullable()->after('dpa_version');
            }

            if (! Schema::hasColumn('users', 'retention_version')) {
                $table->string('retention_version')->nullable()->after('security_version');
            }

            if (! Schema::hasColumn('users', 'cookies_accepted_at')) {
                $table->timestamp('cookies_accepted_at')->nullable()->after('accepted_privacy_at');
            }

            if (! Schema::hasColumn('users', 'dpa_accepted_at')) {
                $table->timestamp('dpa_accepted_at')->nullable()->after('cookies_accepted_at');
            }

            if (! Schema::hasColumn('users', 'security_accepted_at')) {
                $table->timestamp('security_accepted_at')->nullable()->after('dpa_accepted_at');
            }

            if (! Schema::hasColumn('users', 'retention_accepted_at')) {
                $table->timestamp('retention_accepted_at')->nullable()->after('security_accepted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cookies_version',
                'dpa_version',
                'security_version',
                'retention_version',
                'cookies_accepted_at',
                'dpa_accepted_at',
                'security_accepted_at',
                'retention_accepted_at',
            ]);
        });
    }
};
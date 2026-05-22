<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('accepted_terms_at')->nullable()->after('weekly_status_email_enabled');
            $table->timestamp('accepted_privacy_at')->nullable()->after('accepted_terms_at');
            $table->string('terms_version', 20)->nullable()->after('accepted_privacy_at');
            $table->string('privacy_version', 20)->nullable()->after('terms_version');
            $table->boolean('newsletter_opt_in')->default(false)->after('privacy_version');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'accepted_terms_at',
                'accepted_privacy_at',
                'terms_version',
                'privacy_version',
                'newsletter_opt_in',
            ]);
        });
    }
};
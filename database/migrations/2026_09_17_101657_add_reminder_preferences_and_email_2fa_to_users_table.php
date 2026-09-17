<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('email_2fa_enabled')
                ->default(false);

            $table->boolean('reminder_30_days_enabled')
                ->default(true);

            $table->boolean('reminder_14_days_enabled')
                ->default(false);

            $table->boolean('reminder_7_days_enabled')
                ->default(false);

            $table->boolean('reminder_overdue_enabled')
                ->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_2fa_enabled',
                'reminder_30_days_enabled',
                'reminder_14_days_enabled',
                'reminder_7_days_enabled',
                'reminder_overdue_enabled',
            ]);
        });
    }
};
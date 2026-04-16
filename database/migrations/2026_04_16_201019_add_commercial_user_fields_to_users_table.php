<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'parent_user_id')) {
                $table->foreignId('parent_user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'organization_name')) {
                $table->string('organization_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'can_manage_subusers')) {
                $table->boolean('can_manage_subusers')->default(false)->after('quick_actions');
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('can_manage_subusers');
            }

            if (! Schema::hasColumn('users', 'daily_status_email_enabled')) {
                $table->boolean('daily_status_email_enabled')->default(true)->after('is_active');
            }

            if (! Schema::hasColumn('users', 'weekly_status_email_enabled')) {
                $table->boolean('weekly_status_email_enabled')->default(false)->after('daily_status_email_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'parent_user_id',
                'organization_name',
                'can_manage_subusers',
                'is_active',
                'daily_status_email_enabled',
                'weekly_status_email_enabled',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
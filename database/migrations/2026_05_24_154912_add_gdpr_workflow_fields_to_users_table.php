<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            if (! Schema::hasColumn('users', 'account_status')) {
                $table->string('account_status')
                    ->default('active')
                    ->after('is_active');
            }

            if (! Schema::hasColumn('users', 'gdpr_request_status')) {
                $table->string('gdpr_request_status')
                    ->nullable()
                    ->after('account_status');
            }

            if (! Schema::hasColumn('users', 'gdpr_request_processed_at')) {
                $table->timestamp('gdpr_request_processed_at')
                    ->nullable()
                    ->after('gdpr_request_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $columns = [
                'account_status',
                'gdpr_request_status',
                'gdpr_request_processed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
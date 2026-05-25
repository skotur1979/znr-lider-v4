<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'legal_consent_withdrawn_at')) {
                $table->timestamp('legal_consent_withdrawn_at')->nullable()->after('newsletter_opt_in');
            }

            if (! Schema::hasColumn('users', 'legal_consent_withdrawn_reason')) {
                $table->text('legal_consent_withdrawn_reason')->nullable()->after('legal_consent_withdrawn_at');
            }

            if (! Schema::hasColumn('users', 'account_deletion_requested_at')) {
                $table->timestamp('account_deletion_requested_at')->nullable()->after('legal_consent_withdrawn_reason');
            }

            if (! Schema::hasColumn('users', 'account_deletion_reason')) {
                $table->text('account_deletion_reason')->nullable()->after('account_deletion_requested_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'legal_consent_withdrawn_at',
                'legal_consent_withdrawn_reason',
                'account_deletion_requested_at',
                'account_deletion_reason',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
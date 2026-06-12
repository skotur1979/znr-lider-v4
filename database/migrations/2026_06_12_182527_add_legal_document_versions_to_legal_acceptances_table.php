<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_acceptances', function (Blueprint $table) {
            if (! Schema::hasColumn('legal_acceptances', 'cookies_version')) {
                $table->string('cookies_version')->nullable()->after('privacy_version');
            }

            if (! Schema::hasColumn('legal_acceptances', 'dpa_version')) {
                $table->string('dpa_version')->nullable()->after('cookies_version');
            }

            if (! Schema::hasColumn('legal_acceptances', 'security_version')) {
                $table->string('security_version')->nullable()->after('dpa_version');
            }

            if (! Schema::hasColumn('legal_acceptances', 'retention_version')) {
                $table->string('retention_version')->nullable()->after('security_version');
            }

            if (! Schema::hasColumn('legal_acceptances', 'accepted_documents')) {
                $table->json('accepted_documents')->nullable()->after('retention_version');
            }
        });
    }

    public function down(): void
    {
        Schema::table('legal_acceptances', function (Blueprint $table) {
            $table->dropColumn([
                'cookies_version',
                'dpa_version',
                'security_version',
                'retention_version',
                'accepted_documents',
            ]);
        });
    }
};
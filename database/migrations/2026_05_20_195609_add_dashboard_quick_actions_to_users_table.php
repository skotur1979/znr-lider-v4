<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'dashboard_quick_actions')) {
                $table->json('dashboard_quick_actions')->nullable()->after('quick_actions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dashboard_quick_actions')) {
                $table->dropColumn('dashboard_quick_actions');
            }
        });
    }
};
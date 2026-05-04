<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operational_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('operational_logs', 'items')) {
                $table->json('items')->nullable()->after('note');
            }
        });

        if (Schema::hasColumn('operational_logs', 'note')) {
            DB::statement('ALTER TABLE operational_logs MODIFY note LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('operational_logs', function (Blueprint $table) {
            if (Schema::hasColumn('operational_logs', 'items')) {
                $table->dropColumn('items');
            }
        });

        if (Schema::hasColumn('operational_logs', 'note')) {
            DB::statement('ALTER TABLE operational_logs MODIFY note LONGTEXT NOT NULL');
        }
    }
};
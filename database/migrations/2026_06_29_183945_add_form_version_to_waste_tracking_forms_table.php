<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_tracking_forms', function (Blueprint $table) {
            $table->string('form_version')->default('PLO_2026')->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('waste_tracking_forms', function (Blueprint $table) {
            $table->dropColumn('form_version');
        });
    }
};
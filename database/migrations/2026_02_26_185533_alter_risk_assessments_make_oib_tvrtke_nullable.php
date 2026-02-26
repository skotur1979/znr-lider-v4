<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->string('oib_tvrtke', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->string('oib_tvrtke', 50)->nullable(false)->change();
        });
    }
};
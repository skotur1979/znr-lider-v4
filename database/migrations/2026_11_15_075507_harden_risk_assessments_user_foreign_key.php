<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $nullUsers = DB::table('risk_assessments')
            ->whereNull('user_id')
            ->count();

        if ($nullUsers > 0) {
           throw new \RuntimeException(
                "Risk assessments migration aborted: {$nullUsers} record(s) have NULL user_id."
            );
        }

        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->change();
        });

        Schema::table('risk_assessments', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
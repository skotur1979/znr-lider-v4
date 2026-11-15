<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $nullUsers = DB::table('work_permits')
            ->whereNull('user_id')
            ->count();

        if ($nullUsers > 0) {
            throw new \RuntimeException(
                "Work permits migration aborted: {$nullUsers} record(s) have NULL user_id."
            );
        }

        Schema::table('work_permits', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('work_permits', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('work_permits', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_permits', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('work_permits', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->change();
        });

        Schema::table('work_permits', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
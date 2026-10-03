<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table): void {
            $table->foreignId('responsible_user_id')
                ->nullable()
                ->after('responsible')
                ->constrained('users')
                ->nullOnDelete();

            $table->date('due_soon_notified_for')
                ->nullable()
                ->after('responsible_user_id');

            $table->date('overdue_notified_for')
                ->nullable()
                ->after('due_soon_notified_for');
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table): void {
            $table->dropForeign(['responsible_user_id']);

            $table->dropColumn([
                'responsible_user_id',
                'due_soon_notified_for',
                'overdue_notified_for',
            ]);
        });
    }
};
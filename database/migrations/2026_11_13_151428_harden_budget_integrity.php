<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign('expenses_budget_id_foreign');

            $table->foreign(
                'budget_id',
                'expenses_budget_id_foreign'
            )
                ->references('id')
                ->on('budgets')
                ->restrictOnDelete();
        });

        Schema::table('budgets', function (Blueprint $table): void {
            $table->unique(
                ['user_id', 'godina'],
                'budgets_user_id_godina_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table): void {
            $table->dropUnique(
                'budgets_user_id_godina_unique'
            );
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign(
                'expenses_budget_id_foreign'
            );

            $table->foreign(
                'budget_id',
                'expenses_budget_id_foreign'
            )
                ->references('id')
                ->on('budgets')
                ->cascadeOnDelete();
        });
    }
};
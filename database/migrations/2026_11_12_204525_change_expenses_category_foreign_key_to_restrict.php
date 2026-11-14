<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign('expenses_category_id_foreign');

            $table->foreign(
                'category_id',
                'expenses_category_id_foreign'
            )
                ->references('id')
                ->on('categories')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropForeign('expenses_category_id_foreign');

            $table->foreign(
                'category_id',
                'expenses_category_id_foreign'
            )
                ->references('id')
                ->on('categories')
                ->nullOnDelete();
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'categories',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'user_id',
                        'name',
                    ],
                    'categories_user_id_name_unique'
                );
            }
        );

        Schema::table(
            'miscellaneouses',
            function (Blueprint $table): void {
                $table->index(
                    'category_id',
                    'miscellaneouses_category_id_index'
                );

                $table->foreign(
                    'category_id',
                    'miscellaneouses_category_id_foreign'
                )
                    ->references('id')
                    ->on('categories')
                    ->restrictOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'miscellaneouses',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'miscellaneouses_category_id_foreign'
                );

                $table->dropIndex(
                    'miscellaneouses_category_id_index'
                );
            }
        );

        Schema::table(
            'categories',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'categories_user_id_name_unique'
                );
            }
        );
    }
};
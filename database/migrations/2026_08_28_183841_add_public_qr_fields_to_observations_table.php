<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'observations',
            function (Blueprint $table): void {

                $table
                    ->string(
                        'source',
                        50
                    )
                    ->default('internal')
                    ->after('user_id');

                $table
                    ->foreignId(
                        'source_qr_code_id'
                    )
                    ->nullable()
                    ->after('source')
                    ->constrained('qr_codes')
                    ->nullOnDelete();

                $table
                    ->string(
                        'reporter_contact'
                    )
                    ->nullable()
                    ->after('comments');

                $table->index(
                    [
                        'user_id',
                        'source',
                    ],
                    'observations_user_source_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'observations',
            function (Blueprint $table): void {

                $table->dropIndex(
                    'observations_user_source_index'
                );

                $table->dropConstrainedForeignId(
                    'source_qr_code_id'
                );

                $table->dropColumn([
                    'source',
                    'reporter_contact',
                ]);
            }
        );
    }
};
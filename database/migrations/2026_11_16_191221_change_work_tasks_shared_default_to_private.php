<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Novi WorkTask mora po defaultu biti privatan.
         *
         * Dijeljenje s cijelom organizacijom mora biti
         * eksplicitna odluka korisnika.
         */
        DB::statement(
            'ALTER TABLE work_tasks '
            . 'MODIFY is_shared_with_organization '
            . 'TINYINT(1) NOT NULL DEFAULT 0'
        );
    }

    public function down(): void
    {
        /*
         * Vraćanje starog ponašanja:
         * novi zadatak je po defaultu organizacijski.
         */
        DB::statement(
            'ALTER TABLE work_tasks '
            . 'MODIFY is_shared_with_organization '
            . 'TINYINT(1) NOT NULL DEFAULT 1'
        );
    }
};
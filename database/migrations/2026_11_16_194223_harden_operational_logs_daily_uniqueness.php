<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | DATUM DNEVNIKA JE OBAVEZAN
        |--------------------------------------------------------------------------
        |
        | U postojećim podacima već smo potvrdili:
        | - nema NULL log_date vrijednosti
        |
        | Zato ovo možemo sigurno očvrsnuti na DB razini.
        |
        */

        DB::statement(
            'ALTER TABLE operational_logs
             MODIFY log_date DATE NOT NULL'
        );

        /*
        |--------------------------------------------------------------------------
        | AKTIVNI MARKER
        |--------------------------------------------------------------------------
        |
        | Aktivni zapis:
        | deleted_at IS NULL -> marker = 1
        |
        | Soft-deleted zapis:
        | deleted_at IS NOT NULL -> marker = NULL
        |
        | UNIQUE indeks u MySQL/MariaDB dopušta više NULL vrijednosti,
        | pa možemo imati više povijesnih soft-deleted zapisa za isti
        | datum, ali samo jedan aktivni.
        |
        */

        DB::statement(
            'ALTER TABLE operational_logs
             ADD COLUMN active_daily_unique_marker TINYINT
             GENERATED ALWAYS AS (
                 CASE
                     WHEN deleted_at IS NULL THEN 1
                     ELSE NULL
                 END
             ) STORED'
        );

        /*
        |--------------------------------------------------------------------------
        | JEDAN AKTIVNI DNEVNIK PO KORISNIKU I DATUMU
        |--------------------------------------------------------------------------
        */

        DB::statement(
            'CREATE UNIQUE INDEX operational_logs_user_date_active_unique
             ON operational_logs (
                 user_id,
                 log_date,
                 active_daily_unique_marker
             )'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX operational_logs_user_date_active_unique
             ON operational_logs'
        );

        DB::statement(
            'ALTER TABLE operational_logs
             DROP COLUMN active_daily_unique_marker'
        );

        DB::statement(
            'ALTER TABLE operational_logs
             MODIFY log_date DATE NULL'
        );
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasInvalidPermitNumbers = DB::table('work_permits')
            ->whereNull('permit_number')
            ->orWhereRaw("TRIM(permit_number) = ''")
            ->exists();

        if ($hasInvalidPermitNumbers) {
            throw new \RuntimeException(
                'Work permits contain NULL or blank permit_number values. Clean them before running this migration.'
            );
        }

        $hasInvalidIssueDates = DB::table('work_permits')
            ->whereNull('issue_date')
            ->exists();

        if ($hasInvalidIssueDates) {
            throw new \RuntimeException(
                'Work permits contain NULL issue_date values. Clean them before running this migration.'
            );
        }

        DB::statement(
            'ALTER TABLE `work_permits`
             MODIFY `permit_number` VARCHAR(255) NOT NULL'
        );

        DB::statement(
            'ALTER TABLE `work_permits`
             MODIFY `issue_date` DATE NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE `work_permits`
             MODIFY `permit_number` VARCHAR(255) NULL'
        );

        DB::statement(
            'ALTER TABLE `work_permits`
             MODIFY `issue_date` DATE NULL'
        );
    }
};
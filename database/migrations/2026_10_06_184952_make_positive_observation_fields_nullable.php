<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table): void {
            $table
                ->string('priority')
                ->nullable()
                ->change();

            $table
                ->string('potential_incident_type')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        /*
         * Ako se migracija ikad vraća,
         * NULL vrijednosti moramo prvo popuniti.
         */
        DB::table('observations')
            ->whereNull('priority')
            ->update([
                'priority' => 'medium',
            ]);

        DB::table('observations')
            ->whereNull('potential_incident_type')
            ->update([
                'potential_incident_type' => 'Ostalo',
            ]);

        Schema::table('observations', function (Blueprint $table): void {
            $table
                ->string('priority')
                ->nullable(false)
                ->change();

            $table
                ->string('potential_incident_type')
                ->nullable(false)
                ->change();
        });
    }
};
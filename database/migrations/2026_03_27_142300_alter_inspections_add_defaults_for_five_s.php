<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            if (! Schema::hasColumn('inspections', 'inspection_type')) {
                $table->string('inspection_type')->default('general')->after('number');
            } else {
                $table->string('inspection_type')->default('general')->change();
            }

            if (! Schema::hasColumn('inspections', 'five_s_score')) {
                $table->decimal('five_s_score', 6, 2)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            // po potrebi vrati ručno
        });
    }
};
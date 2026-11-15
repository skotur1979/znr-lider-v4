<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_permits', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'permit_number'],
                'work_permits_user_permit_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('work_permits', function (Blueprint $table) {
            $table->dropUnique(
                'work_permits_user_permit_unique'
            );
        });
    }
};
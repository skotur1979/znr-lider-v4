<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('employee_certificates', function (Blueprint $table) {
        $table->string('title')->nullable()->change();
        $table->date('valid_from')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('employee_certificates', function (Blueprint $table) {
        $table->string('title')->nullable(false)->change();
        $table->date('valid_from')->nullable(false)->change();
    });
}
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE waste_catalog_items MODIFY name TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE waste_catalog_items MODIFY name VARCHAR(255) NOT NULL');
    }
};
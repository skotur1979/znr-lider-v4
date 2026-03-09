<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_organization_locations', function (Blueprint $table) {
            $table->foreignId('waste_organization_id')
                ->nullable()
                ->after('id');

            $table->string('name')->nullable()->after('waste_organization_id');
            $table->string('unit_code', 20)->nullable()->after('name');
            $table->string('internal_code', 20)->nullable()->after('unit_code');
            $table->string('address')->nullable()->after('internal_code');
            $table->boolean('is_active')->default(true)->after('address');
            $table->softDeletes();
        });

        Schema::table('waste_organization_locations', function (Blueprint $table) {
            $table->foreign('waste_organization_id')
                ->references('id')
                ->on('waste_organizations')
                ->cascadeOnDelete();

            $table->index('name');
            $table->index('unit_code');
            $table->index('internal_code');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('waste_organization_locations', function (Blueprint $table) {
            $table->dropForeign(['waste_organization_id']);
            $table->dropIndex(['name']);
            $table->dropIndex(['unit_code']);
            $table->dropIndex(['internal_code']);
            $table->dropIndex(['is_active']);

            $table->dropColumn([
                'waste_organization_id',
                'name',
                'unit_code',
                'internal_code',
                'address',
                'is_active',
                'deleted_at',
            ]);
        });
    }
};
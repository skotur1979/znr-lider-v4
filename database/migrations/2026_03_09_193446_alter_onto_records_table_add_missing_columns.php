<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onto_records', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id');

            $table->foreignId('waste_organization_id')
                ->nullable()
                ->after('user_id');

            $table->foreignId('waste_organization_location_id')
                ->nullable()
                ->after('waste_organization_id');

            $table->foreignId('waste_type_id')
                ->nullable()
                ->after('waste_organization_location_id');

            $table->year('year')->nullable()->after('waste_type_id');
            $table->string('responsible_person')->nullable()->after('year');
            $table->date('opening_date')->nullable()->after('responsible_person');
            $table->date('closing_date')->nullable()->after('opening_date');
            $table->decimal('current_balance_kg', 12, 2)->default(0)->after('closing_date');
            $table->boolean('is_closed')->default(false)->after('current_balance_kg');
            $table->text('notes')->nullable()->after('is_closed');
            $table->softDeletes();
        });

        Schema::table('onto_records', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('waste_organization_id')
                ->references('id')
                ->on('waste_organizations')
                ->cascadeOnDelete();

            $table->foreign('waste_organization_location_id')
                ->references('id')
                ->on('waste_organization_locations')
                ->cascadeOnDelete();

            $table->foreign('waste_type_id')
                ->references('id')
                ->on('waste_types')
                ->cascadeOnDelete();

            $table->index('year');
            $table->index('is_closed');
            $table->index('current_balance_kg');

            $table->unique(
                ['waste_organization_location_id', 'waste_type_id', 'year'],
                'onto_unique_location_waste_year'
            );
        });
    }

    public function down(): void
    {
        Schema::table('onto_records', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['waste_organization_id']);
            $table->dropForeign(['waste_organization_location_id']);
            $table->dropForeign(['waste_type_id']);

            $table->dropIndex(['year']);
            $table->dropIndex(['is_closed']);
            $table->dropIndex(['current_balance_kg']);
            $table->dropUnique('onto_unique_location_waste_year');

            $table->dropColumn([
                'user_id',
                'waste_organization_id',
                'waste_organization_location_id',
                'waste_type_id',
                'year',
                'responsible_person',
                'opening_date',
                'closing_date',
                'current_balance_kg',
                'is_closed',
                'notes',
                'deleted_at',
            ]);
        });
    }
};
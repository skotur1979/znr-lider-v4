<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onto_entries', function (Blueprint $table) {
            $table->foreignId('onto_record_id')
                ->nullable()
                ->after('id');

            $table->unsignedInteger('entry_no')->default(1)->after('onto_record_id');
            $table->date('entry_date')->nullable()->after('entry_no');
            $table->string('entry_type', 20)->nullable()->after('entry_date');
            $table->decimal('input_kg', 12, 2)->default(0)->after('entry_type');
            $table->decimal('output_kg', 12, 2)->default(0)->after('input_kg');
            $table->string('method', 100)->nullable()->after('output_kg');
            $table->decimal('balance_after_kg', 12, 2)->default(0)->after('method');
            $table->text('note')->nullable()->after('balance_after_kg');

            $table->foreignId('waste_tracking_form_id')
                ->nullable()
                ->after('note');

            $table->softDeletes();
        });

        Schema::table('onto_entries', function (Blueprint $table) {
            $table->foreign('onto_record_id')
                ->references('id')
                ->on('onto_records')
                ->cascadeOnDelete();

            $table->foreign('waste_tracking_form_id')
                ->references('id')
                ->on('waste_tracking_forms')
                ->nullOnDelete();

            $table->index('entry_no');
            $table->index('entry_date');
            $table->index('entry_type');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::table('onto_entries', function (Blueprint $table) {
            $table->dropForeign(['onto_record_id']);
            $table->dropForeign(['waste_tracking_form_id']);

            $table->dropIndex(['entry_no']);
            $table->dropIndex(['entry_date']);
            $table->dropIndex(['entry_type']);
            $table->dropIndex(['method']);

            $table->dropColumn([
                'onto_record_id',
                'entry_no',
                'entry_date',
                'entry_type',
                'input_kg',
                'output_kg',
                'method',
                'balance_after_kg',
                'note',
                'waste_tracking_form_id',
                'deleted_at',
            ]);
        });
    }
};
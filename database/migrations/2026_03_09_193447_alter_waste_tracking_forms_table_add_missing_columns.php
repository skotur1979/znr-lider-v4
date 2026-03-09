<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_tracking_forms', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id');

            $table->foreignId('onto_record_id')
                ->nullable()
                ->after('user_id');

            $table->string('document_number')->nullable()->after('onto_record_id');
            $table->date('handover_date')->nullable()->after('document_number');
            $table->decimal('quantity_kg', 12, 2)->default(0)->after('handover_date');
            $table->string('status', 20)->default('draft')->after('quantity_kg');

            $table->text('description')->nullable()->after('status');

            $table->string('sender_name')->nullable()->after('description');
            $table->string('sender_oib', 20)->nullable()->after('sender_name');
            $table->string('sender_address')->nullable()->after('sender_oib');

            $table->string('carrier_name')->nullable()->after('sender_address');
            $table->string('carrier_oib', 20)->nullable()->after('carrier_name');
            $table->string('carrier_authorization')->nullable()->after('carrier_oib');
            $table->string('carrier_vehicle_registration')->nullable()->after('carrier_authorization');

            $table->string('receiver_name')->nullable()->after('carrier_vehicle_registration');
            $table->string('receiver_oib', 20)->nullable()->after('receiver_name');
            $table->string('receiver_authorization')->nullable()->after('receiver_oib');
            $table->string('receiver_address')->nullable()->after('receiver_authorization');

            $table->string('processing_method')->nullable()->after('receiver_address');
            $table->text('note')->nullable()->after('processing_method');
            $table->timestamp('locked_at')->nullable()->after('note');

            $table->softDeletes();
        });

        Schema::table('waste_tracking_forms', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('onto_record_id')
                ->references('id')
                ->on('onto_records')
                ->cascadeOnDelete();

            $table->index('document_number');
            $table->index('handover_date');
            $table->index('status');
            $table->index('locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('waste_tracking_forms', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['onto_record_id']);

            $table->dropIndex(['document_number']);
            $table->dropIndex(['handover_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['locked_at']);

            $table->dropColumn([
                'user_id',
                'onto_record_id',
                'document_number',
                'handover_date',
                'quantity_kg',
                'status',
                'description',
                'sender_name',
                'sender_oib',
                'sender_address',
                'carrier_name',
                'carrier_oib',
                'carrier_authorization',
                'carrier_vehicle_registration',
                'receiver_name',
                'receiver_oib',
                'receiver_authorization',
                'receiver_address',
                'processing_method',
                'note',
                'locked_at',
                'deleted_at',
            ]);
        });
    }
};
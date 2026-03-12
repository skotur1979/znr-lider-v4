<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_tracking_forms', function (Blueprint $table) {
            if (! Schema::hasColumn('waste_tracking_forms', 'waste_owner_at_handover')) {
                $table->string('waste_owner_at_handover')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'report_choice')) {
                $table->string('report_choice')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'purpose_choice')) {
                $table->string('purpose_choice')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'dispatch_point')) {
                $table->string('dispatch_point')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'destination_point')) {
                $table->string('destination_point')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'quantity_m3')) {
                $table->decimal('quantity_m3', 12, 3)->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'quantity_determination_choice')) {
                $table->string('quantity_determination_choice')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'handover_datetime')) {
                $table->dateTime('handover_datetime')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'handed_over_by')) {
                $table->string('handed_over_by')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'carrier_contact_person')) {
                $table->string('carrier_contact_person')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'carrier_contact_data')) {
                $table->string('carrier_contact_data')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'carrier_taken_over_by')) {
                $table->string('carrier_taken_over_by')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'carrier_taken_over_at')) {
                $table->dateTime('carrier_taken_over_at')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'carrier_delivered_by')) {
                $table->string('carrier_delivered_by')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'receiver_contact_person')) {
                $table->string('receiver_contact_person')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'receiver_contact_data')) {
                $table->string('receiver_contact_data')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'receiver_taken_over_by')) {
                $table->string('receiver_taken_over_by')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'receiver_weighing_time')) {
                $table->dateTime('receiver_weighing_time')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'receiver_measured_quantity_kg')) {
                $table->decimal('receiver_measured_quantity_kg', 12, 2)->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'trader_name')) {
                $table->string('trader_name')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'trader_oib')) {
                $table->string('trader_oib')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'trader_authorization')) {
                $table->string('trader_authorization')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'trader_contact_person')) {
                $table->string('trader_contact_person')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'trader_contact_data')) {
                $table->string('trader_contact_data')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'processor_name')) {
                $table->string('processor_name')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'processor_oib')) {
                $table->string('processor_oib')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'processor_authorization')) {
                $table->string('processor_authorization')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'processing_completed_at')) {
                $table->date('processing_completed_at')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'final_processing_method')) {
                $table->string('final_processing_method')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'processor_confirmed_by')) {
                $table->string('processor_confirmed_by')->nullable();
            }

            if (! Schema::hasColumn('waste_tracking_forms', 'attachments')) {
                $table->json('attachments')->nullable();
            }
        });
    }

    public function down(): void
    {
        // namjerno prazno da ne briše postojeće podatke
    }
};
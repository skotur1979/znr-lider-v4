<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_tracking_forms', function (Blueprint $table) {

            if (!Schema::hasColumn('waste_tracking_forms', 'waste_code_manual')) {
                $table->string('waste_code_manual')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'waste_kind')) {
                $table->string('waste_kind')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'waste_source_types')) {
                $table->json('waste_source_types')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'hazard_properties')) {
                $table->json('hazard_properties')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'physical_properties')) {
                $table->json('physical_properties')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'physical_properties_other')) {
                $table->string('physical_properties_other')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'packaging_types')) {
                $table->json('packaging_types')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'packaging_other')) {
                $table->string('packaging_other')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'package_count')) {
                $table->string('package_count')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'waste_description')) {
                $table->text('waste_description')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'municipal_origin_note')) {
                $table->text('municipal_origin_note')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'sender_person_name')) {
                $table->string('sender_person_name')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'sender_oib')) {
                $table->string('sender_oib')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'sender_nkd_code')) {
                $table->string('sender_nkd_code')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'sender_contact_person')) {
                $table->string('sender_contact_person')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'sender_contact_data')) {
                $table->string('sender_contact_data')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'report_choice')) {
                $table->string('report_choice')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'purpose_choice')) {
                $table->string('purpose_choice')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'dispatch_point')) {
                $table->string('dispatch_point')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'destination_point')) {
                $table->string('destination_point')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'quantity_m3')) {
                $table->decimal('quantity_m3', 12, 3)->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'quantity_determination_choice')) {
                $table->string('quantity_determination_choice')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'handover_datetime')) {
                $table->dateTime('handover_datetime')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'handed_over_by')) {
                $table->string('handed_over_by')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'transport_modes')) {
                $table->json('transport_modes')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'carrier_vehicle_registration')) {
                $table->string('carrier_vehicle_registration')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'receiver_contact_person')) {
                $table->string('receiver_contact_person')->nullable();
            }

            if (!Schema::hasColumn('waste_tracking_forms', 'receiver_contact_data')) {
                $table->string('receiver_contact_data')->nullable();
            }

        });
    }

    public function down(): void
    {
        // ne brišemo ništa jer samo dodajemo polja
    }
};
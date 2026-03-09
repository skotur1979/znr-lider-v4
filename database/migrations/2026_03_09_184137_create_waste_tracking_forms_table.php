<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_tracking_forms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('onto_record_id')
                ->constrained('onto_records')
                ->cascadeOnDelete();

            $table->string('document_number')->nullable();
            $table->date('handover_date')->nullable();

            $table->decimal('quantity_kg', 12, 2)->default(0);
            $table->string('status', 20)->default('draft'); // draft / locked

            $table->text('description')->nullable();

            $table->string('sender_name')->nullable();
            $table->string('sender_oib', 20)->nullable();
            $table->string('sender_address')->nullable();

            $table->string('carrier_name')->nullable();
            $table->string('carrier_oib', 20)->nullable();
            $table->string('carrier_authorization')->nullable();
            $table->string('carrier_vehicle_registration')->nullable();

            $table->string('receiver_name')->nullable();
            $table->string('receiver_oib', 20)->nullable();
            $table->string('receiver_authorization')->nullable();
            $table->string('receiver_address')->nullable();

            $table->string('processing_method')->nullable();
            $table->text('note')->nullable();

            $table->timestamp('locked_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('document_number');
            $table->index('handover_date');
            $table->index('status');
            $table->index('locked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_tracking_forms');
    }
};
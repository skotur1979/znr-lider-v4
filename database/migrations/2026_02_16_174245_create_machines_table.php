<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('factory_number')->nullable();
            $table->string('inventory_number')->nullable();
            $table->string('location');

            $table->date('examination_valid_from')->nullable();
            $table->date('examination_valid_until')->nullable();

            $table->string('examined_by')->nullable();
            $table->string('report_number')->nullable();
            $table->text('remark')->nullable();

            $table->json('pdf')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};


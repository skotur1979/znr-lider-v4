<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('number')->unique();
            $table->string('inspection_type'); // internal, contractors, themed, five_s
            $table->string('title');
            $table->string('location')->nullable();

            $table->date('performed_at')->nullable();
            $table->string('performed_by')->nullable();
            $table->text('present_persons')->nullable();

            $table->string('status')->default('draft'); // draft, completed
            $table->string('overall_status')->default('ok'); // ok, issue, critical

            $table->unsignedInteger('five_s_score')->nullable();

            $table->text('description')->nullable();
            $table->text('conclusion')->nullable();

            $table->json('attachments')->nullable();

            $table->timestamps();
            $table->softDeletes();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
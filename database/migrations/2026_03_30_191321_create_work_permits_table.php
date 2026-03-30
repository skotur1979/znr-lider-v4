<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_permits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('permit_number')->nullable();
            $table->date('issue_date')->nullable();

            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();

            $table->json('work_types')->nullable();
            $table->string('other_work_type')->nullable();
            $table->text('request_or_regulation')->nullable();

            $table->json('executor_types')->nullable();

            $table->string('worker_1')->nullable();
            $table->string('worker_2')->nullable();
            $table->string('worker_3')->nullable();
            $table->string('worker_4')->nullable();
            $table->string('worker_5')->nullable();
            $table->string('worker_6')->nullable();
            $table->string('worker_7')->nullable();
            $table->string('worker_8')->nullable();
            $table->string('worker_9')->nullable();

            $table->text('work_description')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();

            $table->json('required_measures')->nullable();
            $table->text('additional_measures')->nullable();
            $table->text('required_equipment')->nullable();

            $table->json('work_hazards')->nullable();
            $table->string('other_hazard')->nullable();

            $table->json('required_ppe')->nullable();

            $table->string('requester_name')->nullable();
            $table->string('requester_signature')->nullable();

            $table->string('approver_name')->nullable();
            $table->string('approver_signature')->nullable();

            $table->dateTime('extension_valid_from')->nullable();
            $table->dateTime('extension_valid_until')->nullable();
            $table->string('extension_approver_name')->nullable();
            $table->string('extension_approver_signature')->nullable();

            $table->boolean('works_finished')->nullable();
            $table->text('unfinished_reason')->nullable();
            $table->string('checked_after')->nullable();

            $table->string('verification_name')->nullable();
            $table->string('verification_signature')->nullable();
            $table->date('verification_date')->nullable();
            $table->time('verification_time')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_permits');
    }
};
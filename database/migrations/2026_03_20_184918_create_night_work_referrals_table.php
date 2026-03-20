<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('night_work_referrals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();

            $table->boolean('manual_entry')->default(false);

            $table->string('referral_number')->nullable();
            $table->date('referral_date')->nullable();

            $table->string('employer_name')->nullable();
            $table->string('employer_address')->nullable();
            $table->string('employer_oib', 20)->nullable();

            $table->string('full_name')->nullable();
            $table->string('name_of_parents')->nullable();
            $table->string('place_of_birth')->nullable(); // datum i mjesto rođenja u jednom polju
            $table->string('oib', 20)->nullable();
            $table->string('job_title')->nullable(); // noćni rad za koje se utvrđuje radna sposobnost
            $table->string('education')->nullable();

            $table->json('exam_type')->nullable(); // prethodni, kontrolni

            $table->date('last_exam_date')->nullable();
            $table->string('last_exam_reference3')->nullable(); // s ocjenom zdravstvene sposobnosti

            $table->text('short_description')->nullable(); // kratak opis noćnog rada, poslova i trajanje
            $table->string('tools')->nullable(); // strojevi alati uređaji
            $table->string('job_tasks')->nullable(); // predmet rada

            $table->json('workplace_location')->nullable(); // mjesto rada
            $table->json('organization')->nullable(); // organizacija rada
            $table->json('body_position')->nullable(); // položaj tijela i aktivnosti

            $table->boolean('lifting_enabled')->default(false);
            $table->string('lifting_weight')->nullable();

            $table->boolean('carrying_enabled')->default(false);
            $table->string('carrying_weight')->nullable();

            $table->boolean('pushing_enabled')->default(false);
            $table->string('pushing_weight')->nullable();

            $table->json('job_characteristics')->nullable(); // pri radu je važan
            $table->json('hazards')->nullable(); // uvjeti rada

            $table->string('chemcial_substances')->nullable();
            $table->string('biological_hazards')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('night_work_referrals');
    }
};
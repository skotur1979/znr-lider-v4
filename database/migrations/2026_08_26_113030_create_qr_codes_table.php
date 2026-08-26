<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();

            /*
             * Organizacija kojoj QR pripada.
             * Kod poslovnih zapisa to je ownerId glavnog korisnika.
             */
            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Korisnik koji je QR prvi generirao.
             */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Tip QR-a:
             * machine
             * fire
             * observation
             * ...
             */
            $table->string('type', 50);

            /*
             * Model na koji se QR odnosi.
             *
             * Za Machine:
             * qrable_type = App\Models\Machine
             * qrable_id   = ID stroja
             *
             * Nullable nam kasnije omogućuje QR za javna
             * Zapažanja koja nisu vezana uz postojeći zapis.
             */
            $table->nullableMorphs('qrable');

            /*
             * Javni token.
             * Nikada ne koristimo ID modela u javnom QR URL-u.
             */
            $table->string('token', 64)
                ->unique();

            $table->string('name')
                ->nullable();

            /*
             * Kasnije:
             * lokacija QR postera,
             * dodatne postavke,
             * tip obrasca...
             */
            $table->json('metadata')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            /*
             * Jednostavna evidencija korištenja.
             */
            $table->unsignedBigInteger('scan_count')
                ->default(0);

            $table->timestamp('last_scanned_at')
                ->nullable();

            $table->timestamps();

            /*
             * Jedan aktivni konceptualni QR po objektu/tipu.
             */
            $table->unique(
                [
                    'type',
                    'qrable_type',
                    'qrable_id',
                ],
                'qr_codes_subject_unique'
            );

            $table->index([
                'owner_id',
                'type',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
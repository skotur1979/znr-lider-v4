<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Ne brišemo niti automatski dodjeljujemo legacy incidente.
         *
         * Ako postoje incidenti bez ownera, deployment se mora
         * zaustaviti kako bi se podaci prvo ručno pregledali.
         */
        $nullOwnerCount = DB::table('incidents')
            ->whereNull('user_id')
            ->count();

        if ($nullOwnerCount > 0) {
            throw new \RuntimeException(
            "Nije moguće hardenati incidents.user_id: "
            . "pronađeno je {$nullOwnerCount} incidenata bez user_id. "
            . "Prvo pregledajte i ispravite legacy podatke."
        );
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->change();
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
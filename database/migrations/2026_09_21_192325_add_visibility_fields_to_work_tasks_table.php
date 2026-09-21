<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            /*
             * user_id i dalje ostaje ownerId organizacije.
             *
             * created_by_user_id pamti stvarnog korisnika
             * koji je kreirao zadatak.
             */
            $table
                ->foreignId('created_by_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Default TRUE namjerno:
             *
             * - svi postojeći zadaci ostaju organizacijski
             * - eventualni stari dijelovi aplikacije koji još
             *   ne šalju ovo polje nastavljaju se ponašati kao prije
             *
             * Novi WorkTask i Operativni dnevnik serverski
             * izričito postavljaju FALSE ako korisnik ne uključi toggle.
             */
            $table
                ->boolean('is_shared_with_organization')
                ->default(true)
                ->after('created_by_user_id');
        });

        /*
         * Svi postojeći zadaci do sada su bili
         * dostupni cijeloj organizaciji.
         */
        DB::table('work_tasks')
            ->update([
                'is_shared_with_organization' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropForeign([
                'created_by_user_id',
            ]);

            $table->dropColumn([
                'created_by_user_id',
                'is_shared_with_organization',
            ]);
        });
    }
};
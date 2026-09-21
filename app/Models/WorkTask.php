<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class WorkTask extends Model
{
    use LogsActivity;

    protected static string $activityModule =
        'Radni zadaci';

    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'is_shared_with_organization',
        'title',
        'description',
        'due_date',
        'is_done',
        'completed_at',
    ];

    protected $casts = [
        'due_date' =>
            'date',

        'is_done' =>
            'boolean',

        'completed_at' =>
            'datetime',

        'is_shared_with_organization' =>
            'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | GLOBALNA VIDLJIVOST RADNIH ZADATAKA
    |--------------------------------------------------------------------------
    |
    | user_id
    | = vlasnik organizacije / ownerId
    |
    | created_by_user_id
    | = stvarni korisnik koji je napravio zadatak
    |
    | is_shared_with_organization = true
    | = zadatak vide svi korisnici iste organizacije
    |
    | is_shared_with_organization = false
    | = zadatak vidi samo korisnik koji ga je napravio
    |
    | Superadmin vidi sve.
    |
    | U CLI / cron kontekstu nema Auth korisnika pa se ovaj
    | scope ne primjenjuje. Servisi koji rade iz crona moraju
    | tada sami primijeniti odgovarajući korisnički scope.
    |
    */

    protected static function booted(): void
    {
        static::addGlobalScope(
            'work_task_visibility',
            function (
                Builder $query
            ): void {
                $user =
                    Auth::user();

                /*
                 * Console / scheduler / queue.
                 */
                if (! $user) {
                    return;
                }

                /*
                 * Superadmin vidi sve zadatke.
                 */
                if (
                    $user->isSuperAdmin()
                ) {
                    return;
                }

                $ownerId =
                    $user->ownerId();

                if (! $ownerId) {
                    $query->whereRaw(
                        '1 = 0'
                    );

                    return;
                }

                $table =
                    $query
                        ->getModel()
                        ->getTable();

                /*
                 * Prvo ograničavamo zadatke
                 * na korisnikovu organizaciju.
                 */
                $query->where(
                    $table . '.user_id',
                    $ownerId
                );

                /*
                 * Zatim:
                 *
                 * - organizacijski zadatak vide svi
                 *   korisnici organizacije
                 *
                 * - privatni zadatak vidi samo autor
                 */
                $query->where(
                    function (
                        Builder $visibilityQuery
                    ) use (
                        $table,
                        $user
                    ): void {
                        $visibilityQuery
                            ->where(
                                $table
                                    . '.is_shared_with_organization',
                                true
                            )
                            ->orWhere(
                                $table
                                    . '.created_by_user_id',
                                $user->id
                            );
                    }
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIJE
    |--------------------------------------------------------------------------
    */

    /**
     * Organizacija / glavni korisnik kojem
     * radni zadatak pripada.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /**
     * Stvarni korisnik koji je
     * kreirao radni zadatak.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Zadaci dostupni trenutnom korisniku.
     *
     * Ovaj scope zadržavamo jer ga možda
     * postojeći dijelovi aplikacije koriste.
     *
     * Pravilo je isto kao kod globalnog scopea:
     *
     * - superadmin sve
     * - ista organizacija
     * - shared zadaci
     * - vlastiti privatni zadaci
     */
    public function scopeMine(
        Builder $query
    ): Builder {
        $user =
            Auth::user();

        if (! $user) {
            return $query->whereRaw(
                '1 = 0'
            );
        }

        if (
            $user->isSuperAdmin()
        ) {
            return $query;
        }

        $ownerId =
            $user->ownerId();

        if (! $ownerId) {
            return $query->whereRaw(
                '1 = 0'
            );
        }

        $table =
            $query
                ->getModel()
                ->getTable();

        return $query
            ->where(
                $table . '.user_id',
                $ownerId
            )
            ->where(
                function (
                    Builder $visibilityQuery
                ) use (
                    $table,
                    $user
                ): void {
                    $visibilityQuery
                        ->where(
                            $table
                                . '.is_shared_with_organization',
                            true
                        )
                        ->orWhere(
                            $table
                                . '.created_by_user_id',
                            $user->id
                        );
                }
            );
    }

    /**
     * Samo otvoreni radni zadaci.
     */
    public function scopeOpen(
        Builder $query
    ): Builder {
        return $query->where(
            'is_done',
            false
        );
    }

    /**
     * Samo završeni radni zadaci.
     */
    public function scopeClosed(
        Builder $query
    ): Builder {
        return $query->where(
            'is_done',
            true
        );
    }
}
<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
        /*
        |--------------------------------------------------------------------------
        | GLOBALNI VISIBILITY SCOPE
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | INTEGRITET STATUSA
        |--------------------------------------------------------------------------
        |
        | Završeni zadatak mora imati completed_at.
        |
        | Otvoreni zadatak ne smije imati completed_at.
        |
        | Pravilo vrijedi bez obzira dolazi li spremanje
        | iz Filamenta, API-ja, importa ili nekog servisa.
        |
        */

        static::saving(
            function (self $record): void {
                /*
                 * Ako je zadatak završen,
                 * mora imati vrijeme završetka.
                 */
                if (
                    (bool) $record->is_done
                ) {
                    if (
                        ! $record->completed_at
                    ) {
                        $record->completed_at =
                            now();
                    }

                    return;
                }

                /*
                 * Ako je zadatak ponovno otvoren,
                 * brišemo vrijeme završetka.
                 */
                $record->completed_at =
                    null;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | INTEGRITET KOD KREIRANJA
        |--------------------------------------------------------------------------
        |
        | Novi WorkTask uvijek pripada organizaciji
        | prijavljenog korisnika.
        |
        | user_id
        | = ownerId prijavljenog korisnika
        |
        | created_by_user_id
        | = stvarni prijavljeni korisnik
        |
        | Ta polja se ne smiju moći lažirati kroz API,
        | Tinker, import ili neki budući način kreiranja.
        |
        */

        static::creating(
            function (self $record): void {
                $user =
                    Auth::user();

                /*
                 * CLI / scheduler / queue može imati
                 * vlastitu eksplicitnu ownership logiku.
                 */
                if (! $user) {
                    return;
                }

                /*
                 * Superadmin ne kreira poslovne
                 * radne zadatke organizacije.
                 */
                if (
                    $user->isSuperAdmin()
                ) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'Superadmin ne može kreirati radni zadatak u ime organizacije.',
                    ]);
                }

                $ownerId =
                    (int) (
                        $user->ownerId()
                        ?? 0
                    );

                if ($ownerId <= 0) {
                    throw ValidationException::withMessages([
                        'user_id' =>
                            'Organizacija radnog zadatka nije valjano određena.',
                    ]);
                }

                /*
                 * Ownership uvijek određujemo
                 * prema prijavljenom korisniku.
                 */
                $record->user_id =
                    $ownerId;

                /*
                 * Autor je uvijek stvarni korisnik
                 * koji kreira radni zadatak.
                 */
                $record->created_by_user_id =
                    $user->id;

                /*
                 * Sigurni privacy default.
                 *
                 * Ako vrijednost nije eksplicitno
                 * zadana, zadatak ostaje privatan.
                 */
                if (
                    $record
                        ->is_shared_with_organization
                    === null
                ) {
                    $record
                        ->is_shared_with_organization =
                        false;
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | INTEGRITET KOD AŽURIRANJA
        |--------------------------------------------------------------------------
        |
        | Postojećem zadatku ne smije se promijeniti:
        |
        | - organizacija kojoj pripada
        | - postojeći autor
        |
        | Samo autor smije mijenjati
        | privatno / organizacijsko dijeljenje.
        |
        | Legacy zadatak bez autora može preuzeti
        | samo glavni korisnik njegove organizacije.
        |
        */

        static::updating(
            function (self $record): void {
                $user =
                    Auth::user();

                /*
                 * CLI / scheduler / queue:
                 * ne primjenjujemo Auth pravila.
                 */
                if (! $user) {
                    return;
                }

                /*
                 * Organizacija postojećeg zadatka
                 * nikada se ne smije promijeniti.
                 */
                $originalOwnerId =
                    (int) $record->getOriginal(
                        'user_id'
                    );

                $record->user_id =
                    $originalOwnerId;

                $originalCreatorId =
                    (int) (
                        $record->getOriginal(
                            'created_by_user_id'
                        )
                        ?? 0
                    );

                /*
                 * POSTOJEĆI AUTOR
                 *
                 * Ako zadatak već ima autora,
                 * autor se više ne smije promijeniti.
                 */
                if (
                    $originalCreatorId > 0
                ) {
                    $record->created_by_user_id =
                        $originalCreatorId;

                    /*
                     * Samo stvarni autor zadatka
                     * smije promijeniti vidljivost.
                     *
                     * Drugi korisnik organizacije
                     * može uređivati shared zadatak
                     * ako mu Resource to dopušta,
                     * ali ne može mijenjati njegovo
                     * private / shared stanje.
                     */
                    if (
                        (int) $user->id
                        !== $originalCreatorId
                    ) {
                        $record
                            ->is_shared_with_organization =
                            (bool) $record->getOriginal(
                                'is_shared_with_organization'
                            );
                    }

                    return;
                }

                /*
                 * LEGACY ZADATAK BEZ AUTORA
                 *
                 * Superadmin ga ne smije
                 * preuzeti kao svoj zadatak.
                 */
                if (
                    $user->isSuperAdmin()
                ) {
                    $record->created_by_user_id =
                        null;

                    $record
                        ->is_shared_with_organization =
                        (bool) $record->getOriginal(
                            'is_shared_with_organization'
                        );

                    return;
                }

                $ownerId =
                    (int) (
                        $user->ownerId()
                        ?? 0
                    );

                /*
                 * Samo glavni korisnik organizacije
                 * može preuzeti legacy zadatak
                 * koji nema autora.
                 */
                if (
                    $ownerId > 0
                    && (int) $user->id === $ownerId
                    && $originalOwnerId === $ownerId
                ) {
                    $record->created_by_user_id =
                        $user->id;

                    return;
                }

                /*
                 * Podkorisnik ili korisnik druge
                 * organizacije ne može preuzeti
                 * legacy zadatak niti promijeniti
                 * njegovu vidljivost.
                 */
                $record->created_by_user_id =
                    null;

                $record
                    ->is_shared_with_organization =
                    (bool) $record->getOriginal(
                        'is_shared_with_organization'
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
<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class OperationalLog extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule =
        'Operativni dnevnik';

    protected $fillable = [
        'user_id',
        'log_date',
        'title',
        'note',
        'items',
        'location',
        'type',
        'status',
        'attachments',
        'converted_type',
        'converted_id',
    ];

    protected $casts = [
        'log_date' =>
            'date',

        'items' =>
            'array',

        'attachments' =>
            'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | MODEL INTEGRITET
    |--------------------------------------------------------------------------
    |
    | Operativni dnevnik je osobni dnevnik korisnika.
    |
    | Za jednog korisnika i jedan datum smije postojati
    | samo jedan AKTIVNI zapis.
    |
    | Soft-deleted povijesni zapisi za isti datum
    | smiju ostati u bazi.
    |
    | DB unique indeks ostaje zadnja zaštitna razina,
    | dok model daje korisniku razumljivu poruku.
    |
    */

    protected static function booted(): void
    {
        /*
        |--------------------------------------------------------------------------
        | KREIRANJE
        |--------------------------------------------------------------------------
        */

        static::creating(
            function (self $record): void {
                static::validateDailyRecord(
                    $record
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | AŽURIRANJE
        |--------------------------------------------------------------------------
        |
        | Provjera vrijedi i ako korisnik promijeni datum
        | postojećeg dnevnika.
        |
        */

        static::updating(
            function (self $record): void {
                static::validateDailyRecord(
                    $record
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | RESTORE
        |--------------------------------------------------------------------------
        |
        | Ako za isti datum već postoji novi aktivni dnevnik,
        | stari soft-deleted zapis ne smije se vratiti.
        |
        */

        static::restoring(
            function (self $record): void {
                static::validateDailyRecord(
                    $record
                );
            }
        );
    }

    /**
     * Provjerava:
     *
     * - postoji li user_id
     * - postoji li log_date
     * - postoji li već drugi aktivni dnevnik
     *   za istog korisnika i isti datum
     */
    protected static function validateDailyRecord(
        self $record
    ): void {
        $userId =
            (int) (
                $record->user_id
                ?? 0
            );

        if ($userId <= 0) {
            throw ValidationException::withMessages([
                'user_id' =>
                    'Korisnik operativnog dnevnika nije valjano određen.',
            ]);
        }

        if (
            blank(
                $record->log_date
            )
        ) {
            throw ValidationException::withMessages([
                'log_date' =>
                    'Datum operativnog dnevnika je obavezan.',
            ]);
        }

        $logDate =
            Carbon::parse(
                $record->log_date
            )->toDateString();

        $duplicateQuery =
            static::withTrashed()
                ->where(
                    'user_id',
                    $userId
                )
                ->whereDate(
                    'log_date',
                    $logDate
                )
                ->whereNull(
                    'deleted_at'
                );

        /*
         * Kod updatea i restorea vlastiti zapis
         * ne smijemo računati kao duplikat.
         */
        if (
            $record->exists
            && $record->getKey()
        ) {
            $duplicateQuery->where(
                $record->getKeyName(),
                '!=',
                $record->getKey()
            );
        }

        if (
            $duplicateQuery->exists()
        ) {
            throw ValidationException::withMessages([
                'log_date' =>
                    'Za odabrani datum već postoji aktivni zapis operativnog dnevnika.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIJE
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function converted(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | POMOĆNE METODE
    |--------------------------------------------------------------------------
    */

    public function itemsCount(): int
    {
        return collect(
            $this->items
            ?? []
        )
            ->filter(
                fn ($item) =>
                    filled(
                        $item['note']
                        ?? null
                    )
            )
            ->count();
    }

    public function tasksCount(): int
    {
        return collect(
            $this->items
            ?? []
        )
            ->filter(
                fn ($item) =>
                    ! empty(
                        $item['create_task']
                        ?? null
                    )
            )
            ->count();
    }
}

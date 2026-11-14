<?php

namespace App\Imports;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use App\Models\PPEEquipment;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

class PPEEquipmentImport implements ToCollection
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $unchanged = 0;

    protected int $ownerId;

    public function __construct(
        int $ownerId
    ) {
        $this->ownerId =
            $ownerId;

        $user =
            Auth::user();

        /*
        |--------------------------------------------------------------------------
        | AUTORIZACIJA
        |--------------------------------------------------------------------------
        */

        if (
            $this->ownerId <= 0
            || ! $user
            || $user->isSuperAdmin()
            || (int) $user->ownerId()
                !== $this->ownerId
            || ! PPEEquipmentResource::canCreate()
            || ! PPEEquipmentResource::allowsModulePermission(
                'update'
            )
        ) {
            throw new RuntimeException(
                'Nemate dopuštenje za uvoz Registra OZO.'
            );
        }
    }

    public function collection(
        Collection $rows
    ): void {
        /*
        |--------------------------------------------------------------------------
        | ZAGLAVLJE
        |--------------------------------------------------------------------------
        */

        $header =
            $rows->first();

        if (! $header) {
            $this->skipped++;

            $this->logImport();

            return;
        }

        $map = [];

        foreach (
            $header
            as $index => $column
        ) {
            $key =
                $this->normalize(
                    $column
                );

            if (
                $key !== ''
            ) {
                $map[$key] =
                    $index;
            }
        }

        if (
            ! $this->hasAnyHeader(
                $map,
                [
                    'naziv_ozo',
                    'naziv',
                    'name',
                ]
            )
        ) {
            throw new RuntimeException(
                'Excel mora sadržavati stupac Naziv OZO.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | REDOVI
        |--------------------------------------------------------------------------
        */

        foreach (
            $rows->skip(1)
            as $row
        ) {
            if (
                $this->isEmptyRow(
                    $row
                )
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | NAZIV
            |--------------------------------------------------------------------------
            */

            $name =
                $this->cleanText(
                    $this->value(
                        $row,
                        $map,
                        [
                            'naziv_ozo',
                            'naziv',
                            'name',
                        ]
                    )
                );

            if (
                ! $name
                || mb_strlen(
                    $name
                ) > 191
            ) {
                $this->skipped++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | NORMA
            |--------------------------------------------------------------------------
            */

            $standard =
                $this->cleanText(
                    $this->value(
                        $row,
                        $map,
                        [
                            'hrn_en_norma',
                            'hrn_en',
                            'norma',
                            'standard',
                        ]
                    )
                );

            if (
                $standard !== null
                && mb_strlen(
                    $standard
                ) > 191
            ) {
                $this->skipped++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | ROK UPORABE
            |--------------------------------------------------------------------------
            */

            $durationRaw =
                $this->value(
                    $row,
                    $map,
                    [
                        'rok_uporabe',
                        'rok_uporabe_mjeseci',
                        'rok_mjeseci',
                        'duration_months',
                    ]
                );

            $duration =
                null;

            if (
                $durationRaw !== null
                && trim(
                    (string)
                    $durationRaw
                ) !== ''
            ) {
                if (
                    ! is_numeric(
                        $durationRaw
                    )
                ) {
                    $this->skipped++;

                    continue;
                }

                $duration =
                    (int) $durationRaw;

                if (
                    $duration < 0
                    || $duration > 120
                ) {
                    $this->skipped++;

                    continue;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | TRAŽENJE POSTOJEĆEG ZAPISA
            |--------------------------------------------------------------------------
            |
            | Identitet Registra OZO:
            |
            | user_id + name
            |
            | Isto pravilo postoji kao UNIQUE constraint
            | u bazi podataka.
            |
            */

            $record =
                PPEEquipment::query()
                    ->where(
                        'user_id',
                        $this->ownerId
                    )
                    ->where(
                        'name',
                        $name
                    )
                    ->first();

            /*
            |--------------------------------------------------------------------------
            | CREATE
            |--------------------------------------------------------------------------
            */

            if (! $record) {
                PPEEquipment::create([
                    'user_id' =>
                        $this->ownerId,

                    'name' =>
                        $name,

                    'standard' =>
                        $standard,

                    'duration_months' =>
                        $duration,

                    'is_active' =>
                        true,
                ]);

                $this->created++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE
            |--------------------------------------------------------------------------
            |
            | Prazna vrijednost iz Excela ne briše
            | postojeću vrijednost.
            |
            */

            $changed = [];

            if (
                $standard !== null
                && (string) (
                    $record->standard
                    ?? ''
                )
                    !==
                (string) $standard
            ) {
                $changed['standard'] =
                    $standard;
            }

            if (
                $duration !== null
                && (
                    $record->duration_months
                        === null
                    || (int)
                        $record->duration_months
                        !== $duration
                )
            ) {
                $changed[
                    'duration_months'
                ] =
                    $duration;
            }

            /*
             * is_active namjerno ne mijenjamo.
             *
             * Ako je korisnik ručno deaktivirao OZO,
             * ponovni import ga ne smije aktivirati.
             */

            if (
                empty(
                    $changed
                )
            ) {
                $this->unchanged++;

                continue;
            }

            $record->update(
                $changed
            );

            $this->updated++;
        }

        $this->logImport();
    }

    /*
    |--------------------------------------------------------------------------
    | HEADER
    |--------------------------------------------------------------------------
    */

    protected function hasAnyHeader(
        array $map,
        array $keys
    ): bool {
        foreach (
            $keys
            as $key
        ) {
            if (
                array_key_exists(
                    $key,
                    $map
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | LOG
    |--------------------------------------------------------------------------
    */

    protected function logImport(): void
    {
        ActivityLogger::import(
            module:
                'Registar OZO',

            created:
                $this->created,

            updated:
                $this->updated,

            unchanged:
                $this->unchanged,

            skipped:
                $this->skipped,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VALUE
    |--------------------------------------------------------------------------
    */

    protected function value(
        $row,
        array $map,
        array $keys
    ): mixed {
        foreach (
            $keys
            as $key
        ) {
            if (
                array_key_exists(
                    $key,
                    $map
                )
            ) {
                return
                    $row[
                        $map[$key]
                    ]
                    ?? null;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAN TEXT
    |--------------------------------------------------------------------------
    */

    protected function cleanText(
        mixed $value
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }

        $value =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string)
                    $value
                )
            );

        return
            $value === ''
                ? null
                : $value;
    }

    protected function clean(
        mixed $value
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }

        $value =
            trim(
                (string)
                $value
            );

        return
            $value === ''
                ? null
                : $value;
    }

    /*
    |--------------------------------------------------------------------------
    | EMPTY ROW
    |--------------------------------------------------------------------------
    */

    protected function isEmptyRow(
        $row
    ): bool {
        foreach (
            $row
            as $value
        ) {
            if (
                $this->clean(
                    $value
                ) !== null
            ) {
                return false;
            }
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE HEADER
    |--------------------------------------------------------------------------
    */

    protected function normalize(
        $value
    ): string {
        return Str::of(
            (string)
            $value
        )
            ->lower()
            ->ascii()
            ->replace(
                [
                    '.',
                    ',',
                    '/',
                    '\\',
                    '-',
                    '(',
                    ')',
                ],
                ' '
            )
            ->replaceMatches(
                '/\s+/',
                '_'
            )
            ->trim('_')
            ->toString();
    }
}
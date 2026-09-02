<?php

namespace App\Imports;

use App\Models\PPEEquipment;
use App\Services\ActivityLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class PPEEquipmentImport implements ToCollection
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $unchanged = 0;

    public function collection(
        Collection $rows
    ): void {
        $user =
            auth()->user();

        if (! $user) {
            $this->skipped++;

            $this->logImport();

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | ORGANIZACIJSKI REGISTAR OZO
        |--------------------------------------------------------------------------
        |
        | Registar OZO više nema globalne zapise.
        |
        | Svaki zapis pripada organizaciji:
        |
        | user_id = ownerId()
        |
        | Glavni korisnik i njegovi podkorisnici
        | koriste isti Registar OZO.
        |
        | Superadmin ne uvozi OZO u ime organizacije.
        |
        */

        if ($user->isSuperAdmin()) {
            $this->skipped++;

            $this->logImport();

            return;
        }

        $ownerId =
            (int) $user->ownerId();

        if ($ownerId <= 0) {
            $this->skipped++;

            $this->logImport();

            return;
        }

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

            if ($key !== '') {
                $map[$key] =
                    $index;
            }
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
            /*
             * Prazan red ignoriramo.
             */
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
                $this->clean(
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

            /*
             * Naziv je obavezan.
             */
            if (! $name) {
                $this->skipped++;

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | NORMA
            |--------------------------------------------------------------------------
            */

            $standard =
                $this->clean(
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

            $duration = null;

            /*
             * Rok nije obavezan.
             *
             * Ako postoji, mora biti broj
             * između 0 i 240 mjeseci.
             */
            if (
                $durationRaw !== null
                && trim(
                    (string) $durationRaw
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
                    || $duration > 240
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
            | Zapis tražimo samo unutar
            | trenutne organizacije.
            |
            | Organizacija A nikada ne može
            | pronaći ili izmijeniti zapis
            | organizacije B.
            |
            */

            $record =
                PPEEquipment::query()
                    ->where(
                        'user_id',
                        $ownerId
                    )
                    ->where(
                        'name',
                        $name
                    )
                    ->first();

            /*
            |--------------------------------------------------------------------------
            | NOVI ZAPIS
            |--------------------------------------------------------------------------
            */

            if (! $record) {
                PPEEquipment::create([
                    'user_id' =>
                        $ownerId,

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
            | AŽURIRANJE POSTOJEĆEG ZAPISA
            |--------------------------------------------------------------------------
            |
            | Prazna vrijednost iz Excela
            | ne briše postojeću vrijednost.
            |
            */

            $changed = [];

            if (
                $standard !== null
                && (string) (
                    $record->standard
                    ?? ''
                ) !==
                (string) $standard
            ) {
                $changed['standard'] =
                    $standard;
            }

            if (
                $duration !== null
                && (
                    $record
                        ->duration_months
                    === null

                    || (int) $record
                        ->duration_months
                    !== $duration
                )
            ) {
                $changed[
                    'duration_months'
                ] = $duration;
            }

            /*
             * is_active namjerno ne mijenjamo.
             *
             * Ako je korisnik ručno deaktivirao OZO,
             * novi import ga neće ponovno aktivirati.
             */

            if (empty($changed)) {
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

    protected function clean(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value === ''
            ? null
            : $value;
    }

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

    protected function normalize(
        $value
    ): string {
        return Str::of(
            (string) $value
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
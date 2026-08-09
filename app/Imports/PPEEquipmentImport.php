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

    public function collection(Collection $rows): void
    {
        $user = auth()->user();

        if (! $user) {
            $this->skipped++;

            ActivityLogger::import(
                module: 'Registar OZO',
                created: $this->created,
                updated: $this->updated,
                unchanged: $this->unchanged,
                skipped: $this->skipped,
            );

            return;
        }

        /*
         * Registar OZO koristi posebnu logiku:
         *
         * Superadmin:
         * user_id = NULL
         * => globalni OZO zapis koji vide sve organizacije.
         *
         * Glavni korisnik / podkorisnik:
         * user_id = ownerId()
         * => zapis pripada cijeloj organizaciji.
         */
        $ownerId = $user->isSuperAdmin()
            ? null
            : $user->ownerId();

        /*
         * Organizacijski korisnik bez ownerId-a
         * ne smije napraviti import.
         */
        if (! $user->isSuperAdmin() && ! $ownerId) {
            $this->skipped++;

            ActivityLogger::import(
                module: 'Registar OZO',
                created: $this->created,
                updated: $this->updated,
                unchanged: $this->unchanged,
                skipped: $this->skipped,
            );

            return;
        }

        $header = $rows->first();

        if (! $header) {
            $this->skipped++;

            ActivityLogger::import(
                module: 'Registar OZO',
                created: $this->created,
                updated: $this->updated,
                unchanged: $this->unchanged,
                skipped: $this->skipped,
            );

            return;
        }

        $map = [];

        foreach ($header as $index => $column) {
            $key = $this->normalize($column);

            if ($key !== '') {
                $map[$key] = $index;
            }
        }

        foreach ($rows->skip(1) as $row) {
            $name = trim(
                (string) $this->value(
                    $row,
                    $map,
                    [
                        'naziv_ozo',
                        'naziv',
                        'name',
                    ]
                )
            );

            if ($name === '') {
                $this->skipped++;

                continue;
            }

            $standard = trim(
                (string) $this->value(
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

            $duration = $this->value(
                $row,
                $map,
                [
                    'rok_uporabe',
                    'rok_uporabe_mjeseci',
                    'rok_mjeseci',
                    'duration_months',
                ]
            );

            /*
             * Tražimo zapis samo unutar istog scopea.
             *
             * Globalni import:
             * user_id IS NULL
             *
             * Organizacijski import:
             * user_id = ownerId()
             */
            $record = PPEEquipment::query()
                ->where('user_id', $ownerId)
                ->where('name', $name)
                ->first();

            $data = [
                'standard' => $standard !== ''
                    ? $standard
                    : null,

                'duration_months' => is_numeric($duration)
                    ? (int) $duration
                    : null,

                'is_active' => true,
            ];

            if (! $record) {
                PPEEquipment::create([
                    'user_id' => $ownerId,
                    'name' => $name,
                    ...$data,
                ]);

                $this->created++;

                continue;
            }

            $changed = [];

            foreach ($data as $field => $value) {
                $current = $record->{$field};

                /*
                 * Boolean vrijednosti normaliziramo
                 * prije usporedbe.
                 */
                if ($field === 'is_active') {
                    $current = (bool) $current;
                    $value = (bool) $value;
                }

                /*
                 * Integer vrijednosti također normaliziramo.
                 */
                if ($field === 'duration_months') {
                    $current = $current !== null
                        ? (int) $current
                        : null;

                    $value = $value !== null
                        ? (int) $value
                        : null;
                }

                if ($current !== $value) {
                    $changed[$field] = $value;
                }
            }

            if (empty($changed)) {
                $this->unchanged++;

                continue;
            }

            $record->update($changed);

            $this->updated++;
        }

        ActivityLogger::import(
            module: 'Registar OZO',
            created: $this->created,
            updated: $this->updated,
            unchanged: $this->unchanged,
            skipped: $this->skipped,
        );
    }

    protected function value(
        $row,
        array $map,
        array $keys
    ): mixed {
        foreach ($keys as $key) {
            if (array_key_exists($key, $map)) {
                return $row[$map[$key]]
                    ?? null;
            }
        }

        return null;
    }

    protected function normalize($value): string
    {
        return Str::of((string) $value)
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
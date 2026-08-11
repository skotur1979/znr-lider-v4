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
            $this->logImport();

            return;
        }

        /*
         * Registar OZO:
         *
         * Superadmin:
         * user_id = NULL
         * => globalni zapis.
         *
         * Organizacijski korisnik:
         * user_id = ownerId()
         * => zapis cijele organizacije.
         */
        $ownerId = $user->isSuperAdmin()
            ? null
            : $user->ownerId();

        if (! $user->isSuperAdmin() && ! $ownerId) {
            $this->skipped++;
            $this->logImport();

            return;
        }

        $header = $rows->first();

        if (! $header) {
            $this->skipped++;
            $this->logImport();

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
            /*
             * Prazan red ignoriramo i ne brojimo
             * ga kao preskočeni zapis.
             */
            if ($this->isEmptyRow($row)) {
                continue;
            }

            $name = $this->clean(
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
             * Naziv je obavezan kao i kod ručnog unosa.
             */
            if (! $name) {
                $this->skipped++;

                continue;
            }

            $standard = $this->clean(
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

            $durationRaw = $this->value(
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
             * Rok uporabe nije obavezan.
             *
             * Ako je upisan, mora odgovarati
             * pravilima ručnog unosa:
             * 0 - 240 mjeseci.
             */
            $duration = null;

            if (
                $durationRaw !== null
                && trim((string) $durationRaw) !== ''
            ) {
                if (! is_numeric($durationRaw)) {
                    $this->skipped++;

                    continue;
                }

                $duration = (int) $durationRaw;

                if ($duration < 0 || $duration > 240) {
                    $this->skipped++;

                    continue;
                }
            }

            /*
             * Zapis tražimo isključivo
             * unutar odgovarajućeg scopea.
             */
            $recordQuery = PPEEquipment::query()
                ->where('name', $name);

            if ($ownerId === null) {
                $recordQuery->whereNull('user_id');
            } else {
                $recordQuery->where(
                    'user_id',
                    $ownerId
                );
            }

            $record = $recordQuery->first();

            /*
             * Novi zapis.
             */
            if (! $record) {
                PPEEquipment::create([
                    'user_id' => $ownerId,
                    'name' => $name,
                    'standard' => $standard,
                    'duration_months' => $duration,
                    'is_active' => true,
                ]);

                $this->created++;

                continue;
            }

            /*
             * Postojeći zapis.
             *
             * Prazna vrijednost u Excelu ne briše
             * postojeću vrijednost iz baze.
             */
            $changed = [];

            if (
                $standard !== null
                && (string) ($record->standard ?? '')
                    !== (string) $standard
            ) {
                $changed['standard'] = $standard;
            }

            if (
                $duration !== null
                && (
                    $record->duration_months === null
                    || (int) $record->duration_months
                        !== $duration
                )
            ) {
                $changed['duration_months'] =
                    $duration;
            }

            /*
             * is_active se namjerno ne mijenja importom.
             *
             * Ako je OZO ručno deaktiviran,
             * import ga neće ponovno aktivirati.
             */

            if (empty($changed)) {
                $this->unchanged++;

                continue;
            }

            $record->update($changed);

            $this->updated++;
        }

        $this->logImport();
    }

    protected function logImport(): void
    {
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

    protected function clean(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }

    protected function isEmptyRow(
        $row
    ): bool {
        foreach ($row as $value) {
            if ($this->clean($value) !== null) {
                return false;
            }
        }

        return true;
    }

    protected function normalize(
        $value
    ): string {
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
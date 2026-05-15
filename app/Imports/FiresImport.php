<?php

namespace App\Imports;

use App\Models\Fire;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class FiresImport implements ToCollection
{
    public int $created = 0;
    public int $updated = 0;
    public int $unchanged = 0;
    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        $headerRowIndex = 3;
        $dataStartIndex = 4;

        $headers = $rows->get($headerRowIndex);

        if (! $headers) {
            $this->skipped++;

            ActivityLogger::import(
                module: 'Vatrogasni aparati',
                created: $this->created,
                updated: $this->updated,
                unchanged: $this->unchanged,
                skipped: $this->skipped,
            );

            return;
        }

        $map = [];

        foreach ($headers as $index => $header) {
            $key = $this->normalizeKey($header);

            if ($key) {
                $map[$key] = $index;
            }
        }

        for ($i = $dataStartIndex; $i < $rows->count(); $i++) {
            $row = $rows->get($i);

            if (! $row || $this->isEmptyRow($row)) {
                continue;
            }

            $place = $this->clean($this->value($row, $map, 'mjesto'));

            if (! $place) {
                $this->skipped++;
                continue;
            }

            $type = $this->clean($this->value($row, $map, 'tip'));
            $factory = $this->clean($this->value($row, $map, 'tvor_broj'));
            $serial = $this->clean($this->value($row, $map, 'serijski_broj'));

            $serviceFrom = $this->parseDate($this->value($row, $map, 'datum_periodickog_servisa'));
            $validUntil = $this->parseDate($this->value($row, $map, 'vrijedi_do'));
            $regularFrom = $this->parseDate($this->value($row, $map, 'datum_redovnog_pregleda'));

            $service = $this->clean($this->value($row, $map, 'serviser'));
            $visible = $this->clean($this->value($row, $map, 'uocljivost'));
            $remarkAction = $this->clean($this->value($row, $map, 'uoceni_nedostaci_postupci_otklanjanja'));

            if (! $serviceFrom || ! $validUntil || ! $regularFrom) {
                $this->skipped++;
                continue;
            }

            $userId = Auth::user()?->ownerId() ?? Auth::id();

            $fire = Fire::query()
                ->where('user_id', $userId)
                ->where('place', $place)
                ->where(function ($query) use ($serial) {
                    if ($serial) {
                        $query->where('serial_label_number', $serial);
                    } else {
                        $query->whereNull('serial_label_number');
                    }
                })
                ->first();

            $data = [
                'user_id' => $userId,
                'place' => $place,
                'type' => $type,
                'factory_number_year_of_production' => $factory,
                'serial_label_number' => $serial,
                'examination_valid_from' => $serviceFrom,
                'examination_valid_until' => $validUntil,
                'regular_examination_valid_from' => $regularFrom,
                'service' => $service,
                'visible' => $visible,
                'remark' => $remarkAction,
                'action' => $remarkAction,
            ];

            if (! $fire) {
                Fire::create($data);
                $this->created++;
                continue;
            }

            $changed = [];

            foreach ($data as $field => $value) {
                if (in_array($field, ['user_id', 'place', 'serial_label_number'], true)) {
                    continue;
                }

                if ($value === null) {
                    continue;
                }

                $current = $fire->{$field};

                if ($current instanceof \Carbon\CarbonInterface) {
                    $current = $current->format('Y-m-d');
                }

                if ((string) ($current ?? '') !== (string) $value) {
                    $changed[$field] = $value;
                }
            }

            if (empty($changed)) {
                $this->unchanged++;
                continue;
            }

            $fire->update($changed);
            $this->updated++;
        }

        ActivityLogger::import(
            module: 'Vatrogasni aparati',
            created: $this->created,
            updated: $this->updated,
            unchanged: $this->unchanged,
            skipped: $this->skipped,
        );
    }

    private function value($row, array $map, string $key)
    {
        return array_key_exists($key, $map) ? ($row[$map[$key]] ?? null) : null;
    }

    private function isEmptyRow($row): bool
    {
        foreach ($row as $value) {
            if ($this->clean($value) !== null) {
                return false;
            }
        }

        return true;
    }

    private function clean($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $value = rtrim(trim((string) $value), '.');

        foreach (['d.m.Y', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.y', 'd/m/y', 'd-m-y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date->format('Y-m-d');
                }
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeKey($key): ?string
    {
        if ($key === null || trim((string) $key) === '') {
            return null;
        }

        $key = Str::of((string) $key)
            ->lower()
            ->replace(['š', 'đ', 'č', 'ć', 'ž'], ['s', 'd', 'c', 'c', 'z'])
            ->replace(['/', '-', '.', '(', ')'], ' ')
            ->replace("\u{00A0}", ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->replace(' ', '_')
            ->toString();

        return match ($key) {
            'mjesto' => 'mjesto',
            'tip' => 'tip',
            'tvor_broj', 'tvorn_broj', 'tvornicki_broj', 'tvornicki_broj_godina_proizvodnje' => 'tvor_broj',
            'serijski_broj', 'ser_broj', 'serijski_broj_evidencijske_naljepnice' => 'serijski_broj',
            'datum_periodickog_servisa' => 'datum_periodickog_servisa',
            'vrijedi_do' => 'vrijedi_do',
            'datum_redovnog_pregleda' => 'datum_redovnog_pregleda',
            'serviser' => 'serviser',
            'uocljivost' => 'uocljivost',
            'uoceni_nedostaci_postupci_otklanjanja',
            'uoceni_nedostaci',
            'postupci_otklanjanja' => 'uoceni_nedostaci_postupci_otklanjanja',
            default => $key,
        };
    }
}
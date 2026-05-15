<?php

namespace App\Imports;

use App\Models\Machine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Services\ActivityLogger;

class MachinesImport implements ToCollection
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

            $name = $this->clean($this->value($row, $map, 'naziv'));

            if (! $name) {
                $this->skipped++;
                continue;
            }

            $manufacturer = $this->clean($this->value($row, $map, 'proizvodac'));
            $factoryNumber = $this->clean($this->value($row, $map, 'tvornicki_broj'));
            $inventoryNumber = $this->clean($this->value($row, $map, 'inventarni_broj'));
            $validFrom = $this->parseDate($this->value($row, $map, 'vrijedi_od'));
            $validUntil = $this->parseDate($this->value($row, $map, 'vrijedi_do'));
            $examinedBy = $this->clean($this->value($row, $map, 'ispitao'));
            $reportNo = $this->clean($this->value($row, $map, 'broj_izvjestaja'));
            $location = $this->clean($this->value($row, $map, 'lokacija'));
            $remark = $this->clean($this->value($row, $map, 'napomena'));

            if (! $validFrom || ! $validUntil || ! $location) {
                $this->skipped++;
                continue;
            }

            $userId = Auth::user()?->ownerId() ?? Auth::id();

            $machine = Machine::query()
                ->where('user_id', $userId)
                ->where('name', $name)
                ->where(function ($query) use ($factoryNumber) {
                    if ($factoryNumber) {
                        $query->where('factory_number', $factoryNumber);
                    } else {
                        $query->whereNull('factory_number');
                    }
                })
                ->first();

            $data = [
                'user_id' => $userId,
                'name' => $name,
                'manufacturer' => $manufacturer,
                'factory_number' => $factoryNumber,
                'inventory_number' => $inventoryNumber,
                'examination_valid_from' => $validFrom,
                'examination_valid_until' => $validUntil,
                'examined_by' => $examinedBy,
                'report_number' => $reportNo,
                'location' => $location,
                'remark' => $remark,
            ];

            if (! $machine) {
                Machine::create($data);
                $this->created++;
                continue;
            }

            $changed = [];

            foreach ($data as $field => $value) {
                if (in_array($field, ['user_id', 'name', 'factory_number'], true)) {
                    continue;
                }

                if ($value === null) {
                    continue;
                }

                $current = $machine->{$field};

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

            $machine->update($changed);
            $this->updated++;
        }

        ActivityLogger::import(
            module: 'Radna oprema',
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
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->replace(' ', '_')
            ->toString();

        return match ($key) {
            'naziv' => 'naziv',
            'proizvodac' => 'proizvodac',
            'tvornicki_broj', 'tvorn_broj', 'tvornicki_br', 'tvor_broj' => 'tvornicki_broj',
            'inventarni_broj', 'inventarni_br' => 'inventarni_broj',
            'vrijedi_od' => 'vrijedi_od',
            'vrijedi_do' => 'vrijedi_do',
            'ispitao' => 'ispitao',
            'broj_izvjestaja', 'broj_izvestaja', 'izvjestaj_broj' => 'broj_izvjestaja',
            'lokacija' => 'lokacija',
            'napomena' => 'napomena',
            default => $key,
        };
    }
}
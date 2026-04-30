<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Miscellaneous;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MiscellaneousImport implements ToCollection
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

            $categoryName = $this->clean($this->value($row, $map, 'kategorija'));
            $examiner = $this->clean($this->value($row, $map, 'ispitao'));
            $reportNo = $this->clean($this->value($row, $map, 'broj_izvjestaja'));
            $validFrom = $this->parseDate($this->value($row, $map, 'vrijedi_od'));
            $validUntil = $this->parseDate($this->value($row, $map, 'vrijedi_do'));
            $remark = $this->clean($this->value($row, $map, 'napomena'));

            if (! $categoryName || ! $validFrom || ! $validUntil) {
                $this->skipped++;
                continue;
            }

            $userId = Auth::user()?->ownerId() ?? Auth::id();

            $category = Category::firstOrCreate([
                'user_id' => $userId,
                'name' => $categoryName,
            ]);

            $record = Miscellaneous::query()
                ->where('user_id', $userId)
                ->where('name', $name)
                ->where('category_id', $category->id)
                ->first();

            $data = [
                'user_id' => $userId,
                'category_id' => $category->id,
                'name' => $name,
                'examiner' => $examiner,
                'report_number' => $reportNo,
                'examination_valid_from' => $validFrom,
                'examination_valid_until' => $validUntil,
                'remark' => $remark,
            ];

            if (! $record) {
                Miscellaneous::create($data);
                $this->created++;
                continue;
            }

            $changed = [];

            foreach ($data as $field => $value) {
                if (in_array($field, ['user_id', 'name', 'category_id'], true)) {
                    continue;
                }

                if ($value === null) {
                    continue;
                }

                $current = $record->{$field};

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

            $record->update($changed);
            $this->updated++;
        }
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
            'kategorija' => 'kategorija',
            'ispitao' => 'ispitao',
            'broj_izvjestaja', 'broj_izvestaja', 'izvjestaj_broj' => 'broj_izvjestaja',
            'vrijedi_od' => 'vrijedi_od',
            'vrijedi_do' => 'vrijedi_do',
            'napomena' => 'napomena',
            default => $key,
        };
    }
}
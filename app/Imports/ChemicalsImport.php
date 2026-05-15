<?php

namespace App\Imports;

use App\Models\Chemical;
use App\Services\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ChemicalsImport implements ToCollection
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
                module: 'Kemikalije',
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

            $productName = $this->clean($this->value($row, $map, 'ime_proizvoda'));

            if (! $productName) {
                $this->skipped++;
                continue;
            }

            $userId = Auth::user()?->ownerId() ?? Auth::id();

            $cas = $this->clean($this->value($row, $map, 'cas_broj'));

            $chemical = Chemical::query()
                ->where('user_id', $userId)
                ->where('product_name', $productName)
                ->where(function ($query) use ($cas) {
                    if ($cas) {
                        $query->where('cas_number', $cas);
                    } else {
                        $query->whereNull('cas_number');
                    }
                })
                ->first();

            $data = [
                'user_id' => $userId,
                'product_name' => $productName,
                'cas_number' => $cas,
                'ufi_number' => $this->clean($this->value($row, $map, 'ufi_broj')),
                'hazard_pictograms' => $this->parseList($this->value($row, $map, 'piktogrami')),
                'h_statements' => $this->parseList($this->value($row, $map, 'h_oznake')),
                'p_statements' => $this->parseList($this->value($row, $map, 'p_oznake')),
                'usage_location' => $this->clean($this->value($row, $map, 'mjesto_upotrebe')),
                'annual_quantity' => $this->clean($this->value($row, $map, 'kolicina_kg_l')),
                'gvi_kgvi' => $this->clean($this->value($row, $map, 'gvi_kgvi')),
                'voc' => $this->clean($this->value($row, $map, 'voc')),
                'stl_hzjz' => $this->parseDate($this->value($row, $map, 'stl_hzjz')),
            ];

            if (! $chemical) {
                Chemical::create($data);
                $this->created++;
                continue;
            }

            $changed = [];

            foreach ($data as $field => $value) {
                if (in_array($field, ['user_id', 'product_name', 'cas_number'], true)) {
                    continue;
                }

                if ($value === null) {
                    continue;
                }

                $current = $chemical->{$field};

                if ($current instanceof \Carbon\CarbonInterface) {
                    $current = $current->format('Y-m-d');
                }

                if (is_array($current)) {
                    $current = json_encode(array_values($current));
                }

                $compareValue = is_array($value)
                    ? json_encode(array_values($value))
                    : (string) $value;

                if ((string) ($current ?? '') !== (string) $compareValue) {
                    $changed[$field] = $value;
                }
            }

            if (empty($changed)) {
                $this->unchanged++;
                continue;
            }

            $chemical->update($changed);
            $this->updated++;
        }

        ActivityLogger::import(
            module: 'Kemikalije',
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

    private function parseList($value): array
    {
        $value = $this->clean($value);

        if (! $value) {
            return [];
        }

        return collect(preg_split('/[,\n\r;]+/', $value) ?: [])
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
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
            'ime_proizvoda', 'product_name' => 'ime_proizvoda',
            'cas', 'cas_broj', 'cas_number' => 'cas_broj',
            'ufi', 'ufi_broj', 'ufi_number' => 'ufi_broj',
            'piktogrami', 'hazard_pictograms' => 'piktogrami',
            'h_oznake', 'h_oznaka', 'h_statements' => 'h_oznake',
            'p_oznake', 'p_oznaka', 'p_statements' => 'p_oznake',
            'mjesto_upotrebe', 'usage_location' => 'mjesto_upotrebe',
            'kolicina', 'kolicina_kg_l', 'annual_quantity' => 'kolicina_kg_l',
            'gvi_kgvi' => 'gvi_kgvi',
            'voc' => 'voc',
            'stl', 'stl_hzjz' => 'stl_hzjz',
            default => $key,
        };
    }
}
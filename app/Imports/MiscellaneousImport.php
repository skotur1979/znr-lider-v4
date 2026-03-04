<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Miscellaneous;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MiscellaneousImport implements ToModel, WithHeadingRow
{
    public function headingRow(): int
    {
        return 1;
    }

    public function model(array $row)
    {
        $row = $this->normalizeKeys($row);

        $name = $row['naziv'] ?? $row['name'] ?? null;
        $categoryName = $row['kategorija'] ?? $row['category'] ?? null;

        $examiner = $row['ispitao'] ?? $row['examiner'] ?? null;
        $reportNumber = $row['broj_izvjestaja'] ?? $row['report_number'] ?? null;

        $from = $this->parseDate($row['vrijedi_od'] ?? $row['examination_valid_from'] ?? null);
        $until = $this->parseDate($row['vrijedi_do'] ?? $row['examination_valid_until'] ?? null);

        $remark = $row['napomena'] ?? $row['remark'] ?? null;

        if (! $name) {
            return null;
        }

        if (! $from || ! $until) {
            Log::warning('MiscellaneousesImport skipped row (missing required dates)', [
                'name' => $name,
                'from' => $from,
                'until' => $until,
                'row' => $row,
            ]);
            return null;
        }

        $userId = Auth::id();

        $categoryId = null;
        if ($categoryName) {
            $categoryName = trim((string) $categoryName);

            $category = Category::firstOrCreate(
                ['user_id' => $userId, 'name' => $categoryName],
                ['user_id' => $userId, 'name' => $categoryName],
            );

            $categoryId = $category->id;
        }

        return Miscellaneous::updateOrCreate(
            [
                'user_id' => $userId,
                'name' => $name,
                'category_id' => $categoryId,
                'examination_valid_until' => $until,
            ],
            [
                'examiner' => $examiner,
                'report_number' => $reportNumber,
                'examination_valid_from' => $from,
                'remark' => $remark,
            ]
        );
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float) $value);
                return Carbon::instance($dt)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        $value = trim((string) $value);
        $value = rtrim($value, '.');

        $formats = ['d.m.Y', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'd.m.y', 'd/m/y', 'd-m-y'];

        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value);
                if ($dt !== false) {
                    return $dt->format('Y-m-d');
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

    private function normalizeKeys(array $row): array
    {
        $out = [];

        foreach ($row as $key => $val) {
            $k = Str::of((string) $key)
                ->lower()
                ->replace(['š','đ','č','ć','ž'], ['s','d','c','c','z'])
                ->replace(['/', '-', '.', '(', ')'], ' ')
                ->replace("\u{00A0}", ' ')
                ->trim()
                ->toString();

            $k = preg_replace('/\s+/', '_', $k);

            $out[$k] = $val;
        }

        return $out;
    }
}
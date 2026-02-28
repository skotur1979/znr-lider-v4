<?php

namespace App\Imports;

use App\Models\Chemical;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ChemicalsImport implements ToModel, WithHeadingRow
{
    public function headingRow(): int
    {
        return 1;
    }

    public function model(array $row)
    {
        $row = $this->normalizeKeys($row);

        $productName = $row['ime_proizvoda'] ?? $row['product_name'] ?? null;
        $cas         = $row['cas'] ?? $row['cas_broj'] ?? $row['cas_number'] ?? null;
        $ufi         = $row['ufi'] ?? $row['ufi_broj'] ?? $row['ufi_number'] ?? null;

        $pikt = $this->parseList($row['piktogrami'] ?? $row['hazard_pictograms'] ?? null);
        $h    = $this->parseList($row['h_oznake'] ?? $row['h_oznaka'] ?? $row['h_statements'] ?? null);
        $p    = $this->parseList($row['p_oznake'] ?? $row['p_oznaka'] ?? $row['p_statements'] ?? null);

        $usage = $row['mjesto_upotrebe'] ?? $row['usage_location'] ?? null;

        $qty   = $row['kolicina_kg_l'] ?? $row['kolicina'] ?? $row['annual_quantity'] ?? null;
        $gvi   = $row['gvi_kgvi'] ?? null;
        $voc   = $row['voc'] ?? null;

        $stl = $this->parseDate($row['stl_hzjz'] ?? $row['stl'] ?? null);

        // Preskoči prazne retke
        if (! $productName) {
            return null;
        }

        $userId = Auth::id();

        // ✅ update-or-create po (product_name + cas_number) za tog usera
        return Chemical::updateOrCreate(
            [
                'user_id' => $userId,
                'product_name' => $productName,
                'cas_number' => $cas,
            ],
            [
                'ufi_number' => $ufi,
                'hazard_pictograms' => $pikt,
                'h_statements' => $h,
                'p_statements' => $p,
                'usage_location' => $usage,
                'annual_quantity' => $qty,
                'gvi_kgvi' => $gvi,
                'voc' => $voc,
                'stl_hzjz' => $stl,
            ]
        );
    }

    private function parseList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return collect($value)
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->values()
                ->all();
        }

        $str = trim((string) $value);
        if ($str === '') {
            return [];
        }

        return collect(preg_split('/[,\n\r;]+/', $str) ?: [])
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel serial number
        if (is_numeric($value)) {
            try {
                $date = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value));
                return $date->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);
        $value = rtrim($value, '.');

        $formats = [
            'd.m.Y',
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'd.m.y',
            'd/m/y',
            'd-m-y',
        ];

        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value);
                if ($dt !== false) {
                    return $dt->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                // continue
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeKeys(array $row): array
    {
        $out = [];

        foreach ($row as $key => $val) {
            $k = (string) $key;

            $k = Str::of($k)
                ->lower()
                ->replace(['š','đ','č','ć','ž'], ['s','d','c','c','z'])
                ->replace(['/', '-', '.', '(', ')'], ' ')
                ->replace(['  '], ' ')
                ->trim()
                ->toString();

            $k = preg_replace('/\s+/', '_', $k);

            // pomoćne normalizacije
            $k = str_replace('ime_proizvoda', 'ime_proizvoda', $k);
            $k = str_replace('stl_hzjz', 'stl_hzjz', $k);

            $out[$k] = $val;
        }

        return $out;
    }
}
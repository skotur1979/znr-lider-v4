<?php

namespace App\Imports;

use App\Models\Fire;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FiresImport implements ToModel, WithHeadingRow
{
    public function headingRow(): int
    {
        return 1;
    }

    public function model(array $row)
    {
        $row = $this->normalizeKeys($row);

        $place  = $row['mjesto'] ?? null;
        $type   = $row['tip'] ?? null;

        // tvor. broj (export header ti je "Tvor. broj")
        $factory = $row['tvor_broj'] ?? $row['tvorn_broj'] ?? $row['tvornicki_broj'] ?? null;

        // serijski (export header ti je "Serijski broj")
        $serial  = $row['serijski_broj'] ?? $row['ser_broj'] ?? null;

        // datumi
        $serviceFrom = $this->parseDate($row['datum_periodickog_servisa'] ?? null);
        $validUntil  = $this->parseDate($row['vrijedi_do'] ?? null);
        $regularFrom = $this->parseDate($row['datum_redovnog_pregleda'] ?? null);

        $service = $row['serviser'] ?? null;
        $visible = $row['uocljivost'] ?? null;
        $remark  = $row['uoceni_nedostaci'] ?? null;
        $action  = $row['postupci_otklanjanja'] ?? null;

        // preskoči prazne retke
        if (! $place) {
            return null;
        }

        // ova 3 su NOT NULL u bazi -> ako fale, preskoči red, ali logiraj da znaš zašto
        if (! $serviceFrom || ! $validUntil || ! $regularFrom) {
            Log::warning('FiresImport skipped row (missing required dates)', [
                'place' => $place,
                'serviceFrom' => $serviceFrom,
                'validUntil' => $validUntil,
                'regularFrom' => $regularFrom,
                'row' => $row,
            ]);

            return null;
        }

        $userId = Auth::id();

        return Fire::updateOrCreate(
            [
                'user_id' => $userId,
                'place' => $place,
                'serial_label_number' => $serial,
            ],
            [
                'type' => $type,
                'factory_number_year_of_production' => $factory,
                'examination_valid_from' => $serviceFrom,
                'examination_valid_until' => $validUntil,
                'regular_examination_valid_from' => $regularFrom,
                'service' => $service,
                'visible' => $visible,
                'remark' => $remark,
                'action' => $action,
            ]
        );
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // ✅ ako Maatwebsite vrati Carbon/DateTime objekt
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        // Excel serial number (npr. 45231)
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
                // continue
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
                ->replace("\u{00A0}", ' ') // non-breaking space
                ->trim()
                ->toString();

            $k = preg_replace('/\s+/', '_', $k);

            // ✅ ciljano mapiranje tvojih headera (bez širokih replace-ova)
            $aliases = [
                'tvor_broj' => 'tvor_broj',
                'tvorn_broj' => 'tvor_broj',
                'tvornicki_broj' => 'tvor_broj',
                'serijski_broj' => 'serijski_broj',
                'ser_broj' => 'serijski_broj',

                'datum_periodickog_servisa' => 'datum_periodickog_servisa',
                'datum_periodickog_servisa_' => 'datum_periodickog_servisa',
                'datum_periodickog_servisa__' => 'datum_periodickog_servisa',

                'datum_redovnog_pregleda' => 'datum_redovnog_pregleda',

                'uoceni_nedostaci' => 'uoceni_nedostaci',
                'postupci_otklanjanja' => 'postupci_otklanjanja',
            ];

            $k = $aliases[$k] ?? $k;

            $out[$k] = $val;
        }

        return $out;
    }
}
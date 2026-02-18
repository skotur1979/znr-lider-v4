<?php

namespace App\Imports;

use App\Models\Machine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MachinesImport implements ToModel, WithHeadingRow
{
    public function headingRow(): int
    {
        return 1; // prva linija je header
    }

    public function model(array $row)
    {
        // Normalizirani ključevi (ako Excel header ima razmake/čudna slova)
        $row = $this->normalizeKeys($row);

        // Mapiranje kolona (prilagodi ako ti se headeri zovu drugačije)
        $name            = $row['naziv'] ?? null;
        $manufacturer    = $row['proizvodac'] ?? null;
        $factoryNumber   = $row['tvornicki_broj'] ?? null;
        $inventoryNumber = $row['inventarni_broj'] ?? null;

        $validFrom  = $this->parseDate($row['vrijedi_od'] ?? null);
        $validUntil = $this->parseDate($row['vrijedi_do'] ?? null);

        $examinedBy  = $row['ispitao'] ?? null;
        $reportNo    = $row['broj_izvjestaja'] ?? null;
        $location    = $row['lokacija'] ?? null;
        $remark      = $row['napomena'] ?? null;

        // Preskoči prazne retke
        if (! $name) {
            return null;
        }

        // ✅ user_id: standardni korisnik uvozi sebi, admin može uvesti sebi (ili kasnije proširimo da bira korisnika)
        $userId = Auth::id();

        // ✅ update-or-create po (name + factory_number) (po želji promijeni kriterij)
        return Machine::updateOrCreate(
            [
                'user_id' => $userId,
                'name' => $name,
                'factory_number' => $factoryNumber,
            ],
            [
                'manufacturer' => $manufacturer,
                'inventory_number' => $inventoryNumber,
                'examination_valid_from' => $validFrom,
                'examination_valid_until' => $validUntil,
                'examined_by' => $examinedBy,
                'report_number' => $reportNo,
                'location' => $location,
                'remark' => $remark,
            ]
        );
    }

    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Excel serial number (npr. 45231)
        if (is_numeric($value)) {
            try {
                // Excel date serial -> Carbon (1900 date system)
                $date = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value));
                return $date->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        // Makni završnu točku (13.05.2025.)
        $value = rtrim($value, '.');

        // Najčešći formati koje želiš podržati
        $formats = [
            'd.m.Y',     // 13.05.2025
            'd/m/Y',     // 13/05/2025
            'd-m-Y',     // 13-05-2025
            'Y-m-d',     // 2025-05-13
            'd.m.y',     // 13.05.25
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
                // nastavi
            }
        }

        // Zadnja šansa: Carbon “pogađanje”
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

            // Neke tvoje kolone
            $k = str_replace('tvorn_broj', 'tvornicki_broj', $k);
            $k = str_replace('broj_izvjestaja', 'broj_izvjestaja', $k);

            $out[$k] = $val;
        }

        return $out;
    }
}

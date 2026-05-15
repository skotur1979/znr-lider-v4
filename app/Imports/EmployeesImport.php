<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Services\ActivityLogger;

class EmployeesImport implements ToCollection
{
    public int $created = 0;
    public int $updated = 0;
    public int $unchanged = 0;
    public int $skipped = 0;

    public int $certificatesCreated = 0;
    public int $certificatesUpdated = 0;
    public int $certificatesUnchanged = 0;

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

            $name = $this->clean($this->value($row, $map, 'ime_i_prezime'));

            if (! $name) {
                $this->skipped++;
                continue;
            }

            $userId = Auth::user()?->ownerId() ?? Auth::id();

            $oib = $this->clean($this->value($row, $map, 'oib'));
            $email = $this->clean($this->value($row, $map, 'email'));
            $phone = $this->clean($this->value($row, $map, 'telefon'));

            $query = Employee::query()->where('user_id', $userId);

            $employee = null;

            if ($oib) {
                $employee = (clone $query)->where('OIB', $oib)->first();
            }

            if (! $employee && $email) {
                $employee = (clone $query)->where('email', $email)->first();
            }

            if (! $employee && $phone) {
                $employee = (clone $query)
                    ->where('name', $name)
                    ->where('phone', $phone)
                    ->first();
            }

            if (! $employee) {
                $employee = (clone $query)
                    ->where('name', $name)
                    ->first();
            }

            $data = [
                'user_id' => $userId,
                'name' => $name,
                'job_title' => $this->clean($this->value($row, $map, 'zanimanje')),
                'education' => $this->clean($this->value($row, $map, 'skolska_sprema')),
                'place_of_birth' => $this->clean($this->value($row, $map, 'datum_i_mjesto_rodenja')),
                'name_of_parents' => $this->clean($this->value($row, $map, 'ime_oca_majke')),
                'address' => $this->clean($this->value($row, $map, 'adresa')),
                'gender' => $this->clean($this->value($row, $map, 'spol')),
                'OIB' => $oib,
                'phone' => $phone,
                'email' => $email,
                'workplace' => $this->clean($this->value($row, $map, 'radno_mjesto')),
                'organization_unit' => $this->clean($this->value($row, $map, 'organizacijska_jedinica')),
                'contract_type' => $this->clean($this->value($row, $map, 'vrsta_ugovora')),
                'employeed_at' => $this->parseDate($this->value($row, $map, 'datum_zaposlenja')),
                'contract_ended_at' => $this->parseDate($this->value($row, $map, 'datum_prekida_ugovora')),
                'medical_examination_valid_from' => $this->parseDate($this->value($row, $map, 'lijecnicki_pregled_od')),
                'medical_examination_valid_until' => $this->parseDate($this->value($row, $map, 'lijecnicki_pregled_do')),
                'article' => $this->clean($this->value($row, $map, 'clanak_3_tocke')),
                'remark' => $this->clean($this->value($row, $map, 'napomena')),
                'occupational_safety_valid_from' => $this->parseDate($this->value($row, $map, 'znr_od')),
                'fire_protection_valid_from' => $this->parseDate($this->value($row, $map, 'zop_od')),
                'fire_protection_statement_at' => $this->parseDate($this->value($row, $map, 'zop_izjava_od')),
                'evacuation_valid_from' => $this->parseDate($this->value($row, $map, 'evakuacija_od')),
                'first_aid_valid_from' => $this->parseDate($this->value($row, $map, 'prva_pomoc_od')),
                'first_aid_valid_until' => $this->parseDate($this->value($row, $map, 'prva_pomoc_do')),
                'toxicology_valid_from' => $this->parseDate($this->value($row, $map, 'toksikologija_od')),
                'toxicology_valid_until' => $this->parseDate($this->value($row, $map, 'toksikologija_do')),
                'handling_flammable_materials_valid_from' => $this->parseDate($this->value($row, $map, 'rukovanje_zapaljivim_tvarima_od')),
                'handling_flammable_materials_valid_until' => $this->parseDate($this->value($row, $map, 'rukovanje_zapaljivim_tvarima_do')),
                'employers_authorization_valid_from' => $this->parseDate($this->value($row, $map, 'ovlastenik_poslodavca_od')),
                'employers_authorization_valid_until' => $this->parseDate($this->value($row, $map, 'ovlastenik_poslodavca_do')),
            ];

            if (! $employee) {
                $employee = Employee::create($data);
                $this->created++;
            } else {
                $changed = [];

                foreach ($data as $field => $value) {
                    if (in_array($field, ['user_id'], true)) {
                        continue;
                    }

                    if ($value === null) {
                        continue;
                    }

                    $current = $employee->{$field};

                    if ($current instanceof \Carbon\CarbonInterface) {
                        $current = $current->format('Y-m-d');
                    }

                    if ((string) ($current ?? '') !== (string) $value) {
                        $changed[$field] = $value;
                    }
                }

                if (empty($changed)) {
                    $this->unchanged++;
                } else {
                    $employee->update($changed);
                    $this->updated++;
                }
            }

            $this->importCertificates($employee, $row, $map);
            ActivityLogger::import(
            module: 'Zaposlenici',
            created: $this->created,
            updated: $this->updated,
            unchanged: $this->unchanged,
            skipped: $this->skipped,
        );
        }
    }

    private function importCertificates(Employee $employee, $row, array $map): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $title = $this->clean($this->value($row, $map, "certifikat_{$i}_naziv"));

            if (! $title) {
                continue;
            }

            $validFrom = $this->parseDate($this->value($row, $map, "certifikat_{$i}_od"));
            $validUntil = $this->parseDate($this->value($row, $map, "certifikat_{$i}_do"));

            $certificate = EmployeeCertificate::query()
                ->where('employee_id', $employee->id)
                ->where('title', $title)
                ->first();

            $data = [
                'employee_id' => $employee->id,
                'title' => $title,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
            ];

            if (! $certificate) {
                EmployeeCertificate::create($data);
                $this->certificatesCreated++;
                continue;
            }

            $changed = [];

            foreach ($data as $field => $value) {
                if (in_array($field, ['employee_id', 'title'], true)) {
                    continue;
                }

                if ($value === null) {
                    continue;
                }

                $current = $certificate->{$field};

                if ($current instanceof \Carbon\CarbonInterface) {
                    $current = $current->format('Y-m-d');
                }

                if ((string) ($current ?? '') !== (string) $value) {
                    $changed[$field] = $value;
                }
            }

            if (empty($changed)) {
                $this->certificatesUnchanged++;
                continue;
            }

            $certificate->update($changed);
            $this->certificatesUpdated++;
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
            'ime_i_prezime' => 'ime_i_prezime',
            'zanimanje' => 'zanimanje',
            'skolska_sprema' => 'skolska_sprema',
            'datum_i_mjesto_rodenja' => 'datum_i_mjesto_rodenja',
            'ime_oca_majke' => 'ime_oca_majke',
            'adresa' => 'adresa',
            'spol' => 'spol',
            'oib' => 'oib',
            'telefon' => 'telefon',
            'email' => 'email',
            'radno_mjesto' => 'radno_mjesto',
            'organizacijska_jedinica' => 'organizacijska_jedinica',
            'vrsta_ugovora' => 'vrsta_ugovora',
            'datum_zaposlenja' => 'datum_zaposlenja',
            'datum_prekida_ugovora' => 'datum_prekida_ugovora',
            'lijecnicki_pregled_od' => 'lijecnicki_pregled_od',
            'lijecnicki_pregled_do' => 'lijecnicki_pregled_do',
            'clanak_3_tocke' => 'clanak_3_tocke',
            'napomena' => 'napomena',
            'znr_od' => 'znr_od',
            'zop_od' => 'zop_od',
            'zop_izjava_od' => 'zop_izjava_od',
            'evakuacija_od' => 'evakuacija_od',
            'prva_pomoc_od' => 'prva_pomoc_od',
            'prva_pomoc_do' => 'prva_pomoc_do',
            'toksikologija_od' => 'toksikologija_od',
            'toksikologija_do' => 'toksikologija_do',
            'rukovanje_zapaljivim_tvarima_od' => 'rukovanje_zapaljivim_tvarima_od',
            'rukovanje_zapaljivim_tvarima_do' => 'rukovanje_zapaljivim_tvarima_do',
            'ovlastenik_poslodavca_od' => 'ovlastenik_poslodavca_od',
            'ovlastenik_poslodavca_do' => 'ovlastenik_poslodavca_do',
            default => $key,
        };
    }
}
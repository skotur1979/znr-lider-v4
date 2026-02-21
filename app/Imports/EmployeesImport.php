<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $d = $row->toArray();

            Validator::make($d, [
                'ime_i_prezime' => ['required', 'string', 'max:255'],
            ])->validate();

            $userId = Auth::id();

            $oib   = trim((string) ($d['oib'] ?? ''));
            $email = trim((string) ($d['email'] ?? ''));

            // ✅ pronađi postojećeg po prioritetu:
            // 1) OIB (najbolje)
            // 2) email
            // 3) fallback: ime + telefon (ako baš ništa)
            $employeeQuery = Employee::query()
                ->when(! Auth::user()?->isAdmin(), fn ($q) => $q->where('user_id', $userId));

            $employee = null;

            if ($oib !== '') {
                $employee = (clone $employeeQuery)->where('OIB', $oib)->first();
            }

            if (! $employee && $email !== '') {
                $employee = (clone $employeeQuery)->where('email', $email)->first();
            }

            if (! $employee) {
                $name  = trim((string) ($d['ime_i_prezime'] ?? ''));
                $phone = trim((string) ($d['telefon'] ?? ''));

                if ($name !== '' && $phone !== '') {
                    $employee = (clone $employeeQuery)
                        ->where('name', $name)
                        ->where('phone', $phone)
                        ->first();
                }
            }

            if (! $employee) {
                $employee = new Employee();
                $employee->user_id = $userId;
            }

            // ✅ mapiranje
            $employee->name = $d['ime_i_prezime'] ?? null;
            $employee->address = $d['adresa'] ?? null;
            $employee->gender = $d['spol'] ?? null;
            $employee->OIB = $oib !== '' ? $oib : null;
            $employee->phone = $d['telefon'] ?? null;
            $employee->email = $email !== '' ? $email : null;

            $employee->workplace = $d['radno_mjesto'] ?? null;
            $employee->organization_unit = $d['organizacijska_jedinica'] ?? null;
            $employee->contract_type = $d['vrsta_ugovora'] ?? null;

            $employee->job_title = $d['zanimanje'] ?? null;
            $employee->education = $d['skolska_sprema'] ?? null;
            $employee->place_of_birth = $d['datum_i_mjesto_rodenja'] ?? null;
            $employee->name_of_parents = $d['ime_oca_majke'] ?? null;

            $employee->employeed_at = $this->parseCroDate($d['datum_zaposlenja'] ?? null);
            $employee->contract_ended_at = $this->parseCroDate($d['datum_prekida_ugovora'] ?? null);

            $employee->medical_examination_valid_from = $this->parseCroDate($d['lijecnicki_pregled_od'] ?? null);
            $employee->medical_examination_valid_until = $this->parseCroDate($d['lijecnicki_pregled_do'] ?? null);

            $employee->article = $d['clanak_3_tocke'] ?? null;

            $employee->occupational_safety_valid_from = $this->parseCroDate($d['znr_od'] ?? null);
            $employee->fire_protection_valid_from = $this->parseCroDate($d['zop_od'] ?? null);
            $employee->fire_protection_statement_at = $this->parseCroDate($d['zop_izjava_od'] ?? null);
            $employee->evacuation_valid_from = $this->parseCroDate($d['evakuacija_od'] ?? null);

            $employee->first_aid_valid_from = $this->parseCroDate($d['prva_pomoc_od'] ?? null);
            $employee->first_aid_valid_until = $this->parseCroDate($d['prva_pomoc_do'] ?? null);

            $employee->toxicology_valid_from = $this->parseCroDate($d['toksikologija_od'] ?? null);
            $employee->toxicology_valid_until = $this->parseCroDate($d['toksikologija_do'] ?? null);

            $employee->employers_authorization_valid_from = $this->parseCroDate($d['ovlastenik_poslodavca_od'] ?? null);
            $employee->employers_authorization_valid_until = $this->parseCroDate($d['ovlastenik_poslodavca_do'] ?? null);

            $employee->save();

            // ✅ certifikati 1..10 (bez dupliranja)
            for ($i = 1; $i <= 10; $i++) {
                $title = trim((string) ($d["certifikat_{$i}_naziv"] ?? ''));
                if ($title === '') continue;

                $from = $this->parseCroDate($d["certifikat_{$i}_od"] ?? null);
                $until = $this->parseCroDate($d["certifikat_{$i}_do"] ?? null);

                EmployeeCertificate::query()->firstOrCreate([
                    'employee_id' => $employee->id,
                    'title'       => $title,
                    'valid_from'  => $from,
                    'valid_until' => $until,
                ]);
            }
        }
    }

    private function parseCroDate($value): ?string
    {
        if ($value === null || $value === '') return null;

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }

        $v = trim((string) $value);
        if ($v === '') return null;

        $v = rtrim($v, '.');

        try {
            return Carbon::createFromFormat('d.m.Y', $v)->format('Y-m-d');
        } catch (\Throwable) {
            try {
                return Carbon::parse($v)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
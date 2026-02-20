<?php

namespace App\Exports;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeesExport extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    ShouldAutoSize,
    WithEvents,
    WithCustomValueBinder
{
    /** @var \Illuminate\Support\Collection<int, \App\Models\Employee> */
    protected $employees;

    public function __construct()
    {
        $this->employees = EmployeeResource::getEloquentQuery()
            ->orderBy('name')
            ->get();
    }

    public function collection()
    {
        return $this->employees;
    }

    /**
     * ✅ samo OIB i telefon forsiramo kao TEXT
     * D = oib, E = telefon
     */
    public function bindValue(Cell $cell, $value): bool
    {
        $col = $cell->getColumn();

        if (in_array($col, ['D', 'E'], true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        // sve ostalo (uključujući datume) ostaje normalno: brojevi ostaju brojevi
        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'ime_i_prezime','adresa','spol','oib','telefon','email','radno_mjesto','organizacijska_jedinica',
            'vrsta_ugovora','zanimanje','skolska_sprema','datum_i_mjesto_rodenja','ime_oca_majke',
            'datum_zaposlenja','datum_prekida_ugovora','lijecnicki_pregled_od','lijecnicki_pregled_do',
            'clanak_3_tocke','znr_od','zop_od','zop_izjava_od','evakuacija_od','prva_pomoc_od','prva_pomoc_do',
            'toksikologija_od','toksikologija_do','ovlastenik_poslodavca_od','ovlastenik_poslodavca_do',

            'certifikat_1_naziv','certifikat_1_od','certifikat_1_do',
            'certifikat_2_naziv','certifikat_2_od','certifikat_2_do',
            'certifikat_3_naziv','certifikat_3_od','certifikat_3_do',
            'certifikat_4_naziv','certifikat_4_od','certifikat_4_do',
            'certifikat_5_naziv','certifikat_5_od','certifikat_5_do',
            'certifikat_6_naziv','certifikat_6_od','certifikat_6_do',
            'certifikat_7_naziv','certifikat_7_od','certifikat_7_do',
            'certifikat_8_naziv','certifikat_8_od','certifikat_8_do',
            'certifikat_9_naziv','certifikat_9_od','certifikat_9_do',
            'certifikat_10_naziv','certifikat_10_od','certifikat_10_do',
        ];
    }

    public function map($e): array
    {
        /** @var Employee $e */
        $certs = $e->certificates?->values() ?? collect();

        $excel = fn ($date) => $date ? ExcelDate::dateTimeToExcel(Carbon::parse($date)) : null;

        $row = [
            $e->name,
            $e->address,
            $e->gender,
            (string) ($e->OIB ?? ''),
            (string) ($e->phone ?? ''),
            $e->email,
            $e->workplace,
            $e->organization_unit,
            $e->contract_type,
            $e->job_title,
            $e->education,
            $e->place_of_birth,
            $e->name_of_parents,

            $excel($e->employeed_at),
            $excel($e->contract_ended_at),
            $excel($e->medical_examination_valid_from),
            $excel($e->medical_examination_valid_until),

            $e->article,

            $excel($e->occupational_safety_valid_from),
            $excel($e->fire_protection_valid_from),
            $excel($e->fire_protection_statement_at),
            $excel($e->evacuation_valid_from),
            $excel($e->first_aid_valid_from),
            $excel($e->first_aid_valid_until),
            $excel($e->toxicology_valid_from),
            $excel($e->toxicology_valid_until),
            $excel($e->employers_authorization_valid_from),
            $excel($e->employers_authorization_valid_until),
        ];

        for ($i = 0; $i < 10; $i++) {
            $c = $certs->get($i);
            $row[] = $c?->title ?? null;
            $row[] = $excel($c?->valid_from);
            $row[] = $excel($c?->valid_until);
        }

        return $row;
    }

    public function columnFormats(): array
    {
        $d = NumberFormat::FORMAT_DATE_DDMMYYYY;

        return [
            'N' => $d, 'O' => $d, 'P' => $d, 'Q' => $d,
            'S' => $d, 'T' => $d, 'U' => $d, 'V' => $d,
            'W' => $d, 'X' => $d, 'Y' => $d, 'Z' => $d,
            'AA' => $d, 'AB' => $d,

            'AD' => $d, 'AE' => $d,
            'AG' => $d, 'AH' => $d,
            'AJ' => $d, 'AK' => $d,
            'AM' => $d, 'AN' => $d,
            'AP' => $d, 'AQ' => $d,
            'AS' => $d, 'AT' => $d,
            'AV' => $d, 'AW' => $d,
            'AY' => $d, 'AZ' => $d,
            'BB' => $d, 'BC' => $d,
            'BE' => $d, 'BF' => $d,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Header kao Machines
                $sheet->getStyle('A1:BF1')->getFont()->setBold(true);
                $sheet->getStyle('A1:BF1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Wrap duga polja
                foreach (['B', 'F', 'G', 'H', 'L', 'M', 'R'] as $col) {
                    $sheet->getStyle("{$col}1:{$col}{$lastRow}")
                        ->getAlignment()
                        ->setWrapText(true);
                }

                // Boje isteka kao Machines (računamo iz modela)
                $today = Carbon::today();
                $soon  = $today->copy()->addDays(30);

                $doMap = [
                    'Q'  => fn(Employee $e) => $e->medical_examination_valid_until,
                    'X'  => fn(Employee $e) => $e->first_aid_valid_until,
                    'Z'  => fn(Employee $e) => $e->toxicology_valid_until,
                    'AB' => fn(Employee $e) => $e->employers_authorization_valid_until,
                ];

                $certDoCols = [
                    'AE' => 0, 'AH' => 1, 'AK' => 2, 'AN' => 3, 'AQ' => 4,
                    'AT' => 5, 'AW' => 6, 'AZ' => 7, 'BC' => 8, 'BF' => 9,
                ];

                foreach ($this->employees as $i => $e) {
                    $row = $i + 2;

                    foreach ($doMap as $col => $getter) {
                        $untilRaw = $getter($e);
                        if (! $untilRaw) continue;

                        $until = Carbon::parse($untilRaw);
                        $cell  = "{$col}{$row}";

                        if ($until->lt($today)) {
                            $this->fillCell($sheet, $cell, 'FFFF0000'); // 🔴
                        } elseif ($until->lte($soon)) {
                            $this->fillCell($sheet, $cell, 'FFFFFF00'); // 🟡
                        }
                    }

                    $certs = $e->certificates?->values() ?? collect();
                    foreach ($certDoCols as $col => $idx) {
                        $c = $certs->get($idx);
                        if (! $c?->valid_until) continue;

                        $until = Carbon::parse($c->valid_until);
                        $cell  = "{$col}{$row}";

                        if ($until->lt($today)) {
                            $this->fillCell($sheet, $cell, 'FFFF0000'); // 🔴
                        } elseif ($until->lte($soon)) {
                            $this->fillCell($sheet, $cell, 'FFFFFF00'); // 🟡
                        }
                    }
                }
            },
        ];
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $style = $sheet->getStyle($cell);

        $style->getFill()->setFillType(Fill::FILL_SOLID);
        $style->getFill()->getStartColor()->setARGB($argb);
        $style->getFont()->setBold(true);
    }
}
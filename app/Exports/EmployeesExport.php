<?php

namespace App\Exports;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
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

class EmployeesExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithEvents, WithCustomValueBinder
{
    protected $employees;

    protected bool $showUserColumn = false;

    public function __construct(?array $employeeIds = null)
{
    $user = auth()->user();

    $this->showUserColumn =
        (bool) $user?->isSuperAdmin()
        || (bool) $user?->canCreateSubusers();

    $query = EmployeeResource::getEloquentQuery()
        ->with(['user', 'certificates'])
        ->orderBy('name');

    if ($employeeIds !== null && count($employeeIds) > 0) {
        $query->whereIn('employees.id', $employeeIds);
    }

    $this->employees = $query->get();
}

    public function collection()
    {
        return $this->employees;
    }

    public function bindValue(Cell $cell, $value): bool
{
    $textColumns = $this->showUserColumn
        ? ['I', 'J', 'V']
        : ['H', 'I', 'U'];

    if (in_array($cell->getColumn(), $textColumns, true)) {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
        return true;
    }

    return parent::bindValue($cell, $value);
}

    public function headings(): array
    {
        $headings = ['Ime i prezime'];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Zanimanje',
            'Školska sprema',
            'Datum i mjesto rođenja',
            'Ime oca/majke',
            'Adresa',
            'Spol',
            'OIB',
            'Telefon',
            'E-mail',
            'Radno mjesto',
            'Organizacijska jedinica',
            'Vrsta ugovora',
            'Datum zaposlenja',
            'Datum prekida ugovora',
            'Liječnički pregled od',
            'Liječnički pregled do',
            'Članak 3. točke',
            'Napomena liječnika',
            'ZNR od',
            'ZNR rok do',
            'ZOP od',
            'ZOP izjava od',
            'Evakuacija od',
            'Prva pomoć od',
            'Prva pomoć do',
            'Toksikologija od',
            'Toksikologija do',
            'Rukovanje zapaljivim tvarima od',
            'Rukovanje zapaljivim tvarima do',
            'Ovlaštenik poslodavca od',
            'Ovlaštenik poslodavca do',

            'Broj priloga',

            'Certifikat 1 naziv', 'Certifikat 1 od', 'Certifikat 1 do',
            'Certifikat 2 naziv', 'Certifikat 2 od', 'Certifikat 2 do',
            'Certifikat 3 naziv', 'Certifikat 3 od', 'Certifikat 3 do',
            'Certifikat 4 naziv', 'Certifikat 4 od', 'Certifikat 4 do',
            'Certifikat 5 naziv', 'Certifikat 5 od', 'Certifikat 5 do',
            'Certifikat 6 naziv', 'Certifikat 6 od', 'Certifikat 6 do',
            'Certifikat 7 naziv', 'Certifikat 7 od', 'Certifikat 7 do',
            'Certifikat 8 naziv', 'Certifikat 8 od', 'Certifikat 8 do',
            'Certifikat 9 naziv', 'Certifikat 9 od', 'Certifikat 9 do',
            'Certifikat 10 naziv', 'Certifikat 10 od', 'Certifikat 10 do',
        ]);
    }

    public function map($employee): array
{
    /** @var Employee $employee */

    $certs = $employee->certificates?->values() ?? collect();

    $excel = fn ($date) => $date
        ? ExcelDate::dateTimeToExcel(Carbon::parse($date))
        : null;

    $znrDueText = null;

    if (! $employee->occupational_safety_valid_from && $employee->znrTrainingDueDate()) {
        $dueDate = Carbon::parse($employee->znrTrainingDueDate())->format('d.m.Y.');

        $znrDueText = $employee->znrTrainingStatus() === 'expired'
            ? "ISTEKAO ROK:\n{$dueDate}"
            : "POLOŽITI DO:\n{$dueDate}";
    }

    $row = [$employee->name];

    if ($this->showUserColumn) {
        $row[] = $employee->user?->name ?? '';
    }

    $row = array_merge($row, [
        $employee->job_title,
        $employee->education,
        $employee->place_of_birth,
        $employee->name_of_parents,
        $employee->address,
        $employee->gender,
        (string) ($employee->OIB ?? ''),
        (string) ($employee->phone ?? ''),
        $employee->email,
        $employee->workplace,
        $employee->organization_unit,
        $employee->contract_type,

        $excel($employee->employeed_at),
        $excel($employee->contract_ended_at),
        $excel($employee->medical_examination_valid_from),
        $excel($employee->medical_examination_valid_until),

        $employee->article,
        $employee->remark,

        $excel($employee->occupational_safety_valid_from),
        $znrDueText,

        $excel($employee->fire_protection_valid_from),
        $excel($employee->fire_protection_statement_at),
        $excel($employee->evacuation_valid_from),
        $excel($employee->first_aid_valid_from),
        $excel($employee->first_aid_valid_until),
        $excel($employee->toxicology_valid_from),
        $excel($employee->toxicology_valid_until),
        $excel($employee->handling_flammable_materials_valid_from),
        $excel($employee->handling_flammable_materials_valid_until),
        $excel($employee->employers_authorization_valid_from),
        $excel($employee->employers_authorization_valid_until),

        is_array($employee->pdf) ? count($employee->pdf) : 0,
    ]);

    for ($i = 0; $i < 10; $i++) {
        $certificate = $certs->get($i);

        $row[] = $certificate?->title;
        $row[] = $excel($certificate?->valid_from);
        $row[] = $excel($certificate?->valid_until);
    }

    return $row;
}

    public function columnFormats(): array
{
    $date = NumberFormat::FORMAT_DATE_DDMMYYYY;

    if ($this->showUserColumn) {
        return [
            'O' => $date, 'P' => $date, 'Q' => $date, 'R' => $date,
            'U' => $date,
            'W' => $date, 'X' => $date, 'Y' => $date,
            'Z' => $date, 'AA' => $date, 'AB' => $date, 'AC' => $date,
            'AD' => $date, 'AE' => $date, 'AF' => $date, 'AG' => $date,

            'AJ' => $date, 'AK' => $date,
            'AM' => $date, 'AN' => $date,
            'AP' => $date, 'AQ' => $date,
            'AS' => $date, 'AT' => $date,
            'AV' => $date, 'AW' => $date,
            'AY' => $date, 'AZ' => $date,
            'BB' => $date, 'BC' => $date,
            'BE' => $date, 'BF' => $date,
            'BH' => $date, 'BI' => $date,
            'BK' => $date, 'BL' => $date,
        ];
    }

    return [
        'N' => $date, 'O' => $date, 'P' => $date, 'Q' => $date,
        'T' => $date,
        'V' => $date, 'W' => $date, 'X' => $date,
        'Y' => $date, 'Z' => $date, 'AA' => $date, 'AB' => $date,
        'AC' => $date, 'AD' => $date, 'AE' => $date, 'AF' => $date,

        'AI' => $date, 'AJ' => $date,
        'AL' => $date, 'AM' => $date,
        'AO' => $date, 'AP' => $date,
        'AR' => $date, 'AS' => $date,
        'AU' => $date, 'AV' => $date,
        'AX' => $date, 'AY' => $date,
        'BA' => $date, 'BB' => $date,
        'BD' => $date, 'BE' => $date,
        'BG' => $date, 'BH' => $date,
        'BJ' => $date, 'BK' => $date,
    ];
}

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            $lastRow = $this->employees->count() + 1;
            $lastCol = $this->showUserColumn ? 'BL' : 'BK';

            $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                ->getFont()
                ->setName('DejaVu Sans')
                ->setSize(10);

            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'name' => 'DejaVu Sans',
                    'size' => 10,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1F2937'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);

            $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(false);

            $sheet->getRowDimension(1)->setRowHeight(42);

            for ($row = 2; $row <= $lastRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(32);
            }

            if ($this->showUserColumn) {
                $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("K2:K{$lastRow}")->getAlignment()->setWrapText(false);

                $sheet->getStyle("O2:R{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("U2:AG{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("V2:V{$lastRow}")->getAlignment()
                    ->setWrapText(true)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("AI2:BL{$lastRow}")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $widths = [
                    'A' => 26,
                    'B' => 16,
                    'C' => 34,
                    'D' => 15,
                    'E' => 36,
                    'F' => 22,
                    'G' => 36,
                    'H' => 8,
                    'I' => 17,
                    'J' => 21,
                    'K' => 38,
                    'L' => 26,
                    'M' => 22,
                    'N' => 16,

                    'O' => 15,
                    'P' => 15,
                    'Q' => 15,
                    'R' => 15,
                    'S' => 22,
                    'T' => 22,

                    'U' => 15,
                    'V' => 15,
                    'W' => 15,
                    'X' => 15,
                    'Y' => 15,
                    'Z' => 15,
                    'AA' => 15,
                    'AB' => 15,
                    'AC' => 15,
                    'AD' => 15,
                    'AE' => 15,
                    'AF' => 15,
                    'AG' => 15,
                    'AH' => 14,

                    'AI' => 25,
                    'AJ' => 13,
                    'AK' => 13,

                    'AL' => 25,
                    'AM' => 13,
                    'AN' => 13,

                    'AO' => 25,
                    'AP' => 13,
                    'AQ' => 13,

                    'AR' => 25,
                    'AS' => 13,
                    'AT' => 13,

                    'AU' => 25,
                    'AV' => 13,
                    'AW' => 13,

                    'AX' => 25,
                    'AY' => 13,
                    'AZ' => 13,

                    'BA' => 25,
                    'BB' => 13,
                    'BC' => 13,

                    'BD' => 25,
                    'BE' => 13,
                    'BF' => 13,

                    'BG' => 25,
                    'BH' => 13,
                    'BI' => 13,

                    'BJ' => 25,
                    'BK' => 13,
                    'BL' => 13,
                ];

                $deadlineColumns = [
                    'R' => fn (Employee $e) => $e->medical_examination_valid_until,
                    'AA' => fn (Employee $e) => $e->first_aid_valid_until,
                    'AC' => fn (Employee $e) => $e->toxicology_valid_until,
                    'AE' => fn (Employee $e) => $e->handling_flammable_materials_valid_until,
                    'AG' => fn (Employee $e) => $e->employers_authorization_valid_until,
                ];

                $znrDueColumn = 'V';

                $certificateDeadlineColumns = [
                    'AK' => 0,
                    'AN' => 1,
                    'AQ' => 2,
                    'AT' => 3,
                    'AW' => 4,
                    'AZ' => 5,
                    'BC' => 6,
                    'BF' => 7,
                    'BI' => 8,
                    'BL' => 9,
                ];
            } else {
                $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("F2:F{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("J2:J{$lastRow}")->getAlignment()->setWrapText(false);

                $sheet->getStyle("N2:Q{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("T2:AF{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("U2:U{$lastRow}")->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("AH2:BK{$lastRow}")->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("AH2:AH{$lastRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("AI2:BK{$lastRow}")->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setWrapText(true);

                $widths = [
                    'A' => 26,
                    'B' => 16,
                    'C' => 34,
                    'D' => 15,
                    'E' => 36,
                    'F' => 22,
                    'G' => 36,
                    'H' => 8,
                    'I' => 17,
                    'J' => 21,
                    'K' => 38,
                    'L' => 26,
                    'M' => 22,
                    'N' => 16,

                    'O' => 15,
                    'P' => 15,
                    'Q' => 15,
                    'R' => 15,
                    'S' => 22,
                    'T' => 22,

                    'U' => 15,
                    'V' => 15,
                    'W' => 15,
                    'X' => 15,
                    'Y' => 15,
                    'Z' => 15,
                    'AA' => 15,
                    'AB' => 15,
                    'AC' => 15,
                    'AD' => 15,
                    'AE' => 15,
                    'AF' => 15,
                    'AG' => 14,

                    'AH' => 10,   // Broj priloga

                    'AI' => 24,
                    'AJ' => 13,
                    'AK' => 13,

                    'AL' => 24,
                    'AM' => 13,
                    'AN' => 13,

                    'AO' => 24,
                    'AP' => 13,
                    'AQ' => 13,

                    'AR' => 24,
                    'AS' => 13,
                    'AT' => 13,

                    'AU' => 24,
                    'AV' => 13,
                    'AW' => 13,

                    'AX' => 24,
                    'AY' => 13,
                    'AZ' => 13,

                    'BA' => 24,
                    'BB' => 13,
                    'BC' => 13,

                    'BD' => 24,
                    'BE' => 13,
                    'BF' => 13,

                    'BG' => 24,
                    'BH' => 13,
                    'BI' => 13,

                    'BJ' => 24,
                    'BK' => 13,
                ];

                $deadlineColumns = [
                    'Q' => fn (Employee $e) => $e->medical_examination_valid_until,
                    'Z' => fn (Employee $e) => $e->first_aid_valid_until,
                    'AB' => fn (Employee $e) => $e->toxicology_valid_until,
                    'AD' => fn (Employee $e) => $e->handling_flammable_materials_valid_until,
                    'AF' => fn (Employee $e) => $e->employers_authorization_valid_until,
                ];

                $znrDueColumn = 'U';

                $certificateDeadlineColumns = [
                    'AJ' => 0,
                    'AM' => 1,
                    'AP' => 2,
                    'AS' => 3,
                    'AV' => 4,
                    'AY' => 5,
                    'BB' => 6,
                    'BE' => 7,
                    'BH' => 8,
                    'BK' => 9,
                ];
            }

            foreach ($widths as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }

            $today = Carbon::today();
            $soon = $today->copy()->addDays(30);

            foreach ($this->employees as $index => $employee) {
                $row = $index + 2;

                foreach ($deadlineColumns as $column => $getter) {
                    $date = $getter($employee);

                    if (! $date) {
                        continue;
                    }

                    $this->colorDeadlineCell($sheet, "{$column}{$row}", Carbon::parse($date), $today, $soon);
                }

                if (! $employee->occupational_safety_valid_from && $employee->znrTrainingDueDate()) {
                    $dueDate = Carbon::parse($employee->znrTrainingDueDate());

                    $this->colorDeadlineCell($sheet, "{$znrDueColumn}{$row}", $dueDate, $today, $soon);
                }

                $certificates = $employee->certificates?->values() ?? collect();

                foreach ($certificateDeadlineColumns as $column => $certificateIndex) {
                    $certificate = $certificates->get($certificateIndex);

                    if (! $certificate?->valid_until) {
                        continue;
                    }

                    $this->colorDeadlineCell($sheet, "{$column}{$row}", Carbon::parse($certificate->valid_until), $today, $soon);
                }
            }

            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
        },
    ];
}

    private function colorDeadlineCell($sheet, string $cell, Carbon $date, Carbon $today, Carbon $soon): void
    {
        if ($date->lt($today)) {
            $this->fillCell($sheet, $cell, 'FFFF0000');
            return;
        }

        if ($date->lte($soon)) {
            $this->fillCell($sheet, $cell, 'FFFFFF00');
        }
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $style = $sheet->getStyle($cell);

        $style->getFill()->setFillType(Fill::FILL_SOLID);
        $style->getFill()->getStartColor()->setARGB($argb);
        $style->getFont()->setBold(true);
    }
}
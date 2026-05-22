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

    public function __construct()
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->employees = EmployeeResource::getEloquentQuery()
            ->with(['user', 'certificates'])
            ->orderBy('name')
            ->get();
    }

    public function collection()
    {
        return $this->employees;
    }

    public function bindValue(Cell $cell, $value): bool
    {
        $textColumns = $this->showUserColumn ? ['E', 'F'] : ['D', 'E'];

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
            'Adresa',
            'Spol',
            'OIB',
            'Telefon',
            'E-mail',
            'Radno mjesto',
            'Organizacijska jedinica',
            'Vrsta ugovora',
            'Zanimanje',
            'Školska sprema',
            'Datum i mjesto rođenja',
            'Ime oca/majke',
            'Datum zaposlenja',
            'Datum prekida ugovora',
            'Liječnički pregled od',
            'Liječnički pregled do',
            'Članak 3. točke',
            'ZNR od',
            'ZNR status',
            'ZNR rok do',
            'ZOP od',
            'ZOP izjava od',
            'Evakuacija od',
            'Prva pomoć od',
            'Prva pomoć do',
            'Toksikologija od',
            'Toksikologija do',
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

        $row = [$employee->name];

        if ($this->showUserColumn) {
            $row[] = $employee->user?->name ?? '';
        }

        $row = array_merge($row, [
            $employee->address,
            $employee->gender,
            (string) ($employee->OIB ?? ''),
            (string) ($employee->phone ?? ''),
            $employee->email,
            $employee->workplace,
            $employee->organization_unit,
            $employee->contract_type,
            $employee->job_title,
            $employee->education,
            $employee->place_of_birth,
            $employee->name_of_parents,

            $excel($employee->employeed_at),
            $excel($employee->contract_ended_at),
            $excel($employee->medical_examination_valid_from),
            $excel($employee->medical_examination_valid_until),

            $employee->article,

            $excel($employee->occupational_safety_valid_from),

            $employee->occupational_safety_valid_from
                ? 'Položeno'
                : match ($employee->znrTrainingStatus()) {
                    'expired' => 'NIJE POLOŽENO - ISTEKAO ROK',
                    'expiring' => 'NIJE POLOŽENO - USKORO ISTIČE',
                    default => 'U TIJEKU',
                },

            $excel($employee->znrTrainingDueDate()),

            $excel($employee->fire_protection_valid_from),
            $excel($employee->fire_protection_statement_at),
            $excel($employee->evacuation_valid_from),
            $excel($employee->first_aid_valid_from),
            $excel($employee->first_aid_valid_until),
            $excel($employee->toxicology_valid_from),
            $excel($employee->toxicology_valid_until),
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
                'T' => $date, 'V' => $date, 'W' => $date, 'X' => $date,
                'Y' => $date, 'Z' => $date, 'AA' => $date, 'AB' => $date,
                'AC' => $date, 'AD' => $date, 'AE' => $date,

                'AH' => $date, 'AI' => $date,
                'AK' => $date, 'AL' => $date,
                'AN' => $date, 'AO' => $date,
                'AQ' => $date, 'AR' => $date,
                'AT' => $date, 'AU' => $date,
                'AW' => $date, 'AX' => $date,
                'AZ' => $date, 'BA' => $date,
                'BC' => $date, 'BD' => $date,
                'BF' => $date, 'BG' => $date,
                'BI' => $date, 'BJ' => $date,
            ];
        }

        return [
            'N' => $date, 'O' => $date, 'P' => $date, 'Q' => $date,
            'S' => $date, 'U' => $date, 'V' => $date, 'W' => $date,
            'X' => $date, 'Y' => $date, 'Z' => $date, 'AA' => $date,
            'AB' => $date, 'AC' => $date, 'AD' => $date,

            'AG' => $date, 'AH' => $date,
            'AJ' => $date, 'AK' => $date,
            'AM' => $date, 'AN' => $date,
            'AP' => $date, 'AQ' => $date,
            'AS' => $date, 'AT' => $date,
            'AV' => $date, 'AW' => $date,
            'AY' => $date, 'AZ' => $date,
            'BB' => $date, 'BC' => $date,
            'BE' => $date, 'BF' => $date,
            'BH' => $date, 'BI' => $date,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->employees->count() + 1;
                $lastCol = $this->showUserColumn ? 'BJ' : 'BI';

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

$sheet->getRowDimension(1)->setRowHeight(34);

for ($row = 2; $row <= $lastRow; $row++) {
    $sheet->getRowDimension($row)->setRowHeight(20);
}

if ($this->showUserColumn) {
    $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("O2:R{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("T2:AE{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("AF2:AF{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $widths = [
        'A' => 24, 'B' => 18, 'C' => 24, 'D' => 7, 'E' => 15, 'F' => 16,
        'G' => 28, 'H' => 20, 'I' => 23, 'J' => 17, 'K' => 18, 'L' => 18,
        'M' => 22, 'N' => 18,

        'O' => 13, 'P' => 13, 'Q' => 13, 'R' => 13,
        'S' => 18, 'T' => 13, 'U' => 31, 'V' => 13,

        'W' => 13, 'X' => 13, 'Y' => 13,
        'Z' => 13, 'AA' => 13, 'AB' => 13,
        'AC' => 13, 'AD' => 13, 'AE' => 13,

        'AF' => 12,

        'AG' => 20, 'AH' => 13, 'AI' => 13,
        'AJ' => 20, 'AK' => 13, 'AL' => 13,
        'AM' => 20, 'AN' => 13, 'AO' => 13,
        'AP' => 20, 'AQ' => 13, 'AR' => 13,
        'AS' => 20, 'AT' => 13, 'AU' => 13,
        'AV' => 20, 'AW' => 13, 'AX' => 13,
        'AY' => 20, 'AZ' => 13, 'BA' => 13,
        'BB' => 20, 'BC' => 13, 'BD' => 13,
        'BE' => 20, 'BF' => 13, 'BG' => 13,
        'BH' => 20, 'BI' => 13, 'BJ' => 13,
    ];

    $deadlineColumns = [
        'R' => fn (Employee $e) => $e->medical_examination_valid_until,
        'AA' => fn (Employee $e) => $e->first_aid_valid_until,
        'AC' => fn (Employee $e) => $e->toxicology_valid_until,
        'AE' => fn (Employee $e) => $e->employers_authorization_valid_until,
    ];

    $znrStatusColumn = 'U';
    $znrDueColumn = 'V';

    $certificateDeadlineColumns = [
        'AI' => 0, 'AL' => 1, 'AO' => 2, 'AR' => 3, 'AU' => 4,
        'AX' => 5, 'BA' => 6, 'BD' => 7, 'BG' => 8, 'BJ' => 9,
    ];
} else {
    $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setWrapText(true);
    $sheet->getStyle("N2:Q{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("S2:AD{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle("AE2:AE{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $widths = [
        'A' => 24, 'B' => 24, 'C' => 7, 'D' => 15, 'E' => 16,
        'F' => 28, 'G' => 20, 'H' => 23, 'I' => 17, 'J' => 18, 'K' => 18,
        'L' => 22, 'M' => 18,

        'N' => 13, 'O' => 13, 'P' => 13, 'Q' => 13,
        'R' => 18, 'S' => 13, 'T' => 31, 'U' => 13,

        'V' => 13, 'W' => 13, 'X' => 13,
        'Y' => 13, 'Z' => 13, 'AA' => 13,
        'AB' => 13, 'AC' => 13, 'AD' => 13,

        'AE' => 12,

        'AF' => 20, 'AG' => 13, 'AH' => 13,
        'AI' => 20, 'AJ' => 13, 'AK' => 13,
        'AL' => 20, 'AM' => 13, 'AN' => 13,
        'AO' => 20, 'AP' => 13, 'AQ' => 13,
        'AR' => 20, 'AS' => 13, 'AT' => 13,
        'AU' => 20, 'AV' => 13, 'AW' => 13,
        'AX' => 20, 'AY' => 13, 'AZ' => 13,
        'BA' => 20, 'BB' => 13, 'BC' => 13,
        'BD' => 20, 'BE' => 13, 'BF' => 13,
        'BG' => 20, 'BH' => 13, 'BI' => 13,
    ];

    $deadlineColumns = [
        'Q' => fn (Employee $e) => $e->medical_examination_valid_until,
        'Z' => fn (Employee $e) => $e->first_aid_valid_until,
        'AB' => fn (Employee $e) => $e->toxicology_valid_until,
        'AD' => fn (Employee $e) => $e->employers_authorization_valid_until,
    ];

    $znrStatusColumn = 'T';
    $znrDueColumn = 'U';

    $certificateDeadlineColumns = [
        'AH' => 0, 'AK' => 1, 'AN' => 2, 'AQ' => 3, 'AT' => 4,
        'AW' => 5, 'AZ' => 6, 'BC' => 7, 'BF' => 8, 'BI' => 9,
    ];
}

foreach ($widths as $column => $width) {
    $sheet->getColumnDimension($column)->setWidth($width);
}

$today = Carbon::today();

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

                        $this->colorDeadlineCell($sheet, "{$znrStatusColumn}{$row}", $dueDate, $today, $soon);
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
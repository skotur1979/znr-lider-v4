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
    protected $employees;

    public function __construct()
    {
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
        if (in_array($cell->getColumn(), ['E', 'F'], true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function headings(): array
    {
        return [
            'Ime i prezime',
            'Korisnik',
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
        ];
    }

    public function map($employee): array
    {
        /** @var Employee $employee */

        $certs = $employee->certificates?->values() ?? collect();
        $excel = fn ($date) => $date ? ExcelDate::dateTimeToExcel(Carbon::parse($date)) : null;

        $row = [
            $employee->name,
            $employee->user?->name ?? '',
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
        ];

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

        return [
            'O' => $date,
            'P' => $date,
            'Q' => $date,
            'R' => $date,
            'T' => $date,
            'U' => $date,
            'V' => $date,
            'W' => $date,
            'X' => $date,
            'Y' => $date,
            'Z' => $date,
            'AA' => $date,
            'AB' => $date,
            'AC' => $date,

            'AF' => $date,
            'AG' => $date,
            'AI' => $date,
            'AJ' => $date,
            'AL' => $date,
            'AM' => $date,
            'AO' => $date,
            'AP' => $date,
            'AR' => $date,
            'AS' => $date,
            'AU' => $date,
            'AV' => $date,
            'AX' => $date,
            'AY' => $date,
            'BA' => $date,
            'BB' => $date,
            'BD' => $date,
            'BE' => $date,
            'BG' => $date,
            'BH' => $date,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->employees->count() + 1;

                $sheet->getStyle("A1:BH{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:BH1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name' => 'DejaVu Sans',
                        'size' => 10,
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '1F2937'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A2:BH{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("O2:R{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("T2:AC{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("AD2:AD{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $widths = [
                    'A' => 30,
                    'B' => 22,
                    'C' => 34,
                    'D' => 12,
                    'E' => 16,
                    'F' => 16,
                    'G' => 28,
                    'H' => 30,
                    'I' => 28,
                    'J' => 18,
                    'K' => 22,
                    'L' => 22,
                    'M' => 28,
                    'N' => 22,
                    'O' => 16,
                    'P' => 16,
                    'Q' => 16,
                    'R' => 16,
                    'S' => 22,
                    'AD' => 14,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $today = Carbon::today();
                $soon = $today->copy()->addDays(30);

                $deadlineColumns = [
                    'R' => fn (Employee $e) => $e->medical_examination_valid_until,
                    'Y' => fn (Employee $e) => $e->first_aid_valid_until,
                    'AA' => fn (Employee $e) => $e->toxicology_valid_until,
                    'AC' => fn (Employee $e) => $e->employers_authorization_valid_until,
                ];

                $certificateDeadlineColumns = [
                    'AG' => 0,
                    'AJ' => 1,
                    'AM' => 2,
                    'AP' => 3,
                    'AS' => 4,
                    'AV' => 5,
                    'AY' => 6,
                    'BB' => 7,
                    'BE' => 8,
                    'BH' => 9,
                ];

                foreach ($this->employees as $index => $employee) {
                    $row = $index + 2;

                    foreach ($deadlineColumns as $column => $getter) {
                        $date = $getter($employee);

                        if (! $date) {
                            continue;
                        }

                        $this->colorDeadlineCell($sheet, "{$column}{$row}", Carbon::parse($date), $today, $soon);
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
                $sheet->setAutoFilter("A1:BH{$lastRow}");
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
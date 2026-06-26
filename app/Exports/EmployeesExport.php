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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
            ->with(['user', 'certificates', 'latestAlcoholTest'])
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
        ? ['J', 'K', 'O', 'X']
        : ['H', 'I', 'M', 'W'];

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
            'Zadnje alkotestiranje',
            'Rezultat alkotesta',
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

            $excel($employee->latestAlcoholTest?->test_date),
            $employee->latestAlcoholTest?->result,

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

        $dateColumns = [
            12, 16, 17, 18, 19, 22,
            24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34,
            37, 38, 40, 41, 43, 44, 46, 47, 49, 50,
            52, 53, 55, 56, 58, 59, 61, 62, 64, 65,
        ];

        $formats = [];

        foreach ($dateColumns as $index) {
            $formats[$this->column($this->idx($index))] = $date;
        }

        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->employees->count() + 1;
                $lastCol = $this->showUserColumn ? 'BN' : 'BM';

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

                $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle($this->column($this->idx(4)) . "2:" . $this->column($this->idx(6)) . "{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                $sheet->getStyle($this->column($this->idx(12)) . "2:" . $this->column($this->idx(19)) . "{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle($this->column($this->idx(22)) . "2:" . $this->column($this->idx(34)) . "{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle($this->column($this->idx(23)) . "2:" . $this->column($this->idx(23)) . "{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle($this->column($this->idx(35)) . "2:" . $lastCol . "{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $widths = [
                    1 => 24,  // Ime i prezime
                    2 => 28,  // Zanimanje
                    3 => 14,  // Školska sprema
                    4 => 34,  // Datum i mjesto rođenja
                    5 => 22,  // Ime oca/majke
                    6 => 34,  // Adresa
                    7 => 7,   // Spol
                    8 => 16,  // OIB
                    9 => 18,  // Telefon
                    10 => 30, // E-mail
                    11 => 34, // Radno mjesto

                    12 => 18, // Zadnje alkotestiranje
                    13 => 16, // Rezultat alkotesta

                    14 => 24,
                    15 => 18,
                    16 => 15,
                    17 => 15,
                    18 => 15,
                    19 => 15,
                    20 => 18,
                    21 => 24,
                    22 => 15,
                    23 => 16,
                    24 => 15,
                    25 => 15,
                    26 => 15,
                    27 => 15,
                    28 => 15,
                    29 => 15,
                    30 => 15,
                    31 => 15,
                    32 => 15,
                    33 => 15,
                    34 => 15,
                    35 => 10,
                ];

                for ($i = 36; $i <= 65; $i += 3) {
                    $widths[$i] = 24;
                    $widths[$i + 1] = 13;
                    $widths[$i + 2] = 13;
                }

                if ($this->showUserColumn) {
                    $sheet->getColumnDimension('B')->setWidth(16);
                }

                foreach ($widths as $index => $width) {
                    $sheet->getColumnDimension($this->column($this->idx($index)))->setWidth($width);
                }

                $today = Carbon::today();
                $soon = $today->copy()->addDays(30);

                $deadlineColumns = [
                    $this->column($this->idx(19)) => fn (Employee $e) => $e->medical_examination_valid_until,
                    $this->column($this->idx(28)) => fn (Employee $e) => $e->first_aid_valid_until,
                    $this->column($this->idx(30)) => fn (Employee $e) => $e->toxicology_valid_until,
                    $this->column($this->idx(32)) => fn (Employee $e) => $e->handling_flammable_materials_valid_until,
                    $this->column($this->idx(34)) => fn (Employee $e) => $e->employers_authorization_valid_until,
                ];

                $znrDueColumn = $this->column($this->idx(23));

                $certificateDeadlineColumns = [
                    $this->column($this->idx(38)) => 0,
                    $this->column($this->idx(41)) => 1,
                    $this->column($this->idx(44)) => 2,
                    $this->column($this->idx(47)) => 3,
                    $this->column($this->idx(50)) => 4,
                    $this->column($this->idx(53)) => 5,
                    $this->column($this->idx(56)) => 6,
                    $this->column($this->idx(59)) => 7,
                    $this->column($this->idx(62)) => 8,
                    $this->column($this->idx(65)) => 9,
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

                    $alcoholResultColumn = $this->column($this->idx(13));
                    $result = trim((string) ($employee->latestAlcoholTest?->result ?? ''));

                    $value = (float) str_replace(',', '.', $result);

                    if ($result !== '' && $value > 0.5) {
                        $this->fillCell($sheet, "{$alcoholResultColumn}{$row}", 'FFFF0000');
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function idx(int $noUserColumnIndex): int
{
    return $this->showUserColumn && $noUserColumnIndex > 1
        ? $noUserColumnIndex + 1
        : $noUserColumnIndex;
}

    private function column(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
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
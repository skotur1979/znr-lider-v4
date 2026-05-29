<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InspectionFindingsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected Collection $findings
    ) {}

    public function collection()
    {
        return $this->findings;
    }

    public function headings(): array
    {
        return [
            'Područje',
            'Što je uočeno / pronađeno',
            'Vrsta',
            'Status postupanja',
            'Treba akcija',
            'Odgovorna osoba',
            'Rok',
            'Napomena / rješenje',
        ];
    }

    public function map($f): array
    {
        return [
            $f->category,
            $f->description,
            $this->findingStatusLabel($f->finding_status),
            $this->workflowStatusLabel($f->workflow_status),
            $f->action_required ? 'DA' : 'NE',
            $f->responsible_person,
            $f->due_date ? ExcelDate::dateTimeToExcel(Carbon::parse($f->due_date)) : null,
            $f->resolution_note,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->findings->count() + 1;
                $lastCol = 'H';

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
                        'fillType' => 'solid',
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
                    ->setWrapText(true);

                $sheet->getStyle("A2:A{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("C2:E{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("G2:G{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $widths = [
                    'A' => 22,
                    'B' => 55,
                    'C' => 22,
                    'D' => 24,
                    'E' => 14,
                    'F' => 28,
                    'G' => 16,
                    'H' => 45,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(32);

                foreach ($this->findings as $idx => $finding) {
                    $row = $idx + 2;
                    $sheet->getRowDimension($row)->setRowHeight(42);

                    if ($finding->workflow_status === 'in_progress') {
                        $this->fillCell($sheet, "D{$row}", 'FFFFFF00');
                    }

                    if (in_array($finding->workflow_status, ['closed', 'resolved_no_action'], true)) {
                        $this->fillCell($sheet, "D{$row}", 'FF00B050', true);
                    }

                    if ($finding->workflow_status === 'rejected') {
                        $this->fillCell($sheet, "D{$row}", 'FFFF0000', true);
                    }

                    if (! $finding->due_date) {
                        continue;
                    }

                    if (in_array($finding->workflow_status, ['closed', 'rejected', 'resolved_no_action'], true)) {
                        $this->fillCell($sheet, "G{$row}", 'FF00B050', true);
                        continue;
                    }

                    $dueDate = Carbon::parse($finding->due_date)->startOfDay();
                    $today = Carbon::today();

                    if ($dueDate->lt($today)) {
                        $this->fillCell($sheet, "G{$row}", 'FFFF0000', true);
                        continue;
                    }

                    if ($dueDate->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, "G{$row}", 'FFFFFF00');
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function findingStatusLabel(?string $state): string
    {
        return match ($state) {
            'ok' => 'Uredno',
            'recommendation' => 'Preporuka',
            'noncompliance' => 'Nepravilnost',
            'critical' => 'Kritična nepravilnost',
            default => $state ?: '-',
        };
    }

    private function workflowStatusLabel(?string $state): string
    {
        return match ($state) {
            'open' => 'Nije započeto',
            'in_progress' => 'U tijeku',
            'closed' => 'Zatvoreno',
            'resolved_no_action' => 'Riješeno bez akcija',
            'converted_to_observation' => 'Pretvoreno u zapažanje',
            'rejected' => 'Odbačeno',
            default => $state ?: '-',
        };
    }

    private function fillCell($sheet, string $cell, string $argb, bool $whiteText = false): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);

        if ($whiteText) {
            $sheet->getStyle($cell)->getFont()->getColor()->setRGB('FFFFFF');
        }
    }
}
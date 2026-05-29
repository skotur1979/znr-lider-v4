<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InspectionZone5sExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $answers;

    public function __construct(
        protected $zone
    ) {
        $this->answers = $zone->answers
            ->sortBy(fn ($a) => $a->question?->sort_order ?? $a->question?->id ?? $a->id)
            ->values();
    }

    public function collection()
    {
        return $this->answers;
    }

    public function headings(): array
    {
        return [
            'Br.',
            'Skupina',
            'Pitanje',
            'Ocjena',
        ];
    }

    public function map($answer): array
    {
        $question = $answer->question;

        return [
            $this->answers->search($answer) + 1,
            $question->group ?? $question->category ?? $question->section ?? '',
            $question->question ?? $question->text ?? $question->title ?? $question->name ?? '',
            (int) ($answer->score ?? 0),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->answers->count() + 1;
                $lastCol = 'D';

                $sheet->insertNewRowBefore(1, 4);

                $sheet->setCellValue('A1', '5S izvještaj - ' . $this->zone->name);
                $sheet->setCellValue('A2', 'Datum izvoza: ' . now()->format('d.m.Y. H:i'));
                $sheet->setCellValue('A3', 'Ukupno bodova: ' . $this->zone->total_points . ' | Maksimalno bodova: ' . $this->zone->max_points . ' | Rezultat: ' . number_format((float) $this->zone->percentage, 0) . '%');

                if (filled($this->zone->note)) {
                    $sheet->setCellValue('A4', 'Napomena zone: ' . $this->zone->note);
                }

                $headerRow = 5;
                $dataStartRow = 6;
                $dataLastRow = $this->answers->count() + 5;

                $sheet->mergeCells('A1:D1');
                $sheet->mergeCells('A2:D2');
                $sheet->mergeCells('A3:D3');
                $sheet->mergeCells('A4:D4');

                $sheet->getStyle("A1:D{$dataLastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
                $sheet->getStyle('A3')->getFont()->setBold(true);

                $sheet->getStyle("A{$headerRow}:D{$headerRow}")->applyFromArray([
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

                $sheet->getStyle("A{$dataStartRow}:D{$dataLastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A{$dataStartRow}:B{$dataLastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("D{$dataStartRow}:D{$dataLastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $widths = [
                    'A' => 8,
                    'B' => 24,
                    'C' => 80,
                    'D' => 12,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension($headerRow)->setRowHeight(30);

                foreach ($this->answers as $idx => $answer) {
                    $row = $idx + 6;
                    $sheet->getRowDimension($row)->setRowHeight(42);

                    $score = (int) ($answer->score ?? 0);

                    if ($score <= 2) {
                        $this->fillCell($sheet, "D{$row}", 'FFFF0000', true);
                    } elseif ($score === 3) {
                        $this->fillCell($sheet, "D{$row}", 'FFFFFF00', false);
                    } else {
                        $this->fillCell($sheet, "D{$row}", 'FF00B050', true);
                    }
                }

                $sheet->freezePane('A6');
                $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$dataLastRow}");
            },
        ];
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
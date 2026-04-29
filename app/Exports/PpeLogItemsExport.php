<?php

namespace App\Exports;

use App\Models\PPELog;
use Illuminate\Support\Carbon;
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

class PpeLogItemsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    protected PPELog $log;

    protected $items;

    protected bool $showUserColumn = false;

    public function __construct(PPELog $log)
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->log = $log->load(['items', 'user']);
        $this->items = $this->log->items->sortBy('equipment_name')->values();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        $headings = [];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Prezime i ime',
            'OIB',
            'Radno mjesto',
            'Organizacijska jedinica',
            'Naziv OZO',
            'HRN EN',
            'Veličina',
            'Rok (mjeseci)',
            'Izdano',
            'Istek',
            'Datum vraćanja',
        ]);
    }

    public function map($item): array
    {
        $issue = $item->issue_date ? Carbon::parse($item->issue_date) : null;
        $end = $item->end_date ? Carbon::parse($item->end_date) : null;
        $return = $item->return_date ? Carbon::parse($item->return_date) : null;

        $row = [];

        if ($this->showUserColumn) {
            $row[] = $this->log->user?->name ?? '';
        }

        return array_merge($row, [
            $this->log->user_last_name,
            $this->log->user_oib,
            $this->log->workplace,
            $this->log->organization_unit,
            $item->equipment_name,
            $item->standard,
            $item->size,
            $item->duration_months,
            $issue ? ExcelDate::dateTimeToExcel($issue) : null,
            $end ? ExcelDate::dateTimeToExcel($end) : null,
            $return ? ExcelDate::dateTimeToExcel($return) : null,
        ]);
    }

    public function columnFormats(): array
    {
        if ($this->showUserColumn) {
            return [
                'J' => 'dd.mm.yyyy',
                'K' => 'dd.mm.yyyy',
                'L' => 'dd.mm.yyyy',
            ];
        }

        return [
            'I' => 'dd.mm.yyyy',
            'J' => 'dd.mm.yyyy',
            'K' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->items->count() + 1;
                $lastCol = $this->showUserColumn ? 'L' : 'K';

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

                if ($this->showUserColumn) {
                    foreach (['C', 'G', 'H', 'I', 'J', 'K', 'L'] as $col) {
                        $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $widths = [
                        'A' => 22,
                        'B' => 28,
                        'C' => 16,
                        'D' => 26,
                        'E' => 28,
                        'F' => 32,
                        'G' => 18,
                        'H' => 14,
                        'I' => 16,
                        'J' => 16,
                        'K' => 16,
                        'L' => 18,
                    ];

                    $expiryColumn = 'K';
                } else {
                    foreach (['B', 'F', 'G', 'H', 'I', 'J', 'K'] as $col) {
                        $sheet->getStyle("{$col}2:{$col}{$lastRow}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $widths = [
                        'A' => 28,
                        'B' => 16,
                        'C' => 26,
                        'D' => 28,
                        'E' => 32,
                        'F' => 18,
                        'G' => 14,
                        'H' => 16,
                        'I' => 16,
                        'J' => 16,
                        'K' => 18,
                    ];

                    $expiryColumn = 'J';
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $today = Carbon::today();
                $soon = $today->copy()->addDays(30);

                foreach ($this->items as $i => $item) {
                    $row = $i + 2;
                    $end = $item->end_date ? Carbon::parse($item->end_date) : null;

                    if (! $end) {
                        continue;
                    }

                    if ($end->lt($today)) {
                        $this->fillCell($sheet, "{$expiryColumn}{$row}", 'FFFF0000');
                    } elseif ($end->lte($soon)) {
                        $this->fillCell($sheet, "{$expiryColumn}{$row}", 'FFFFFF00');
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}
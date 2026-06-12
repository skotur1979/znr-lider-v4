<?php

namespace App\Exports;

use App\Services\KpiCalculationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class KpiReportsExport implements FromArray, WithEvents
{
    protected array $groups;
    protected array $categoryRows = [];
    protected array $headerRows = [];
    protected int $lastRow = 1;

    public function __construct(protected int $year)
    {
        $this->groups = app(KpiCalculationService::class)
            ->yearlyReportGrouped($this->year)
            ->toArray();
    }

    public function array(): array
    {
        $rows = [];

        $rows[] = ['KPI izvještaji'];
        $rows[] = ['Godina: ' . $this->year . ' | Datum izvoza: ' . now()->format('d.m.Y. H:i')];
        $rows[] = [''];

        foreach ($this->groups as $category => $items) {
            $rows[] = [$category];
            $this->categoryRows[] = count($rows);

            $rows[] = array_merge(
                ['KPI', 'Jedinica', 'Cilj'],
                ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
                ['Prosjek', 'Ukupno']
            );
            $this->headerRows[] = count($rows);

            foreach ($items as $item) {
                $values = $this->normalizeValues($item['values'] ?? []);

                $row = [
                    $item['name'] ?? '',
                    $item['unit'] ?? '-',
                    $this->numberOrDash($this->extractTarget($item)),
                ];

                for ($m = 1; $m <= 12; $m++) {
                    $row[] = $this->numberOrDash($values[$m] ?? null);
                }

                $row[] = $this->numberOrDash($item['average'] ?? null);
                $row[] = $this->numberOrDash($item['total'] ?? null);

                $rows[] = $row;
            }

            $rows[] = [''];
        }

        $this->lastRow = count($rows);

        return $rows;
    }

    protected function normalizeValues(mixed $values): array
    {
        if ($values instanceof Collection) {
            $values = $values->toArray();
        }

        $normalized = [];

        foreach ((array) $values as $key => $value) {
            $normalized[(int) $key] = $value;
        }

        return $normalized;
    }

    protected function extractTarget(array $item): mixed
    {
        if (isset($item['targets']) && is_array($item['targets'])) {
            return $item['targets'][12] ?? null;
        }

        return $item['formatted_target'] ?? null;
    }

    protected function numberOrDash(mixed $value): mixed
    {
        if ($value === null || $value === '' || $value === '-') {
            return '-';
        }

        if (is_string($value)) {
            $clean = str_replace(['.', ','], ['', '.'], $value);

            return is_numeric($clean) ? (float) $clean : $value;
        }

        return is_numeric($value) ? (float) $value : $value;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = 'Q';

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");

                $sheet->getStyle("A1:{$lastCol}{$this->lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(9);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A2')->getFont()->setSize(10);

                foreach ($this->categoryRows as $row) {
                    $sheet->mergeCells("A{$row}:{$lastCol}{$row}");

                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '111827'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    $sheet->getRowDimension($row)->setRowHeight(24);
                }

                foreach ($this->headerRows as $row) {
                    foreach (range('D', 'O') as $col) {
                        $month = str_pad((string) (ord($col) - ord('D') + 1), 2, '0', STR_PAD_LEFT);
                        $sheet->setCellValueExplicit("{$col}{$row}", $month, DataType::TYPE_STRING);
                    }

                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '111827'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E5E7EB'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '6B7280'],
                            ],
                        ],
                    ]);

                    $sheet->getRowDimension($row)->setRowHeight(24);
                }

                $sheet->getStyle("A4:{$lastCol}{$this->lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '6B7280'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("B4:{$lastCol}{$this->lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("C4:{$lastCol}{$this->lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $widths = [
                    'A' => 38,
                    'B' => 12,
                    'C' => 12,
                    'D' => 10,
                    'E' => 10,
                    'F' => 10,
                    'G' => 10,
                    'H' => 11,
                    'I' => 11,
                    'J' => 10,
                    'K' => 10,
                    'L' => 10,
                    'M' => 10,
                    'N' => 10,
                    'O' => 10,
                    'P' => 12,
                    'Q' => 12,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                for ($row = 1; $row <= $this->lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getPageMargins()->setTop(0.35);
                $sheet->getPageMargins()->setRight(0.25);
                $sheet->getPageMargins()->setLeft(0.25);
                $sheet->getPageMargins()->setBottom(0.35);

                $sheet->freezePane('A4');
            },
        ];
    }
}
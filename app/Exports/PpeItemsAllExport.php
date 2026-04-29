<?php

namespace App\Exports;

use App\Filament\Resources\PpeLogs\PPELogResource;
use App\Models\PPEItem;
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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PpeItemsAllExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    protected $items;

    protected ?string $lastKey = null;

    public function __construct()
    {
        $logIds = PPELogResource::getEloquentQuery()
            ->select('id')
            ->pluck('id');

        $this->items = PPEItem::query()
            ->whereIn('personal_protective_equipment_log_id', $logIds)
            ->with(['log.user'])
            ->join(
                'personal_protective_equipment_logs as l',
                'l.id',
                '=',
                'personal_protective_equipment_items.personal_protective_equipment_log_id'
            )
            ->select('personal_protective_equipment_items.*')
            ->orderBy('l.user_last_name')
            ->orderBy('l.user_oib')
            ->orderBy('l.workplace')
            ->orderBy('l.organization_unit')
            ->orderBy('personal_protective_equipment_items.equipment_name')
            ->get();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'Korisnik',
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
        ];
    }

    public function map($item): array
    {
        /** @var PPEItem $item */

        $log = $item->log;

        $key = trim(
            ($log?->user?->name ?? '') . '|' .
            ($log?->user_last_name ?? '') . '|' .
            ($log?->user_oib ?? '') . '|' .
            ($log?->workplace ?? '') . '|' .
            ($log?->organization_unit ?? '')
        );

        $isRepeat = $this->lastKey !== null && $this->lastKey === $key;

        if (! $isRepeat) {
            $this->lastKey = $key;
        }

        $issue = $item->issue_date ? Carbon::parse($item->issue_date) : null;
        $end = $item->end_date ? Carbon::parse($item->end_date) : null;
        $return = $item->return_date ? Carbon::parse($item->return_date) : null;

        return [
            $isRepeat ? '' : ($log?->user?->name ?? ''),
            $isRepeat ? '' : ($log?->user_last_name ?? ''),
            $isRepeat ? '' : ($log?->user_oib ?? ''),
            $isRepeat ? '' : ($log?->workplace ?? ''),
            $isRepeat ? '' : ($log?->organization_unit ?? ''),
            $item->equipment_name,
            $item->standard,
            $item->size,
            $item->duration_months,
            $issue ? ExcelDate::dateTimeToExcel($issue) : null,
            $end ? ExcelDate::dateTimeToExcel($end) : null,
            $return ? ExcelDate::dateTimeToExcel($return) : null,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'J' => 'dd.mm.yyyy',
            'K' => 'dd.mm.yyyy',
            'L' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:L{$highestRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:L1')->applyFromArray([
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

                $sheet->getStyle("A2:L{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                foreach (['C', 'G', 'H', 'I', 'J', 'K', 'L'] as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$highestRow}")
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

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(22);
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
                        $this->fillCell($sheet, "K{$row}", 'FFFF0000');
                    } elseif ($end->lte($soon)) {
                        $this->fillCell($sheet, "K{$row}", 'FFFFFF00');
                    }
                }

                $sheet->getStyle("A1:L{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $this->mergeGroupedColumns($sheet, $highestRow, ['A', 'B', 'C', 'D', 'E']);
                $this->applyGroupTopBorders($sheet, $highestRow);

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:L{$highestRow}");
            },
        ];
    }

    private function mergeGroupedColumns($sheet, int $highestRow, array $columns): void
    {
        if ($highestRow < 3) {
            return;
        }

        $groupStart = 2;
        $prevKey = $this->groupKeyFromSheet($sheet, 2);

        for ($row = 3; $row <= $highestRow + 1; $row++) {
            $currentKey = $row <= $highestRow
                ? $this->groupKeyFromSheet($sheet, $row)
                : '__END__';

            if ($currentKey !== $prevKey) {
                $groupEnd = $row - 1;

                if ($groupEnd > $groupStart && $prevKey !== '') {
                    foreach ($columns as $col) {
                        $topCell = "{$col}{$groupStart}";
                        $bottomCell = "{$col}{$groupEnd}";

                        $topValue = $sheet->getCell($topCell)->getValue();

                        for ($r = $groupStart + 1; $r <= $groupEnd; $r++) {
                            $sheet->setCellValue("{$col}{$r}", $topValue);
                        }

                        $sheet->mergeCells("{$topCell}:{$bottomCell}");

                        $sheet->getStyle("{$topCell}:{$bottomCell}")
                            ->getAlignment()
                            ->setVertical(Alignment::VERTICAL_CENTER)
                            ->setHorizontal(in_array($col, ['C'], true)
                                ? Alignment::HORIZONTAL_CENTER
                                : Alignment::HORIZONTAL_LEFT);
                    }
                }

                $groupStart = $row;
                $prevKey = $currentKey;
            }
        }
    }

    private function applyGroupTopBorders($sheet, int $highestRow): void
    {
        if ($highestRow < 2) {
            return;
        }

        $groupStartRows = [2];
        $prevKey = $this->groupKeyFromSheet($sheet, 2);

        for ($row = 3; $row <= $highestRow; $row++) {
            $currentKey = $this->groupKeyFromSheet($sheet, $row);

            if ($currentKey !== '' && $currentKey !== $prevKey) {
                $groupStartRows[] = $row;
                $prevKey = $currentKey;
            }
        }

        foreach ($groupStartRows as $row) {
            $sheet->getStyle("A{$row}:L{$row}")
                ->getBorders()
                ->getTop()
                ->setBorderStyle(Border::BORDER_MEDIUM);
        }
    }

    private function groupKeyFromSheet($sheet, int $row): string
    {
        $values = [];

        foreach (['A', 'B', 'C', 'D', 'E'] as $column) {
            $values[] = trim((string) $sheet->getCell("{$column}{$row}")->getValue());
        }

        if (collect($values)->every(fn ($value) => $value === '')) {
            return '';
        }

        return implode('|', $values);
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}
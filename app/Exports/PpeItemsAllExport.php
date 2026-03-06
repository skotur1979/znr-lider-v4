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
    /** @var \Illuminate\Support\Collection<int, \App\Models\PPEItem> */
    protected $items;

    protected ?string $lastKey = null;

    public function __construct()
    {
        $logIds = PPELogResource::getEloquentQuery()
            ->select('id')
            ->pluck('id');

        $this->items = PPEItem::query()
            ->whereIn('personal_protective_equipment_log_id', $logIds)
            ->with('log')
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
        /** @var \App\Models\PPEItem $item */
        $log = $item->log;

        $key = trim(
            ($log?->user_last_name ?? '') . '|' .
            ($log?->user_oib ?? '') . '|' .
            ($log?->workplace ?? '') . '|' .
            ($log?->organization_unit ?? '')
        );

        $isRepeat = ($this->lastKey !== null && $this->lastKey === $key);

        if (! $isRepeat) {
            $this->lastKey = $key;
        }

        $issue = $item->issue_date ? Carbon::parse($item->issue_date) : null;
        $end   = $item->end_date ? Carbon::parse($item->end_date) : null;
        $ret   = $item->return_date ? Carbon::parse($item->return_date) : null;

        return [
            $isRepeat ? '' : ($log?->user_last_name),
            $isRepeat ? '' : ($log?->user_oib),
            $isRepeat ? '' : ($log?->workplace),
            $isRepeat ? '' : ($log?->organization_unit),

            $item->equipment_name,
            $item->standard,
            $item->size,
            $item->duration_months,

            $issue ? ExcelDate::dateTimeToExcel($issue) : null,
            $end   ? ExcelDate::dateTimeToExcel($end) : null,
            $ret   ? ExcelDate::dateTimeToExcel($ret) : null,
        ];
    }

    public function columnFormats(): array
    {
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
                $highestRow = $sheet->getHighestRow();

                // Header
                $sheet->getStyle('A1:K1')->getFont()->setBold(true);
                $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:K1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                // Opći alignment
                $sheet->getStyle("A1:K{$highestRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                foreach (['B', 'F', 'G', 'H', 'I', 'J', 'K'] as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$highestRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach (['A', 'C', 'D', 'E', 'F'] as $col) {
                    $sheet->getStyle("{$col}2:{$col}{$highestRow}")
                        ->getAlignment()
                        ->setWrapText(true);
                }

                $today = Carbon::today();

                // Bojanje "Istek"
                foreach ($this->items as $i => $item) {
                    $row = $i + 2;
                    $end = $item->end_date ? Carbon::parse($item->end_date) : null;

                    if (! $end) {
                        continue;
                    }

                    $cell = "J{$row}";

                    if ($end->lt($today)) {
                        $this->fillCell($sheet, $cell, 'FFFF0000');
                        continue;
                    }

                    if ($end->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, $cell, 'FFFFFF00');
                        continue;
                    }
                }

                // Tanki border svugdje
                $sheet->getStyle("A1:K{$highestRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Merge A-D po grupama
                $this->mergeGroupedColumns($sheet, $highestRow, ['A', 'B', 'C', 'D']);

                // Deblja gornja crta na početku svake nove grupe radnika
                $this->applyGroupTopBorders($sheet, $highestRow);
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
                            ->setVertical(Alignment::VERTICAL_CENTER);

                        if (in_array($col, ['A', 'B', 'C', 'D'], true)) {
                            $sheet->getStyle("{$topCell}:{$bottomCell}")
                                ->getAlignment()
                                ->setHorizontal($col === 'B'
                                    ? Alignment::HORIZONTAL_CENTER
                                    : Alignment::HORIZONTAL_LEFT);
                        }
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

        // prvi red podataka uvijek je početak grupe
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
            $sheet->getStyle("A{$row}:K{$row}")
                ->getBorders()
                ->getTop()
                ->setBorderStyle(Border::BORDER_MEDIUM);
        }
    }

    private function groupKeyFromSheet($sheet, int $row): string
    {
        $a = trim((string) $sheet->getCell("A{$row}")->getValue());
        $b = trim((string) $sheet->getCell("B{$row}")->getValue());
        $c = trim((string) $sheet->getCell("C{$row}")->getValue());
        $d = trim((string) $sheet->getCell("D{$row}")->getValue());

        if ($a === '' && $b === '' && $c === '' && $d === '') {
            return '';
        }

        return "{$a}|{$b}|{$c}|{$d}";
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}
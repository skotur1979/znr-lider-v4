<?php

namespace App\Exports;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use App\Models\FirstAidItem;
use App\Models\FirstAidKit;
use Carbon\Carbon;
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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FirstAidKitsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    protected Collection $kits;

    private int $maxItems = 15;

    public function __construct()
    {
        $this->kits = FirstAidKitResource::getEloquentQuery()
            ->with([
                'user',
                'items' => fn ($query) => $query->orderBy('valid_until'),
            ])
            ->withCount('items')
            ->orderByDesc('inspected_at')
            ->get();
    }

    public function collection()
    {
        return $this->kits;
    }

    public function headings(): array
    {
        $heads = [
            'Lokacija ormarića',
            'Korisnik',
            'Pregled obavljen',
            'Napomena',
            'Ukupan broj stavki',
            'Uskoro ističe',
            'Isteklo',
            'Najraniji rok',
        ];

        for ($i = 1; $i <= $this->maxItems; $i++) {
            $heads[] = "Stavka {$i} - vrsta";
            $heads[] = "Stavka {$i} - namjena";
            $heads[] = "Stavka {$i} - vrijedi do";
        }

        return $heads;
    }

    public function map($kit): array
    {
        /** @var FirstAidKit $kit */

        $items = $kit->items?->values() ?? collect();

        $today = Carbon::today();
        $soonLimit = $today->copy()->addDays(30);

        $dates = $items
            ->pluck('valid_until')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->startOfDay());

        $soon = $dates
            ->filter(fn (Carbon $date) => ! $date->lt($today) && $date->lte($soonLimit))
            ->count();

        $expired = $dates
            ->filter(fn (Carbon $date) => $date->lt($today))
            ->count();

        $earliest = $dates->sort()->first();

        $excelDate = fn ($date) => $date ? ExcelDate::dateTimeToExcel(Carbon::parse($date)) : null;

        $row = [
            $kit->location,
            $kit->user?->name ?? '',
            $excelDate($kit->inspected_at),
            $kit->note,
            (int) $kit->items_count,
            (int) $soon,
            (int) $expired,
            $earliest ? ExcelDate::dateTimeToExcel($earliest) : null,
        ];

        for ($i = 0; $i < $this->maxItems; $i++) {
            /** @var FirstAidItem|null $item */
            $item = $items->get($i);

            $row[] = $item?->material_type;
            $row[] = $item?->purpose;
            $row[] = $excelDate($item?->valid_until);
        }

        return $row;
    }

    public function columnFormats(): array
    {
        $dateFormat = NumberFormat::FORMAT_DATE_DDMMYYYY;

        $formats = [
            'C' => $dateFormat,
            'H' => $dateFormat,
        ];

        $startColIndex = $this->colToIndex('I');

        for ($i = 0; $i < $this->maxItems; $i++) {
            $dateColIndex = $startColIndex + ($i * 3) + 2;
            $formats[$this->indexToCol($dateColIndex)] = $dateFormat;
        }

        return $formats;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $sheet->getHighestRow();
                $lastCol = $sheet->getHighestColumn();

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

                $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("C2:H{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $startColIndex = $this->colToIndex('I');

                for ($i = 0; $i < $this->maxItems; $i++) {
                    $dateColIndex = $startColIndex + ($i * 3) + 2;
                    $dateCol = $this->indexToCol($dateColIndex);

                    $sheet->getStyle("{$dateCol}2:{$dateCol}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $widths = [
                    'A' => 28,
                    'B' => 22,
                    'C' => 18,
                    'D' => 36,
                    'E' => 16,
                    'F' => 16,
                    'G' => 14,
                    'H' => 16,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                for ($i = 0; $i < $this->maxItems; $i++) {
                    $base = $startColIndex + ($i * 3);

                    $sheet->getColumnDimension($this->indexToCol($base))->setWidth(28);
                    $sheet->getColumnDimension($this->indexToCol($base + 1))->setWidth(32);
                    $sheet->getColumnDimension($this->indexToCol($base + 2))->setWidth(16);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $today = Carbon::today();
                $soon = $today->copy()->addDays(30);

                foreach ($this->kits as $i => $kit) {
                    $row = $i + 2;

                    $earliest = $kit->items
                        ?->pluck('valid_until')
                        ->filter()
                        ->map(fn ($date) => Carbon::parse($date)->startOfDay())
                        ->sort()
                        ->first();

                    if ($earliest) {
                        if ($earliest->lt($today)) {
                            $this->fillCell($sheet, "H{$row}", 'FFFF0000');
                        } elseif ($earliest->lte($soon)) {
                            $this->fillCell($sheet, "H{$row}", 'FFFFFF00');
                        }
                    }

                    for ($k = 0; $k < $this->maxItems; $k++) {
                        $item = $kit->items?->values()->get($k);

                        if (! $item?->valid_until) {
                            continue;
                        }

                        $date = Carbon::parse($item->valid_until)->startOfDay();
                        $dateColIndex = $startColIndex + ($k * 3) + 2;
                        $cell = $this->indexToCol($dateColIndex) . $row;

                        if ($date->lt($today)) {
                            $this->fillCell($sheet, $cell, 'FFFF0000');
                        } elseif ($date->lte($soon)) {
                            $this->fillCell($sheet, $cell, 'FFFFFF00');
                        }
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $style = $sheet->getStyle($cell);
        $style->getFill()->setFillType(Fill::FILL_SOLID);
        $style->getFill()->getStartColor()->setARGB($argb);
        $style->getFont()->setBold(true);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function colToIndex(string $col): int
    {
        $col = strtoupper($col);
        $num = 0;

        for ($i = 0; $i < strlen($col); $i++) {
            $num = $num * 26 + (ord($col[$i]) - 64);
        }

        return $num;
    }

    private function indexToCol(int $index): string
    {
        $col = '';

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $col = chr(65 + $mod) . $col;
            $index = intdiv($index - 1, 26);
        }

        return $col;
    }
}
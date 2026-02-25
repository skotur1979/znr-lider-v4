<?php

namespace App\Exports;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use App\Models\FirstAidKit;
use App\Models\FirstAidItem;
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

class FirstAidKitsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    ShouldAutoSize,
    WithEvents
{
    /** @var Collection<int, FirstAidKit> */
    protected Collection $kits;

    private int $maxItems = 15; // promijeni ako želiš više

    public function __construct()
    {
        // koristi isti query scope kao tablica (admin vidi sve, user samo svoje)
        $this->kits = FirstAidKitResource::getEloquentQuery()
            ->with(['items' => fn ($q) => $q->orderBy('valid_until')])
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
            'lokacija_ormarica',
            'pregled_obavljen',
            'napomena',
            'ukupan_broj_stavki',
            'uskoro_istice',
            'isteklo',
            'najraniji_rok',
        ];

        for ($i = 1; $i <= $this->maxItems; $i++) {
            $heads[] = "stavka_{$i}_vrsta";
            $heads[] = "stavka_{$i}_namjena";
            $heads[] = "stavka_{$i}_vrijedi_do";
        }

        return $heads;
    }

    public function map($kit): array
    {
        /** @var FirstAidKit $kit */
        $items = $kit->items?->values() ?? collect();

        $today = Carbon::today();
        $soonLimit = $today->copy()->addDays(30);

        $soon = 0;
        $expired = 0;

        $dates = $items
            ->pluck('valid_until')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->startOfDay());

        foreach ($dates as $d) {
            if ($d->lt($today)) $expired++;
            elseif ($d->lte($soonLimit)) $soon++;
        }

        $earliest = $dates->sort()->first();

        $excelDate = fn ($date) => $date ? ExcelDate::dateTimeToExcel(Carbon::parse($date)) : null;

        $row = [
            $kit->location,
            $excelDate($kit->inspected_at),
            $kit->note,
            (int) $kit->items_count,
            (int) $soon,
            (int) $expired,
            $earliest ? ExcelDate::dateTimeToExcel($earliest) : null,
        ];

        for ($i = 0; $i < $this->maxItems; $i++) {
            /** @var FirstAidItem|null $it */
            $it = $items->get($i);

            $row[] = $it?->material_type ?? null;
            $row[] = $it?->purpose ?? null;
            $row[] = $excelDate($it?->valid_until);
        }

        return $row;
    }

    public function columnFormats(): array
    {
        $d = NumberFormat::FORMAT_DATE_DDMMYYYY;

        // B = inspected_at, G = earliest_rok
        $formats = [
            'B' => $d,
            'G' => $d,
        ];

        // svaka 3. kolona u item blokovima je datum
        // početak item blokova je H
        $startColIndex = $this->colToIndex('H');
        for ($i = 0; $i < $this->maxItems; $i++) {
            $dateColIndex = $startColIndex + ($i * 3) + 2; // vrsta, namjena, datum
            $formats[$this->indexToCol($dateColIndex)] = $d;
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

                // header
                $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastCol}1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // wrap za napomenu + namjene
                $sheet->getStyle("C1:C{$lastRow}")->getAlignment()->setWrapText(true);

                // boje isteka
                $today = Carbon::today();
                $soon  = $today->copy()->addDays(30);

                foreach ($this->kits as $i => $kit) {
                    $row = $i + 2;

                    // G = najraniji rok
                    $earliest = $kit->items
                        ?->pluck('valid_until')
                        ->filter()
                        ->map(fn ($d) => Carbon::parse($d)->startOfDay())
                        ->sort()
                        ->first();

                    if ($earliest) {
                        $cell = "G{$row}";
                        if ($earliest->lt($today)) {
                            $this->fillCell($sheet, $cell, 'FFFF0000'); // 🔴
                        } elseif ($earliest->lte($soon)) {
                            $this->fillCell($sheet, $cell, 'FFFFFF00'); // 🟡
                        }
                    }

                    // oboji sve "stavka_i_vrijedi_do" datume
                    $startColIndex = $this->colToIndex('H');
                    for ($k = 0; $k < $this->maxItems; $k++) {
                        $it = $kit->items?->values()->get($k);
                        if (! $it?->valid_until) continue;

                        $d = Carbon::parse($it->valid_until)->startOfDay();

                        $dateColIndex = $startColIndex + ($k * 3) + 2;
                        $cell = $this->indexToCol($dateColIndex) . $row;

                        if ($d->lt($today)) {
                            $this->fillCell($sheet, $cell, 'FFFF0000'); // 🔴
                        } elseif ($d->lte($soon)) {
                            $this->fillCell($sheet, $cell, 'FFFFFF00'); // 🟡
                        }
                    }
                }
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

    // helpers (A=1, B=2...)
    private function colToIndex(string $col): int
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $num = 0;

        for ($i = 0; $i < $len; $i++) {
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
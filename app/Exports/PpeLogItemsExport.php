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

    /** @var \Illuminate\Support\Collection */
    protected $items;

    public function __construct(PPELog $log)
    {
        $this->log = $log->load('items');
        $this->items = $this->log->items->sortBy('equipment_name')->values();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
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
        $issue = $item->issue_date ? Carbon::parse($item->issue_date) : null;
        $end   = $item->end_date ? Carbon::parse($item->end_date) : null;
        $ret   = $item->return_date ? Carbon::parse($item->return_date) : null;

        return [
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
            'E' => 'dd.mm.yyyy', // Izdano
            'F' => 'dd.mm.yyyy', // Istek
            'G' => 'dd.mm.yyyy', // Datum vraćanja
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:G1')->getFont()->setBold(true);
                $sheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $today = Carbon::today();

                foreach ($this->items as $i => $item) {
                    $row = $i + 2; // header = 1
                    $end = $item->end_date ? Carbon::parse($item->end_date) : null;

                    if (! $end) {
                        continue;
                    }

                    // F = Istek
                    $cell = "F{$row}";

                    if ($end->lt($today)) {
                        $this->fillCell($sheet, $cell, 'FFFF0000'); // crveno
                        continue;
                    }

                    if ($end->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, $cell, 'FFFFFF00'); // žuto
                        continue;
                    }
                }
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
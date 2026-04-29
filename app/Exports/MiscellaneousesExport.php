<?php

namespace App\Exports;

use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Models\Miscellaneous;
use Carbon\Carbon;
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

class MiscellaneousesExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    protected $records;

    public function __construct()
    {
        $this->records = MiscellaneousResource::getEloquentQuery()
            ->with(['user', 'category'])
            ->orderByDesc('examination_valid_until')
            ->get();
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Naziv',
            'Korisnik',
            'Kategorija',
            'Ispitao',
            'Broj izvještaja',
            'Vrijedi od',
            'Vrijedi do',
            'Napomena',
            'Broj priloga',
        ];
    }

    public function map($record): array
    {
        /** @var Miscellaneous $record */

        $from = $record->examination_valid_from ? Carbon::parse($record->examination_valid_from) : null;
        $until = $record->examination_valid_until ? Carbon::parse($record->examination_valid_until) : null;

        return [
            $record->name,
            $record->user?->name ?? '',
            $record->category?->name ?? '',
            $record->examiner,
            $record->report_number,
            $from ? ExcelDate::dateTimeToExcel($from) : null,
            $until ? ExcelDate::dateTimeToExcel($until) : null,
            $record->remark,
            is_array($record->pdf) ? count($record->pdf) : 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => 'dd.mm.yyyy',
            'G' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->records->count() + 1;

                $sheet->getStyle("A1:I{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:I1')->applyFromArray([
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

                $sheet->getStyle("A2:I{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:I{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("F2:G{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("I2:I{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $widths = [
                    'A' => 32,
                    'B' => 22,
                    'C' => 26,
                    'D' => 24,
                    'E' => 22,
                    'F' => 16,
                    'G' => 16,
                    'H' => 45,
                    'I' => 14,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $today = Carbon::today();

                foreach ($this->records as $i => $record) {
                    $row = $i + 2;

                    $until = $record->examination_valid_until
                        ? Carbon::parse($record->examination_valid_until)
                        : null;

                    if (! $until) {
                        continue;
                    }

                    if ($until->lt($today)) {
                        $this->fillCell($sheet, "G{$row}", 'FFFF0000');
                        continue;
                    }

                    if ($until->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, "G{$row}", 'FFFFFF00');
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:I{$lastRow}");
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
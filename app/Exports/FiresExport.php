<?php

namespace App\Exports;

use App\Filament\Resources\Fires\FireResource;
use App\Models\Fire;
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

class FiresExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    protected $fires;

    public function __construct()
    {
        $this->fires = FireResource::getEloquentQuery()
            ->with('user')
            ->orderBy('place')
            ->get();
    }

    public function collection()
    {
        return $this->fires;
    }

    public function headings(): array
    {
        return [
            'Mjesto',
            'Korisnik',
            'Tip',
            'Tvor. broj / god. proizv.',
            'Serijski broj',
            'Datum periodičkog servisa',
            'Vrijedi do',
            'Serviser',
            'Datum redovnog pregleda',
            'Uočljivost',
            'Uočeni nedostatci',
            'Postupci otklanjanja',
            'Broj priloga',
        ];
    }

    public function map($fire): array
    {
        /** @var Fire $fire */

        $serviceFrom = $fire->examination_valid_from ? Carbon::parse($fire->examination_valid_from) : null;
        $validUntil = $fire->examination_valid_until ? Carbon::parse($fire->examination_valid_until) : null;
        $regularFrom = $fire->regular_examination_valid_from ? Carbon::parse($fire->regular_examination_valid_from) : null;

        return [
            $fire->place,
            $fire->user?->name ?? '',
            $fire->type,
            $fire->factory_number_year_of_production,
            $fire->serial_label_number,
            $serviceFrom ? ExcelDate::dateTimeToExcel($serviceFrom) : null,
            $validUntil ? ExcelDate::dateTimeToExcel($validUntil) : null,
            $fire->service,
            $regularFrom ? ExcelDate::dateTimeToExcel($regularFrom) : null,
            $fire->visible,
            $fire->remark,
            $fire->action,
            is_array($fire->pdf) ? count($fire->pdf) : 0,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => 'dd.mm.yyyy',
            'G' => 'dd.mm.yyyy',
            'I' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->fires->count() + 1;

                $sheet->getStyle("A1:M{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:M1')->applyFromArray([
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

                $sheet->getStyle("A2:M{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:M{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("D2:G{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("I2:I{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("M2:M{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $widths = [
                    'A' => 30,
                    'B' => 22,
                    'C' => 18,
                    'D' => 24,
                    'E' => 24,
                    'F' => 18,
                    'G' => 16,
                    'H' => 28,
                    'I' => 20,
                    'J' => 28,
                    'K' => 32,
                    'L' => 32,
                    'M' => 14,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $today = Carbon::today();

                foreach ($this->fires as $i => $fire) {
                    $row = $i + 2;

                    $until = $fire->examination_valid_until
                        ? Carbon::parse($fire->examination_valid_until)
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
                $sheet->setAutoFilter("A1:M{$lastRow}");
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
<?php

namespace App\Exports;

use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Models\Miscellaneous;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MiscellaneousesExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithEvents
{
    protected $records;

    protected bool $showUserColumn = false;

    public function __construct(?array $recordIds = null)
    {
        $user = auth()->user();

        $this->showUserColumn =
        (bool) $user?->isSuperAdmin();

        $query = MiscellaneousResource::getEloquentQuery()
            ->with(['user', 'category'])
            ->orderByDesc('examination_valid_until');

        if ($recordIds !== null && count($recordIds) > 0) {
            $query->whereIn('miscellaneouses.id', $recordIds);
        } else {
            $query->withoutTrashed();
        }

        $this->records = $query->get();
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        $headings = ['Naziv'];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Kategorija',
            'Ispitao',
            'Broj izvještaja',
            'Vrijedi od',
            'Vrijedi do',
            'Napomena',
            'Broj priloga',
        ]);
    }

    public function map($record): array
    {
        /** @var Miscellaneous $record */

        $from = $record->examination_valid_from ? Carbon::parse($record->examination_valid_from) : null;
        $until = $record->examination_valid_until ? Carbon::parse($record->examination_valid_until) : null;

        $row = [$record->name];

        if ($this->showUserColumn) {
            $row[] = $record->user?->name ?? '';
        }

        return array_merge($row, [
            $record->category?->name ?? '',
            $record->examiner,
            $record->report_number,
            $from ? ExcelDate::dateTimeToExcel($from) : null,
            $until ? ExcelDate::dateTimeToExcel($until) : null,
            $record->remark,
            is_array($record->pdf) ? count($record->pdf) : 0,
        ]);
    }

    public function columnFormats(): array
    {
        if ($this->showUserColumn) {
            return [
                'F' => 'dd.mm.yyyy',
                'G' => 'dd.mm.yyyy',
            ];
        }

        return [
            'E' => 'dd.mm.yyyy',
            'F' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->records->count() + 1;
                $lastCol = $this->showUserColumn ? 'I' : 'H';

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
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setWrapText(false);

                if ($this->showUserColumn) {
                    $sheet->getStyle("F2:G{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("I2:I{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("H2:H{$lastRow}")->getAlignment()->setWrapText(true);

                    $widths = [
                    'A' => 50,
                    'B' => 18,
                    'C' => 34,
                    'D' => 36,
                    'E' => 28,
                    'F' => 15,
                    'G' => 15,
                    'H' => 28,
                    'I' => 10,
                ];

                    $expiryColumn = 'G';
                } else {
                    $sheet->getStyle("E2:F{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("H2:H{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setWrapText(true);
                    $sheet->getStyle("G2:G{$lastRow}")->getAlignment()->setWrapText(true);

                    $widths = [
                    'A' => 50,
                    'B' => 30,
                    'C' => 34,
                    'D' => 24,
                    'E' => 14,
                    'F' => 14,
                    'G' => 28,
                    'H' => 10,
                ];

                    $expiryColumn = 'F';
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(40);
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
                        $this->fillCell($sheet, "{$expiryColumn}{$row}", 'FFFF0000');
                        continue;
                    }

                    if ($until->lte($today->copy()->addDays(30))) {
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
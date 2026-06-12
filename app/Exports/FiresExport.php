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

    protected bool $showUserColumn = false;

    public function __construct(?array $fireIds = null)
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $query = FireResource::getEloquentQuery()
            ->with('user')
            ->orderBy('place');

        if ($fireIds !== null && count($fireIds) > 0) {
            $query->whereIn('fires.id', $fireIds);
        } else {
            $query->withoutTrashed();
        }

        $this->fires = $query->get();
    }

    public function collection()
    {
        return $this->fires;
    }

    public function headings(): array
    {
        $headings = ['Mjesto'];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Tip',
            'Tvor. broj / god. proizv.',
            'Serijski broj eviden. naljepnice',
            'Datum periodičkog servisa',
            'Periodički servis vrijedi do',
            'Datum redovnog pregleda',
            'Redovni pregled vrijedi do',
            'Serviser',
            'Uočljivost',
            'Uočeni nedostaci',
            'Postupci otklanjanja',
            'Broj priloga',
        ]);
    }

    public function map($fire): array
    {
        /** @var Fire $fire */

        $serviceFrom = $fire->examination_valid_from
            ? Carbon::parse($fire->examination_valid_from)
            : null;

        $validUntil = $fire->examination_valid_until
            ? Carbon::parse($fire->examination_valid_until)
            : null;

        $regularFrom = $fire->regular_examination_valid_from
            ? Carbon::parse($fire->regular_examination_valid_from)
            : null;

        $regularUntil = $regularFrom
            ? $regularFrom->copy()->addMonthsNoOverflow(3)
            : null;

        $row = [$fire->place];

        if ($this->showUserColumn) {
            $row[] = $fire->user?->name ?? '';
        }

        return array_merge($row, [
            $fire->type,
            $fire->factory_number_year_of_production,
            $fire->serial_label_number,
            $serviceFrom ? ExcelDate::dateTimeToExcel($serviceFrom) : null,
            $validUntil ? ExcelDate::dateTimeToExcel($validUntil) : null,
            $regularFrom ? ExcelDate::dateTimeToExcel($regularFrom) : null,
            $regularUntil ? ExcelDate::dateTimeToExcel($regularUntil) : null,
            $fire->service,
            $fire->visible,
            $fire->remark,
            $fire->action,
            is_array($fire->pdf) ? count($fire->pdf) : 0,
        ]);
    }

    public function columnFormats(): array
    {
        if ($this->showUserColumn) {
            return [
                'F' => 'dd.mm.yyyy',
                'G' => 'dd.mm.yyyy',
                'H' => 'dd.mm.yyyy',
                'I' => 'dd.mm.yyyy',
            ];
        }

        return [
            'E' => 'dd.mm.yyyy',
            'F' => 'dd.mm.yyyy',
            'G' => 'dd.mm.yyyy',
            'H' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->fires->count() + 1;
                $lastCol = $this->showUserColumn ? 'N' : 'M';

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

                if ($this->showUserColumn) {
                    $sheet->getStyle("D2:I{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("N2:N{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 30,
                        'B' => 22,
                        'C' => 18,
                        'D' => 24,
                        'E' => 26,
                        'F' => 18,
                        'G' => 18,
                        'H' => 22,
                        'I' => 22,
                        'J' => 28,
                        'K' => 22,
                        'L' => 32,
                        'M' => 32,
                        'N' => 14,
                    ];

                    $periodicExpiryColumn = 'G';
                    $regularExpiryColumn = 'I';
                } else {
                    $sheet->getStyle("C2:H{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("M2:M{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 30,
                        'B' => 18,
                        'C' => 24,
                        'D' => 26,
                        'E' => 18,
                        'F' => 18,
                        'G' => 22,
                        'H' => 22,
                        'I' => 28,
                        'J' => 22,
                        'K' => 32,
                        'L' => 32,
                        'M' => 14,
                    ];

                    $periodicExpiryColumn = 'F';
                    $regularExpiryColumn = 'H';
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(34);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $today = Carbon::today();
                $soonDate = Carbon::today()->addDays(30);

                foreach ($this->fires as $i => $fire) {
                    $row = $i + 2;

                    $periodicUntil = $fire->examination_valid_until
                        ? Carbon::parse($fire->examination_valid_until)
                        : null;

                    if ($periodicUntil) {
                        if ($periodicUntil->lt($today)) {
                            $this->fillCell($sheet, "{$periodicExpiryColumn}{$row}", 'FFFF0000');
                        } elseif ($periodicUntil->lte($soonDate)) {
                            $this->fillCell($sheet, "{$periodicExpiryColumn}{$row}", 'FFFFFF00');
                        }
                    }

                    $regularUntil = $fire->regular_examination_valid_from
                        ? Carbon::parse($fire->regular_examination_valid_from)->addMonthsNoOverflow(3)
                        : null;

                    if ($regularUntil) {
                        if ($regularUntil->lt($today)) {
                            $this->fillCell($sheet, "{$regularExpiryColumn}{$row}", 'FFFF0000');
                        } elseif ($regularUntil->lte($soonDate)) {
                            $this->fillCell($sheet, "{$regularExpiryColumn}{$row}", 'FFFFFF00');
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
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}
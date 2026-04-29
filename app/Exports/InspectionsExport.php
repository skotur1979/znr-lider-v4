<?php

namespace App\Exports;

use App\Filament\Resources\Inspections\InspectionResource;
use App\Models\Inspection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class InspectionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $inspections;

    protected bool $showUserColumn = false;

    public function __construct()
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->inspections = InspectionResource::getEloquentQuery()
            ->with('user')
            ->withCount(['findings', 'zones'])
            ->orderByDesc('performed_at')
            ->get();
    }

    public function collection()
    {
        return $this->inspections;
    }

    public function headings(): array
    {
        $headings = [
            'Broj nadzora',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Tip nadzora',
            'Datum nadzora',
            'Naziv nadzora',
            'Lokacija',
            'Proveo nadzor',
            'Prisutne osobe',
            'Status',
            'Opći status',
            '5S rezultat',
            'Broj nalaza',
            'Broj 5S zona',
            'Opis',
            'Zaključak',
        ]);
    }

    public function map($inspection): array
    {
        /** @var Inspection $inspection */

        $fiveSScore = $inspection->calculateFiveSScore();

        $row = [
            $inspection->number,
        ];

        if ($this->showUserColumn) {
            $row[] = $inspection->user?->name ?? '';
        }

        return array_merge($row, [
            $this->inspectionTypeLabel($inspection->inspection_type),
            $inspection->performed_at ? $inspection->performed_at->format('d.m.Y.') : '',
            $inspection->title,
            $inspection->location,
            $inspection->performed_by,
            $inspection->present_persons,
            $inspection->status,
            $inspection->overall_status,
            filled($fiveSScore) ? $fiveSScore . '%' : '-',
            $inspection->findings_count ?? $inspection->findings()->count(),
            $inspection->zones_count ?? $inspection->zones()->count(),
            $inspection->description,
            $inspection->conclusion,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->inspections->count() + 1;
                $lastCol = $this->showUserColumn ? 'O' : 'N';

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
                    $sheet->getStyle("D2:D{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("K2:M{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 16,
                        'B' => 20,
                        'C' => 18,
                        'D' => 16,
                        'E' => 34,
                        'F' => 24,
                        'G' => 24,
                        'H' => 36,
                        'I' => 18,
                        'J' => 18,
                        'K' => 14,
                        'L' => 14,
                        'M' => 14,
                        'N' => 45,
                        'O' => 45,
                    ];
                } else {
                    $sheet->getStyle("C2:C{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("J2:L{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 16,
                        'B' => 18,
                        'C' => 16,
                        'D' => 34,
                        'E' => 24,
                        'F' => 24,
                        'G' => 36,
                        'H' => 18,
                        'I' => 18,
                        'J' => 14,
                        'K' => 14,
                        'L' => 14,
                        'M' => 45,
                        'N' => 45,
                    ];
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(28);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(36);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function inspectionTypeLabel(?string $state): string
    {
        return match ($state) {
            'five_s' => '5S nadzor',
            default => 'Nadzor',
        };
    }
}
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

    public function __construct()
    {
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
        return [
            'Broj nadzora',
            'Korisnik',
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
        ];
    }

    public function map($inspection): array
    {
        /** @var Inspection $inspection */

        $fiveSScore = $inspection->calculateFiveSScore();

        return [
            $inspection->number,
            $inspection->user?->name ?? '',
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
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->inspections->count() + 1;

                $sheet->getStyle("A1:O{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:O1')->applyFromArray([
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

                $sheet->getStyle("A2:O{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:O{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("D2:D{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("K2:M{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getColumnDimension('A')->setWidth(16);
                $sheet->getColumnDimension('B')->setWidth(20);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(16);
                $sheet->getColumnDimension('E')->setWidth(34);
                $sheet->getColumnDimension('F')->setWidth(24);
                $sheet->getColumnDimension('G')->setWidth(24);
                $sheet->getColumnDimension('H')->setWidth(36);
                $sheet->getColumnDimension('I')->setWidth(18);
                $sheet->getColumnDimension('J')->setWidth(18);
                $sheet->getColumnDimension('K')->setWidth(14);
                $sheet->getColumnDimension('L')->setWidth(14);
                $sheet->getColumnDimension('M')->setWidth(14);
                $sheet->getColumnDimension('N')->setWidth(45);
                $sheet->getColumnDimension('O')->setWidth(45);

                $sheet->getRowDimension(1)->setRowHeight(28);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(36);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:O{$lastRow}");
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
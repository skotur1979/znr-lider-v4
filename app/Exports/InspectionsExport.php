<?php

namespace App\Exports;

use App\Filament\Resources\Inspections\InspectionResource;
use App\Models\Inspection;
use App\Models\InspectionFinding;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InspectionsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithDrawings
{
    protected Collection $rows;

    protected bool $showUserColumn = false;

    public function __construct()
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $inspections = InspectionResource::getEloquentQuery()
            ->with(['user', 'findings'])
            ->withCount(['findings', 'zones'])
            ->orderByDesc('performed_at')
            ->get();

        $this->rows = collect();

        foreach ($inspections as $inspection) {
            if ($inspection->findings->isEmpty()) {
                $this->rows->push([
                    'inspection' => $inspection,
                    'finding' => null,
                ]);

                continue;
            }

            foreach ($inspection->findings as $finding) {
                $this->rows->push([
                    'inspection' => $inspection,
                    'finding' => $finding,
                ]);
            }
        }
    }

    public function collection()
    {
        return $this->rows;
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
            'Status',
            'Opći status',
            '5S rezultat',
            'Broj nalaza',
            'Broj 5S zona',

            'Područje / kategorija',
            'Što je uočeno / pronađeno',
            'Opis nalaza',
            'Vrsta nalaza',
            'Status postupanja',
            'Potrebna akcija',
            'Odgovorna osoba',
            'Rok',
            'Napomena rješenja',

            'Slika nalaza',
            'Zaključak',
        ]);
    }

    public function map($row): array
    {
        /** @var Inspection $inspection */
        $inspection = $row['inspection'];

        /** @var InspectionFinding|null $finding */
        $finding = $row['finding'];

        $fiveSScore = $inspection->calculateFiveSScore();

        $data = [
            $inspection->number,
        ];

        if ($this->showUserColumn) {
            $data[] = $inspection->user?->name ?? '';
        }

        return array_merge($data, [
            $this->inspectionTypeLabel($inspection->inspection_type),
            $inspection->performed_at ? $inspection->performed_at->format('d.m.Y.') : '',
            $inspection->title,
            $inspection->location,
            $inspection->performed_by,
            $inspection->status,
            $inspection->overall_status,
            filled($fiveSScore) ? $fiveSScore . '%' : '-',
            $inspection->findings_count ?? $inspection->findings()->count(),
            $inspection->zones_count ?? $inspection->zones()->count(),

            $finding?->category ?? '',
            $finding?->title ?? '',
            $finding?->description ?? '',
            $this->findingTypeLabel($finding?->finding_status),
            $this->workflowStatusLabel($finding?->workflow_status),
            $finding?->action_required ? 'Da' : 'Ne',
            $finding?->responsible_person ?? '',
            $finding?->due_date ? $finding->due_date->format('d.m.Y.') : '',
            $finding?->resolution_note ?? '',

            '', // slika se ubacuje preko drawings()
            $inspection->conclusion,
        ]);
    }

    public function drawings(): array
    {
        $drawings = [];

        $imageColumn = $this->showUserColumn ? 'V' : 'U';

        foreach ($this->rows->values() as $index => $row) {
            /** @var InspectionFinding|null $finding */
            $finding = $row['finding'];

            if (! $finding || blank($finding->photo_path)) {
                continue;
            }

            $path = $this->photoFullPath($finding->photo_path);

            if (! $path || ! file_exists($path)) {
                continue;
            }

            $excelRow = $index + 2;

            $drawing = new Drawing();
            $drawing->setName('Slika nalaza');
            $drawing->setDescription('Slika nalaza');
            $drawing->setPath($path);
            $drawing->setCoordinates($imageColumn . $excelRow);
            $drawing->setHeight(85);
            $drawing->setOffsetX(8);
            $drawing->setOffsetY(5);

            $drawings[] = $drawing;
        }

        return $drawings;
    }

    private function photoFullPath(?string $photoPath): ?string
    {
        if (blank($photoPath)) {
            return null;
        }

        $photoPath = ltrim($photoPath, '/');

        if (str_starts_with($photoPath, 'storage/')) {
            $photoPath = str_replace('storage/', '', $photoPath);
        }

        return storage_path('app/public/' . $photoPath);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->rows->count() + 1;
                $lastCol = $this->showUserColumn ? 'W' : 'V';

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
                    ->setVertical(Alignment::VERTICAL_TOP)
                    ->setWrapText(true);

                $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $centerColumns = $this->showUserColumn
                    ? ['D', 'I', 'J', 'K', 'L', 'M', 'Q', 'R', 'T', 'V']
                    : ['C', 'H', 'I', 'J', 'K', 'L', 'P', 'Q', 'S', 'U'];

                foreach ($centerColumns as $column) {
                    $sheet->getStyle("{$column}2:{$column}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $widths = $this->showUserColumn
                    ? [
                        'A' => 16,
                        'B' => 20,
                        'C' => 16,
                        'D' => 16,
                        'E' => 28,
                        'F' => 22,
                        'G' => 22,
                        'H' => 18,
                        'I' => 18,
                        'J' => 14,
                        'K' => 14,
                        'L' => 14,
                        'M' => 22,
                        'N' => 38,
                        'O' => 45,
                        'P' => 20,
                        'Q' => 22,
                        'R' => 14,
                        'S' => 24,
                        'T' => 16,
                        'U' => 38,
                        'V' => 24,
                        'W' => 45,
                    ]
                    : [
                        'A' => 16,
                        'B' => 16,
                        'C' => 16,
                        'D' => 28,
                        'E' => 22,
                        'F' => 22,
                        'G' => 18,
                        'H' => 18,
                        'I' => 14,
                        'J' => 14,
                        'K' => 14,
                        'L' => 22,
                        'M' => 38,
                        'N' => 45,
                        'O' => 20,
                        'P' => 22,
                        'Q' => 14,
                        'R' => 24,
                        'S' => 16,
                        'T' => 38,
                        'U' => 24,
                        'V' => 45,
                    ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(34);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(78);
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

    private function findingTypeLabel(?string $state): string
    {
        return match ($state) {
            'ok' => 'Uredno',
            'noncompliance' => 'Nepravilnost',
            'recommendation' => 'Preporuka',
            'critical' => 'Kritična nepravilnost',
            'positive' => 'Pozitivno zapažanje',
            default => $state ?? '',
        };
    }

    private function workflowStatusLabel(?string $state): string
    {
        return match ($state) {
            'open' => 'Nije započeto',
            'in_progress' => 'U tijeku',
            'resolved' => 'Riješeno',
            'resolved_no_action' => 'Riješeno bez akcija',
            'closed' => 'Zatvoreno',
            'converted_to_observation' => 'Pretvoreno u zapažanje',
            'rejected' => 'Odbačeno',
            default => $state ?? '',
        };
    }
}
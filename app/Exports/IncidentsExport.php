<?php

namespace App\Exports;

use App\Filament\Resources\Incidents\IncidentResource;
use App\Models\Incident;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class IncidentsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents, WithDrawings
{
    protected $incidents;

    protected array $filters = [];

    protected bool $showUserColumn = false;

    private int $imgHeight = 70;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin();

        $query = IncidentResource::getEloquentQuery()
            ->with('user')
            ->orderByDesc('date_occurred');

        $query = $this->applyFilters($query, $filters);

        $this->incidents = $query->get();
    }

    public function collection()
    {
        return $this->incidents;
    }

    public function headings(): array
    {
        $headings = [
            'Datum nastanka',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Vrsta incidenta',
            'Vrsta zaposlenja',
            'Lokacija',
            'Ozlijeđeni dio tijela',
            'Izgubljeni radni dani',
            'Datum povratka',
            'Uzrok ozljede',
            'Tip ozljede',
            'Napomena / podaci o ozlijeđenom radniku',
            'Broj priloga',
            'Slika',
        ]);
    }

    public function map($i): array
    {
        /** @var Incident $i */

        $occurred = $i->date_occurred ? Carbon::parse($i->date_occurred) : null;
        $return = $i->date_of_return ? Carbon::parse($i->date_of_return) : null;

        $row = [
            $occurred ? ExcelDate::dateTimeToExcel($occurred) : null,
        ];

        if ($this->showUserColumn) {
            $row[] = $i->user?->name ?? '';
        }

        return array_merge($row, [
            $this->incidentTypeLabel($i->type_of_incident),
            $this->employmentTypeLabel($i->permanent_or_temporary),
            $i->location,
            $i->injured_body_part,
            $i->working_days_lost,
            $return ? ExcelDate::dateTimeToExcel($return) : null,
            $i->causes_of_injury,
            $i->accident_injury_type,
            $i->other,
            is_array($i->investigation_report) ? count($i->investigation_report) : 0,
            null,
        ]);
    }

    public function columnFormats(): array
    {
        if ($this->showUserColumn) {
            return [
                'A' => 'dd.mm.yyyy',
                'H' => 'dd.mm.yyyy',
            ];
        }

        return [
            'A' => 'dd.mm.yyyy',
            'G' => 'dd.mm.yyyy',
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        $imageColumn = $this->showUserColumn ? 'M' : 'L';

        foreach ($this->incidents as $idx => $i) {
            if (! $i->image_path) {
                continue;
            }

            $fullPath = storage_path('app/public/' . $i->image_path);

            if (! file_exists($fullPath)) {
                continue;
            }

            $row = $idx + 2;

            $drawing = new Drawing();
            $drawing->setName("slika_{$row}");
            $drawing->setDescription('Slika incidenta');
            $drawing->setPath($fullPath);
            $drawing->setHeight($this->imgHeight);
            $drawing->setResizeProportional(true);
            $drawing->setCoordinates("{$imageColumn}{$row}");
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);

            $drawings[] = $drawing;
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->incidents->count() + 1;
                $lastCol = $this->showUserColumn ? 'M' : 'L';

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
                    $sheet->getStyle("A2:D{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("G2:H{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("L2:M{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 16,
                        'B' => 20,
                        'C' => 26,
                        'D' => 18,
                        'E' => 24,
                        'F' => 28,
                        'G' => 18,
                        'H' => 16,
                        'I' => 40,
                        'J' => 40,
                        'K' => 45,
                        'L' => 14,
                        'M' => 15,
                    ];

                    $returnDateColumn = 'H';
                } else {
                    $sheet->getStyle("A2:C{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("F2:G{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("K2:L{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 16,
                        'B' => 26,
                        'C' => 18,
                        'D' => 24,
                        'E' => 28,
                        'F' => 18,
                        'G' => 16,
                        'H' => 40,
                        'I' => 40,
                        'J' => 45,
                        'K' => 14,
                        'L' => 15,
                    ];

                    $returnDateColumn = 'G';
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                foreach ($this->incidents as $idx => $i) {
                    $row = $idx + 2;
                    $sheet->getRowDimension($row)->setRowHeight($i->image_path ? $this->imgHeight * 0.75 : 38);
                }

                $today = Carbon::today();

                foreach ($this->incidents as $idx => $i) {
                    $row = $idx + 2;

                    $return = $i->date_of_return ? Carbon::parse($i->date_of_return) : null;

                    if (! $return) {
                        continue;
                    }

                    if ($return->lt($today)) {
                        $this->fillCell($sheet, "{$returnDateColumn}{$row}", 'FFFF0000');
                        continue;
                    }

                    if ($return->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, "{$returnDateColumn}{$row}", 'FFFFFF00');
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function incidentTypeLabel(?string $value): string
    {
        return match ($value) {
            'LTA' => 'LTA – Ozljeda na radu',
            'MTA' => 'MTA – Pružanje PP izvan tvrtke',
            'FAA' => 'FAA – Pružanje PP u tvrtki',
            default => $value ?? '',
        };
    }

    private function employmentTypeLabel(?string $value): string
    {
        return match ($value) {
            'Permanent' => 'Stalni',
            'Temporary' => 'Privremeni',
            default => $value ?? '',
        };
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }

    private function applyFilters($query, array $filters)
    {
        $status = data_get($filters, 'status.value');

        $query = match ($status) {
            'trashed' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query->withoutTrashed(),
        };

        $type = data_get($filters, 'type_of_incident.value');

        if ($type) {
            $query->where('type_of_incident', $type);
        }

        $year = data_get($filters, 'godina_filter.value');

        if ($year) {
            $query->whereYear('date_occurred', $year);
        }

        return $query;
    }
}
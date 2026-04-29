<?php

namespace App\Exports;

use App\Filament\Resources\Observations\ObservationResource;
use App\Models\Observation;
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

class ObservationsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents, WithDrawings
{
    protected $observations;

    protected bool $showUserColumn = false;

    private int $imgHeight = 70;

    public function __construct()
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->observations = ObservationResource::getEloquentQuery()
            ->with('user')
            ->orderByDesc('incident_date')
            ->get();
    }

    public function collection()
    {
        return $this->observations;
    }

    public function headings(): array
    {
        $headings = [
            'Datum',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Vrsta zapažanja',
            'Prioritet',
            'Lokacija',
            'Opis',
            'Vrsta opasnosti',
            'Potrebna radnja',
            'Odgovorna osoba',
            'Rok za provedbu',
            'Status',
            'E-mail primatelji',
            'Poslano',
            'Komentar',
            'Slika',
        ]);
    }

    public function map($o): array
    {
        /** @var Observation $o */

        $incident = $o->incident_date ? Carbon::parse($o->incident_date) : null;
        $target = $o->target_date ? Carbon::parse($o->target_date) : null;
        $sentAt = $o->sent_at ? Carbon::parse($o->sent_at) : null;

        $row = [
            $incident ? ExcelDate::dateTimeToExcel($incident) : null,
        ];

        if ($this->showUserColumn) {
            $row[] = $o->user?->name ?? '';
        }

        return array_merge($row, [
            $this->observationTypeLabel($o->observation_type),
            $this->priorityLabel($o->priority),
            $o->location,
            $o->item,
            $o->potential_incident_type,
            $o->action,
            $o->responsible,
            $target ? ExcelDate::dateTimeToExcel($target) : null,
            $this->statusLabel($o->status),
            $this->emails($o->notification_emails ?? null),
            $sentAt ? ExcelDate::dateTimeToExcel($sentAt) : null,
            $o->comments,
            null,
        ]);
    }

    public function columnFormats(): array
    {
        if ($this->showUserColumn) {
            return [
                'A' => 'dd.mm.yyyy',
                'J' => 'dd.mm.yyyy',
                'M' => 'dd.mm.yyyy hh:mm',
            ];
        }

        return [
            'A' => 'dd.mm.yyyy',
            'I' => 'dd.mm.yyyy',
            'L' => 'dd.mm.yyyy hh:mm',
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        $imageColumn = $this->showUserColumn ? 'O' : 'N';

        foreach ($this->observations as $i => $o) {
            if (! $o->picture_path) {
                continue;
            }

            $fullPath = storage_path('app/public/' . $o->picture_path);

            if (! file_exists($fullPath)) {
                continue;
            }

            $row = $i + 2;

            $drawing = new Drawing();
            $drawing->setName("slika_{$row}");
            $drawing->setDescription('Slika zapažanja');
            $drawing->setPath($fullPath);
            $drawing->setHeight($this->imgHeight);
            $drawing->setCoordinates("{$imageColumn}{$row}");
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setResizeProportional(true);

            $drawings[] = $drawing;
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->observations->count() + 1;
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
                    $sheet->getStyle("A2:D{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("I2:M{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 14,
                        'B' => 20,
                        'C' => 24,
                        'D' => 14,
                        'E' => 22,
                        'F' => 42,
                        'G' => 30,
                        'H' => 42,
                        'I' => 22,
                        'J' => 16,
                        'K' => 18,
                        'L' => 34,
                        'M' => 18,
                        'N' => 35,
                        'O' => 15,
                    ];

                    $targetDateColumn = 'J';
                } else {
                    $sheet->getStyle("A2:C{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("H2:L{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 14,
                        'B' => 24,
                        'C' => 14,
                        'D' => 22,
                        'E' => 42,
                        'F' => 30,
                        'G' => 42,
                        'H' => 22,
                        'I' => 16,
                        'J' => 18,
                        'K' => 34,
                        'L' => 18,
                        'M' => 35,
                        'N' => 15,
                    ];

                    $targetDateColumn = 'I';
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                foreach ($this->observations as $i => $o) {
                    $row = $i + 2;
                    $sheet->getRowDimension($row)->setRowHeight($o->picture_path ? $this->imgHeight * 0.75 : 38);
                }

                $today = Carbon::today();

                foreach ($this->observations as $i => $o) {
                    $row = $i + 2;
                    $target = $o->target_date ? Carbon::parse($o->target_date) : null;

                    if (! $target || $o->status === 'Complete') {
                        continue;
                    }

                    if ($target->lt($today)) {
                        $this->fillCell($sheet, "{$targetDateColumn}{$row}", 'FFFF0000');
                        continue;
                    }

                    if ($target->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, "{$targetDateColumn}{$row}", 'FFFFFF00');
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function observationTypeLabel(?string $state): string
    {
        return match ($state) {
            'Near Miss' => 'NM - Skoro nezgoda',
            'Negative Observation' => 'Negativno zapažanje',
            'Positive Observation' => 'Pozitivno zapažanje',
            default => $state ?? '',
        };
    }

    private function priorityLabel(?string $state): string
    {
        return match ($state) {
            'low' => 'Nisko',
            'medium' => 'Srednje',
            'high' => 'Visoko',
            'critical' => 'Kritično',
            default => $state ?? '',
        };
    }

    private function statusLabel(?string $state): string
    {
        return match ($state) {
            'Not started' => 'Nije započeto',
            'In progress' => 'U tijeku',
            'Complete' => 'Završeno',
            default => $state ?? '',
        };
    }

    private function emails($value): string
    {
        if (is_array($value)) {
            return collect($value)->filter()->implode(', ');
        }

        return (string) ($value ?? '');
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}
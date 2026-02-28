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

class ObservationsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    ShouldAutoSize,
    WithEvents,
    WithDrawings
{
    protected $observations;

    // kontrola veličine slike
    private int $imgHeight = 70; // visina slike u px

    public function __construct()
    {
        $this->observations = ObservationResource::getEloquentQuery()
            ->orderByDesc('incident_date')
            ->get();
    }

    public function collection()
    {
        return $this->observations;
    }

    public function headings(): array
    {
        return [
            'Datum',
            'Vrsta zapažanja',
            'Lokacija',
            'Opis',
            'Vrsta opasnosti',
            'Potrebna radnja',
            'Odgovorna osoba',
            'Rok za provedbu',
            'Status',
            'Komentar',
            'Slika',
        ];
    }

    public function map($o): array
    {
        $incident = $o->incident_date ? Carbon::parse($o->incident_date) : null;
        $target   = $o->target_date ? Carbon::parse($o->target_date) : null;

        $type = match ($o->observation_type) {
            'Near Miss' => 'NM - Skoro nezgoda',
            'Negative Observation' => 'Negativno zapažanje',
            'Positive Observation' => 'Pozitivno zapažanje',
            default => (string) $o->observation_type,
        };

        $status = match ($o->status) {
            'Not started' => 'Nije započeto',
            'In progress' => 'U tijeku',
            'Complete' => 'Završeno',
            default => (string) $o->status,
        };

        return [
            $incident ? ExcelDate::dateTimeToExcel($incident) : null,
            $type,
            $o->location,
            $o->item,
            $o->potential_incident_type,
            $o->action,
            $o->responsible,
            $target ? ExcelDate::dateTimeToExcel($target) : null,
            $status,
            $o->comments,
            null,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => 'dd.mm.yyyy',
            'H' => 'dd.mm.yyyy',
        ];
    }

    /**
     * 🔥 Slika zaključana u ćeliji
     */
    public function drawings(): array
    {
        $drawings = [];

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
            $drawing->setName('Slika');
            $drawing->setDescription('Observation image');
            $drawing->setPath($fullPath);

            // Zaključaj veličinu slike
            $drawing->setHeight($this->imgHeight);

            $drawing->setCoordinates("K{$row}");

            // Centriraj unutar ćelije
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);

            // 🔥 Zaključaj na ćeliju
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

                // ===== WRAP TEXT SVUGDJE =====
                $sheet->getStyle("A1:K{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                // ===== CENTRIRAJ SVE =====
                $sheet->getStyle("A1:K{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // tekstualne kolone lijevo
                $sheet->getStyle("C2:F{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("J2:J{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // ===== POSTAVI VISINU REDA TOČNO KAO SLIKA =====
                foreach ($this->observations as $i => $o) {
                    $row = $i + 2;
                    $sheet->getRowDimension($row)->setRowHeight($this->imgHeight * 0.75);
                }

                // ===== POSTAVI ŠIRINU KOLONE K TOČNO KAO SLIKA =====
                // 1 Excel width unit ≈ 7 px
                $excelWidth = ($this->imgHeight * 1.3) / 7;
                $sheet->getColumnDimension('K')->setWidth($excelWidth);

                // ===== BOJE ROKA =====
                $today = Carbon::today();

                foreach ($this->observations as $i => $o) {

                    $row = $i + 2;
                    $target = $o->target_date ? Carbon::parse($o->target_date) : null;

                    if (! $target || $o->status === 'Complete') {
                        continue;
                    }

                    if ($target->lt($today)) {
                        $this->fillCell($sheet, "H{$row}", 'FFFF0000');
                        continue;
                    }

                    if ($target->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, "H{$row}", 'FFFFFF00');
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
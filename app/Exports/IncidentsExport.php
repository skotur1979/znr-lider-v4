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

class IncidentsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    ShouldAutoSize,
    WithEvents,
    WithDrawings
{
    protected $incidents;

    protected array $filters = [];

    // kontrola veličine slike
    private int $imgHeight = 70; // px

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;

        $query = IncidentResource::getEloquentQuery()
            ->orderByDesc('date_occurred');

        // ✅ primijeni iste filtere kao u tablici / exportu
        $query = $this->applyFilters($query, $filters);

        $this->incidents = $query->get();
    }

    public function collection()
    {
        return $this->incidents;
    }

    public function headings(): array
    {
        return [
            'Datum nastanka',
            'Vrsta incidenta',
            'Lokacija',
            'Ozlijeđeni dio tijela',
            'Izgubljeni radni dani',
            'Datum povratka',
            'Uzrok ozljede',
            'Tip ozljede',
            'Napomena',
            'Broj priloga',
            'Slika',
        ];
    }

    public function map($i): array
    {
        /** @var Incident $i */
        $occurred = $i->date_occurred ? Carbon::parse($i->date_occurred) : null;
        $return   = $i->date_of_return ? Carbon::parse($i->date_of_return) : null;

        $type = match ($i->type_of_incident) {
            'LTA' => 'LTA – Ozljeda na radu',
            'MTA' => 'MTA – Pružanje PP izvan tvrtke',
            'FAA' => 'FAA – Pružanje PP u tvrtki',
            default => (string) $i->type_of_incident,
        };

        $attachmentsCount = is_array($i->investigation_report) ? count($i->investigation_report) : 0;

        return [
            $occurred ? ExcelDate::dateTimeToExcel($occurred) : null,
            $type,
            $i->location,
            $i->injured_body_part,
            $i->working_days_lost,
            $return ? ExcelDate::dateTimeToExcel($return) : null,
            $i->causes_of_injury,
            $i->accident_injury_type,
            $i->other,
            $attachmentsCount,
            null, // slika ide preko drawings()
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => 'dd.mm.yyyy', // datum nastanka
            'F' => 'dd.mm.yyyy', // datum povratka
        ];
    }

    /**
     * 🔥 Slika zaključana u ćeliji (kolona K)
     */
    public function drawings(): array
    {
        $drawings = [];

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
            $drawing->setName('Slika');
            $drawing->setDescription('Incident image');
            $drawing->setPath($fullPath);

            // Zaključaj veličinu slike
            $drawing->setHeight($this->imgHeight);
            $drawing->setResizeProportional(true);

            // ✅ kolona K = "Slika"
            $drawing->setCoordinates("K{$row}");

            // centriraj unutar ćelije
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

                // ===== WRAP TEXT SVUGDJE =====
                $sheet->getStyle("A1:K{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true);

                // ===== CENTRIRAJ SVE =====
                $sheet->getStyle("A1:K{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // tekstualne kolone lijevo (Lokacija, Ozlijeđeni dio, Uzrok, Tip, Napomena)
                $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("D2:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("G2:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // ===== VISINA REDA = slika =====
                foreach ($this->incidents as $idx => $i) {
                    $row = $idx + 2;
                    $sheet->getRowDimension($row)->setRowHeight($this->imgHeight * 0.75);
                }

                // ===== ŠIRINA KOLONE K (Slika) =====
                // 1 Excel width unit ≈ 7 px
                $excelWidth = ($this->imgHeight * 1.3) / 7;
                $sheet->getColumnDimension('K')->setWidth($excelWidth);

                // ===== BOJE DATUM POVRATKA (F) =====
                // crveno: prošao datum povratka
                // žuto: povratak unutar 30 dana
                $today = Carbon::today();

                foreach ($this->incidents as $idx => $i) {
                    $row = $idx + 2;

                    $return = $i->date_of_return ? Carbon::parse($i->date_of_return) : null;
                    if (! $return) {
                        continue;
                    }

                    if ($return->lt($today)) {
                        $this->fillCell($sheet, "F{$row}", 'FFFF0000');
                        continue;
                    }

                    if ($return->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, "F{$row}", 'FFFFFF00');
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

    private function applyFilters($query, array $filters)
    {
        // status: active/trashed/all
        $status = data_get($filters, 'status.value');
        $query = match ($status) {
            'trashed' => $query->onlyTrashed(),
            'all'     => $query->withTrashed(),
            default   => $query->withoutTrashed(),
        };

        // vrsta incidenta
        $type = data_get($filters, 'type_of_incident.value');
        if ($type) {
            $query->where('type_of_incident', $type);
        }

        // godina
        $year = data_get($filters, 'godina_filter.value');
        if ($year) {
            $query->whereYear('date_occurred', $year);
        }

        return $query;
    }
}
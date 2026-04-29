<?php

namespace App\Exports;

use App\Filament\Resources\WorkPermits\WorkPermitResource;
use App\Models\WorkPermit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WorkPermitsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $permits;

    protected bool $showUserColumn = false;

    public function __construct()
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->permits = WorkPermitResource::getEloquentQuery()
            ->with('user')
            ->orderByDesc('issue_date')
            ->get();
    }

    public function collection()
    {
        return $this->permits;
    }

    public function headings(): array
    {
        $headings = ['Broj dozvole'];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Datum',
            'Vrijedi od',
            'Vrijedi do',
            'Vrsta poslova',
            'Ostala vrsta posla',
            'Zahtjev / propis',
            'Izvođači',
            'Radnici',
            'Opis radova',
            'Kontakt osoba',
            'Telefon',
            'Mjere',
            'Dodatne mjere',
            'Potrebna oprema',
            'Opasnosti rada',
            'Ostala opasnost',
            'OZO',
            'Osoba koja zahtjeva dozvolu',
            'Osoba koja odobrava dozvolu',
            'Produženje vrijedi od',
            'Produženje vrijedi do',
            'Odobrio produženje',
            'Radovi završeni',
            'Provjera nakon',
            'Razlog nezavršetka',
            'Provjerio',
            'Datum provjere',
            'Vrijeme provjere',
        ]);
    }

    public function map($permit): array
    {
        /** @var WorkPermit $permit */

        $row = [$permit->permit_number];

        if ($this->showUserColumn) {
            $row[] = $permit->user?->name ?? '';
        }

        return array_merge($row, [
            $permit->issue_date ? $permit->issue_date->format('d.m.Y.') : '',
            $permit->valid_from ? $permit->valid_from->format('d.m.Y. H:i') : '',
            $permit->valid_until ? $permit->valid_until->format('d.m.Y. H:i') : '',
            $this->labels($permit->work_types, WorkPermit::workTypeOptions()),
            $permit->other_work_type,
            $permit->request_or_regulation,
            $this->labels($permit->executor_types, WorkPermit::executorTypeOptions()),
            $this->workers($permit),
            $permit->work_description,
            $permit->contact_person,
            $permit->phone,
            $this->labels($permit->required_measures, WorkPermit::requiredMeasuresOptions()),
            $permit->additional_measures,
            $permit->required_equipment,
            $this->labels($permit->work_hazards, WorkPermit::hazardOptions()),
            $permit->other_hazard,
            $this->labels($permit->required_ppe, WorkPermit::ppeOptions()),
            $permit->requester_name,
            $permit->approver_name,
            $permit->extension_valid_from ? $permit->extension_valid_from->format('d.m.Y. H:i') : '',
            $permit->extension_valid_until ? $permit->extension_valid_until->format('d.m.Y. H:i') : '',
            $permit->extension_approver_name,
            $this->yesNo($permit->works_finished),
            $permit->checked_after,
            $permit->unfinished_reason,
            $permit->verification_name,
            $permit->verification_date ? $permit->verification_date->format('d.m.Y.') : '',
            $permit->verification_time,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->permits->count() + 1;
                $lastCol = $this->showUserColumn ? 'AD' : 'AC';

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
                    $sheet->getStyle("A2:E{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("Y2:Z{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("AC2:AD{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 16, 'B' => 20, 'C' => 14, 'D' => 18, 'E' => 18,
                        'F' => 32, 'G' => 22, 'H' => 36, 'I' => 28, 'J' => 36,
                        'K' => 44, 'L' => 24, 'M' => 18, 'N' => 55, 'O' => 42,
                        'P' => 36, 'Q' => 55, 'R' => 24, 'S' => 55, 'T' => 28,
                        'U' => 28, 'V' => 18, 'W' => 18, 'X' => 28, 'Y' => 16,
                        'Z' => 16, 'AA' => 42, 'AB' => 24, 'AC' => 16, 'AD' => 16,
                    ];
                } else {
                    $sheet->getStyle("A2:D{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("X2:Y{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("AB2:AC{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 16, 'B' => 14, 'C' => 18, 'D' => 18,
                        'E' => 32, 'F' => 22, 'G' => 36, 'H' => 28, 'I' => 36,
                        'J' => 44, 'K' => 24, 'L' => 18, 'M' => 55, 'N' => 42,
                        'O' => 36, 'P' => 55, 'Q' => 24, 'R' => 55, 'S' => 28,
                        'T' => 28, 'U' => 18, 'V' => 18, 'W' => 28, 'X' => 16,
                        'Y' => 16, 'Z' => 42, 'AA' => 24, 'AB' => 16, 'AC' => 16,
                    ];
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(42);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function labels($values, array $options): string
    {
        if (! is_array($values)) {
            return '';
        }

        return collect($values)
            ->map(fn ($value) => $options[$value] ?? $value)
            ->filter()
            ->implode(', ');
    }

    private function workers(WorkPermit $permit): string
    {
        return collect(range(1, 9))
            ->map(fn ($i) => $permit->{'worker_' . $i})
            ->filter()
            ->values()
            ->implode(', ');
    }

    private function yesNo($value): string
    {
        return match ($value) {
            true => 'DA',
            false => 'NE',
            default => '-',
        };
    }
}
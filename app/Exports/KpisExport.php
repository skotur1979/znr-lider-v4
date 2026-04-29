<?php

namespace App\Exports;

use App\Filament\Resources\Kpis\KpiResource;
use App\Models\Kpi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class KpisExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $kpis;

    protected bool $showUserColumn = false;

    public function __construct()
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->kpis = KpiResource::getEloquentQuery()
            ->with('user')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function collection()
    {
        return $this->kpis;
    }

    public function headings(): array
    {
        $headings = [
            'Naziv KPI-a',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Kategorija',
            'Jedinica',
            'Cilj',
            'Tolerancija upozorenja',
            'Ocjena',
            'Tip izračuna',
            'Automatski izvor / formula',
            'Zadnja vrijednost',
            'Status',
            'Aktivan',
            'Prikaz na dashboardu',
            'Redoslijed',
            'Opis formule / napomena',
            'Opis',
        ]);
    }

    public function map($kpi): array
    {
        /** @var Kpi $kpi */

        $ownerId = KpiResource::resolveOwnerId();
        $latestValue = $kpi->latestValue()?->value;
        $status = $kpi->evaluateStatus($latestValue, $ownerId);

        $row = [
            $kpi->name,
        ];

        if ($this->showUserColumn) {
            $row[] = $kpi->user?->name ?? 'Globalni KPI';
        }

        return array_merge($row, [
            $kpi->category,
            $kpi->unit,
            $kpi->formatNumberOnly($kpi->effectiveTargetValue($ownerId)),
            $kpi->formatNumberOnly($kpi->effectiveWarningOffset($ownerId)),
            $this->directionLabel($kpi->direction),
            $this->calculationTypeLabel($kpi->calculation_type),
            $this->sourceKeyLabel($kpi->source_key),
            $kpi->formatNumberOnly($latestValue),
            $this->statusLabel($status),
            $kpi->is_active ? 'Da' : 'Ne',
            $kpi->show_on_dashboard ? 'Da' : 'Ne',
            $kpi->sort_order,
            $kpi->formula_text,
            $kpi->description,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->kpis->count() + 1;
                $lastCol = $this->showUserColumn ? 'P' : 'O';

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
                    $sheet->getStyle("C2:H{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("J2:N{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 34,
                        'B' => 22,
                        'C' => 16,
                        'D' => 14,
                        'E' => 14,
                        'F' => 18,
                        'G' => 20,
                        'H' => 18,
                        'I' => 34,
                        'J' => 18,
                        'K' => 18,
                        'L' => 12,
                        'M' => 18,
                        'N' => 12,
                        'O' => 45,
                        'P' => 45,
                    ];
                } else {
                    $sheet->getStyle("B2:G{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $sheet->getStyle("I2:M{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 34,
                        'B' => 16,
                        'C' => 14,
                        'D' => 14,
                        'E' => 18,
                        'F' => 20,
                        'G' => 18,
                        'H' => 34,
                        'I' => 18,
                        'J' => 18,
                        'K' => 12,
                        'L' => 18,
                        'M' => 12,
                        'N' => 45,
                        'O' => 45,
                    ];
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(36);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    private function directionLabel(?string $value): string
    {
        return match ($value) {
            'lower_better' => 'Manje je bolje',
            'higher_better' => 'Više je bolje',
            'target_value' => 'Ciljana vrijednost',
            default => $value ?? '',
        };
    }

    private function calculationTypeLabel(?string $value): string
    {
        return match ($value) {
            'manual' => 'Ručno',
            'automatic' => 'Automatski',
            'formula' => 'Formula',
            default => $value ?? '',
        };
    }

    private function statusLabel(?string $value): string
    {
        return match ($value) {
            'success' => 'U cilju',
            'warning' => 'Upozorenje',
            'danger' => 'Izvan cilja',
            default => 'Bez cilja',
        };
    }

    private function sourceKeyLabel(?string $value): string
    {
        return match ($value) {
            'days_without_lta' => 'Broj dana bez LTA',
            'lta_count' => 'Broj ozljeda LTA',
            'lta_lost_days' => 'Dani izgubljeni zbog LTA',
            'near_miss_count' => 'Near Miss',
            'negative_observation_count' => 'Negativna zapažanja',
            'inspection_count' => 'Interni nadzori',
            'corrective_actions_open' => 'Otvorene korektivne radnje',
            'corrective_actions_closed' => 'Zatvorene korektivne radnje',
            'corrective_actions_in_progress' => 'Korektivne radnje u tijeku',
            'corrective_actions_delay_days' => 'Dani kašnjenja korektivnih radnji',
            'non_hazardous_waste_kg' => 'Neopasni otpad',
            'hazardous_waste_kg' => 'Opasni otpad',
            'municipal_waste_kg' => 'Miješani komunalni otpad',
            'afr' => 'AFR formula',
            'asr' => 'ASR formula',
            default => $value ?? '',
        };
    }
}
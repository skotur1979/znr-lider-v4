<?php

namespace App\Exports;

use App\Models\OntoEntry;
use App\Models\OntoRecord;
use App\Models\WasteOrganizationLocation;
use App\Models\WasteType;
use App\Support\WasteCodeFormatter;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WasteMonthlyReportExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{
    public function __construct(
        protected int $year,
        protected ?int $locationId = null,
    ) {}

    public function headings(): array
    {
        return [
            'R.br.',
            'K.B.',
            'Naziv',
            'Sij',
            'Velj',
            'Ožu',
            'Tra',
            'Svi',
            'Lip',
            'Srp',
            'Kol',
            'Ruj',
            'Lis',
            'Stu',
            'Pro',
            (string) $this->year,
        ];
    }

    public function array(): array
    {
        $rows = $this->rows();
        $data = [];

        $totals = array_fill(1, 12, 0.0);
        $grandTotal = 0.0;

        foreach ($rows as $index => $row) {
            foreach ($row['months'] as $monthNo => $value) {
                $totals[$monthNo] += (float) $value;
            }

            $grandTotal += (float) $row['total'];

            $data[] = [
                $index + 1,
                WasteCodeFormatter::plain($row['waste_code']),
                $row['name'] . ($row['is_hazardous'] ? ' (Opasan)' : ''),
                ...array_map(fn ($v) => (float) $v, $row['months']),
                (float) $row['total'],
            ];
        }

        $data[] = [
            '',
            '',
            'Ukupno po mjesecima',
            ...array_map(fn ($v) => (float) $v, $totals),
            (float) $grandTotal,
        ];

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:P{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name' => 'DejaVu Sans',
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '1F2937'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A2:P{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:A{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("D2:P{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("D2:P{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $sheet->getStyle("A{$lastRow}:P{$lastRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '374151'],
                    ],
                ]);

                $widths = [
                    'A' => 8,
                    'B' => 16,
                    'C' => 38,
                    'D' => 12,
                    'E' => 12,
                    'F' => 12,
                    'G' => 12,
                    'H' => 12,
                    'I' => 12,
                    'J' => 12,
                    'K' => 12,
                    'L' => 12,
                    'M' => 12,
                    'N' => 12,
                    'O' => 12,
                    'P' => 14,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(28);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(30);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:P{$lastRow}");
            },
        ];
    }

    private function rows(): array
    {
        $baseWasteTypeIds = OntoRecord::query()
            ->when(
                ! Auth::user()?->isAdmin(),
                fn ($query) => $query->where('user_id', Auth::id())
            )
            ->where('year', $this->year)
            ->when(
                filled($this->locationId),
                fn ($query) => $query->where('waste_organization_location_id', $this->locationId)
            )
            ->distinct()
            ->pluck('waste_type_id')
            ->filter()
            ->values()
            ->all();

        if (empty($baseWasteTypeIds)) {
            return [];
        }

        $wasteTypes = WasteType::query()
            ->whereIn('id', $baseWasteTypeIds)
            ->get()
            ->keyBy('id');

        $outputSums = OntoEntry::query()
            ->selectRaw('onto_records.waste_type_id as waste_type_id, MONTH(onto_entries.entry_date) as month_no, SUM(onto_entries.output_kg) as total_kg')
            ->join('onto_records', 'onto_records.id', '=', 'onto_entries.onto_record_id')
            ->where('onto_entries.entry_type', 'output')
            ->whereYear('onto_entries.entry_date', $this->year)
            ->when(
                ! Auth::user()?->isAdmin(),
                fn ($query) => $query->where('onto_records.user_id', Auth::id())
            )
            ->when(
                filled($this->locationId),
                fn ($query) => $query->where('onto_records.waste_organization_location_id', $this->locationId)
            )
            ->groupByRaw('onto_records.waste_type_id, MONTH(onto_entries.entry_date)')
            ->get();

        $matrix = [];

        foreach ($baseWasteTypeIds as $wasteTypeId) {
            $matrix[$wasteTypeId] = array_fill(1, 12, 0.0);
        }

        foreach ($outputSums as $sum) {
            $matrix[(int) $sum->waste_type_id][(int) $sum->month_no] = (float) $sum->total_kg;
        }

        $rows = [];

        foreach ($baseWasteTypeIds as $wasteTypeId) {
            $wasteType = $wasteTypes->get($wasteTypeId);

            if (! $wasteType) {
                continue;
            }

            $months = $matrix[$wasteTypeId] ?? array_fill(1, 12, 0.0);

            $rows[] = [
                'waste_code' => $wasteType->waste_code,
                'name' => $wasteType->name,
                'is_hazardous' => (bool) $wasteType->is_hazardous,
                'months' => $months,
                'total' => array_sum($months),
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $codeA = preg_replace('/\D+/', '', (string) ($a['waste_code'] ?? ''));
            $codeB = preg_replace('/\D+/', '', (string) ($b['waste_code'] ?? ''));

            return strcmp(
                str_pad($codeA, 10, '0', STR_PAD_RIGHT),
                str_pad($codeB, 10, '0', STR_PAD_RIGHT)
            );
        });

        return $rows;
    }
}
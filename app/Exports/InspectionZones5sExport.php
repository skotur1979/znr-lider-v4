<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InspectionZones5sExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents
{
    protected Collection $rows;

    protected Collection $zones;

    public function __construct(
        protected $inspection
    ) {
        $this->inspection->loadMissing([
            'zones.questions',
            'zones.answers.question',
        ]);

        $this->zones = $this->inspection
            ->zones
            ->sortBy('sort_order')
            ->values();

        $this->rows = collect();

        foreach ($this->zones as $zone) {
            $answers = $zone->answers
                ->sortBy(
                    fn ($answer) =>
                        $answer->question?->sort_order
                        ?? $answer->question?->id
                        ?? $answer->id
                )
                ->values();

            foreach ($answers as $index => $answer) {
                $this->rows->push([
                    'zone' => $zone,
                    'answer' => $answer,
                    'number' => $index + 1,
                ]);
            }
        }
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Zona',
            'Rezultat zone',
            'Br.',
            'Skupina',
            'Pitanje',
            'Ocjena',
        ];
    }

    public function map($row): array
    {
        $zone = $row['zone'];
        $answer = $row['answer'];
        $question = $answer->question;

        return [
            $zone->name,

            number_format(
                (float) ($zone->percentage ?? 0),
                0
            ) . '%',

            $row['number'],

            $question->group
                ?? $question->category
                ?? $question->section
                ?? '',

            $question->question
                ?? $question->text
                ?? $question->title
                ?? $question->name
                ?? '',

            (int) ($answer->score ?? 0),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (
                AfterSheet $event
            ): void {
                $sheet =
                    $event->sheet->getDelegate();

                /*
                 * Ukupni rezultat računamo iz
                 * stvarnih bodova svih zona.
                 */
                $totalPoints = (float) $this->zones
                    ->sum('total_points');

                $maxPoints = (float) $this->zones
                    ->sum('max_points');

                $overallPercentage =
                    $maxPoints > 0
                        ? ($totalPoints / $maxPoints) * 100
                        : 0;

                /*
                 * Dodajemo zaglavlje izvještaja
                 * iznad tablice.
                 */
                $sheet->insertNewRowBefore(
                    1,
                    6
                );

                $inspectionNumber =
                    $this->inspection->number
                    ?? $this->inspection->id;

                $sheet->setCellValue(
                    'A1',
                    '5S izvještaj nadzora - '
                    . $inspectionNumber
                );

                $sheet->setCellValue(
                    'A2',
                    'Naziv nadzora: '
                    . (
                        $this->inspection->title
                        ?? '-'
                    )
                );

                $sheet->setCellValue(
                    'A3',
                    'Datum nadzora: '
                    . (
                        $this->inspection->performed_at
                            ? $this->inspection
                                ->performed_at
                                ->format('d.m.Y.')
                            : '-'
                    )
                    . ' | Lokacija: '
                    . (
                        $this->inspection->location
                        ?? '-'
                    )
                );

                $sheet->setCellValue(
                    'A4',
                    'Ukupno bodova: '
                    . number_format(
                        $totalPoints,
                        0
                    )
                    . ' | Maksimalno bodova: '
                    . number_format(
                        $maxPoints,
                        0
                    )
                    . ' | Ukupni rezultat: '
                    . number_format(
                        $overallPercentage,
                        0
                    )
                    . '%'
                );

                $sheet->setCellValue(
                    'A5',
                    'Broj 5S zona: '
                    . $this->zones->count()
                );

                $sheet->setCellValue(
                    'A6',
                    'Datum izvoza: '
                    . now()->format(
                        'd.m.Y. H:i'
                    )
                );

                /*
                 * Nakon umetanja 6 redaka,
                 * header originalne tablice je red 7.
                 */
                $headerRow = 7;
                $dataStartRow = 8;
                $dataLastRow =
                    $this->rows->count() + 7;

                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');
                $sheet->mergeCells('A3:F3');
                $sheet->mergeCells('A4:F4');
                $sheet->mergeCells('A5:F5');
                $sheet->mergeCells('A6:F6');

                $sheet
                    ->getStyle(
                        "A1:F{$dataLastRow}"
                    )
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet
                    ->getStyle('A1')
                    ->getFont()
                    ->setBold(true)
                    ->setSize(15);

                $sheet
                    ->getStyle('A4')
                    ->getFont()
                    ->setBold(true);

                /*
                 * Header tablice.
                 */
                $sheet
                    ->getStyle(
                        "A{$headerRow}:F{$headerRow}"
                    )
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => [
                                'rgb' => 'FFFFFF',
                            ],
                            'name' =>
                                'DejaVu Sans',
                            'size' => 10,
                        ],

                        'fill' => [
                            'fillType' =>
                                Fill::FILL_SOLID,

                            'startColor' => [
                                'rgb' =>
                                    '1F2937',
                            ],
                        ],

                        'alignment' => [
                            'horizontal' =>
                                Alignment::HORIZONTAL_CENTER,

                            'vertical' =>
                                Alignment::VERTICAL_CENTER,

                            'wrapText' => true,
                        ],
                    ]);

                if ($this->rows->isNotEmpty()) {
                    $sheet
                        ->getStyle(
                            "A{$dataStartRow}:F{$dataLastRow}"
                        )
                        ->getAlignment()
                        ->setVertical(
                            Alignment::VERTICAL_CENTER
                        )
                        ->setWrapText(true);

                    $sheet
                        ->getStyle(
                            "A{$dataStartRow}:D{$dataLastRow}"
                        )
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    $sheet
                        ->getStyle(
                            "F{$dataStartRow}:F{$dataLastRow}"
                        )
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );
                }

                /*
                 * Širine stupaca.
                 */
                $widths = [
                    'A' => 24,
                    'B' => 16,
                    'C' => 8,
                    'D' => 24,
                    'E' => 80,
                    'F' => 12,
                ];

                foreach (
                    $widths
                    as $column => $width
                ) {
                    $sheet
                        ->getColumnDimension(
                            $column
                        )
                        ->setWidth(
                            $width
                        );
                }

                $sheet
                    ->getRowDimension(
                        $headerRow
                    )
                    ->setRowHeight(30);

                /*
                 * Oboji rezultat zone i ocjenu
                 * svakog pitanja.
                 */
                foreach (
                    $this->rows
                    as $index => $row
                ) {
                    $excelRow =
                        $index + $dataStartRow;

                    $sheet
                        ->getRowDimension(
                            $excelRow
                        )
                        ->setRowHeight(42);

                    $zone = $row['zone'];

                    $zonePercentage =
                        (float) (
                            $zone->percentage
                            ?? 0
                        );

                    /*
                     * Rezultat zone - stupac B.
                     */
                    if (
                        $zonePercentage < 40
                    ) {
                        $this->fillCell(
                            $sheet,
                            "B{$excelRow}",
                            'FF991B1B',
                            true
                        );
                    } elseif (
                        $zonePercentage < 60
                    ) {
                        $this->fillCell(
                            $sheet,
                            "B{$excelRow}",
                            'FFF59E0B',
                            false
                        );
                    } elseif (
                        $zonePercentage < 80
                    ) {
                        $this->fillCell(
                            $sheet,
                            "B{$excelRow}",
                            'FFFDE047',
                            false
                        );
                    } else {
                        $this->fillCell(
                            $sheet,
                            "B{$excelRow}",
                            'FF16A34A',
                            true
                        );
                    }

                    /*
                     * Ocjena pitanja - stupac F.
                     */
                    $score =
                        (int) (
                            $row['answer']
                                ->score
                            ?? 0
                        );

                    if ($score <= 2) {
                        $this->fillCell(
                            $sheet,
                            "F{$excelRow}",
                            'FFFF0000',
                            true
                        );
                    } elseif ($score === 3) {
                        $this->fillCell(
                            $sheet,
                            "F{$excelRow}",
                            'FFFFFF00',
                            false
                        );
                    } else {
                        $this->fillCell(
                            $sheet,
                            "F{$excelRow}",
                            'FF00B050',
                            true
                        );
                    }
                }

                $sheet->freezePane(
                    "A{$dataStartRow}"
                );

                if ($dataLastRow >= $headerRow) {
                    $sheet->setAutoFilter(
                        "A{$headerRow}:F{$dataLastRow}"
                    );
                }
            },
        ];
    }

    private function fillCell(
        $sheet,
        string $cell,
        string $argb,
        bool $whiteText = false
    ): void {
        $sheet
            ->getStyle($cell)
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            );

        $sheet
            ->getStyle($cell)
            ->getFill()
            ->getStartColor()
            ->setARGB($argb);

        $sheet
            ->getStyle($cell)
            ->getFont()
            ->setBold(true);

        if ($whiteText) {
            $sheet
                ->getStyle($cell)
                ->getFont()
                ->getColor()
                ->setRGB('FFFFFF');
        }
    }
}
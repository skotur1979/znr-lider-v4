<?php

namespace App\Exports;

use App\Filament\Resources\Observations\ObservationResource;
use App\Models\Observation;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ObservationsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    ShouldAutoSize,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    protected $observations;

    protected bool $showUserColumn =
        false;

    private int $imgHeight =
        70;

    private int $headingRow =
        6;

    private int $firstDataRow =
        7;

    public function __construct(
        ?array $observationIds = null
    ) {
        $user =
            auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $query =
            ObservationResource
                ::getEloquentQuery()
                ->with('user')
                ->orderByDesc(
                    'incident_date'
                );

        if (
            $observationIds
            !== null
        ) {
            $query->whereIn(
                'observations.id',
                $observationIds
            );
        }

        $this->observations =
            $query->get();
    }

    public function startCell(): string
    {
        return 'A'
            . $this->headingRow;
    }

    public function collection()
    {
        return $this->observations;
    }

    public function headings(): array
    {
        $headings = [
            'Datum zapažanja',
        ];

        if (
            $this->showUserColumn
        ) {
            $headings[] =
                'Korisnik';
        }

        return array_merge(
            $headings,
            [
                'Vrsta zapažanja',
                'Izvor prijave',
                'Prioritet',
                'Lokacija',
                'Opis',
                'Vrsta opasnosti',
                'Potrebna radnja',
                'Odgovorna osoba',
                'Rok za provedbu',
                'Datum zatvaranja',
                'Broj dana do zatvaranja',
                'Status',

                /*
                 * NOVO.
                 */
                'Kontakt / ime prijavitelja',

                /*
                 * Ovo ostaje jer predstavlja
                 * primatelje sustavne obavijesti.
                 */
                'E-mail primatelji',

                'Poslano',
                'Komentar',
                'Slika',
            ]
        );
    }

    public function map(
        $observation
    ): array {
        /** @var Observation $observation */

        $incidentDate =
            filled(
                $observation
                    ->incident_date
            )
                ? Carbon::parse(
                    $observation
                        ->incident_date
                )
                : null;

        $targetDate =
            filled(
                $observation
                    ->target_date
            )
                ? Carbon::parse(
                    $observation
                        ->target_date
                )
                : null;

        $completedAt =
            filled(
                $observation
                    ->completed_at
            )
                ? Carbon::parse(
                    $observation
                        ->completed_at
                )
                : null;

        $sentAt =
            filled(
                $observation
                    ->sent_at
            )
                ? Carbon::parse(
                    $observation
                        ->sent_at
                )
                : null;

        $closingDays =
            null;

        if (
            $incidentDate
            && $completedAt
        ) {
            $closingDays =
                $incidentDate
                    ->copy()
                    ->startOfDay()
                    ->diffInDays(
                        $completedAt
                            ->copy()
                            ->startOfDay()
                    );
        }

        $row = [
            $incidentDate
                ? ExcelDate
                    ::dateTimeToExcel(
                        $incidentDate
                    )
                : null,
        ];

        if (
            $this->showUserColumn
        ) {
            $row[] =
                $observation
                    ->user
                    ?->name
                ?? '';
        }

        return array_merge(
            $row,
            [
                $this
                    ->observationTypeLabel(
                        $observation
                            ->observation_type
                    ),

                $this
                    ->sourceLabel(
                        $observation
                            ->source
                    ),

                $this
                    ->priorityLabel(
                        $observation
                            ->priority
                    ),

                $observation
                    ->location,

                $observation
                    ->item,

                $observation
                    ->potential_incident_type,

                $observation
                    ->action,

                $observation
                    ->responsible,

                $targetDate
                    ? ExcelDate
                        ::dateTimeToExcel(
                            $targetDate
                        )
                    : null,

                $completedAt
                    ? ExcelDate
                        ::dateTimeToExcel(
                            $completedAt
                        )
                    : null,

                $closingDays,

                $this
                    ->statusLabel(
                        $observation
                            ->status
                    ),

                $observation
                    ->reporter_contact
                ?? '',

                $this->emails(
                    $observation
                        ->notification_emails
                    ?? null
                ),

                $sentAt
                    ? ExcelDate
                        ::dateTimeToExcel(
                            $sentAt
                        )
                    : null,

                $observation
                    ->comments,

                /*
                 * Slika dolazi kroz drawings().
                 */
                null,
            ]
        );
    }

    public function columnFormats(): array
    {
        if (
            $this->showUserColumn
        ) {
            /*
             * A datum zapažanja
             * K rok
             * L datum zatvaranja
             * Q poslano
             */
            return [
                'A' =>
                    'dd.mm.yyyy',

                'K' =>
                    'dd.mm.yyyy',

                'L' =>
                    'dd.mm.yyyy',

                'Q' =>
                    'dd.mm.yyyy hh:mm',
            ];
        }

        /*
         * Bez korisnika:
         * A datum
         * J rok
         * K zatvaranje
         * P poslano
         */
        return [
            'A' =>
                'dd.mm.yyyy',

            'J' =>
                'dd.mm.yyyy',

            'K' =>
                'dd.mm.yyyy',

            'P' =>
                'dd.mm.yyyy hh:mm',
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        $imageColumn =
            $this->showUserColumn
                ? 'S'
                : 'R';

        foreach (
            $this->observations
            as $index =>
                $observation
        ) {
            if (
                blank(
                    $observation
                        ->picture_path
                )
            ) {
                continue;
            }

            $fullPath =
                storage_path(
                    'app/public/'
                    . ltrim(
                        (string)
                        $observation
                            ->picture_path,
                        '/'
                    )
                );

            if (
                ! file_exists(
                    $fullPath
                )
            ) {
                continue;
            }

            $row =
                $index
                + $this
                    ->firstDataRow;

            $drawing =
                new Drawing();

            $drawing->setName(
                'slika_'
                . $row
            );

            $drawing->setDescription(
                'Slika zapažanja'
            );

            $drawing->setPath(
                $fullPath
            );

            $drawing->setHeight(
                $this->imgHeight
            );

            $drawing->setCoordinates(
                $imageColumn
                . $row
            );

            $drawing->setOffsetX(
                5
            );

            $drawing->setOffsetY(
                5
            );

            $drawing
                ->setResizeProportional(
                    true
                );

            $drawings[] =
                $drawing;
        }

        return $drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class =>
                function (
                    AfterSheet $event
                ): void {
                    $sheet =
                        $event
                            ->sheet
                            ->getDelegate();

                    $lastColumn =
                        $this->showUserColumn
                            ? 'S'
                            : 'R';

                    $lastDataRow =
                        max(
                            $this->headingRow,
                            $this
                                ->observations
                                ->count()
                                + $this
                                    ->headingRow
                        );

                    $user =
                        auth()->user();

                    /*
                     * Naslov.
                     */
                    $sheet->mergeCells(
                        "A1:{$lastColumn}1"
                    );

                    $sheet->setCellValue(
                        'A1',
                        'ZNR LIDER – POPIS ZAPAŽANJA'
                    );

                    $sheet
                        ->getStyle('A1')
                        ->applyFromArray([
                            'font' => [
                                'bold' =>
                                    true,

                                'size' =>
                                    16,

                                'name' =>
                                    'DejaVu Sans',

                                'color' => [
                                    'rgb' =>
                                        'FFFFFF',
                                ],
                            ],

                            'fill' => [
                                'fillType' =>
                                    Fill::FILL_SOLID,

                                'startColor' => [
                                    'rgb' =>
                                        '111827',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,

                                'vertical' =>
                                    Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                    $sheet
                        ->getRowDimension(1)
                        ->setRowHeight(
                            28
                        );

                    /*
                     * Podatci izvoza.
                     */
                    $sheet->mergeCells(
                        "A2:{$lastColumn}2"
                    );

                    $sheet->setCellValue(
                        'A2',
                        'Izvoz izradio: '
                        . (
                            $user?->name
                            ?? '-'
                        )
                        . ' | Datum izvoza: '
                        . now()->format(
                            'd.m.Y. H:i'
                        )
                    );

                    $sheet
                        ->getStyle('A2')
                        ->applyFromArray([
                            'font' => [
                                'name' =>
                                    'DejaVu Sans',

                                'size' =>
                                    10,

                                'italic' =>
                                    true,

                                'color' => [
                                    'rgb' =>
                                        '374151',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,
                            ],
                        ]);

                    /*
                     * Sažetak.
                     */
                    $total =
                        $this
                            ->observations
                            ->count();

                    $nearMiss =
                        $this
                            ->observations
                            ->where(
                                'observation_type',
                                'Near Miss'
                            )
                            ->count();

                    $negative =
                        $this
                            ->observations
                            ->where(
                                'observation_type',
                                'Negative Observation'
                            )
                            ->count();

                    $positive =
                        $this
                            ->observations
                            ->where(
                                'observation_type',
                                'Positive Observation'
                            )
                            ->count();

                    $qrPublic =
                        $this
                            ->observations
                            ->where(
                                'source',
                                'qr_public'
                            )
                            ->count();

                    $internal =
                        $total
                        - $qrPublic;

                    $notStarted =
                        $this
                            ->observations
                            ->where(
                                'status',
                                'Not started'
                            )
                            ->count();

                    $inProgress =
                        $this
                            ->observations
                            ->where(
                                'status',
                                'In progress'
                            )
                            ->count();

                    $completed =
                        $this
                            ->observations
                            ->where(
                                'status',
                                'Complete'
                            )
                            ->count();

                    $sheet->mergeCells(
                        "A3:{$lastColumn}3"
                    );

                    $sheet->setCellValue(
                        'A3',
                        'Ukupno: '
                        . $total
                        . ' | Near Miss: '
                        . $nearMiss
                        . ' | Negativna: '
                        . $negative
                        . ' | Pozitivna: '
                        . $positive
                        . ' | QR prijave: '
                        . $qrPublic
                        . ' | Interno: '
                        . $internal
                        . ' | Nije započeto: '
                        . $notStarted
                        . ' | U tijeku: '
                        . $inProgress
                        . ' | Završeno: '
                        . $completed
                    );

                    $sheet
                        ->getStyle('A3')
                        ->applyFromArray([
                            'font' => [
                                'name' =>
                                    'DejaVu Sans',

                                'size' =>
                                    10,

                                'bold' =>
                                    true,

                                'color' => [
                                    'rgb' =>
                                        '111827',
                                ],
                            ],

                            'fill' => [
                                'fillType' =>
                                    Fill::FILL_SOLID,

                                'startColor' => [
                                    'rgb' =>
                                        'F3F4F6',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,

                                'vertical' =>
                                    Alignment::VERTICAL_CENTER,

                                'wrapText' =>
                                    true,
                            ],
                        ]);

                    $sheet
                        ->getRowDimension(3)
                        ->setRowHeight(
                            30
                        );

                    /*
                     * Legenda.
                     */
                    $sheet->mergeCells(
                        "A4:{$lastColumn}4"
                    );

                    $sheet->setCellValue(
                        'A4',
                        'Legenda roka: CRVENO = rok je istekao | ŽUTO = rok istječe u sljedećih 30 dana'
                    );

                    $sheet
                        ->getStyle('A4')
                        ->applyFromArray([
                            'font' => [
                                'name' =>
                                    'DejaVu Sans',

                                'size' =>
                                    9,

                                'bold' =>
                                    true,

                                'color' => [
                                    'rgb' =>
                                        '4B5563',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,
                            ],
                        ]);

                    $sheet
                        ->getStyle(
                            "A1:{$lastColumn}{$lastDataRow}"
                        )
                        ->getFont()
                        ->setName(
                            'DejaVu Sans'
                        )
                        ->setSize(
                            10
                        );

                    /*
                     * Zaglavlje.
                     */
                    $sheet
                        ->getStyle(
                            "A{$this->headingRow}:{$lastColumn}{$this->headingRow}"
                        )
                        ->applyFromArray([
                            'font' => [
                                'bold' =>
                                    true,

                                'color' => [
                                    'rgb' =>
                                        'FFFFFF',
                                ],

                                'name' =>
                                    'DejaVu Sans',

                                'size' =>
                                    10,
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

                                'wrapText' =>
                                    true,
                            ],

                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' =>
                                        Border::BORDER_THIN,

                                    'color' => [
                                        'rgb' =>
                                            '9CA3AF',
                                    ],
                                ],
                            ],
                        ]);

                    $sheet
                        ->getRowDimension(
                            $this->headingRow
                        )
                        ->setRowHeight(
                            32
                        );

                    if (
                        $this
                            ->observations
                            ->isNotEmpty()
                    ) {
                        $sheet
                            ->getStyle(
                                "A{$this->firstDataRow}:{$lastColumn}{$lastDataRow}"
                            )
                            ->applyFromArray([
                                'alignment' => [
                                    'vertical' =>
                                        Alignment::VERTICAL_CENTER,

                                    'horizontal' =>
                                        Alignment::HORIZONTAL_LEFT,

                                    'wrapText' =>
                                        true,
                                ],

                                'borders' => [
                                    'allBorders' => [
                                        'borderStyle' =>
                                            Border::BORDER_THIN,

                                        'color' => [
                                            'rgb' =>
                                                'D1D5DB',
                                        ],
                                    ],
                                ],
                            ]);
                    }

                    /*
                     * Stupci.
                     */
                    if (
                        $this->showUserColumn
                    ) {
                        $widths = [
                            'A' => 15,
                            'B' => 24,
                            'C' => 24,
                            'D' => 17,
                            'E' => 14,
                            'F' => 22,
                            'G' => 42,
                            'H' => 32,
                            'I' => 42,
                            'J' => 23,
                            'K' => 17,
                            'L' => 18,
                            'M' => 19,
                            'N' => 18,
                            'O' => 28,
                            'P' => 34,
                            'Q' => 19,
                            'R' => 35,
                            'S' => 15,
                        ];

                        $sourceColumn =
                            'D';

                        $priorityColumn =
                            'E';

                        $targetDateColumn =
                            'K';

                        $completedDateColumn =
                            'L';

                        $closingDaysColumn =
                            'M';

                        $statusColumn =
                            'N';
                    } else {
                        $widths = [
                            'A' => 15,
                            'B' => 24,
                            'C' => 17,
                            'D' => 14,
                            'E' => 22,
                            'F' => 42,
                            'G' => 32,
                            'H' => 42,
                            'I' => 23,
                            'J' => 17,
                            'K' => 18,
                            'L' => 19,
                            'M' => 18,
                            'N' => 28,
                            'O' => 34,
                            'P' => 19,
                            'Q' => 35,
                            'R' => 15,
                        ];

                        $sourceColumn =
                            'C';

                        $priorityColumn =
                            'D';

                        $targetDateColumn =
                            'J';

                        $completedDateColumn =
                            'K';

                        $closingDaysColumn =
                            'L';

                        $statusColumn =
                            'M';
                    }

                    foreach (
                        $widths
                        as $column =>
                            $width
                    ) {
                        $sheet
                            ->getColumnDimension(
                                $column
                            )
                            ->setWidth(
                                $width
                            );
                    }

                    if (
                        $this
                            ->observations
                            ->isNotEmpty()
                    ) {
                        $centerColumns = [
                            'A',
                            $sourceColumn,
                            $priorityColumn,
                            $targetDateColumn,
                            $completedDateColumn,
                            $closingDaysColumn,
                            $statusColumn,
                        ];

                        foreach (
                            $centerColumns
                            as $column
                        ) {
                            $sheet
                                ->getStyle(
                                    "{$column}{$this->firstDataRow}:{$column}{$lastDataRow}"
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );
                        }
                    }

                    foreach (
                        $this->observations
                        as $index =>
                            $observation
                    ) {
                        $row =
                            $index
                            + $this
                                ->firstDataRow;

                        $sheet
                            ->getRowDimension(
                                $row
                            )
                            ->setRowHeight(
                                filled(
                                    $observation
                                        ->picture_path
                                )
                                    ? $this
                                        ->imgHeight
                                        * 0.75
                                    : 38
                            );
                    }

                    $today =
                        Carbon::today();

                    foreach (
                        $this->observations
                        as $index =>
                            $observation
                    ) {
                        $row =
                            $index
                            + $this
                                ->firstDataRow;

                        $targetDate =
                            filled(
                                $observation
                                    ->target_date
                            )
                                ? Carbon::parse(
                                    $observation
                                        ->target_date
                                )->startOfDay()
                                : null;

                        if (
                            $targetDate
                            && $observation
                                ->status
                                !== 'Complete'
                        ) {
                            if (
                                $targetDate
                                    ->lt(
                                        $today
                                    )
                            ) {
                                $this
                                    ->styleCell(
                                        $sheet,
                                        "{$targetDateColumn}{$row}",
                                        'DC2626',
                                        'FFFFFF'
                                    );
                            } elseif (
                                $targetDate
                                    ->lte(
                                        $today
                                            ->copy()
                                            ->addDays(
                                                30
                                            )
                                    )
                            ) {
                                $this
                                    ->styleCell(
                                        $sheet,
                                        "{$targetDateColumn}{$row}",
                                        'FDE047',
                                        '111827'
                                    );
                            }
                        }

                        [$priorityBackground, $priorityText] =
                            $this
                                ->priorityColors(
                                    $observation
                                        ->priority
                                );

                        $this->styleCell(
                            $sheet,
                            "{$priorityColumn}{$row}",
                            $priorityBackground,
                            $priorityText
                        );

                        [$statusBackground, $statusText] =
                            $this
                                ->statusColors(
                                    $observation
                                        ->status
                                );

                        $this->styleCell(
                            $sheet,
                            "{$statusColumn}{$row}",
                            $statusBackground,
                            $statusText
                        );

                        /*
                         * Vizualno označi QR izvor.
                         */
                        if (
                            $observation
                                ->source
                                === 'qr_public'
                        ) {
                            $this->styleCell(
                                $sheet,
                                "{$sourceColumn}{$row}",
                                'DCFCE7',
                                '166534'
                            );
                        }

                        if (
                            $observation
                                ->status
                                === 'Complete'
                            && filled(
                                $observation
                                    ->completed_at
                            )
                        ) {
                            $this->styleCell(
                                $sheet,
                                "{$completedDateColumn}{$row}",
                                'DCFCE7',
                                '166534'
                            );

                            $this->styleCell(
                                $sheet,
                                "{$closingDaysColumn}{$row}",
                                'DCFCE7',
                                '166534'
                            );
                        }
                    }

                    $sheet->freezePane(
                        'A'
                        . $this
                            ->firstDataRow
                    );

                    $sheet->setAutoFilter(
                        "A{$this->headingRow}:{$lastColumn}{$lastDataRow}"
                    );

                    $sheet
                        ->getPageSetup()
                        ->setOrientation(
                            \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                        );

                    $sheet
                        ->getPageSetup()
                        ->setFitToWidth(
                            1
                        );

                    $sheet
                        ->getPageSetup()
                        ->setFitToHeight(
                            0
                        );

                    $sheet
                        ->getPageMargins()
                        ->setTop(0.4)
                        ->setRight(0.3)
                        ->setBottom(0.4)
                        ->setLeft(0.3);

                    $sheet
                        ->getPageSetup()
                        ->setRowsToRepeatAtTopByStartAndEnd(
                            $this->headingRow,
                            $this->headingRow
                        );
                },
        ];
    }

    private function observationTypeLabel(
        ?string $state
    ): string {
        return match ($state) {
            'Near Miss' =>
                'NM - Skoro nezgoda',

            'Negative Observation' =>
                'Negativno zapažanje',

            'Positive Observation' =>
                'Pozitivno zapažanje',

            default =>
                $state ?? '',
        };
    }

    private function sourceLabel(
        ?string $state
    ): string {
        return $state
            === 'qr_public'
                ? 'QR prijava'
                : 'Interni unos';
    }

    private function priorityLabel(
        ?string $state
    ): string {
        return match ($state) {
            'low' =>
                'Nisko',

            'medium' =>
                'Srednje',

            'high' =>
                'Visoko',

            'critical' =>
                'Kritično',

            default =>
                $state ?? '',
        };
    }

    private function statusLabel(
        ?string $state
    ): string {
        return match ($state) {
            'Not started' =>
                'Nije započeto',

            'In progress' =>
                'U tijeku',

            'Complete' =>
                'Završeno',

            default =>
                $state ?? '',
        };
    }

    private function emails(
        $value
    ): string {
        if (
            is_array($value)
        ) {
            return collect(
                $value
            )
                ->map(
                    fn ($email) =>
                        trim(
                            (string)
                            $email
                        )
                )
                ->filter()
                ->unique()
                ->implode(
                    ', '
                );
        }

        return trim(
            (string)
            (
                $value
                ?? ''
            )
        );
    }

    private function priorityColors(
        ?string $priority
    ): array {
        return match ($priority) {
            'critical' => [
                'DC2626',
                'FFFFFF',
            ],

            'high' => [
                'F97316',
                'FFFFFF',
            ],

            'medium' => [
                'FDE68A',
                '92400E',
            ],

            'low' => [
                'E5E7EB',
                '374151',
            ],

            default => [
                'F3F4F6',
                '374151',
            ],
        };
    }

    private function statusColors(
        ?string $status
    ): array {
        return match ($status) {
            'Not started' => [
                'FEE2E2',
                '991B1B',
            ],

            'In progress' => [
                'FEF3C7',
                '92400E',
            ],

            'Complete' => [
                'DCFCE7',
                '166534',
            ],

            default => [
                'F3F4F6',
                '374151',
            ],
        };
    }

    private function styleCell(
        $sheet,
        string $cell,
        string $backgroundColor,
        string $fontColor
    ): void {
        $sheet
            ->getStyle(
                $cell
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            );

        $sheet
            ->getStyle(
                $cell
            )
            ->getFill()
            ->getStartColor()
            ->setRGB(
                $backgroundColor
            );

        $sheet
            ->getStyle(
                $cell
            )
            ->getFont()
            ->setBold(
                true
            )
            ->getColor()
            ->setRGB(
                $fontColor
            );

        $sheet
            ->getStyle(
                $cell
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );
    }
}
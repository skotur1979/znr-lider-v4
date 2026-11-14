<?php

namespace App\Exports;

use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Models\Chemical;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ChemicalsExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithDrawings
{
    protected $chemicals;

    protected bool $showUserColumn = false;

    public function __construct(
        ?array $chemicalIds = null
    ) {
        $user =
            auth()->user();

        if (! $user) {
            abort(403);
        }

        $this->showUserColumn =
            $user->isSuperAdmin();

        /*
         * ChemicalResource query osigurava
         * isti tenant scope kao Filament tablica.
         */
        $query =
            ChemicalResource::
                getEloquentQuery()
                ->with('user')
                ->orderBy(
                    'product_name'
                );

        if (
            $chemicalIds !== null
        ) {
            /*
             * ID-evi dolaze iz već filtrirane
             * Filament tablice.
             *
             * Time se zadržava i status filter
             * active / trashed / all.
             */
            $query->whereIn(
                'chemicals.id',
                array_map(
                    'intval',
                    $chemicalIds
                )
            );
        } else {
            /*
             * Direktno korištenje exportera
             * bez liste ID-eva standardno izvozi
             * samo aktivne zapise.
             */
            $query->withoutTrashed();
        }

        $this->chemicals =
            $query->get();
    }

    public function collection()
    {
        return $this->chemicals;
    }

    public function headings(): array
    {
        $headings = [
            'Ime proizvoda',
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
                'CAS',
                'UFI',
                'Piktogrami',
                'H oznake',
                'P oznake',
                'Mjesto upotrebe',
                'Količina',
                'GVI / KGVI',
                'VOC',
                'STL – HZJZ',
            ]
        );
    }

    public function map(
        $chemical
    ): array {
        /** @var Chemical $chemical */

        $row = [
            $this->oneLine(
                $chemical
                    ->product_name
            ),
        ];

        if (
            $this->showUserColumn
        ) {
            $row[] =
                $this->oneLine(
                    $chemical
                        ->user
                        ?->name
                    ?? ''
                );
        }

        return array_merge(
            $row,
            [
                $this->oneLine(
                    $chemical
                        ->cas_number
                ),

                $this->oneLine(
                    $chemical
                        ->ufi_number
                ),

                implode(
                    '; ',
                    $this->normalizePictos(
                        $chemical
                            ->hazard_pictograms
                    )
                ),

                implode(
                    ', ',
                    $this->toList(
                        $chemical
                            ->h_statements
                    )
                ),

                implode(
                    ', ',
                    $this->toList(
                        $chemical
                            ->p_statements
                    )
                ),

                $this->oneLine(
                    $chemical
                        ->usage_location
                ),

                $this->oneLine(
                    $chemical
                        ->annual_quantity
                ),

                $this->oneLine(
                    $chemical
                        ->gvi_kgvi
                ),

                $this->oneLine(
                    $chemical
                        ->voc
                ),

                $chemical->stl_hzjz
                    ? $chemical
                        ->stl_hzjz
                        ->format(
                            'd.m.Y.'
                        )
                    : '',
            ]
        );
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

                    $count =
                        $this
                            ->chemicals
                            ->count();

                    $lastRow =
                        $count + 1;

                    $lastCol =
                        $this
                            ->showUserColumn
                                ? 'L'
                                : 'K';

                    /*
                     * Header uvijek postoji.
                     */
                    $sheet
                        ->getStyle(
                            "A1:{$lastCol}1"
                        )
                        ->applyFromArray([
                            'font' => [
                                'bold' => true,
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
                                    'solid',

                                'startColor' => [
                                    'rgb' =>
                                        '1F2937',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::
                                        HORIZONTAL_CENTER,

                                'vertical' =>
                                    Alignment::
                                        VERTICAL_CENTER,

                                'wrapText' =>
                                    true,
                            ],
                        ]);

                    /*
                     * Font cijelog stvarno
                     * popunjenog raspona.
                     */
                    $sheet
                        ->getStyle(
                            "A1:{$lastCol}{$lastRow}"
                        )
                        ->getFont()
                        ->setName(
                            'DejaVu Sans'
                        )
                        ->setSize(
                            10
                        );

                    /*
                     * Data range postoji samo
                     * ako postoji barem jedan zapis.
                     */
                    if ($count > 0) {
                        $sheet
                            ->getStyle(
                                "A2:{$lastCol}{$lastRow}"
                            )
                            ->getAlignment()
                            ->setVertical(
                                Alignment::
                                    VERTICAL_TOP
                            )
                            ->setHorizontal(
                                Alignment::
                                    HORIZONTAL_LEFT
                            )
                            ->setWrapText(
                                true
                            );

                        if (
                            $this
                                ->showUserColumn
                        ) {
                            $sheet
                                ->getStyle(
                                    "I2:L{$lastRow}"
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::
                                        HORIZONTAL_CENTER
                                );
                        } else {
                            $sheet
                                ->getStyle(
                                    "H2:K{$lastRow}"
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::
                                        HORIZONTAL_CENTER
                                );
                        }
                    }

                    if (
                        $this
                            ->showUserColumn
                    ) {
                        $widths = [
                            'A' => 30,
                            'B' => 18,
                            'C' => 32,
                            'D' => 16,
                            'E' => 16,
                            'F' => 28,
                            'G' => 48,
                            'H' => 24,
                            'I' => 14,
                            'J' => 14,
                            'K' => 12,
                            'L' => 16,
                        ];
                    } else {
                        $widths = [
                            'A' => 32,
                            'B' => 34,
                            'C' => 16,
                            'D' => 16,
                            'E' => 28,
                            'F' => 50,
                            'G' => 24,
                            'H' => 14,
                            'I' => 14,
                            'J' => 12,
                            'K' => 16,
                        ];
                    }

                    foreach (
                        $widths as
                        $column => $width
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
                            1
                        )
                        ->setRowHeight(
                            28
                        );

                    foreach (
                        $this->chemicals as
                        $i => $chemical
                    ) {
                        $row =
                            $i + 2;

                        $pictogramCount =
                            count(
                                $this
                                    ->normalizePictos(
                                        $chemical
                                            ->hazard_pictograms
                                    )
                            );

                        $sheet
                            ->getRowDimension(
                                $row
                            )
                            ->setRowHeight(
                                $pictogramCount > 3
                                    ? 38
                                    : 24
                            );
                    }

                    $sheet->freezePane(
                        'A2'
                    );

                    $sheet->setAutoFilter(
                        "A1:{$lastCol}{$lastRow}"
                    );
                },
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        $pictogramColumn =
            $this->showUserColumn
                ? 'E'
                : 'D';

        foreach (
            $this->chemicals as
            $i => $chemical
        ) {
            $row =
                $i + 2;

            $codes =
                $this->normalizePictos(
                    $chemical
                        ->hazard_pictograms
                );

            foreach (
                $codes as
                $idx => $code
            ) {
                $path =
                    $this
                        ->findPictogramPath(
                            $code
                        );

                if (! $path) {
                    continue;
                }

                $drawing =
                    new Drawing();

                $drawing->setName(
                    "picto_{$row}_{$idx}"
                );

                $drawing
                    ->setDescription(
                        $code
                    );

                $drawing
                    ->setPath(
                        $path
                    );

                $drawing
                    ->setHeight(
                        17
                    );

                $drawing
                    ->setCoordinates(
                        $pictogramColumn
                        . $row
                    );

                $drawing
                    ->setOffsetX(
                        4
                        + (
                            ($idx % 3)
                            * 23
                        )
                    );

                $drawing
                    ->setOffsetY(
                        3
                        + (
                            intdiv(
                                $idx,
                                3
                            )
                            * 19
                        )
                    );

                $drawings[] =
                    $drawing;
            }
        }

        return $drawings;
    }

    private function oneLine(
        $value
    ): string {
        $value =
            str_replace(
                [
                    "\r",
                    "\n",
                ],
                ' ',
                (string) (
                    $value
                    ?? ''
                )
            );

        return trim(
            preg_replace(
                '/\s+/',
                ' ',
                $value
            ) ?? ''
        );
    }

    private function normalizePictos(
        $value
    ): array {
        return collect(
            $this->toList(
                $value
            )
        )
            ->map(
                function (
                    $value
                ): ?string {
                    $value =
                        strtoupper(
                            trim(
                                (string) $value
                            )
                        );

                    $value =
                        pathinfo(
                            $value,
                            PATHINFO_FILENAME
                        );

                    $value =
                        str_replace(
                            [
                                ' ',
                                '_',
                                '-',
                            ],
                            '',
                            $value
                        );

                    if (
                        preg_match(
                            '/^GHS0?([1-9])$/',
                            $value,
                            $matches
                        )
                    ) {
                        return
                            'GHS0'
                            . $matches[1];
                    }

                    return null;
                }
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function toList(
        $value
    ): array {
        if (
            $value === null
            || $value === ''
        ) {
            return [];
        }

        if (is_array($value)) {
            return collect(
                $value
            )
                ->map(
                    fn ($item) =>
                        $this->oneLine(
                            $item
                        )
                )
                ->filter()
                ->values()
                ->all();
        }

        return collect(
            preg_split(
                '/[,\n\r;:]+/',
                (string) $value
            ) ?: []
        )
            ->map(
                fn ($item) =>
                    $this->oneLine(
                        $item
                    )
            )
            ->filter()
            ->values()
            ->all();
    }

    private function findPictogramPath(
        string $code
    ): ?string {
        $code =
            strtoupper(
                trim(
                    $code
                )
            );

        $candidates = [
            public_path(
                "images/ghs/{$code}.png"
            ),

            public_path(
                "images/ghs/{$code}.jpg"
            ),

            public_path(
                "images/ghs/{$code}.jpeg"
            ),

            public_path(
                "piktogrami/{$code}.png"
            ),

            public_path(
                "piktogrami/{$code}.jpg"
            ),

            public_path(
                "piktogrami/{$code}.jpeg"
            ),

            public_path(
                "images/ghs/{$code}.gif"
            ),

            public_path(
                "piktogrami/{$code}.gif"
            ),
        ];

        foreach (
            $candidates as $path
        ) {
            if (
                is_file(
                    $path
                )
            ) {
                return $path;
            }
        }

        return null;
    }
}
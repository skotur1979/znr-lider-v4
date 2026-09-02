<?php

namespace App\Exports;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use App\Models\PPEEquipment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PPEEquipmentExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents
{
    protected $items;

    protected bool $showUserColumn = false;

    public function __construct(
        ?array $ids = null
    ) {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        /*
         * BaseResource sada automatski osigurava:
         *
         * organizacijski korisnik:
         * user_id = ownerId()
         *
         * superadmin:
         * može administrativno vidjeti sve zapise.
         */
        $query =
            PPEEquipmentResource::getEloquentQuery()
                ->with('user')
                ->orderBy('name');

        /*
         * Ako su ID-evi poslani iz filtrirane
         * Filament tablice, export poštuje
         * upravo taj rezultat.
         */
        if ($ids !== null) {
            $query->whereIn(
                'ppe_equipments.id',
                $ids
            );
        }

        $this->items =
            $query->get();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        $headings = [
            'Naziv OZO',
        ];

        if ($this->showUserColumn) {
            $headings[] =
                'Korisnik';
        }

        return array_merge(
            $headings,
            [
                'HRN EN / Norma',
                'Rok uporabe (mjeseci)',
                'Aktivno',
                'Broj priloga',
            ]
        );
    }

    public function map(
        $item
    ): array {
        /** @var PPEEquipment $item */

        $row = [
            $item->name,
        ];

        if ($this->showUserColumn) {
            $row[] =
                $item->user?->name
                ?? '';
        }

        return array_merge(
            $row,
            [
                $item->standard,

                $item->duration_months,

                $item->is_active
                    ? 'Da'
                    : 'Ne',

                is_array(
                    $item->attachments
                )
                    ? count(
                        $item->attachments
                    )
                    : 0,
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

                    $lastRow =
                        $this->items->count()
                        + 1;

                    /*
                     * Sa korisnikom:
                     *
                     * A Naziv
                     * B Korisnik
                     * C Norma
                     * D Rok
                     * E Aktivno
                     * F Broj priloga
                     *
                     * Bez korisnika:
                     *
                     * A Naziv
                     * B Norma
                     * C Rok
                     * D Aktivno
                     * E Broj priloga
                     */
                    $lastCol =
                        $this->showUserColumn
                            ? 'F'
                            : 'E';

                    /*
                    |--------------------------------------------------------------------------
                    | FONT
                    |--------------------------------------------------------------------------
                    */

                    $sheet
                        ->getStyle(
                            "A1:{$lastCol}{$lastRow}"
                        )
                        ->getFont()
                        ->setName(
                            'DejaVu Sans'
                        )
                        ->setSize(10);

                    /*
                    |--------------------------------------------------------------------------
                    | ZAGLAVLJE
                    |--------------------------------------------------------------------------
                    */

                    $sheet
                        ->getStyle(
                            "A1:{$lastCol}1"
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
                                    'solid',

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
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | PODACI
                    |--------------------------------------------------------------------------
                    */

                    if ($lastRow >= 2) {
                        $sheet
                            ->getStyle(
                                "A2:{$lastCol}{$lastRow}"
                            )
                            ->getAlignment()
                            ->setVertical(
                                Alignment::VERTICAL_CENTER
                            )
                            ->setWrapText(
                                true
                            );

                        $sheet
                            ->getStyle(
                                "A2:{$lastCol}{$lastRow}"
                            )
                            ->getAlignment()
                            ->setHorizontal(
                                Alignment::HORIZONTAL_LEFT
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CENTRIRANI STUPCI
                    |--------------------------------------------------------------------------
                    */

                    if ($lastRow >= 2) {
                        if (
                            $this->showUserColumn
                        ) {
                            /*
                             * D Rok
                             * E Aktivno
                             * F Broj priloga
                             */
                            $sheet
                                ->getStyle(
                                    "D2:F{$lastRow}"
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );
                        } else {
                            /*
                             * C Rok
                             * D Aktivno
                             * E Broj priloga
                             */
                            $sheet
                                ->getStyle(
                                    "C2:E{$lastRow}"
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | ŠIRINE STUPACA
                    |--------------------------------------------------------------------------
                    */

                    $widths =
                        $this->showUserColumn
                            ? [
                                'A' => 32,
                                'B' => 24,
                                'C' => 45,
                                'D' => 20,
                                'E' => 14,
                                'F' => 14,
                            ]
                            : [
                                'A' => 32,
                                'B' => 45,
                                'C' => 20,
                                'D' => 14,
                                'E' => 14,
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

                    /*
                    |--------------------------------------------------------------------------
                    | VISINA REDOVA
                    |--------------------------------------------------------------------------
                    */

                    $sheet
                        ->getRowDimension(1)
                        ->setRowHeight(30);

                    for (
                        $row = 2;
                        $row <= $lastRow;
                        $row++
                    ) {
                        $sheet
                            ->getRowDimension(
                                $row
                            )
                            ->setRowHeight(28);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | EXCEL FUNKCIJE
                    |--------------------------------------------------------------------------
                    */

                    $sheet->freezePane(
                        'A2'
                    );

                    $sheet->setAutoFilter(
                        "A1:{$lastCol}{$lastRow}"
                    );
                },
        ];
    }
}
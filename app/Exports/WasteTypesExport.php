<?php

namespace App\Exports;

use App\Models\WasteType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WasteTypesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithEvents
{
    protected $rows;

    protected int $rowNumber = 0;

    protected bool $showUserColumn = false;

    public function __construct(array $ids)
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        /*
         * ID-evi dolaze iz filtrirane Filament tablice.
         *
         * Zato export poštuje:
         * - tenant scope
         * - aktivne filtere
         * - pretragu
         * - status aktivan/deaktiviran
         */
        $this->rows = WasteType::query()
            ->withTrashed()
            ->with('user')
            ->whereIn('id', $ids)
            ->orderBy('waste_code')
            ->orderBy('name')
            ->get();
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        $headings = [
            'Redni broj',
            'Ključni broj otpada',
            'Naziv otpada',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Opasan otpad',
            'Status',
            'Datum unosa',
        ]);
    }

    public function map($row): array
    {
        /** @var WasteType $row */

        $data = [
            ++$this->rowNumber,
            $this->formatWasteCode($row->waste_code),
            $row->name,
        ];

        if ($this->showUserColumn) {
            $data[] = $row->user?->name ?? '';
        }

        return array_merge($data, [
            $row->is_hazardous
                ? 'DA'
                : 'NE',

            $row->trashed()
                ? 'Deaktiviran'
                : 'Aktivan',

            $row->created_at
                ? $row->created_at->format('d.m.Y. H:i')
                : '',
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (
                AfterSheet $event
            ): void {
                $sheet =
                    $event->sheet->getDelegate();

                $lastRow =
                    $this->rows->count() + 1;

                $lastCol =
                    $this->showUserColumn
                        ? 'G'
                        : 'F';

                $sheet
                    ->getStyle(
                        "A1:{$lastCol}{$lastRow}"
                    )
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet
                    ->getStyle(
                        "A1:{$lastCol}1"
                    )
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => [
                                'rgb' => 'FFFFFF',
                            ],
                            'name' => 'DejaVu Sans',
                            'size' => 10,
                        ],

                        'fill' => [
                            'fillType' => 'solid',
                            'startColor' => [
                                'rgb' => '1F2937',
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

                if ($lastRow >= 2) {
                    $sheet
                        ->getStyle(
                            "A2:{$lastCol}{$lastRow}"
                        )
                        ->getAlignment()
                        ->setVertical(
                            Alignment::VERTICAL_CENTER
                        )
                        ->setWrapText(true);

                    if ($this->showUserColumn) {
                        foreach (
                            [
                                'A',
                                'B',
                                'D',
                                'E',
                                'F',
                                'G',
                            ] as $column
                        ) {
                            $sheet
                                ->getStyle(
                                    "{$column}2:"
                                    . "{$column}{$lastRow}"
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );
                        }

                        $widths = [
                            'A' => 12,
                            'B' => 20,
                            'C' => 48,
                            'D' => 24,
                            'E' => 16,
                            'F' => 18,
                            'G' => 20,
                        ];
                    } else {
                        foreach (
                            [
                                'A',
                                'B',
                                'D',
                                'E',
                                'F',
                            ] as $column
                        ) {
                            $sheet
                                ->getStyle(
                                    "{$column}2:"
                                    . "{$column}{$lastRow}"
                                )
                                ->getAlignment()
                                ->setHorizontal(
                                    Alignment::HORIZONTAL_CENTER
                                );
                        }

                        $widths = [
                            'A' => 12,
                            'B' => 20,
                            'C' => 48,
                            'D' => 16,
                            'E' => 18,
                            'F' => 20,
                        ];
                    }

                    for (
                        $row = 2;
                        $row <= $lastRow;
                        $row++
                    ) {
                        $sheet
                            ->getRowDimension($row)
                            ->setRowHeight(28);
                    }
                } else {
                    $widths =
                        $this->showUserColumn
                            ? [
                                'A' => 12,
                                'B' => 20,
                                'C' => 48,
                                'D' => 24,
                                'E' => 16,
                                'F' => 18,
                                'G' => 20,
                            ]
                            : [
                                'A' => 12,
                                'B' => 20,
                                'C' => 48,
                                'D' => 16,
                                'E' => 18,
                                'F' => 20,
                            ];
                }

                foreach (
                    $widths as $column => $width
                ) {
                    $sheet
                        ->getColumnDimension($column)
                        ->setWidth($width);
                }

                $sheet
                    ->getRowDimension(1)
                    ->setRowHeight(28);

                $sheet->freezePane('A2');

                $sheet->setAutoFilter(
                    "A1:{$lastCol}{$lastRow}"
                );
            },
        ];
    }

    private function formatWasteCode(
        ?string $code
    ): string {
        if (! $code) {
            return '-';
        }

        $hasStar = str_contains(
            $code,
            '*'
        );

        $digits = preg_replace(
            '/\D+/',
            '',
            str_replace('*', '', $code)
        );

        if (strlen($digits) === 6) {
            $formatted =
                substr($digits, 0, 2)
                . ' '
                . substr($digits, 2, 2)
                . ' '
                . substr($digits, 4, 2);
        } else {
            $formatted = $code;
        }

        return $hasStar
            ? $formatted . '*'
            : $formatted;
    }
}
<?php

namespace App\Exports;

use App\Filament\Resources\Machines\MachineResource;
use App\Models\Machine;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MachinesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    WithEvents
{
    protected $machines;

    protected bool $showUserColumn = false;

    public function __construct(?array $machineIds = null)
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $query = MachineResource::getEloquentQuery()
            ->with('user')
            ->orderBy('name');

        /*
         * Ako su ID-evi poslani iz filtrirane tablice,
         * export mora poštovati točno taj rezultat.
         *
         * Važno:
         * [] znači "filtriranje nije pronašlo ništa"
         * i zato mora dati prazan export, a ne sve zapise.
         */
        if ($machineIds !== null) {
            $query->whereIn(
                'machines.id',
                $machineIds
            );
        } else {
            $query->withoutTrashed();
        }

        $this->machines = $query->get();
    }

    public function collection()
    {
        return $this->machines;
    }

    public function headings(): array
    {
        $headings = [
            'Naziv',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Proizvođač',
            'Tvornički broj',
            'Inventarni broj',
            'Vrijedi od',
            'Vrijedi do',
            'Ispitao',
            'Broj izvještaja',
            'Lokacija',
            'Napomena',
            'Broj priloga',
        ]);
    }

    public function map($machine): array
    {
        /** @var Machine $machine */

        $from = $machine->examination_valid_from
            ? Carbon::parse($machine->examination_valid_from)
            : null;

        $until = $machine->examination_valid_until
            ? Carbon::parse($machine->examination_valid_until)
            : null;

        $row = [
            $machine->name,
        ];

        if ($this->showUserColumn) {
            $row[] = $machine->user?->name ?? '';
        }

        return array_merge($row, [
            $machine->manufacturer,
            $machine->factory_number,
            $machine->inventory_number,
            $from
                ? ExcelDate::dateTimeToExcel($from)
                : null,
            $until
                ? ExcelDate::dateTimeToExcel($until)
                : null,
            $machine->examined_by,
            $machine->report_number,
            $machine->location,
            $machine->remark,
            is_array($machine->pdf)
                ? count($machine->pdf)
                : 0,
        ]);
    }

    public function columnFormats(): array
    {
        if ($this->showUserColumn) {
            return [
                'F' => 'dd.mm.yyyy',
                'G' => 'dd.mm.yyyy',
            ];
        }

        return [
            'E' => 'dd.mm.yyyy',
            'F' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->machines->count() + 1;
                $lastCol = $this->showUserColumn
                    ? 'L'
                    : 'K';

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

                    $sheet
                        ->getStyle(
                            "A2:{$lastCol}{$lastRow}"
                        )
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_LEFT
                        );
                }

                if ($this->showUserColumn) {
                    if ($lastRow >= 2) {
                        $sheet
                            ->getStyle(
                                "D2:G{$lastRow}"
                            )
                            ->getAlignment()
                            ->setHorizontal(
                                Alignment::HORIZONTAL_CENTER
                            );

                        $sheet
                            ->getStyle(
                                "L2:L{$lastRow}"
                            )
                            ->getAlignment()
                            ->setHorizontal(
                                Alignment::HORIZONTAL_CENTER
                            );
                    }

                    $widths = [
                        'A' => 30,
                        'B' => 22,
                        'C' => 24,
                        'D' => 26,
                        'E' => 20,
                        'F' => 16,
                        'G' => 16,
                        'H' => 26,
                        'I' => 30,
                        'J' => 24,
                        'K' => 14,
                        'L' => 14,
                    ];

                    $expiryColumn = 'G';
                } else {
                    if ($lastRow >= 2) {
                        $sheet
                            ->getStyle(
                                "C2:F{$lastRow}"
                            )
                            ->getAlignment()
                            ->setHorizontal(
                                Alignment::HORIZONTAL_CENTER
                            );

                        $sheet
                            ->getStyle(
                                "K2:K{$lastRow}"
                            )
                            ->getAlignment()
                            ->setHorizontal(
                                Alignment::HORIZONTAL_CENTER
                            );
                    }

                    $widths = [
                        'A' => 30,
                        'B' => 24,
                        'C' => 22,
                        'D' => 26,
                        'E' => 16,
                        'F' => 16,
                        'G' => 16,
                        'H' => 26,
                        'I' => 30,
                        'J' => 14,
                        'K' => 14,
                    ];

                    $expiryColumn = 'F';
                }

                foreach ($widths as $column => $width) {
                    $sheet
                        ->getColumnDimension($column)
                        ->setWidth($width);
                }

                $sheet
                    ->getRowDimension(1)
                    ->setRowHeight(30);

                for (
                    $row = 2;
                    $row <= $lastRow;
                    $row++
                ) {
                    $sheet
                        ->getRowDimension($row)
                        ->setRowHeight(34);
                }

                $today = Carbon::today();

                foreach (
                    $this->machines
                    as $i => $machine
                ) {
                    $row = $i + 2;

                    $until =
                        $machine->examination_valid_until
                            ? Carbon::parse(
                                $machine
                                    ->examination_valid_until
                            )
                            : null;

                    if (! $until) {
                        continue;
                    }

                    if ($until->lt($today)) {
                        $this->fillCell(
                            $sheet,
                            "{$expiryColumn}{$row}",
                            'FFFF0000'
                        );

                        continue;
                    }

                    if (
                        $until->lte(
                            $today
                                ->copy()
                                ->addDays(30)
                        )
                    ) {
                        $this->fillCell(
                            $sheet,
                            "{$expiryColumn}{$row}",
                            'FFFFFF00'
                        );
                    }
                }

                $sheet->freezePane('A2');

                $sheet->setAutoFilter(
                    "A1:{$lastCol}{$lastRow}"
                );
            },
        ];
    }

    private function fillCell(
        $sheet,
        string $cell,
        string $argb
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
    }
}
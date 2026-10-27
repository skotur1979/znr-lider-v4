<?php

namespace App\Exports;

use App\Filament\Resources\PPELogs\PPELogResource;
use App\Models\PPEItem;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PpeItemsAllExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    ShouldAutoSize,
    WithEvents
{
    protected $items;

    protected ?string $lastKey = null;

    protected bool $showUserColumn = false;

    public function __construct(
        ?array $logIds = null,
        ?string $pregled = null
    ) {
        $user =
            auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        /*
        |--------------------------------------------------------------------------
        | NORMALIZACIJA FILTERA
        |--------------------------------------------------------------------------
        */

        $pregled =
            match ($pregled) {
                'uskoro' =>
                    'istek',

                'isteklo',
                'istek',
                'deaktivirani' =>
                    $pregled,

                default =>
                    'svi',
            };

        /*
        |--------------------------------------------------------------------------
        | PPE LOG ID-EVI
        |--------------------------------------------------------------------------
        |
        | Kada izvoz pokrećemo iz ListPPELogs stranice,
        | ovdje stižu ID-evi samo trenutno filtriranih
        | PPELog zapisa.
        |
        | Ako klasu pozovemo negdje drugdje bez ID-eva,
        | zadržavamo fallback na sve dostupne zapise.
        |
        */

        if ($logIds === null) {
            $logIds =
                PPELogResource
                    ::getEloquentQuery()
                    ->select('id')
                    ->pluck('id')
                    ->map(
                        fn ($id): int =>
                            (int) $id
                    )
                    ->values()
                    ->all();
        } else {
            $logIds =
                collect($logIds)
                    ->map(
                        fn ($id): int =>
                            (int) $id
                    )
                    ->filter(
                        fn (int $id): bool =>
                            $id > 0
                    )
                    ->unique()
                    ->values()
                    ->all();
        }

        $itemsTable =
            'personal_protective_equipment_items';

        /*
        |--------------------------------------------------------------------------
        | OSNOVNI QUERY
        |--------------------------------------------------------------------------
        */

        $query =
            PPEItem::query()
                ->whereIn(
                    "{$itemsTable}.personal_protective_equipment_log_id",
                    $logIds
                )
                ->with([
                    'log' =>
                        function ($query): void {
                            /*
                             * Omogućava prikaz podataka
                             * i kod deaktiviranih PPELog zapisa.
                             */
                            $query
                                ->withTrashed()
                                ->with('user');
                        },
                ])
                ->join(
                    'personal_protective_equipment_logs as l',
                    'l.id',
                    '=',
                    "{$itemsTable}.personal_protective_equipment_log_id"
                )
                ->select(
                    "{$itemsTable}.*"
                );

        /*
        |--------------------------------------------------------------------------
        | FILTER PO OZO STAVKAMA
        |--------------------------------------------------------------------------
        |
        | Ovo je ključna izmjena.
        |
        | PPELog filter na tablici kaže:
        |
        | "prikaži zaposlenika koji ima barem jednu isteklu stavku"
        |
        | ali to ne znači da treba izvesti sve njegove OZO.
        |
        | Zato isti filter primjenjujemo direktno na PPEItem.
        |
        */

        if ($pregled === 'isteklo') {
            $query
                ->whereNull(
                    "{$itemsTable}.return_date"
                )
                ->whereNotNull(
                    "{$itemsTable}.end_date"
                )
                ->whereDate(
                    "{$itemsTable}.end_date",
                    '<',
                    Carbon::today()
                );
        }

        if ($pregled === 'istek') {
            $query
                ->whereNull(
                    "{$itemsTable}.return_date"
                )
                ->whereNotNull(
                    "{$itemsTable}.end_date"
                )
                ->whereBetween(
                    "{$itemsTable}.end_date",
                    [
                        Carbon::today()
                            ->startOfDay(),

                        Carbon::today()
                            ->copy()
                            ->addDays(30)
                            ->endOfDay(),
                    ]
                );
        }

        /*
        |--------------------------------------------------------------------------
        | SORTIRANJE
        |--------------------------------------------------------------------------
        */

        $this->items =
            $query
                ->orderBy(
                    'l.user_last_name'
                )
                ->orderBy(
                    'l.user_oib'
                )
                ->orderBy(
                    'l.workplace'
                )
                ->orderBy(
                    'l.organization_unit'
                )
                ->orderBy(
                    "{$itemsTable}.equipment_name"
                )
                ->get();
    }

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        $headings = [];

        if ($this->showUserColumn) {
            $headings[] =
                'Korisnik';
        }

        return array_merge(
            $headings,
            [
                'Prezime i ime',
                'OIB',
                'Radno mjesto',
                'Organizacijska jedinica',
                'Naziv OZO',
                'HRN EN',
                'Veličina',
                'Rok (mjeseci)',
                'Izdano',
                'Istek',
                'Datum vraćanja',
            ]
        );
    }

    public function map(
        $item
    ): array {
        /** @var PPEItem $item */

        $log =
            $item->log;

        $key =
            trim(
                (
                    $this->showUserColumn
                        ? (
                            $log?->user?->name
                            ?? ''
                        )
                        . '|'
                        : ''
                )
                . (
                    $log?->user_last_name
                    ?? ''
                )
                . '|'
                . (
                    $log?->user_oib
                    ?? ''
                )
                . '|'
                . (
                    $log?->workplace
                    ?? ''
                )
                . '|'
                . (
                    $log?->organization_unit
                    ?? ''
                )
            );

        $isRepeat =
            $this->lastKey !== null
            && $this->lastKey === $key;

        if (! $isRepeat) {
            $this->lastKey =
                $key;
        }

        $issue =
            $item->issue_date
                ? Carbon::parse(
                    $item->issue_date
                )
                : null;

        $end =
            $item->end_date
                ? Carbon::parse(
                    $item->end_date
                )
                : null;

        $return =
            $item->return_date
                ? Carbon::parse(
                    $item->return_date
                )
                : null;

        $row = [];

        if ($this->showUserColumn) {
            $row[] =
                $isRepeat
                    ? ''
                    : (
                        $log?->user?->name
                        ?? ''
                    );
        }

        return array_merge(
            $row,
            [
                $isRepeat
                    ? ''
                    : (
                        $log?->user_last_name
                        ?? ''
                    ),

                $isRepeat
                    ? ''
                    : (
                        $log?->user_oib
                        ?? ''
                    ),

                $isRepeat
                    ? ''
                    : (
                        $log?->workplace
                        ?? ''
                    ),

                $isRepeat
                    ? ''
                    : (
                        $log?->organization_unit
                        ?? ''
                    ),

                $item->equipment_name,

                $item->standard,

                $item->size,

                $item->duration_months,

                $issue
                    ? ExcelDate::dateTimeToExcel(
                        $issue
                    )
                    : null,

                $end
                    ? ExcelDate::dateTimeToExcel(
                        $end
                    )
                    : null,

                $return
                    ? ExcelDate::dateTimeToExcel(
                        $return
                    )
                    : null,
            ]
        );
    }

    public function columnFormats(): array
    {
        if ($this->showUserColumn) {
            return [
                'J' =>
                    'dd.mm.yyyy',

                'K' =>
                    'dd.mm.yyyy',

                'L' =>
                    'dd.mm.yyyy',
            ];
        }

        return [
            'I' =>
                'dd.mm.yyyy',

            'J' =>
                'dd.mm.yyyy',

            'K' =>
                'dd.mm.yyyy',
        ];
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

                    $highestRow =
                        $sheet
                            ->getHighestRow();

                    $lastCol =
                        $this->showUserColumn
                            ? 'L'
                            : 'K';

                    /*
                    |--------------------------------------------------------------------------
                    | FONT
                    |--------------------------------------------------------------------------
                    */

                    $sheet
                        ->getStyle(
                            "A1:{$lastCol}{$highestRow}"
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
                        ]);

                    if ($highestRow >= 2) {
                        $sheet
                            ->getStyle(
                                "A2:{$lastCol}{$highestRow}"
                            )
                            ->getAlignment()
                            ->setVertical(
                                Alignment::VERTICAL_CENTER
                            )
                            ->setWrapText(
                                true
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | STUPCI
                    |--------------------------------------------------------------------------
                    */

                    if ($this->showUserColumn) {
                        foreach (
                            [
                                'C',
                                'G',
                                'H',
                                'I',
                                'J',
                                'K',
                                'L',
                            ]
                            as $col
                        ) {
                            if ($highestRow >= 2) {
                                $sheet
                                    ->getStyle(
                                        "{$col}2:{$col}{$highestRow}"
                                    )
                                    ->getAlignment()
                                    ->setHorizontal(
                                        Alignment::HORIZONTAL_CENTER
                                    );
                            }
                        }

                        $widths = [
                            'A' => 22,
                            'B' => 28,
                            'C' => 16,
                            'D' => 26,
                            'E' => 28,
                            'F' => 32,
                            'G' => 18,
                            'H' => 14,
                            'I' => 16,
                            'J' => 16,
                            'K' => 16,
                            'L' => 18,
                        ];

                        $expiryColumn =
                            'K';

                        $groupColumns = [
                            'A',
                            'B',
                            'C',
                            'D',
                            'E',
                        ];

                        $centerGroupColumns = [
                            'C',
                        ];
                    } else {
                        foreach (
                            [
                                'B',
                                'F',
                                'G',
                                'H',
                                'I',
                                'J',
                                'K',
                            ]
                            as $col
                        ) {
                            if ($highestRow >= 2) {
                                $sheet
                                    ->getStyle(
                                        "{$col}2:{$col}{$highestRow}"
                                    )
                                    ->getAlignment()
                                    ->setHorizontal(
                                        Alignment::HORIZONTAL_CENTER
                                    );
                            }
                        }

                        $widths = [
                            'A' => 28,
                            'B' => 16,
                            'C' => 26,
                            'D' => 28,
                            'E' => 32,
                            'F' => 18,
                            'G' => 14,
                            'H' => 16,
                            'I' => 16,
                            'J' => 16,
                            'K' => 18,
                        ];

                        $expiryColumn =
                            'J';

                        $groupColumns = [
                            'A',
                            'B',
                            'C',
                            'D',
                        ];

                        $centerGroupColumns = [
                            'B',
                        ];
                    }

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
                        ->getRowDimension(1)
                        ->setRowHeight(30);

                    for (
                        $row = 2;
                        $row <= $highestRow;
                        $row++
                    ) {
                        $sheet
                            ->getRowDimension(
                                $row
                            )
                            ->setRowHeight(22);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | BOJE ISTEKA
                    |--------------------------------------------------------------------------
                    */

                    $today =
                        Carbon::today();

                    $soon =
                        $today
                            ->copy()
                            ->addDays(30);

                    foreach (
                        $this->items
                        as $i => $item
                    ) {
                        $row =
                            $i + 2;

                        $end =
                            $item->end_date
                                ? Carbon::parse(
                                    $item->end_date
                                )
                                : null;

                        if (! $end) {
                            continue;
                        }

                        if (
                            $end->lt(
                                $today
                            )
                        ) {
                            $this->fillCell(
                                $sheet,
                                "{$expiryColumn}{$row}",
                                'FFFF0000'
                            );
                        } elseif (
                            $end->lte(
                                $soon
                            )
                        ) {
                            $this->fillCell(
                                $sheet,
                                "{$expiryColumn}{$row}",
                                'FFFFFF00'
                            );
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | RUBOVI
                    |--------------------------------------------------------------------------
                    */

                    $sheet
                        ->getStyle(
                            "A1:{$lastCol}{$highestRow}"
                        )
                        ->getBorders()
                        ->getAllBorders()
                        ->setBorderStyle(
                            Border::BORDER_THIN
                        );

                    if ($highestRow >= 2) {
                        $this
                            ->mergeGroupedColumns(
                                $sheet,
                                $highestRow,
                                $groupColumns,
                                $centerGroupColumns
                            );

                        $this
                            ->applyGroupTopBorders(
                                $sheet,
                                $highestRow,
                                $lastCol
                            );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | EXCEL FUNKCIJE
                    |--------------------------------------------------------------------------
                    */

                    $sheet
                        ->freezePane(
                            'A2'
                        );

                    $sheet
                        ->setAutoFilter(
                            "A1:{$lastCol}{$highestRow}"
                        );
                },
        ];
    }

    private function mergeGroupedColumns(
        $sheet,
        int $highestRow,
        array $columns,
        array $centerColumns = []
    ): void {
        if ($highestRow < 3) {
            return;
        }

        $groupStart = 2;

        $prevKey =
            $this->groupKeyFromSheet(
                $sheet,
                2,
                $columns
            );

        for (
            $row = 3;
            $row <= $highestRow + 1;
            $row++
        ) {
            $currentKey =
                $row <= $highestRow
                    ? $this
                        ->groupKeyFromSheet(
                            $sheet,
                            $row,
                            $columns
                        )
                    : '__END__';

            if (
                $currentKey
                !== $prevKey
            ) {
                $groupEnd =
                    $row - 1;

                if (
                    $groupEnd
                    > $groupStart
                    && $prevKey !== ''
                ) {
                    foreach (
                        $columns
                        as $col
                    ) {
                        $topCell =
                            "{$col}{$groupStart}";

                        $bottomCell =
                            "{$col}{$groupEnd}";

                        $topValue =
                            $sheet
                                ->getCell(
                                    $topCell
                                )
                                ->getValue();

                        for (
                            $r =
                                $groupStart + 1;
                            $r <= $groupEnd;
                            $r++
                        ) {
                            $sheet
                                ->setCellValue(
                                    "{$col}{$r}",
                                    $topValue
                                );
                        }

                        $sheet
                            ->mergeCells(
                                "{$topCell}:{$bottomCell}"
                            );

                        $sheet
                            ->getStyle(
                                "{$topCell}:{$bottomCell}"
                            )
                            ->getAlignment()
                            ->setVertical(
                                Alignment::VERTICAL_CENTER
                            )
                            ->setHorizontal(
                                in_array(
                                    $col,
                                    $centerColumns,
                                    true
                                )
                                    ? Alignment::HORIZONTAL_CENTER
                                    : Alignment::HORIZONTAL_LEFT
                            );
                    }
                }

                $groupStart =
                    $row;

                $prevKey =
                    $currentKey;
            }
        }
    }

    private function applyGroupTopBorders(
        $sheet,
        int $highestRow,
        string $lastCol
    ): void {
        if ($highestRow < 2) {
            return;
        }

        $groupColumns =
            $this->showUserColumn
                ? [
                    'A',
                    'B',
                    'C',
                    'D',
                    'E',
                ]
                : [
                    'A',
                    'B',
                    'C',
                    'D',
                ];

        $groupStartRows = [
            2,
        ];

        $prevKey =
            $this->groupKeyFromSheet(
                $sheet,
                2,
                $groupColumns
            );

        for (
            $row = 3;
            $row <= $highestRow;
            $row++
        ) {
            $currentKey =
                $this->groupKeyFromSheet(
                    $sheet,
                    $row,
                    $groupColumns
                );

            if (
                $currentKey !== ''
                && $currentKey
                    !== $prevKey
            ) {
                $groupStartRows[] =
                    $row;

                $prevKey =
                    $currentKey;
            }
        }

        foreach (
            $groupStartRows
            as $row
        ) {
            $sheet
                ->getStyle(
                    "A{$row}:{$lastCol}{$row}"
                )
                ->getBorders()
                ->getTop()
                ->setBorderStyle(
                    Border::BORDER_MEDIUM
                );
        }
    }

    private function groupKeyFromSheet(
        $sheet,
        int $row,
        array $columns
    ): string {
        $values = [];

        foreach (
            $columns
            as $column
        ) {
            $values[] =
                trim(
                    (string)
                    $sheet
                        ->getCell(
                            "{$column}{$row}"
                        )
                        ->getValue()
                );
        }

        if (
            collect(
                $values
            )->every(
                fn ($value) =>
                    $value === ''
            )
        ) {
            return '';
        }

        return implode(
            '|',
            $values
        );
    }

    private function fillCell(
        $sheet,
        string $cell,
        string $argb
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
            ->setARGB(
                $argb
            );

        $sheet
            ->getStyle(
                $cell
            )
            ->getFont()
            ->setBold(
                true
            );
    }
}
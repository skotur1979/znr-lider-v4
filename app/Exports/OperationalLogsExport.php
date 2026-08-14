<?php

namespace App\Exports;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Models\OperationalLog;
use App\Models\WorkTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OperationalLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected Collection $logs;

    protected bool $showUserColumn = false;

    public function __construct()
    {
        $user = Auth::user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->logs = OperationalLogResource::getEloquentQuery()
            ->with('user')
            ->orderByDesc('log_date')
            ->orderByDesc('id')
            ->get();
    }

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function headings(): array
    {
        $headings = [
            'Datum',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Broj bilješki',
            'Broj radnih zadataka',
            'Bilješke / natuknice',
            'Radni zadaci',
            'Uneseno',
        ]);
    }

    public function map($log): array
    {
        /** @var OperationalLog $log */

        $items = collect($log->items ?? [])
            ->filter(fn ($item) => filled($item['note'] ?? null))
            ->values();

        $notes = $items
            ->map(fn ($item, int $index) => ($index + 1) . '. ' . trim((string) ($item['note'] ?? '')))
            ->implode("\n");

        $tasks = $items
            ->filter(
                fn ($item) =>
                    ! empty($item['task_id'])
            )
            ->values()
            ->map(
                function (
                    $item,
                    int $index
                ) {
                    $note = trim(
                        (string) (
                            $item['note']
                            ?? ''
                        )
                    );

                    $taskId =
                        (int) $item['task_id'];

                    return
                        ($index + 1)
                        . '. '
                        . $note
                        . " [ID: {$taskId}]";
                }
            )
            ->implode("\n");

        $row = [
            $log->log_date?->format('d.m.Y.'),
        ];

        if ($this->showUserColumn) {
            $row[] = $log->user?->name ?? '';
        }

        return array_merge($row, [
            $items->count(),
           $items->filter(
                fn ($item) => ! empty($item['task_id'])
            )->count(),
            $notes,
            $tasks ?: '-',
            $log->created_at?->format('d.m.Y. H:i'),
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->logs->count() + 1;
                $lastCol = $this->showUserColumn ? 'G' : 'F';

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
                        'fillType' => Fill::FILL_SOLID,
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
                    ->setVertical(Alignment::VERTICAL_TOP)
                    ->setWrapText(true);

                $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                if ($this->showUserColumn) {
                    $centerColumns = ['A', 'C', 'D', 'G'];

                    $widths = [
                        'A' => 16,
                        'B' => 22,
                        'C' => 14,
                        'D' => 18,
                        'E' => 60,
                        'F' => 55,
                        'G' => 18,
                    ];

                    $taskCountColumn = 'D';
                } else {
                    $centerColumns = ['A', 'B', 'C', 'F'];

                    $widths = [
                        'A' => 16,
                        'B' => 14,
                        'C' => 18,
                        'D' => 65,
                        'E' => 55,
                        'F' => 18,
                    ];

                    $taskCountColumn = 'C';
                }

                foreach ($centerColumns as $column) {
                    $sheet->getStyle("{$column}2:{$column}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(30);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(70);
                }

                for ($row = 2; $row <= $lastRow; $row++) {
                    $taskCount = (int) $sheet->getCell("{$taskCountColumn}{$row}")->getValue();

                    if ($taskCount > 0) {
                        $sheet->getStyle("{$taskCountColumn}{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => '92400E'],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FEF3C7'],
                            ],
                        ]);
                    }
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }
}
<?php

namespace App\Exports;

use App\Filament\Resources\WorkTasks\WorkTaskResource;
use App\Models\WorkTask;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WorkTasksExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $tasks;

    protected bool $showUserColumn = false;

    public function __construct()
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->tasks = WorkTaskResource::getEloquentQuery()
            ->with('user')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    public function collection()
    {
        return $this->tasks;
    }

    public function headings(): array
    {
        $headings = ['Zadatak'];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
            'Opis',
            'Datum',
            'Status',
            'Zatvoreno',
        ]);
    }

    public function map($task): array
    {
        /** @var WorkTask $task */

        $row = [$task->title];

        if ($this->showUserColumn) {
            $row[] = $task->user?->name ?? '';
        }

        return array_merge($row, [
            $task->description,
            $task->due_date ? $task->due_date->format('d.m.Y.') : '',
            $task->is_done ? 'Riješeno' : 'Otvoreno',
            $task->completed_at ? $task->completed_at->format('d.m.Y. H:i') : '',
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->tasks->count() + 1;
                $lastCol = $this->showUserColumn ? 'F' : 'E';

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
                    $sheet->getStyle("D2:F{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 34,
                        'B' => 22,
                        'C' => 55,
                        'D' => 16,
                        'E' => 16,
                        'F' => 20,
                    ];
                } else {
                    $sheet->getStyle("C2:E{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 34,
                        'B' => 55,
                        'C' => 16,
                        'D' => 16,
                        'E' => 20,
                    ];
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(28);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }
}
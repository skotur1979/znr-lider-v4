<?php

namespace App\Exports;

use App\Filament\Resources\Expenses\Expenses\ExpenseResource;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExpensesExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    protected $expenses;

    protected string $year;

    protected float $totalExpenses = 0.0;
    protected float $totalBudget = 0.0;
    protected float $balance = 0.0;

    public function __construct(string $year)
    {
        $this->year = $year;

        $this->expenses = ExpenseResource::getEloquentQuery()
            ->with(['user', 'budget', 'category'])
            ->whereHas('budget', fn (Builder $query) => $query->where('godina', $this->year))
            ->orderByRaw("
                FIELD(
                    mjesec,
                    'Siječanj','Veljača','Ožujak','Travanj','Svibanj','Lipanj',
                    'Srpanj','Kolovoz','Rujan','Listopad','Studeni','Prosinac'
                )
            ")
            ->orderBy('naziv_troska')
            ->get();

        $this->totalExpenses = (float) $this->expenses->sum('iznos');

        $this->totalBudget = (float) Budget::query()
            ->when(! auth()->user()?->isSuperAdmin(), fn (Builder $query) => $query->where('user_id', auth()->user()?->ownerId()))
            ->where('godina', $this->year)
            ->sum('ukupni_budget');

        $this->balance = $this->totalBudget - $this->totalExpenses;
    }

    public function collection()
    {
        return $this->expenses;
    }

    public function headings(): array
    {
        return [
            'Godina',
            'Korisnik',
            'Budžet (€)',
            'Kategorija',
            'Mjesec',
            'Naziv troška',
            'Iznos (€)',
            'Dobavljač',
            'Realizirano',
        ];
    }

    public function map($expense): array
    {
        /** @var Expense $expense */

        return [
            $expense->budget?->godina ?? '',
            $expense->user?->name ?? '',
            $expense->budget?->ukupni_budget ?? null,
            $expense->category?->name ?? '',
            $expense->mjesec,
            $expense->naziv_troska,
            (float) $expense->iznos,
            $expense->dobavljac,
            $expense->realizirano ? 'Da' : 'Ne',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => '#,##0.00',
            'G' => '#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastDataRow = $this->expenses->count() + 1;
                $summaryStartRow = $lastDataRow + 3;

                $sheet->getStyle("A1:I{$summaryStartRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:I1')->applyFromArray([
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

                $sheet->getStyle("A2:I{$lastDataRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:I{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("A2:A{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("C2:C{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $sheet->getStyle("E2:E{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("G2:I{$lastDataRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $widths = [
                    'A' => 12,
                    'B' => 22,
                    'C' => 16,
                    'D' => 26,
                    'E' => 16,
                    'F' => 38,
                    'G' => 16,
                    'H' => 28,
                    'I' => 16,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(28);

                for ($row = 2; $row <= $lastDataRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(32);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:I{$lastDataRow}");

                $sheet->setCellValue("A{$summaryStartRow}", "SAŽETAK TROŠKOVA ZA {$this->year}. GODINU");
                $sheet->mergeCells("A{$summaryStartRow}:I{$summaryStartRow}");

                $sheet->getStyle("A{$summaryStartRow}:I{$summaryStartRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name' => 'DejaVu Sans',
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '374151'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension($summaryStartRow)->setRowHeight(26);

                $r1 = $summaryStartRow + 1;
                $r2 = $summaryStartRow + 2;
                $r3 = $summaryStartRow + 3;

                $sheet->setCellValue("A{$r1}", 'Ukupno troškova (€)');
                $sheet->setCellValue("B{$r1}", $this->totalExpenses);

                $sheet->setCellValue("A{$r2}", 'Ukupni budžet (€)');
                $sheet->setCellValue("B{$r2}", $this->totalBudget);

                $sheet->setCellValue("A{$r3}", 'Stanje (budžet - troškovi) (€)');
                $sheet->setCellValue("B{$r3}", $this->balance);

                $sheet->getStyle("A{$r1}:A{$r3}")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("B{$r1}:B{$r3}")
                    ->getFont()
                    ->setBold(true);

                $sheet->getStyle("B{$r1}:B{$r3}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                $sheet->getStyle("A{$r1}:B{$r3}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("B{$r1}:B{$r3}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                if ($this->balance < 0) {
                    $this->fillCell($sheet, "B{$r3}", 'FFFF0000');
                } else {
                    $this->fillCell($sheet, "B{$r3}", 'FF00B050');
                }
            },
        ];
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
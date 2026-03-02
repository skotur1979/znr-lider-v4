<?php

namespace App\Exports;

use App\Filament\Resources\Expenses\Expenses\ExpenseResource;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Support\Carbon;
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
    /** @var \Illuminate\Support\Collection<int, \App\Models\Expense> */
    protected $expenses;

    protected string $year;

    protected float $totalExpenses = 0.0;
    protected float $totalBudget = 0.0;
    protected float $balance = 0.0;

    public function __construct(string $year)
    {
        $this->year = $year;

        // ✅ sve po user_id (preko ExpenseResource::getEloquentQuery())
        $q = ExpenseResource::getEloquentQuery()
            ->with(['budget', 'category'])
            ->whereHas('budget', fn (Builder $b) => $b->where('godina', $this->year));

        // (ako želiš samo realizirano u exportu, odkomentiraj)
        // $q->where('realizirano', true);

        $this->expenses = $q->get();

        // Sažetak
        $this->totalExpenses = (float) $this->expenses->sum('iznos');

        $this->totalBudget = (float) Budget::query()
            ->when(! auth()->user()?->isAdmin(), fn (Builder $qb) => $qb->where('user_id', auth()->id()))
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
            'B' => '#,##0.00', // Budžet
            'F' => '#,##0.00', // Iznos
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Header stil
                $sheet->getStyle('A1:H1')->getFont()->setBold(true);
                $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $lastDataRow = 1 + $this->expenses->count(); // header = 1
                $summaryStartRow = $lastDataRow + 3;

                // Naslov sažetka
                $sheet->setCellValue("A{$summaryStartRow}", "SAŽETAK (Godina: {$this->year})");
                $sheet->mergeCells("A{$summaryStartRow}:H{$summaryStartRow}");
                $sheet->getStyle("A{$summaryStartRow}:H{$summaryStartRow}")->getFont()->setBold(true);

                // Redovi sažetka
                $r1 = $summaryStartRow + 1;
                $r2 = $summaryStartRow + 2;
                $r3 = $summaryStartRow + 3;

                $sheet->setCellValue("A{$r1}", 'Ukupno troškova (€)');
                $sheet->setCellValue("B{$r1}", $this->totalExpenses);

                $sheet->setCellValue("A{$r2}", 'Ukupni budžet (€)');
                $sheet->setCellValue("B{$r2}", $this->totalBudget);

                $sheet->setCellValue("A{$r3}", 'Stanje (budžet - troškovi) (€)');
                $sheet->setCellValue("B{$r3}", $this->balance);

                // Format brojeva u sažetku
                $sheet->getStyle("B{$r1}:B{$r3}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');

                // Bold label + value
                $sheet->getStyle("A{$r1}:A{$r3}")->getFont()->setBold(true);
                $sheet->getStyle("B{$r1}:B{$r3}")->getFont()->setBold(true);

                // Stanje: crveno ako minus, zeleno ako plus
                if ($this->balance < 0) {
                    $this->fillCell($sheet, "B{$r3}", 'FFFF0000'); // crveno
                } else {
                    $this->fillCell($sheet, "B{$r3}", 'FF00B050'); // zeleno (excel green)
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
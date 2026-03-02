<?php

namespace App\Filament\Resources\Expenses\Expenses\Pages;

use App\Exports\ExpensesExport;
use App\Filament\Resources\Expenses\Expenses\ExpenseResource;
use App\Models\Budget;
use App\Models\Expense;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getDefaultTableFilters(): ?array
    {
        return [
            'godina' => [
                'value' => (string) Carbon::now('Europe/Zagreb')->year,
            ],
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $year = data_get($this->getTableFilterState('godina'), 'value')
            ?: (string) Carbon::now('Europe/Zagreb')->year;

        return $query->whereHas('budget', fn (Builder $b) => $b->where('godina', $year));
    }

    public function getHeader(): ?View
    {
        $selectedYear = data_get($this->getTableFilterState('godina'), 'value')
            ?: (string) Carbon::now('Europe/Zagreb')->year;

        $isAdmin = auth()->user()?->isAdmin();
        $userId  = auth()->id();

        $base = Expense::query()
            ->where('realizirano', true)
            ->when(! $isAdmin, fn (Builder $q) => $q->where('user_id', $userId))
            ->whereHas('budget', fn (Builder $b) => $b->where('godina', $selectedYear));

        $ukupnoTroskova = (float) (clone $base)->sum('iznos');

        $ukupniBudget = (float) Budget::query()
            ->when(! $isAdmin, fn (Builder $q) => $q->where('user_id', $userId))
            ->where('godina', $selectedYear)
            ->sum('ukupni_budget');

        $razlika = $ukupniBudget - $ukupnoTroskova;

        $grupiraniTroskovi = (clone $base)
            ->whereNotNull('mjesec')
            ->selectRaw('mjesec, SUM(iznos) as ukupno')
            ->groupBy('mjesec')
            ->orderByRaw("FIELD(mjesec,
                'Siječanj','Veljača','Ožujak','Travanj','Svibanj','Lipanj',
                'Srpanj','Kolovoz','Rujan','Listopad','Studeni','Prosinac'
            )")
            ->get();

        return view('filament.resources.expenses.partials.zbroj', [
            'godina'            => $selectedYear,
            'ukupnoTroskova'    => $ukupnoTroskova,
            'ukupniBudget'      => $ukupniBudget,
            'razlika'           => $razlika,
            'grupiraniTroskovi' => $grupiraniTroskovi,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->button()
                ->action(function () {
                    $year = data_get($this->getTableFilterState('godina'), 'value')
                        ?: (string) Carbon::now('Europe/Zagreb')->year;

                    return Excel::download(
                        new ExpensesExport($year),
                        'Troskovi_' . $year . '.xlsx'
                    );
                }),
        ];
    }
}
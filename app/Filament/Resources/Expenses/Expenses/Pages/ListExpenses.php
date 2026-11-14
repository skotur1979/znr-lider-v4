<?php

namespace App\Filament\Resources\Expenses\Expenses\Pages;

use App\Filament\Resources\Expenses\Expenses\ExpenseResource;
use App\Models\Budget;
use App\Models\Expense;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    public function getHeader(): ?View
    {
        $selectedYear = data_get(
            $this->getTableFilterState('godina'),
            'value'
        );

        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $isSuperAdmin = $user->isSuperAdmin();
        $ownerId = $user->ownerId();

        /*
         * U zaglavlju računamo samo realizirane troškove.
         *
         * Ako je odabrana godina:
         * - prikazuju se podaci te godine.
         *
         * Ako je filter godine prazan:
         * - prikazuju se podaci za sve godine.
         */
        $base = Expense::query()
            ->where('realizirano', true)
            ->when(
                ! $isSuperAdmin,
                fn (Builder $query) =>
                    $query->where('user_id', $ownerId)
            )
            ->when(
                filled($selectedYear),
                fn (Builder $query) =>
                    $query->whereHas(
                        'budget',
                        fn (Builder $budgetQuery) =>
                            $budgetQuery->where(
                                'godina',
                                $selectedYear
                            )
                    )
            );

        $ukupnoTroskova = (float) (clone $base)
            ->sum('iznos');

        /*
         * Ukupni budžet prati isti filter godine.
         */
        $ukupniBudget = (float) Budget::query()
            ->when(
                ! $isSuperAdmin,
                fn (Builder $query) =>
                    $query->where('user_id', $ownerId)
            )
            ->when(
                filled($selectedYear),
                fn (Builder $query) =>
                    $query->where(
                        'godina',
                        $selectedYear
                    )
            )
            ->sum('ukupni_budget');

        $razlika = $ukupniBudget - $ukupnoTroskova;

        /*
         * Grupiranje realiziranih troškova po mjesecima.
         *
         * Kod opcije "Sve godine" isti mjeseci svih godina
         * zbrajaju se zajedno.
         */
        $grupiraniTroskovi = (clone $base)
            ->whereNotNull('mjesec')
            ->selectRaw(
                'mjesec, SUM(iznos) as ukupno'
            )
            ->groupBy('mjesec')
            ->orderByRaw("
                FIELD(
                    mjesec,
                    'Siječanj',
                    'Veljača',
                    'Ožujak',
                    'Travanj',
                    'Svibanj',
                    'Lipanj',
                    'Srpanj',
                    'Kolovoz',
                    'Rujan',
                    'Listopad',
                    'Studeni',
                    'Prosinac'
                )
            ")
            ->get();

        return view(
            'filament.resources.expenses.partials.zbroj',
            [
                'godina' => filled($selectedYear)
                    ? (string) $selectedYear
                    : 'Sve godine',

                'ukupnoTroskova' => $ukupnoTroskova,
                'ukupniBudget' => $ukupniBudget,
                'razlika' => $razlika,
                'grupiraniTroskovi' => $grupiraniTroskovi,
            ]
        );
    }
}
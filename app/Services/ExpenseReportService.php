<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExpenseReportService
{
    protected array $months = [
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
        'Prosinac',
    ];

    public function report(
        array $filters = []
    ): array {
        $query =
            $this->expenseQuery(
                $filters
            );

        /*
        |--------------------------------------------------------------------------
        | OSNOVNE VRIJEDNOSTI
        |--------------------------------------------------------------------------
        */

        $totalAmount =
            (float) (clone $query)
                ->sum('iznos');

        $realizedAmount =
            (float) (clone $query)
                ->where(
                    'realizirano',
                    true
                )
                ->sum('iznos');

        $unrealizedAmount =
            (float) (clone $query)
                ->where(
                    'realizirano',
                    false
                )
                ->sum('iznos');

        $count =
            (int) (clone $query)
                ->count();

        $average =
            $count > 0
                ? $totalAmount / $count
                : 0;

        $maxAmount =
            (float) (
                (clone $query)
                    ->max('iznos')
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | BUDŽET
        |--------------------------------------------------------------------------
        */

        $selectedYear =
            $filters['year']
            ?? 'all';

        $budgetAmount = null;

        if (
            filled($selectedYear)
            && $selectedYear !== 'all'
        ) {
            $budgetAmount =
                (float)
                $this->budgetQuery()
                    ->where(
                        'godina',
                        $selectedYear
                    )
                    ->sum(
                        'ukupni_budget'
                    );
        }

        $remainingBudget =
            $budgetAmount !== null
                ? $budgetAmount
                    - $realizedAmount
                : null;

        /*
        |--------------------------------------------------------------------------
        | PO MJESECIMA
        |--------------------------------------------------------------------------
        */

        $monthlyRaw =
            (clone $query)
                ->selectRaw(
                    'mjesec, SUM(iznos) as total, COUNT(*) as count'
                )
                ->whereNotNull(
                    'mjesec'
                )
                ->groupBy(
                    'mjesec'
                )
                ->get()
                ->keyBy(
                    'mjesec'
                );

        $monthly = [];

        foreach (
            $this->months
            as $month
        ) {
            $row =
                $monthlyRaw->get(
                    $month
                );

            $monthly[] = [
                'label' =>
                    $month,

                'total' =>
                    (float) (
                        $row?->total
                        ?? 0
                    ),

                'count' =>
                    (int) (
                        $row?->count
                        ?? 0
                    ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PO KATEGORIJAMA
        |--------------------------------------------------------------------------
        */

        $categoryRows =
            (clone $query)
                ->selectRaw(
                    'category_id, SUM(iznos) as total, COUNT(*) as count'
                )
                ->groupBy(
                    'category_id'
                )
                ->orderByDesc(
                    'total'
                )
                ->get();

        $categoryNames =
            $this->categoryQuery()
                ->whereIn(
                    'id',
                    $categoryRows
                        ->pluck(
                            'category_id'
                        )
                        ->filter()
                )
                ->pluck(
                    'name',
                    'id'
                );

        $byCategory =
            $categoryRows
                ->map(
                    fn ($row): array => [
                        'label' =>
                            $categoryNames[
                                $row->category_id
                            ]
                            ?? 'Bez kategorije',

                        'total' =>
                            (float)
                            $row->total,

                        'count' =>
                            (int)
                            $row->count,
                    ]
                )
                ->values()
                ->all();

        /*
        |--------------------------------------------------------------------------
        | PO DOBAVLJAČIMA
        |--------------------------------------------------------------------------
        */

        $bySupplier =
            (clone $query)
                ->whereNotNull(
                    'dobavljac'
                )
                ->where(
                    'dobavljac',
                    '<>',
                    ''
                )
                ->selectRaw(
                    'dobavljac, SUM(iznos) as total, COUNT(*) as count'
                )
                ->groupBy(
                    'dobavljac'
                )
                ->orderByDesc(
                    'total'
                )
                ->limit(10)
                ->get()
                ->map(
                    fn ($row): array => [
                        'label' =>
                            $row->dobavljac,

                        'total' =>
                            (float)
                            $row->total,

                        'count' =>
                            (int)
                            $row->count,
                    ]
                )
                ->all();

        /*
        |--------------------------------------------------------------------------
        | REALIZIRANO / NEREALIZIRANO
        |--------------------------------------------------------------------------
        */

        $realization = [
            [
                'label' =>
                    'Realizirano',

                'total' =>
                    $realizedAmount,

                'count' =>
                    (int)
                    (clone $query)
                        ->where(
                            'realizirano',
                            true
                        )
                        ->count(),
            ],

            [
                'label' =>
                    'Nerealizirano',

                'total' =>
                    $unrealizedAmount,

                'count' =>
                    (int)
                    (clone $query)
                        ->where(
                            'realizirano',
                            false
                        )
                        ->count(),
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | USPOREDBA GODINA
        |--------------------------------------------------------------------------
        */

        $comparison =
            $this->yearComparison(
                $filters
            );

        /*
        |--------------------------------------------------------------------------
        | PREGLED PO SVIM GODINAMA
        |--------------------------------------------------------------------------
        |
        | Ovdje namjerno ignoriramo glavni filter godine,
        | ali zadržavamo mjesec, kategoriju, dobavljača
        | i realizaciju.
        |
        */

        $yearlyQuery =
            $this->expenseQuery(
                $filters,
                ignoreYear: true
            );

        $byYear =
            $yearlyQuery
                ->join(
                    'budgets',
                    'expenses.budget_id',
                    '=',
                    'budgets.id'
                )
                ->selectRaw(
                    'budgets.godina as year, SUM(expenses.iznos) as total, COUNT(expenses.id) as count'
                )
                ->groupBy(
                    'budgets.godina'
                )
                ->orderBy(
                    'budgets.godina'
                )
                ->get()
                ->map(
                    fn ($row): array => [
                        'label' =>
                            (string)
                            $row->year,

                        'total' =>
                            (float)
                            $row->total,

                        'count' =>
                            (int)
                            $row->count,
                    ]
                )
                ->all();

        /*
        |--------------------------------------------------------------------------
        | NAJVEĆI TROŠKOVI
        |--------------------------------------------------------------------------
        */

        $topExpenses =
            (clone $query)
                ->with([
                    'budget',
                    'category',
                ])
                ->orderByDesc(
                    'iznos'
                )
                ->limit(10)
                ->get()
                ->map(
                    fn (
                        Expense $expense
                    ): array => [
                        'id' =>
                            $expense->id,

                        'name' =>
                            $expense
                                ->naziv_troska,

                        'amount' =>
                            (float)
                            $expense->iznos,

                        'year' =>
                            $expense
                                ->budget
                                ?->godina,

                        'month' =>
                            $expense
                                ->mjesec,

                        'category' =>
                            $expense
                                ->category
                                ?->name
                            ?? '-',

                        'supplier' =>
                            $expense
                                ->dobavljac
                            ?: '-',

                        'realized' =>
                            (bool)
                            $expense
                                ->realizirano,
                    ]
                )
                ->all();

        /*
        |--------------------------------------------------------------------------
        | OPCIJE ZA FILTRE
        |--------------------------------------------------------------------------
        */

        return [
            'summary' => [
                'total_amount' =>
                    $totalAmount,

                'realized_amount' =>
                    $realizedAmount,

                'unrealized_amount' =>
                    $unrealizedAmount,

                'budget_amount' =>
                    $budgetAmount,

                'remaining_budget' =>
                    $remainingBudget,

                'count' =>
                    $count,

                'average' =>
                    $average,

                'max_amount' =>
                    $maxAmount,
            ],

            'monthly' =>
                $monthly,

            'by_category' =>
                $byCategory,

            'by_supplier' =>
                $bySupplier,

            'realization' =>
                $realization,

            'comparison' =>
                $comparison,

            'by_year' =>
                $byYear,

            'top_expenses' =>
                $topExpenses,

            'options' => [
                'years' =>
                    $this->yearOptions(),

                'categories' =>
                    $this->categoryOptions(),

                'suppliers' =>
                    $this->supplierOptions(),
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | USPOREDBA DVIJE GODINE
    |--------------------------------------------------------------------------
    */

    protected function yearComparison(
        array $filters
    ): array {
        $year =
            $filters['year']
            ?? 'all';

        $comparisonYear =
            $filters[
                'comparison_year'
            ]
            ?? null;

        if (
            blank($year)
            || $year === 'all'
            || blank($comparisonYear)
            || $comparisonYear === 'none'
        ) {
            return [
                'enabled' =>
                    false,

                'year' =>
                    $year,

                'comparison_year' =>
                    $comparisonYear,

                'months' =>
                    [],
            ];
        }

        $firstFilters =
            $filters;

        $firstFilters['year'] =
            (string) $year;

        $secondFilters =
            $filters;

        $secondFilters['year'] =
            (string)
            $comparisonYear;

        $first =
            $this->monthlyTotals(
                $firstFilters
            );

        $second =
            $this->monthlyTotals(
                $secondFilters
            );

        $months = [];

        foreach (
            $this->months
            as $month
        ) {
            $firstValue =
                (float) (
                    $first[$month]
                    ?? 0
                );

            $secondValue =
                (float) (
                    $second[$month]
                    ?? 0
                );

            $difference =
                $firstValue
                - $secondValue;

            $percentage = null;

            if ($secondValue > 0) {
                $percentage =
                    (
                        $difference
                        / $secondValue
                    )
                    * 100;
            }

            $months[] = [
                'label' =>
                    $month,

                'current' =>
                    $firstValue,

                'comparison' =>
                    $secondValue,

                'difference' =>
                    $difference,

                'percentage' =>
                    $percentage,
            ];
        }

        return [
            'enabled' =>
                true,

            'year' =>
                (string) $year,

            'comparison_year' =>
                (string)
                $comparisonYear,

            'months' =>
                $months,

            'current_total' =>
                array_sum(
                    $first
                ),

            'comparison_total' =>
                array_sum(
                    $second
                ),

            'difference' =>
                array_sum(
                    $first
                )
                - array_sum(
                    $second
                ),
        ];
    }

    protected function monthlyTotals(
        array $filters
    ): array {
        return $this
            ->expenseQuery(
                $filters
            )
            ->whereNotNull(
                'mjesec'
            )
            ->selectRaw(
                'mjesec, SUM(iznos) as total'
            )
            ->groupBy(
                'mjesec'
            )
            ->pluck(
                'total',
                'mjesec'
            )
            ->map(
                fn ($value): float =>
                    (float) $value
            )
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY
    |--------------------------------------------------------------------------
    */

    protected function expenseQuery(
        array $filters = [],
        bool $ignoreYear = false
    ): Builder {
        $query =
            Expense::query();

        $this->applyTenantScope(
            $query
        );

        if (! $ignoreYear) {
            $year =
                $filters['year']
                ?? null;

            if (
                filled($year)
                && $year !== 'all'
            ) {
                $query->whereHas(
                    'budget',
                    fn (
                        Builder $budget
                    ): Builder =>
                        $budget->where(
                            'godina',
                            $year
                        )
                );
            }
        }

        $month =
            $filters['month']
            ?? null;

        if (
            filled($month)
            && $month !== 'all'
        ) {
            $query->where(
                'mjesec',
                $month
            );
        }

        $categoryId =
            $filters[
                'category_id'
            ]
            ?? null;

        if (filled($categoryId)) {
            $query->where(
                'category_id',
                $categoryId
            );
        }

        $realized =
            $filters[
                'realized'
            ]
            ?? 'all';

        if (
            $realized !== null
            && $realized !== ''
            && $realized !== 'all'
        ) {
            $query->where(
                'realizirano',
                (bool)
                (int)
                $realized
            );
        }

        $supplier =
            trim(
                (string) (
                    $filters[
                        'supplier'
                    ]
                    ?? ''
                )
            );

        if ($supplier !== '') {
            $query->where(
                'dobavljac',
                $supplier
            );
        }

        return $query;
    }

    protected function applyTenantScope(
        Builder $query
    ): Builder {
        $user =
            Auth::user();

        if (! $user) {
            return $query
                ->whereRaw(
                    '1 = 0'
                );
        }

        /*
        * Superadmin vidi sve organizacije.
        */
        if (
            $user->isSuperAdmin()
        ) {
            return $query;
        }

        $ownerId =
            (int)
            $user->ownerId();

        if ($ownerId <= 0) {
            return $query
                ->whereRaw(
                    '1 = 0'
                );
        }

        /*
        * Obavezno koristimo puni naziv stupca.
        *
        * U nekim dijelovima izvještaja Expense query
        * radi JOIN na budgets tablicu, a obje tablice
        * imaju user_id.
        *
        * Bez "expenses.user_id" MySQL javlja:
        * Column 'user_id' in where clause is ambiguous.
        */
        return $query->where(
            'expenses.user_id',
            $ownerId
        );
    }

    protected function budgetQuery(): Builder
    {
        $query =
            Budget::query();

        $user =
            Auth::user();

        if (! $user) {
            return $query
                ->whereRaw(
                    '1 = 0'
                );
        }

        if (
            ! $user->isSuperAdmin()
        ) {
            $query->where(
                'user_id',
                $user->ownerId()
            );
        }

        return $query;
    }

    protected function categoryQuery(): Builder
    {
        $query =
            Category::query();

        $user =
            Auth::user();

        if (! $user) {
            return $query
                ->whereRaw(
                    '1 = 0'
                );
        }

        if (
            ! $user->isSuperAdmin()
        ) {
            $query->where(
                'user_id',
                $user->ownerId()
            );
        }

        return $query;
    }

    protected function yearOptions(): array
    {
        return $this
            ->budgetQuery()
            ->orderByDesc(
                'godina'
            )
            ->pluck(
                'godina',
                'godina'
            )
            ->mapWithKeys(
                fn (
                    $year,
                    $key
                ): array => [
                    (string) $key =>
                        (string) $year,
                ]
            )
            ->all();
    }

    protected function categoryOptions(): array
    {
        return $this
            ->categoryQuery()
            ->orderBy(
                'name'
            )
            ->pluck(
                'name',
                'id'
            )
            ->toArray();
    }

    protected function supplierOptions(): array
    {
        $query =
            Expense::query()
                ->whereNotNull(
                    'dobavljac'
                )
                ->where(
                    'dobavljac',
                    '<>',
                    ''
                );

        $this->applyTenantScope(
            $query
        );

        return $query
            ->distinct()
            ->orderBy(
                'dobavljac'
            )
            ->pluck(
                'dobavljac',
                'dobavljac'
            )
            ->toArray();
    }
}
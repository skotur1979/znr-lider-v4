<?php

namespace App\Services;

use App\Filament\Resources\Inspections\InspectionResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class InspectionReportService
{
    public function report(array $filters = []): array
    {
        /*
         * Samo obični nadzori.
         *
         * 5S nadzori imaju vlastiti zasebni
         * izvještaj rezultata zona.
         */
        $inspections = $this
            ->filteredQuery($filters)
            ->with([
                'findings',
            ])
            ->orderByDesc('performed_at')
            ->get();

        $findings = $inspections
            ->flatMap(
                fn ($inspection) =>
                    $inspection->findings
            )
            ->values();

        /*
         * Ako su aktivni filteri vezani
         * uz nalaze, filtriramo i samu
         * kolekciju nalaza.
         */
        $findings = $this->filterFindings(
            $findings,
            $filters
        );

        $today = Carbon::today();

        $openFindings = $findings
            ->where(
                'workflow_status',
                'open'
            )
            ->count();

        $inProgressFindings = $findings
            ->where(
                'workflow_status',
                'in_progress'
            )
            ->count();

        $closedFindings = $findings
            ->where(
                'workflow_status',
                'closed'
            )
            ->count();

        $resolvedNoAction = $findings
            ->where(
                'workflow_status',
                'resolved_no_action'
            )
            ->count();

        $convertedToObservation = $findings
            ->where(
                'workflow_status',
                'converted_to_observation'
            )
            ->count();

        $criticalFindings = $findings
            ->where(
                'finding_status',
                'critical'
            )
            ->count();

        $recommendations = $findings
            ->where(
                'finding_status',
                'recommendation'
            )
            ->count();

        $noncompliances = $findings
            ->where(
                'finding_status',
                'noncompliance'
            )
            ->count();

        $okFindings = $findings
            ->where(
                'finding_status',
                'ok'
            )
            ->count();

        $overdueFindings = $findings
            ->filter(
                function (
                    $finding
                ) use ($today): bool {
                    if (
                        blank(
                            $finding->due_date
                        )
                        || in_array(
                            $finding
                                ->workflow_status,
                            [
                                'closed',
                                'resolved_no_action',
                                'rejected',
                            ],
                            true
                        )
                    ) {
                        return false;
                    }

                    return Carbon::parse(
                        $finding->due_date
                    )
                        ->startOfDay()
                        ->lt($today);
                }
            )
            ->count();

        return [
            'summary' => [
                'inspections' =>
                    $inspections->count(),

                'findings' =>
                    $findings->count(),

                'ok' =>
                    $okFindings,

                'recommendations' =>
                    $recommendations,

                'noncompliances' =>
                    $noncompliances,

                'critical' =>
                    $criticalFindings,

                'open' =>
                    $openFindings,

                'in_progress' =>
                    $inProgressFindings,

                'closed' =>
                    $closedFindings,

                'resolved_no_action' =>
                    $resolvedNoAction,

                'converted_to_observation' =>
                    $convertedToObservation,

                'overdue' =>
                    $overdueFindings,
            ],

            /*
             * Nadzori.
             */
            'months' =>
                $this->months(
                    $inspections
                ),

            'locations' =>
                $this->countBy(
                    $inspections,
                    'location'
                ),

            'inspectors' =>
                $this->countBy(
                    $inspections,
                    'performed_by'
                ),

            /*
             * Nalazi.
             */
            'finding_categories' =>
                $this->countBy(
                    $findings,
                    'category'
                ),

            'finding_types' =>
                $this->findingTypes(
                    $findings
                ),

            'workflow_statuses' =>
                $this->workflowStatuses(
                    $findings
                ),

            'responsible' =>
                $this->countBy(
                    $findings,
                    'responsible_person'
                ),

            /*
             * Detaljan pregled nadzora.
             */
            'inspections' =>
                $inspections
                    ->map(
                        function (
                            $inspection
                        ): array {
                            return [
                                'id' =>
                                    $inspection->id,

                                'number' =>
                                    $inspection->number,

                                'date' =>
                                    $inspection
                                        ->performed_at,

                                'title' =>
                                    $inspection->title,

                                'location' =>
                                    $inspection->location,

                                'performed_by' =>
                                    $inspection
                                        ->performed_by,

                                'findings_count' =>
                                    $inspection
                                        ->findings
                                        ->count(),
                            ];
                        }
                    )
                    ->values()
                    ->all(),

            /*
             * Detaljan pregled nalaza.
             */
            'findings' =>
                $findings
                    ->map(
                        function (
                            $finding
                        ): array {
                            $inspection =
                                $finding
                                    ->inspection;

                            return [
                                'id' =>
                                    $finding->id,

                                'inspection_number' =>
                                    $inspection
                                        ?->number,

                                'inspection_date' =>
                                    $inspection
                                        ?->performed_at,

                                'inspection_title' =>
                                    $inspection
                                        ?->title,

                                'location' =>
                                    $inspection
                                        ?->location,

                                'category' =>
                                    $finding->category,

                                'description' =>
                                    $finding
                                        ->description,

                                'finding_status' =>
                                    $finding
                                        ->finding_status,

                                'workflow_status' =>
                                    $finding
                                        ->workflow_status,

                                'responsible_person' =>
                                    $finding
                                        ->responsible_person,

                                'due_date' =>
                                    $finding->due_date,
                            ];
                        }
                    )
                    ->values()
                    ->all(),

            'options' =>
                $this->filterOptions(),

            'filters' =>
                $filters,
        ];
    }

    protected function filteredQuery(
        array $filters
    ): Builder {
        /*
         * Resource query čuva postojeći
         * organization / ownership scope.
         */
        $query =
            InspectionResource::getEloquentQuery();

        /*
         * Isključujemo 5S nadzore.
         *
         * whereNull ostavljamo zbog eventualnih
         * starijih zapisa bez inspection_type.
         */
        $query->where(
            function (
                Builder $query
            ): void {
                $query
                    ->whereNull(
                        'inspection_type'
                    )
                    ->orWhere(
                        'inspection_type',
                        '!=',
                        'five_s'
                    );
            }
        );

        $year =
            $filters['year']
            ?? 'all';

        if (
            filled($year)
            && $year !== 'all'
        ) {
            $query->whereYear(
                'performed_at',
                $year
            );
        }

        $month =
            $filters['month']
            ?? 'all';

        if (
            filled($month)
            && $month !== 'all'
        ) {
            $query->whereMonth(
                'performed_at',
                (int) $month
            );
        }

        if (
            filled(
                $filters['location']
                ?? null
            )
        ) {
            $query->where(
                'location',
                $filters['location']
            );
        }

        if (
            filled(
                $filters['performed_by']
                ?? null
            )
        ) {
            $query->where(
                'performed_by',
                $filters['performed_by']
            );
        }

        /*
         * Filteri koji pripadaju nalazima.
         */
        $findingFilters = [
            'category',
            'finding_status',
            'workflow_status',
            'responsible_person',
        ];

        foreach (
            $findingFilters
            as $field
        ) {
            $value =
                $filters[$field]
                ?? null;

            if (blank($value)) {
                continue;
            }

            $query->whereHas(
                'findings',
                fn (
                    Builder $findingQuery
                ) =>
                    $findingQuery->where(
                        $field,
                        $value
                    )
            );
        }

        return $query;
    }

    protected function filterFindings(
        Collection $findings,
        array $filters
    ): Collection {
        $fields = [
            'category',
            'finding_status',
            'workflow_status',
            'responsible_person',
        ];

        foreach (
            $fields
            as $field
        ) {
            $value =
                $filters[$field]
                ?? null;

            if (blank($value)) {
                continue;
            }

            $findings = $findings
                ->filter(
                    fn ($finding) =>
                        (string) (
                            $finding->{$field}
                            ?? ''
                        )
                        === (string) $value
                )
                ->values();
        }

        return $findings;
    }

    protected function months(
        Collection $inspections
    ): array {
        $months = [
            1 => 'Siječanj',
            2 => 'Veljača',
            3 => 'Ožujak',
            4 => 'Travanj',
            5 => 'Svibanj',
            6 => 'Lipanj',
            7 => 'Srpanj',
            8 => 'Kolovoz',
            9 => 'Rujan',
            10 => 'Listopad',
            11 => 'Studeni',
            12 => 'Prosinac',
        ];

        return collect($months)
            ->map(
                function (
                    string $label,
                    int $month
                ) use (
                    $inspections
                ): array {
                    return [
                        'label' =>
                            $label,

                        'count' =>
                            $inspections
                                ->filter(
                                    fn (
                                        $inspection
                                    ) =>
                                        optional(
                                            $inspection
                                                ->performed_at
                                        )->month
                                        === $month
                                )
                                ->count(),
                    ];
                }
            )
            ->values()
            ->all();
    }

    protected function countBy(
        Collection $items,
        string $field
    ): array {
        return $items
            ->filter(
                fn ($item) =>
                    filled(
                        $item->{$field}
                        ?? null
                    )
            )
            ->groupBy(
                fn ($item) =>
                    trim(
                        (string) (
                            $item->{$field}
                            ?? ''
                        )
                    )
            )
            ->map(
                fn (
                    Collection $rows,
                    $label
                ): array => [
                    'label' =>
                        $label,

                    'count' =>
                        $rows->count(),
                ]
            )
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    protected function findingTypes(
        Collection $findings
    ): array {
        $labels = [
            'ok' =>
                'Uredno',

            'recommendation' =>
                'Preporuka',

            'noncompliance' =>
                'Nepravilnost',

            'critical' =>
                'Kritična nepravilnost',
        ];

        return collect($labels)
            ->map(
                fn (
                    string $label,
                    string $value
                ): array => [
                    'label' =>
                        $label,

                    'count' =>
                        $findings
                            ->where(
                                'finding_status',
                                $value
                            )
                            ->count(),
                ]
            )
            ->values()
            ->all();
    }

    protected function workflowStatuses(
        Collection $findings
    ): array {
        $labels = [
            'open' =>
                'Nije započeto',

            'in_progress' =>
                'U tijeku',

            'closed' =>
                'Zatvoreno',

            'resolved_no_action' =>
                'Riješeno bez akcija',

            'converted_to_observation' =>
                'Pretvoreno u zapažanje',

            'rejected' =>
                'Odbačeno',
        ];

        return collect($labels)
            ->map(
                fn (
                    string $label,
                    string $value
                ): array => [
                    'label' =>
                        $label,

                    'count' =>
                        $findings
                            ->where(
                                'workflow_status',
                                $value
                            )
                            ->count(),
                ]
            )
            ->values()
            ->all();
    }

    protected function filterOptions(): array
    {
        /*
         * Opcije također samo iz običnih nadzora.
         */
        $query =
            InspectionResource::getEloquentQuery()
                ->where(
                    function (
                        Builder $query
                    ): void {
                        $query
                            ->whereNull(
                                'inspection_type'
                            )
                            ->orWhere(
                                'inspection_type',
                                '!=',
                                'five_s'
                            );
                    }
                );

        $years = (clone $query)
            ->whereNotNull(
                'performed_at'
            )
            ->selectRaw(
                'YEAR(performed_at) as year'
            )
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->mapWithKeys(
                fn ($year): array => [
                    (string) $year =>
                        (string) $year,
                ]
            )
            ->all();

        $currentYear =
            (string) now()->year;

        $years[$currentYear] =
            $currentYear;

        krsort($years);

        $locations =
            (clone $query)
                ->whereNotNull('location')
                ->where(
                    'location',
                    '<>',
                    ''
                )
                ->distinct()
                ->orderBy('location')
                ->pluck(
                    'location',
                    'location'
                )
                ->all();

        $performedBy =
            (clone $query)
                ->whereNotNull(
                    'performed_by'
                )
                ->where(
                    'performed_by',
                    '<>',
                    ''
                )
                ->distinct()
                ->orderBy(
                    'performed_by'
                )
                ->pluck(
                    'performed_by',
                    'performed_by'
                )
                ->all();

        $inspections =
            (clone $query)
                ->with('findings')
                ->get();

        $findings =
            $inspections
                ->flatMap(
                    fn ($inspection) =>
                        $inspection->findings
                );

        $categories =
            $findings
                ->pluck('category')
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(
                    fn ($value): array => [
                        (string) $value =>
                            (string) $value,
                    ]
                )
                ->all();

        $responsible =
            $findings
                ->pluck(
                    'responsible_person'
                )
                ->filter()
                ->unique()
                ->sort()
                ->mapWithKeys(
                    fn ($value): array => [
                        (string) $value =>
                            (string) $value,
                    ]
                )
                ->all();

        return [
            'years' =>
                $years,

            'locations' =>
                $locations,

            'performed_by' =>
                $performedBy,

            'categories' =>
                $categories,

            'responsible' =>
                $responsible,
        ];
    }
}
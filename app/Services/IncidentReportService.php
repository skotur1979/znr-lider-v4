<?php

namespace App\Services;

use App\Filament\Resources\Incidents\IncidentResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class IncidentReportService
{
    public function report(array $filters = []): array
    {
        $query = $this->filteredQuery($filters);

        $incidents = $query
            ->orderBy('date_occurred')
            ->get([
                'id',
                'location',
                'type_of_incident',
                'permanent_or_temporary',
                'date_occurred',
                'working_days_lost',
                'causes_of_injury',
                'accident_injury_type',
                'injured_body_part',
            ]);

        $total = $incidents->count();

        $lta = $incidents
            ->where('type_of_incident', 'LTA')
            ->count();

        $mta = $incidents
            ->where('type_of_incident', 'MTA')
            ->count();

        $faa = $incidents
            ->where('type_of_incident', 'FAA')
            ->count();

        $totalLostDays = (int) $incidents
            ->sum(
                fn ($incident) =>
                    (int) ($incident->working_days_lost ?? 0)
            );

        $incidentsWithLostDays = $incidents
            ->filter(
                fn ($incident): bool =>
                    (int) ($incident->working_days_lost ?? 0) > 0
            )
            ->count();

        $averageLostDays = $incidentsWithLostDays > 0
            ? round(
                $totalLostDays / $incidentsWithLostDays,
                1
            )
            : 0;

        $maxLostDays = (int) (
            $incidents->max('working_days_lost') ?? 0
        );

        return [
            'summary' => [
                'total' => $total,
                'lta' => $lta,
                'mta' => $mta,
                'faa' => $faa,
                'lost_days' => $totalLostDays,
                'average_lost_days' => $averageLostDays,
                'max_lost_days' => $maxLostDays,
                'incidents_with_lost_days' =>
                    $incidentsWithLostDays,
            ],

            'months' => $this->months($incidents),

            'types' => $this->incidentTypes(
                $incidents,
                $total
            ),

            'employment' => $this->employmentTypes(
                $incidents,
                $total
            ),

            'top_locations' => $this->topGrouped(
                $incidents,
                'location'
            ),

            'top_causes' => $this->topGrouped(
                $incidents,
                'causes_of_injury'
            ),

            'top_injury_types' => $this->topGrouped(
                $incidents,
                'accident_injury_type'
            ),

            'top_body_parts' => $this->topGrouped(
                $incidents,
                'injured_body_part'
            ),

            'locations_table' =>
                $this->locationsTable($incidents),

            'options' => $this->filterOptions(),

            'filters' => $filters,
        ];
    }

    protected function filteredQuery(
        array $filters
    ): Builder {
        /*
         * VAŽNO:
         * koristimo IncidentResource::getEloquentQuery()
         * kako bi ostao organization / tenant scope.
         *
         * Deaktivirani incidenti se ne uključuju u
         * statistički izvještaj.
         */
        $query = IncidentResource::getEloquentQuery()
            ->withoutTrashed();

        $year = $filters['year'] ?? 'all';

        if (
            filled($year)
            && $year !== 'all'
        ) {
            $query->whereYear(
                'date_occurred',
                $year
            );
        }

        $month = $filters['month'] ?? 'all';

        if (
            filled($month)
            && $month !== 'all'
        ) {
            $query->whereMonth(
                'date_occurred',
                (int) $month
            );
        }

        if (
            filled($filters['type'] ?? null)
        ) {
            $query->where(
                'type_of_incident',
                $filters['type']
            );
        }

        if (
            filled($filters['location'] ?? null)
        ) {
            $query->where(
                'location',
                $filters['location']
            );
        }

        if (
            filled($filters['employment'] ?? null)
        ) {
            $query->where(
                'permanent_or_temporary',
                $filters['employment']
            );
        }

        if (
            filled($filters['cause'] ?? null)
        ) {
            $query->where(
                'causes_of_injury',
                $filters['cause']
            );
        }

        if (
            filled($filters['injury_type'] ?? null)
        ) {
            $query->where(
                'accident_injury_type',
                $filters['injury_type']
            );
        }

        if (
            filled($filters['body_part'] ?? null)
        ) {
            $query->where(
                'injured_body_part',
                $filters['body_part']
            );
        }

        return $query;
    }

    protected function months(
        Collection $incidents
    ): array {
        $monthNames = [
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

        $rows = [];

        foreach ($monthNames as $number => $name) {
            $items = $incidents->filter(
                function ($incident) use ($number): bool {
                    if (! $incident->date_occurred) {
                        return false;
                    }

                    return Carbon::parse(
                        $incident->date_occurred
                    )->month === $number;
                }
            );

            $rows[] = [
                'number' => $number,
                'label' => $name,
                'total' => $items->count(),

                'lta' => $items
                    ->where(
                        'type_of_incident',
                        'LTA'
                    )
                    ->count(),

                'mta' => $items
                    ->where(
                        'type_of_incident',
                        'MTA'
                    )
                    ->count(),

                'faa' => $items
                    ->where(
                        'type_of_incident',
                        'FAA'
                    )
                    ->count(),

                'lost_days' => (int) $items
                    ->sum(
                        fn ($incident) =>
                            (int) (
                                $incident
                                    ->working_days_lost
                                ?? 0
                            )
                    ),
            ];
        }

        return $rows;
    }

    protected function incidentTypes(
        Collection $incidents,
        int $total
    ): array {
        $types = [
            'LTA' => 'LTA – Ozljeda na radu',
            'MTA' => 'MTA – Pružanje PP izvan tvrtke',
            'FAA' => 'FAA – Pružanje PP u tvrtki',
        ];

        $result = [];

        foreach ($types as $key => $label) {
            $count = $incidents
                ->where(
                    'type_of_incident',
                    $key
                )
                ->count();

            $result[] = [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'percentage' => $total > 0
                    ? round(
                        ($count / $total) * 100,
                        1
                    )
                    : 0,
            ];
        }

        return $result;
    }

    protected function employmentTypes(
        Collection $incidents,
        int $total
    ): array {
        $types = [
            'Permanent' => 'Stalni',
            'Temporary' => 'Privremeni',
        ];

        $result = [];

        foreach ($types as $key => $label) {
            $count = $incidents
                ->where(
                    'permanent_or_temporary',
                    $key
                )
                ->count();

            $result[] = [
                'key' => $key,
                'label' => $label,
                'count' => $count,
                'percentage' => $total > 0
                    ? round(
                        ($count / $total) * 100,
                        1
                    )
                    : 0,
            ];
        }

        return $result;
    }

    protected function topGrouped(
        Collection $incidents,
        string $field
    ): array {
        return $incidents
            ->filter(
                fn ($incident): bool =>
                    filled($incident->{$field})
            )
            ->groupBy(
                fn ($incident): string =>
                    trim(
                        (string) $incident->{$field}
                    )
            )
            ->map(
                function (
                    Collection $items,
                    string $label
                ): array {
                    return [
                        'label' => $label,
                        'count' => $items->count(),

                        'lost_days' => (int) $items
                            ->sum(
                                fn ($incident) =>
                                    (int) (
                                        $incident
                                            ->working_days_lost
                                        ?? 0
                                    )
                            ),
                    ];
                }
            )
            ->sortByDesc('count')
            ->take(5)
            ->values()
            ->all();
    }

    protected function locationsTable(
        Collection $incidents
    ): array {
        return $incidents
            ->filter(
                fn ($incident): bool =>
                    filled($incident->location)
            )
            ->groupBy(
                fn ($incident): string =>
                    trim(
                        (string) $incident->location
                    )
            )
            ->map(
                function (
                    Collection $items,
                    string $location
                ): array {
                    return [
                        'location' => $location,

                        'total' =>
                            $items->count(),

                        'lta' =>
                            $items
                                ->where(
                                    'type_of_incident',
                                    'LTA'
                                )
                                ->count(),

                        'mta' =>
                            $items
                                ->where(
                                    'type_of_incident',
                                    'MTA'
                                )
                                ->count(),

                        'faa' =>
                            $items
                                ->where(
                                    'type_of_incident',
                                    'FAA'
                                )
                                ->count(),

                        'lost_days' =>
                            (int) $items
                                ->sum(
                                    fn ($incident) =>
                                        (int) (
                                            $incident
                                                ->working_days_lost
                                            ?? 0
                                        )
                                ),
                    ];
                }
            )
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    protected function filterOptions(): array
    {
        /*
         * Opcije se uzimaju samo iz aktivnih zapisa
         * organizacije kojoj korisnik pripada.
         */
        $query = IncidentResource::getEloquentQuery()
            ->withoutTrashed();

        $years = (clone $query)
            ->whereNotNull('date_occurred')
            ->selectRaw(
                'YEAR(date_occurred) as year'
            )
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->mapWithKeys(
                fn ($year) => [
                    (string) $year =>
                        (string) $year,
                ]
            )
            ->all();

        $currentYear = (string) now()->year;

        $years[$currentYear] =
            $currentYear;

        krsort($years);

        return [
            'years' => $years,

            'locations' =>
                $this->distinctOptions(
                    clone $query,
                    'location'
                ),

            'causes' =>
                $this->distinctOptions(
                    clone $query,
                    'causes_of_injury'
                ),

            'injury_types' =>
                $this->distinctOptions(
                    clone $query,
                    'accident_injury_type'
                ),

            'body_parts' =>
                $this->distinctOptions(
                    clone $query,
                    'injured_body_part'
                ),
        ];
    }

    protected function distinctOptions(
        Builder $query,
        string $column
    ): array {
        return $query
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->orderBy($column)
            ->pluck($column)
            ->map(
                fn ($value): string =>
                    trim((string) $value)
            )
            ->filter()
            ->unique()
            ->values()
            ->mapWithKeys(
                fn (string $value): array => [
                    $value => $value,
                ]
            )
            ->all();
    }
}
<?php

namespace App\Services;

use App\Filament\Resources\Inspections\InspectionResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InspectionZoneReportService
{
    public function report(array $filters = []): array
    {
        $inspections = $this->filteredQuery($filters)
            ->with([
                'zones',
            ])
            ->orderBy('performed_at')
            ->get();

        /*
         * Jedan red = jedna zona jednog 5S nadzora.
         */
        $rows = $inspections
            ->flatMap(function ($inspection) {
                return $inspection->zones->map(
                    function ($zone) use ($inspection): array {
                        return [
                            'inspection_id' =>
                                $inspection->id,

                            'number' =>
                                $inspection->number,

                            'title' =>
                                $inspection->title,

                            'location' =>
                                $inspection->location,

                            'performed_at' =>
                                $inspection->performed_at,

                            'zone_id' =>
                                $zone->id,

                            'zone' =>
                                trim((string) $zone->name),

                            'total_points' =>
                                (float) (
                                    $zone->total_points
                                    ?? 0
                                ),

                            'max_points' =>
                                (float) (
                                    $zone->max_points
                                    ?? 0
                                ),

                            'percentage' =>
                                (float) (
                                    $zone->percentage
                                    ?? 0
                                ),
                        ];
                    }
                );
            })
            ->values();

        /*
         * Ako je odabrana određena zona,
         * prikazujemo samo nju.
         */
        $selectedZone =
            $filters['zone'] ?? null;

        if (filled($selectedZone)) {
            $rows = $rows
                ->where('zone', $selectedZone)
                ->values();
        }

        $zoneSummary =
            $this->zoneSummary($rows);

        $history =
            $this->history(
                $inspections,
                $rows
            );

        $overallScores = $inspections
            ->map(
                fn ($inspection) =>
                    $inspection
                        ->calculateFiveSScore()
            )
            ->filter(
                fn ($value) =>
                    $value !== null
            )
            ->map(
                fn ($value): float =>
                    (float) $value
            )
            ->values();

        $latestOverall =
            $overallScores->last();

        $previousOverall =
            $overallScores->count() >= 2
                ? $overallScores[
                    $overallScores->count() - 2
                ]
                : null;

        $overallChange =
            $latestOverall !== null
            && $previousOverall !== null
                ? round(
                    $latestOverall
                    - $previousOverall,
                    1
                )
                : null;

        return [
            'summary' => [
                'inspections' =>
                    $inspections->count(),

                'average' =>
                    $overallScores->isNotEmpty()
                        ? round(
                            $overallScores->average(),
                            1
                        )
                        : 0,

                'latest' =>
                    $latestOverall,

                'previous' =>
                    $previousOverall,

                'change' =>
                    $overallChange,
            ],

            'zones' =>
                $zoneSummary,

            'history' =>
                $history,

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
         * VAŽNO:
         * koristimo InspectionResource query
         * kako bi ostao organization / tenant scope.
         */
        $query =
            InspectionResource::getEloquentQuery()
                ->whereHas('zones');

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

        return $query;
    }

    protected function zoneSummary(
        Collection $rows
    ): array {
        return $rows
            ->filter(
                fn (array $row): bool =>
                    filled($row['zone'])
            )
            ->groupBy('zone')
            ->map(
                function (
                    Collection $items,
                    string $zone
                ): array {
                    $items = $items
                        ->sortBy('performed_at')
                        ->values();

                    $latest =
                        $items->last();

                    $previous =
                        $items->count() >= 2
                            ? $items[
                                $items->count() - 2
                            ]
                            : null;

                    $latestPercentage =
                        (float) (
                            $latest[
                                'percentage'
                            ]
                            ?? 0
                        );

                    $previousPercentage =
                        $previous
                            ? (float) (
                                $previous[
                                    'percentage'
                                ]
                                ?? 0
                            )
                            : null;

                    $change =
                        $previousPercentage
                        !== null
                            ? round(
                                $latestPercentage
                                - $previousPercentage,
                                1
                            )
                            : null;

                    return [
                        'zone' =>
                            $zone,

                        'count' =>
                            $items->count(),

                        'latest' =>
                            round(
                                $latestPercentage,
                                1
                            ),

                        'previous' =>
                            $previousPercentage
                            !== null
                                ? round(
                                    $previousPercentage,
                                    1
                                )
                                : null,

                        'change' =>
                            $change,

                        'average' =>
                            round(
                                $items->avg(
                                    'percentage'
                                ),
                                1
                            ),

                        'best' =>
                            round(
                                $items->max(
                                    'percentage'
                                ),
                                1
                            ),

                        'worst' =>
                            round(
                                $items->min(
                                    'percentage'
                                ),
                                1
                            ),
                    ];
                }
            )
            ->sortKeys()
            ->values()
            ->all();
    }

    protected function history(
        Collection $inspections,
        Collection $rows
    ): array {
        return $inspections
            ->sortByDesc('performed_at')
            ->map(
                function (
                    $inspection
                ) use ($rows): array {
                    $zoneRows =
                        $rows->where(
                            'inspection_id',
                            $inspection->id
                        );

                    return [
                        'id' =>
                            $inspection->id,

                        'number' =>
                            $inspection->number,

                        'title' =>
                            $inspection->title,

                        'location' =>
                            $inspection->location,

                        'performed_at' =>
                            $inspection->performed_at,

                        'overall' =>
                            $inspection
                                ->calculateFiveSScore(),

                        'zones' =>
                            $zoneRows
                                ->mapWithKeys(
                                    fn (
                                        array $row
                                    ): array => [
                                        $row['zone'] =>
                                            round(
                                                (float) $row[
                                                    'percentage'
                                                ],
                                                1
                                            ),
                                    ]
                                )
                                ->all(),
                    ];
                }
            )
            ->values()
            ->all();
    }

    protected function filterOptions(): array
    {
        $query =
            InspectionResource::getEloquentQuery()
                ->whereHas('zones');

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
                ->pluck('location')
                ->mapWithKeys(
                    fn ($value): array => [
                        (string) $value =>
                            (string) $value,
                    ]
                )
                ->all();

        /*
         * Zone dobivamo kroz nadzore
         * organizacije, pa tenant scope ostaje.
         */
        $zones = (clone $query)
            ->with('zones')
            ->get()
            ->flatMap(
                fn ($inspection) =>
                    $inspection->zones
                        ->pluck('name')
            )
            ->map(
                fn ($name): string =>
                    trim((string) $name)
            )
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->mapWithKeys(
                fn (string $name): array => [
                    $name => $name,
                ]
            )
            ->all();

        return [
            'years' => $years,
            'locations' => $locations,
            'zones' => $zones,
        ];
    }
}
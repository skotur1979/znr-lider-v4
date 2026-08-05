<?php

namespace App\Services;

use App\Models\Observation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ObservationReportService
{
    public function report(array $filters = []): array
    {
        $query = $this->baseQuery($filters);

        $records = (clone $query)
            ->orderBy('incident_date')
            ->get();

        return [
            'summary' => $this->summary($records),
            'monthly' => $this->monthly($records),
            'types' => $this->groupCount($records, 'observation_type'),
            'priorities' => $this->groupCount($records, 'priority'),
            'statuses' => $this->groupCount($records, 'status'),

            'topHazards' => $this->topValues(
                $records,
                'potential_incident_type'
            ),

            'topLocations' => $this->topValues(
                $records,
                'location'
            ),

            'topResponsibleOpen' => $this->topResponsibleOpen($records),

            'averageClosingByMonth' => $this->averageClosingByMonth($records),

            'availableYears' => $this->availableYears(),
            'availableLocations' => $this->availableOptions('location'),
            'availableResponsible' => $this->availableOptions('responsible'),
            'availableHazards' => $this->availableOptions(
                'potential_incident_type'
            ),
        ];
    }

    protected function baseQuery(array $filters): Builder
    {
        $query = Observation::query()
            ->withoutTrashed();

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->isSuperAdmin()) {
            $ownerId = $user->ownerId();

            if (! $ownerId) {
                return $query->whereRaw('1 = 0');
            }

            $query->where('user_id', $ownerId);
        }

        $year = $filters['year'] ?? null;
        $month = $filters['month'] ?? null;
        $location = $filters['location'] ?? null;
        $responsible = $filters['responsible'] ?? null;
        $priority = $filters['priority'] ?? null;
        $status = $filters['status'] ?? null;
        $type = $filters['type'] ?? null;
        $hazard = $filters['hazard'] ?? null;

        if (filled($year) && $year !== 'all') {
            $query->whereYear('incident_date', (int) $year);
        }

        if (filled($month) && $month !== 'all') {
            $query->whereMonth('incident_date', (int) $month);
        }

        if (filled($location)) {
            $query->where('location', $location);
        }

        if (filled($responsible)) {
            $query->where('responsible', $responsible);
        }

        if (filled($priority)) {
            $query->where('priority', $priority);
        }

        if (filled($status)) {
            $query->where('status', $status);
        }

        if (filled($type)) {
            $query->where('observation_type', $type);
        }

        if (filled($hazard)) {
            $query->where('potential_incident_type', $hazard);
        }

        return $query;
    }

    protected function summary(Collection $records): array
    {
        $today = Carbon::today();

        $open = $records->whereIn(
            'status',
            ['Not started', 'In progress']
        );

        $expired = $open->filter(function (Observation $record) use ($today) {
            if (blank($record->target_date)) {
                return false;
            }

            return Carbon::parse($record->target_date)
                ->startOfDay()
                ->lt($today);
        });

        $expiring = $open->filter(function (Observation $record) use ($today) {
            if (blank($record->target_date)) {
                return false;
            }

            $date = Carbon::parse($record->target_date)->startOfDay();

            return $date->gte($today)
                && $date->lte($today->copy()->addDays(30));
        });

        $completed = $records->where('status', 'Complete');

        $averageClosingDays = $completed
            ->map(function (Observation $record): ?int {
                if (! $record->incident_date || ! $record->completed_at) {
                    return null;
                }

                return Carbon::parse($record->incident_date)
                    ->copy()
                    ->startOfDay()
                    ->diffInDays(
                        $record->completed_at
                            ->copy()
                            ->startOfDay()
                    );
            })
            ->filter(fn ($value) => $value !== null)
            ->avg();

        return [
            'total' => $records->count(),

            'nearMiss' => $records
                ->where('observation_type', 'Near Miss')
                ->count(),

            'negative' => $records
                ->where('observation_type', 'Negative Observation')
                ->count(),

            'positive' => $records
                ->where('observation_type', 'Positive Observation')
                ->count(),

            'notStarted' => $records
                ->where('status', 'Not started')
                ->count(),

            'inProgress' => $records
                ->where('status', 'In progress')
                ->count(),

            'completed' => $completed->count(),

            'expired' => $expired->count(),

            'expiring' => $expiring->count(),

            'withoutDeadline' => $open
                ->filter(fn (Observation $record) => blank($record->target_date))
                ->count(),

            'averageClosingDays' => $averageClosingDays !== null
                ? round((float) $averageClosingDays, 1)
                : null,
        ];
    }

    protected function monthly(Collection $records): array
    {
        $months = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthRecords = $records->filter(function (Observation $record) use ($month) {
                return filled($record->incident_date)
                    && Carbon::parse($record->incident_date)->month === $month;
            });

            $months[$month] = [
                'month' => $month,
                'label' => $this->monthLabel($month),
                'total' => $monthRecords->count(),

                'near_miss' => $monthRecords
                    ->where('observation_type', 'Near Miss')
                    ->count(),

                'negative' => $monthRecords
                    ->where('observation_type', 'Negative Observation')
                    ->count(),

                'positive' => $monthRecords
                    ->where('observation_type', 'Positive Observation')
                    ->count(),

                'not_started' => $monthRecords
                    ->where('status', 'Not started')
                    ->count(),

                'in_progress' => $monthRecords
                    ->where('status', 'In progress')
                    ->count(),

                'completed' => $monthRecords
                    ->where('status', 'Complete')
                    ->count(),
            ];
        }

        return $months;
    }

    protected function groupCount(
        Collection $records,
        string $column
    ): array {
        return $records
            ->filter(fn (Observation $record) => filled($record->{$column}))
            ->groupBy($column)
            ->map(fn (Collection $rows, string $key) => [
                'key' => $key,
                'label' => $this->translateValue($column, $key),
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    protected function topValues(
        Collection $records,
        string $column,
        int $limit = 10
    ): array {
        return $records
            ->filter(fn (Observation $record) => filled($record->{$column}))
            ->groupBy($column)
            ->map(fn (Collection $rows, string $key) => [
                'label' => $key,
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    protected function topResponsibleOpen(Collection $records): array
    {
        return $records
            ->filter(function (Observation $record) {
                return filled($record->responsible)
                    && in_array(
                        $record->status,
                        ['Not started', 'In progress'],
                        true
                    );
            })
            ->groupBy('responsible')
            ->map(function (Collection $rows, string $responsible) {
                $today = Carbon::today();

                $expired = $rows->filter(function (Observation $record) use ($today) {
                    return filled($record->target_date)
                        && Carbon::parse($record->target_date)
                            ->startOfDay()
                            ->lt($today);
                })->count();

                return [
                    'responsible' => $responsible,
                    'open' => $rows->count(),
                    'not_started' => $rows
                        ->where('status', 'Not started')
                        ->count(),
                    'in_progress' => $rows
                        ->where('status', 'In progress')
                        ->count(),
                    'expired' => $expired,
                ];
            })
            ->sortByDesc('open')
            ->take(10)
            ->values()
            ->all();
    }

    protected function averageClosingByMonth(Collection $records): array
    {
        $rows = [];

        for ($month = 1; $month <= 12; $month++) {
            $completed = $records
                ->filter(function (Observation $record) use ($month) {
                    return $record->status === 'Complete'
                        && filled($record->incident_date)
                        && Carbon::parse($record->incident_date)->month === $month;
                });

            $average = $completed
                ->map(function (Observation $record): ?int {
                    if (! $record->incident_date || ! $record->completed_at) {
                        return null;
                    }

                    return Carbon::parse($record->incident_date)
                        ->copy()
                        ->startOfDay()
                        ->diffInDays(
                            $record->completed_at
                                ->copy()
                                ->startOfDay()
                        );
                })
                ->filter(fn ($value) => $value !== null)
                ->avg();

            $rows[$month] = [
                'month' => $month,
                'label' => $this->monthLabel($month),
                'completed' => $completed->count(),
                'average_days' => $average !== null
                    ? round((float) $average, 1)
                    : null,
            ];
        }

        return $rows;
    }

    protected function availableYears(): array
    {
        $query = $this->scopeOptionsQuery();

        return $query
            ->whereNotNull('incident_date')
            ->selectRaw('YEAR(incident_date) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year', 'year')
            ->mapWithKeys(
                fn ($year) => [(string) $year => (string) $year]
            )
            ->toArray();
    }

    protected function availableOptions(string $column): array
    {
        return $this->scopeOptionsQuery()
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->toArray();
    }

    protected function scopeOptionsQuery(): Builder
    {
        $query = Observation::query()->withoutTrashed();

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->isSuperAdmin()) {
            $ownerId = $user->ownerId();

            if (! $ownerId) {
                return $query->whereRaw('1 = 0');
            }

            $query->where('user_id', $ownerId);
        }

        return $query;
    }

    protected function translateValue(
        string $column,
        string $value
    ): string {
        return match ($column) {
            'observation_type' => match ($value) {
                'Near Miss' => 'NM – Skoro nezgoda',
                'Negative Observation' => 'Negativno zapažanje',
                'Positive Observation' => 'Pozitivno zapažanje',
                default => $value,
            },

            'priority' => match ($value) {
                'low' => 'Nisko',
                'medium' => 'Srednje',
                'high' => 'Visoko',
                'critical' => 'Kritično',
                default => $value,
            },

            'status' => match ($value) {
                'Not started' => 'Nije započeto',
                'In progress' => 'U tijeku',
                'Complete' => 'Završeno',
                default => $value,
            },

            default => $value,
        };
    }

    protected function monthLabel(int $month): string
    {
        return match ($month) {
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
        };
    }
}
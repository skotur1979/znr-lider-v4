<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Inspection;
use App\Models\InspectionFinding;
use App\Models\Kpi;
use App\Models\KpiValue;
use App\Models\Observation;
use App\Models\OntoEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class KpiCalculationService
{
    public static function automaticSourceKeys(): array
    {
        return [
            'days_without_lta',
            'lta_count',
            'lta_lost_days',
            'near_miss_count',
            'negative_observation_count',
            'inspection_count',
            'corrective_actions_open',
            'corrective_actions_closed',
            'corrective_actions_in_progress',
            'corrective_actions_delay_days',
            'non_hazardous_waste_kg',
            'hazardous_waste_kg',
            'municipal_waste_kg',
            'afr',
            'asr',
        ];
    }

    public function generateForMonth(int $month, int $year): void
    {
        $this->baseKpiQuery()
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereIn('calculation_type', ['automatic', 'formula'])
                    ->orWhereIn('source_key', self::automaticSourceKeys());
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->each(function (Kpi $kpi) use ($month, $year) {
                $value = $this->calculateSingle($kpi, $month, $year);

                if ($value === null) {
                    return;
                }

                KpiValue::updateOrCreate(
                    [
                        'kpi_id' => $kpi->id,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'value' => $value,
                        'auto_generated' => true,
                        'source_label' => $this->sourceLabel($kpi),
                        'note' => null,
                    ]
                );
            });
    }

    public function calculateSingle(Kpi $kpi, int $month, int $year): ?float
    {
        return match ($kpi->source_key) {
            'days_without_lta' => $this->daysWithoutLta($month, $year),
            'lta_count' => $this->ltaCount($month, $year),
            'lta_lost_days' => $this->ltaLostDays($month, $year),
            'near_miss_count' => $this->nearMissCount($month, $year),
            'negative_observation_count' => $this->negativeObservationCount($month, $year),
            'inspection_count' => $this->inspectionCount($month, $year),
            'corrective_actions_open' => $this->correctiveActionCount($month, $year, ['open']),
            'corrective_actions_closed' => $this->correctiveActionCount($month, $year, ['closed', 'resolved_no_action', 'converted_to_observation']),
            'corrective_actions_in_progress' => $this->correctiveActionCount($month, $year, ['in_progress']),
            'corrective_actions_delay_days' => $this->correctiveActionDelayDays($month, $year),
            'non_hazardous_waste_kg' => $this->nonHazardousWasteKg($month, $year),
            'hazardous_waste_kg' => $this->hazardousWasteKg($month, $year),
            'municipal_waste_kg' => $this->municipalWasteKg($month, $year),
            'afr' => $this->calculateAfr($month, $year),
            'asr' => $this->calculateAsr($month, $year),
            default => null,
        };
    }

    public function sourceLabel(Kpi $kpi): string
    {
        return match ($kpi->source_key) {
            'days_without_lta', 'lta_count', 'lta_lost_days' => 'Incidenti',
            'near_miss_count', 'negative_observation_count' => 'Zapažanja',
            'inspection_count' => 'Nadzori',
            'corrective_actions_open', 'corrective_actions_closed', 'corrective_actions_in_progress', 'corrective_actions_delay_days' => 'Nalazi nadzora',
            'non_hazardous_waste_kg', 'hazardous_waste_kg', 'municipal_waste_kg' => 'ONTO',
            'afr' => 'Formula AFR',
            'asr' => 'Formula ASR',
            default => 'Ručno',
        };
    }

    public function dashboardGroups(
        int $month,
        int $year,
        ?int $compareMonth = null,
        ?int $compareYear = null
    ): Collection {
        return $this->baseKpiQuery()
            ->where('is_active', true)
            ->where('show_on_dashboard', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Kpi $kpi) use ($month, $year, $compareMonth, $compareYear) {
                $current = $kpi->valueFor($month, $year);

                $compare = null;
                if ($compareMonth !== null && $compareYear !== null) {
                    $compare = $kpi->valueFor($compareMonth, $compareYear);
                }

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'current_value' => $current?->value,
                    'compare_value' => $compare?->value,
                    'formatted_current' => $kpi->formatNumberOnly($current?->value),
                    'formatted_compare' => $kpi->formatNumberOnly($compare?->value),
                    'formatted_target' => $kpi->formatNumberOnly($kpi->target_value),
                    'status' => $kpi->evaluateStatus($current?->value),
                    'delta' => $this->delta($current?->value, $compare?->value),
                ];
            })
            ->groupBy('category');
    }

    public function dashboardGroupsYearly(
        int $year,
        ?int $compareYear = null
    ): Collection {
        return $this->baseKpiQuery()
            ->where('is_active', true)
            ->where('show_on_dashboard', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Kpi $kpi) use ($year, $compareYear) {
                $currentValues = collect(range(1, 12))
                    ->map(fn (int $month) => $kpi->valueFor($month, $year)?->value)
                    ->filter(fn ($value) => $value !== null);

                $current = $currentValues->isNotEmpty()
                    ? round((float) $currentValues->sum(), 2)
                    : null;

                $compare = null;

                if ($compareYear !== null) {
                    $compareValues = collect(range(1, 12))
                        ->map(fn (int $month) => $kpi->valueFor($month, $compareYear)?->value)
                        ->filter(fn ($value) => $value !== null);

                    $compare = $compareValues->isNotEmpty()
                        ? round((float) $compareValues->sum(), 2)
                        : null;
                }

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'current_value' => $current,
                    'compare_value' => $compare,
                    'formatted_current' => $kpi->formatNumberOnly($current),
                    'formatted_compare' => $kpi->formatNumberOnly($compare),
                    'formatted_target' => $kpi->formatNumberOnly($kpi->target_value),
                    'status' => $kpi->evaluateStatus($current),
                    'delta' => $this->delta($current, $compare),
                ];
            })
            ->groupBy('category');
    }

    public function yearlyReportGrouped(int $year): Collection
    {
        return $this->baseKpiQuery()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Kpi $kpi) use ($year) {
                $values = collect(range(1, 12))
                    ->mapWithKeys(fn (int $month) => [$month => $kpi->valueFor($month, $year)?->value]);

                $statuses = collect(range(1, 12))
                    ->mapWithKeys(fn (int $month) => [$month => $kpi->evaluateStatus($kpi->valueFor($month, $year)?->value)]);

                $total = $values->filter(fn ($value) => $value !== null)->sum();
                $average = $values->filter(fn ($value) => $value !== null)->avg();

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'formatted_target' => $kpi->formatNumberOnly($kpi->target_value),
                    'values' => $values,
                    'statuses' => $statuses,
                    'total' => $total,
                    'average' => $average,
                ];
            })
            ->groupBy('category');
    }

    protected function delta(?float $current, ?float $compare): ?float
    {
        if ($current === null || $compare === null) {
            return null;
        }

        return round($current - $compare, 2);
    }

    protected function baseKpiQuery(): Builder
    {
        $query = Kpi::query();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereNull('user_id');
        });
    }

    protected function userScopedQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query;
        }

        if (Schema::hasColumn($query->getModel()->getTable(), 'user_id')) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }

    protected function dateRange(int $month, int $year): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = Carbon::create($year, $month, 1)->endOfMonth();

        return [$start, $end];
    }

    protected function daysWithoutLta(int $month, int $year): ?float
    {
        if (! class_exists(Incident::class) || ! Schema::hasTable('incidents')) {
            return null;
        }

        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();

        $lastLta = $this->userScopedQuery(Incident::query())
            ->where('type_of_incident', 'LTA')
            ->whereDate('date_occurred', '<=', $periodEnd->toDateString())
            ->orderByDesc('date_occurred')
            ->first();

        if (! $lastLta?->date_occurred) {
            return null;
        }

        return (float) Carbon::parse($lastLta->date_occurred)->diffInDays($periodEnd);
    }

    protected function ltaCount(int $month, int $year): ?float
    {
        if (! class_exists(Incident::class) || ! Schema::hasTable('incidents')) {
            return null;
        }

        [$start, $end] = $this->dateRange($month, $year);

        return (float) $this->userScopedQuery(Incident::query())
            ->where('type_of_incident', 'LTA')
            ->whereBetween('date_occurred', [$start->toDateString(), $end->toDateString()])
            ->count();
    }

    protected function ltaLostDays(int $month, int $year): ?float
    {
        if (! class_exists(Incident::class) || ! Schema::hasTable('incidents')) {
            return null;
        }

        [$start, $end] = $this->dateRange($month, $year);

        return (float) $this->userScopedQuery(Incident::query())
            ->where('type_of_incident', 'LTA')
            ->whereBetween('date_occurred', [$start->toDateString(), $end->toDateString()])
            ->sum('working_days_lost');
    }

    protected function nearMissCount(int $month, int $year): ?float
    {
        if (! class_exists(Observation::class) || ! Schema::hasTable('observations')) {
            return null;
        }

        [$start, $end] = $this->dateRange($month, $year);

        return (float) $this->userScopedQuery(Observation::query())
            ->whereBetween('incident_date', [$start->toDateString(), $end->toDateString()])
            ->where('observation_type', 'near_miss')
            ->count();
    }

    protected function negativeObservationCount(int $month, int $year): ?float
    {
        if (! class_exists(Observation::class) || ! Schema::hasTable('observations')) {
            return null;
        }

        [$start, $end] = $this->dateRange($month, $year);

        return (float) $this->userScopedQuery(Observation::query())
            ->whereBetween('incident_date', [$start->toDateString(), $end->toDateString()])
            ->where('observation_type', 'negative')
            ->count();
    }

    protected function inspectionCount(int $month, int $year): ?float
    {
        if (! class_exists(Inspection::class) || ! Schema::hasTable('inspections')) {
            return null;
        }

        [$start, $end] = $this->dateRange($month, $year);

        return (float) $this->userScopedQuery(Inspection::query())
            ->whereBetween('performed_at', [$start->toDateString(), $end->toDateString()])
            ->count();
    }

    protected function correctiveActionCount(int $month, int $year, array $statuses): ?float
    {
        if (! class_exists(InspectionFinding::class) || ! Schema::hasTable('inspection_findings')) {
            return null;
        }

        [$start, $end] = $this->dateRange($month, $year);

        return (float) $this->userScopedQuery(InspectionFinding::query())
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('workflow_status', $statuses)
            ->count();
    }

    protected function correctiveActionDelayDays(int $month, int $year): ?float
    {
        if (! class_exists(InspectionFinding::class) || ! Schema::hasTable('inspection_findings')) {
            return null;
        }

        [$start, $end] = $this->dateRange($month, $year);

        $records = $this->userScopedQuery(InspectionFinding::query())
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('due_date')
            ->get(['due_date', 'workflow_status']);

        $delay = 0;

        foreach ($records as $record) {
            if (in_array($record->workflow_status, ['closed', 'resolved_no_action', 'converted_to_observation'], true)) {
                continue;
            }

            $due = Carbon::parse($record->due_date);

            if ($due->isPast()) {
                $delay += $due->diffInDays(now());
            }
        }

        return (float) $delay;
    }

    protected function nonHazardousWasteKg(int $month, int $year): ?float
    {
        return $this->sumOntoWasteOutput($month, $year, function (string $normalizedCode, bool $hasStar) {
            return ! $hasStar && $normalizedCode !== '200301';
        });
    }

    protected function hazardousWasteKg(int $month, int $year): ?float
    {
        return $this->sumOntoWasteOutput($month, $year, function (string $normalizedCode, bool $hasStar) {
            return $hasStar;
        });
    }

    protected function municipalWasteKg(int $month, int $year): ?float
    {
        return $this->sumOntoWasteOutput($month, $year, function (string $normalizedCode, bool $hasStar) {
            return $normalizedCode === '200301';
        });
    }

    protected function sumOntoWasteOutput(int $month, int $year, callable $filter): ?float
    {
        if (! class_exists(OntoEntry::class) || ! Schema::hasTable('onto_entries')) {
            return null;
        }

        [$start, $end] = $this->dateRange($month, $year);

        $entries = $this->userScopedQuery(
            OntoEntry::query()->with(['ontoRecord.wasteType'])
        )
            ->whereDate('entry_date', '>=', $start->toDateString())
            ->whereDate('entry_date', '<=', $end->toDateString())
            ->get();

        $sum = 0.0;

        foreach ($entries as $entry) {
            $code = (string) ($entry->ontoRecord?->wasteType?->waste_code ?? '');
            $normalizedCode = preg_replace('/[^0-9]/', '', $code);
            $hasStar = str_contains($code, '*');

            if ($filter($normalizedCode, $hasStar)) {
                $sum += (float) ($entry->output_kg ?: 0);
            }
        }

        return round($sum, 2);
    }

    protected function manualLinkedValue(string $kpiName, int $month, int $year): ?float
    {
        $kpi = $this->baseKpiQuery()->where('name', $kpiName)->first();

        if (! $kpi) {
            return null;
        }

        return $kpi->valueFor($month, $year)?->value;
    }

    protected function calculateAfr(int $month, int $year): ?float
    {
        $lta = $this->ltaCount($month, $year);
        $hours = $this->manualLinkedValue('Ukupan broj odrađenih radnih sati', $month, $year);

        if ($lta === null || $hours === null || $hours <= 0) {
            return null;
        }

        return round(($lta * 1000000) / $hours, 4);
    }

    protected function calculateAsr(int $month, int $year): ?float
    {
        $lostDays = $this->ltaLostDays($month, $year);
        $hours = $this->manualLinkedValue('Ukupan broj odrađenih radnih sati', $month, $year);

        if ($lostDays === null || $hours === null || $hours <= 0) {
            return null;
        }

        return round(($lostDays * 1000000) / $hours, 4);
    }
}
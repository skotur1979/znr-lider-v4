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
    /**
     * Kada servis radi iz cron/console konteksta,
     * nema prijavljenog korisnika.
     *
     * Tada ovdje privremeno postavljamo organizaciju
     * za koju se KPI računa.
     */
    protected ?int $forcedOwnerId = null;

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

    protected function currentUser()
    {
        return Auth::user();
    }

    protected function isSuperAdmin(): bool
    {
        return $this->currentUser()?->isSuperAdmin() === true;
    }

    /**
     * Vraća organizacijskog ownera.
     *
     * Prioritet:
     * 1. eksplicitni owner iz cron-a
     * 2. ownerId prijavljenog organizacijskog korisnika
     *
     * Superadmin nema vlastite KPI vrijednosti.
     */
    protected function resolveOwnerId(): ?int
    {
        if ($this->forcedOwnerId) {
            return $this->forcedOwnerId;
        }

        $user = $this->currentUser();

        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->ownerId();
    }

    /**
     * Koristi se iz cron/Artisan command konteksta.
     *
     * Svaka organizacija obrađuje se zasebno.
     */
    public function generateForOwner(
        int $ownerId,
        int $month,
        int $year
    ): array {
        if ($ownerId <= 0) {
            return [
                'generated' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total' => 0,
            ];
        }

        $previousOwnerId = $this->forcedOwnerId;

        $this->forcedOwnerId = $ownerId;

        try {
            return $this->generateForMonth(
                $month,
                $year
            );
        } finally {
            /*
             * Obavezno vratimo prethodno stanje kako isti
             * servis ne bi zadržao organizaciju između poziva.
             */
            $this->forcedOwnerId = $previousOwnerId;
        }
    }

    /**
     * Generira automatske KPI vrijednosti
     * za trenutačnu organizaciju.
     *
     * Web:
     * ownerId dolazi iz prijavljenog korisnika.
     *
     * Cron:
     * ownerId dolazi preko generateForOwner().
     */
    public function generateForMonth(
        int $month,
        int $year
    ): array {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return [
                'generated' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total' => 0,
            ];
        }

        $generated = 0;
        $updated = 0;
        $skipped = 0;

        $this->baseKpiQuery()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereIn(
                        'calculation_type',
                        [
                            'automatic',
                            'formula',
                        ]
                    )
                    ->orWhereIn(
                        'source_key',
                        self::automaticSourceKeys()
                    );
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->each(function (Kpi $kpi) use (
                $month,
                $year,
                $ownerId,
                &$generated,
                &$updated,
                &$skipped
            ): void {
                $value = $this->calculateSingle(
                    $kpi,
                    $month,
                    $year
                );

                if ($value === null) {
                    $skipped++;

                    return;
                }

                $existing = KpiValue::query()
                    ->where('kpi_id', $kpi->id)
                    ->where('user_id', $ownerId)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

                KpiValue::updateOrCreate(
                    [
                        'kpi_id' => $kpi->id,
                        'user_id' => $ownerId,
                        'month' => $month,
                        'year' => $year,
                    ],
                    [
                        'value' => $value,
                        'auto_generated' => true,
                        'source_label' => $this->sourceLabel($kpi),
                        'note' => 'Automatski ažurirano: '
                            . now()->format('d.m.Y. H:i'),
                    ]
                );

                if ($existing) {
                    $updated++;
                } else {
                    $generated++;
                }
            });

        return [
            'generated' => $generated,
            'updated' => $updated,
            'skipped' => $skipped,
            'total' => $generated + $updated,
        ];
    }

    public function calculateSingle(
        Kpi $kpi,
        int $month,
        int $year
    ): ?float {
        return match ($kpi->source_key) {
            'days_without_lta' =>
                $this->daysWithoutLta(
                    $month,
                    $year
                ),

            'lta_count' =>
                $this->ltaCount(
                    $month,
                    $year
                ),

            'lta_lost_days' =>
                $this->ltaLostDays(
                    $month,
                    $year
                ),

            'near_miss_count' =>
                $this->nearMissCount(
                    $month,
                    $year
                ),

            'negative_observation_count' =>
                $this->negativeObservationCount(
                    $month,
                    $year
                ),

            'inspection_count' =>
                $this->inspectionCount(
                    $month,
                    $year
                ),

            'corrective_actions_open' =>
                $this->correctiveActionCount(
                    $month,
                    $year,
                    ['open']
                ),

            'corrective_actions_closed' =>
                $this->correctiveActionCount(
                    $month,
                    $year,
                    [
                        'closed',
                        'resolved_no_action',
                        'converted_to_observation',
                    ]
                ),

            'corrective_actions_in_progress' =>
                $this->correctiveActionCount(
                    $month,
                    $year,
                    ['in_progress']
                ),

            'corrective_actions_delay_days' =>
                $this->correctiveActionDelayDays(
                    $month,
                    $year
                ),

            'non_hazardous_waste_kg' =>
                $this->nonHazardousWasteKg(
                    $month,
                    $year
                ),

            'hazardous_waste_kg' =>
                $this->hazardousWasteKg(
                    $month,
                    $year
                ),

            'municipal_waste_kg' =>
                $this->municipalWasteKg(
                    $month,
                    $year
                ),

            'afr' =>
                $this->calculateAfr(
                    $month,
                    $year
                ),

            'asr' =>
                $this->calculateAsr(
                    $month,
                    $year
                ),

            default => null,
        };
    }

    public function sourceLabel(Kpi $kpi): string
    {
        return match ($kpi->source_key) {
            'days_without_lta',
            'lta_count',
            'lta_lost_days'
                => 'Incidenti',

            'near_miss_count',
            'negative_observation_count'
                => 'Zapažanja',

            'inspection_count'
                => 'Nadzori',

            'corrective_actions_open',
            'corrective_actions_closed',
            'corrective_actions_in_progress',
            'corrective_actions_delay_days'
                => 'Nalazi nadzora',

            'non_hazardous_waste_kg',
            'hazardous_waste_kg',
            'municipal_waste_kg'
                => 'ONTO',

            'afr'
                => 'Formula AFR',

            'asr'
                => 'Formula ASR',

            default
                => 'Ručno',
        };
    }

    public function dashboardGroups(
        int $month,
        int $year,
        ?int $compareMonth = null,
        ?int $compareYear = null
    ): Collection {
        $ownerId = $this->resolveOwnerId();

        return $this->baseKpiQuery()
            ->where('is_active', true)
            ->where('show_on_dashboard', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Kpi $kpi) use (
                $month,
                $year,
                $compareMonth,
                $compareYear,
                $ownerId
            ): array {
                $current = $ownerId
                    ? $kpi->valueFor(
                        $month,
                        $year,
                        $ownerId
                    )
                    : null;

                $compare = null;

                if (
                    $ownerId
                    && $compareMonth !== null
                    && $compareYear !== null
                ) {
                    $compare = $kpi->valueFor(
                        $compareMonth,
                        $compareYear,
                        $ownerId
                    );
                }

                $target = $kpi
                    ->effectiveTargetValueForPeriod(
                        $month,
                        $year,
                        $ownerId
                    );

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'is_global' => is_null($kpi->user_id),

                    'current_value' => $current?->value,
                    'compare_value' => $compare?->value,

                    'formatted_current' =>
                        $kpi->formatNumberOnly(
                            $current?->value
                        ),

                    'formatted_compare' =>
                        $kpi->formatNumberOnly(
                            $compare?->value
                        ),

                    'formatted_target' =>
                        $kpi->formatNumberOnly(
                            $target
                        ),

                    'status' =>
                        $kpi->evaluateStatusForPeriod(
                            $current?->value,
                            $month,
                            $year,
                            $ownerId
                        ),

                    'delta' =>
                        $this->delta(
                            $current?->value,
                            $compare?->value
                        ),
                ];
            })
            ->groupBy('category');
    }

    public function dashboardGroupsYearly(
        int $year,
        ?int $compareYear = null
    ): Collection {
        $ownerId = $this->resolveOwnerId();

        return $this->baseKpiQuery()
            ->where('is_active', true)
            ->where('show_on_dashboard', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Kpi $kpi) use (
                $year,
                $compareYear,
                $ownerId
            ): array {
                $currentValues = collect(range(1, 12))
                    ->map(
                        fn (int $month) =>
                            $ownerId
                                ? $kpi->valueFor(
                                    $month,
                                    $year,
                                    $ownerId
                                )?->value
                                : null
                    )
                    ->filter(
                        fn ($value) =>
                            $value !== null
                    );

                $current = $currentValues->isNotEmpty()
                    ? round(
                        (float) $currentValues->sum(),
                        2
                    )
                    : null;

                $compare = null;

                if (
                    $ownerId
                    && $compareYear !== null
                ) {
                    $compareValues = collect(range(1, 12))
                        ->map(
                            fn (int $month) =>
                                $kpi->valueFor(
                                    $month,
                                    $compareYear,
                                    $ownerId
                                )?->value
                        )
                        ->filter(
                            fn ($value) =>
                                $value !== null
                        );

                    $compare = $compareValues->isNotEmpty()
                        ? round(
                            (float) $compareValues->sum(),
                            2
                        )
                        : null;
                }

                $target = $kpi
                    ->effectiveTargetValueForPeriod(
                        12,
                        $year,
                        $ownerId
                    );

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'is_global' => is_null($kpi->user_id),

                    'current_value' => $current,
                    'compare_value' => $compare,

                    'formatted_current' =>
                        $kpi->formatNumberOnly(
                            $current
                        ),

                    'formatted_compare' =>
                        $kpi->formatNumberOnly(
                            $compare
                        ),

                    'formatted_target' =>
                        $kpi->formatNumberOnly(
                            $target
                        ),

                    'status' =>
                        $kpi->evaluateStatusForPeriod(
                            $current,
                            12,
                            $year,
                            $ownerId
                        ),

                    'delta' =>
                        $this->delta(
                            $current,
                            $compare
                        ),
                ];
            })
            ->groupBy('category');
    }

    public function yearlyReportGrouped(
        int $year
    ): Collection {
        $ownerId = $this->resolveOwnerId();

        return $this->baseKpiQuery()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Kpi $kpi) use (
                $year,
                $ownerId
            ): array {
                $values = collect(range(1, 12))
                    ->mapWithKeys(
                        fn (int $month) => [
                            $month => $ownerId
                                ? $kpi->valueFor(
                                    $month,
                                    $year,
                                    $ownerId
                                )?->value
                                : null,
                        ]
                    );

                $statuses = collect(range(1, 12))
                    ->mapWithKeys(
                        function (int $month) use (
                            $kpi,
                            $year,
                            $ownerId
                        ): array {
                            $value = $ownerId
                                ? $kpi->valueFor(
                                    $month,
                                    $year,
                                    $ownerId
                                )?->value
                                : null;

                            return [
                                $month =>
                                    $kpi->evaluateStatusForPeriod(
                                        $value,
                                        $month,
                                        $year,
                                        $ownerId
                                    ),
                            ];
                        }
                    );

                $targets = collect(range(1, 12))
                    ->mapWithKeys(
                        fn (int $month) => [
                            $month =>
                                $kpi
                                    ->effectiveTargetValueForPeriod(
                                        $month,
                                        $year,
                                        $ownerId
                                    ),
                        ]
                    );

                $valuesWithData = $values->filter(
                    fn ($value) =>
                        $value !== null
                );

                $total = $valuesWithData->sum();

                $average = $valuesWithData->isNotEmpty()
                    ? $valuesWithData->avg()
                    : null;

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,

                    'formatted_target' =>
                        $kpi->formatNumberOnly(
                            $kpi
                                ->effectiveTargetValueForPeriod(
                                    12,
                                    $year,
                                    $ownerId
                                )
                        ),

                    'values' => $values,
                    'statuses' => $statuses,
                    'targets' => $targets,
                    'total' => $total,
                    'average' => $average,
                ];
            })
            ->groupBy('category');
    }

    protected function delta(
        ?float $current,
        ?float $compare
    ): ?float {
        if (
            $current === null
            || $compare === null
        ) {
            return null;
        }

        return round(
            $current - $compare,
            2
        );
    }

    /**
     * KPI definicije dostupne određenoj organizaciji.
     *
     * Web organizacija:
     * - vlastiti KPI
     * - globalni KPI za koji nema organizacijsku kopiju
     *
     * Cron:
     * ista logika preko forcedOwnerId.
     *
     * Superadmin:
     * vidi sve KPI definicije, ali nema organizacijske vrijednosti.
     */
    protected function baseKpiQuery(): Builder
    {
        $query = Kpi::query();

        $ownerId = $this->resolveOwnerId();

        /*
         * Ako imamo konkretan owner, bilo iz weba ili cron-a,
         * primjenjujemo organizacijsku KPI logiku.
         */
        if ($ownerId) {
            return $query->where(
                function (Builder $q) use (
                    $ownerId
                ): void {
                    $q->where(
                        'user_id',
                        $ownerId
                    )
                        ->orWhere(
                            function (
                                Builder $global
                            ) use (
                                $ownerId
                            ): void {
                                $global
                                    ->whereNull(
                                        'user_id'
                                    )
                                    ->whereNotExists(
                                        function (
                                            $sub
                                        ) use (
                                            $ownerId
                                        ): void {
                                            $sub
                                                ->selectRaw('1')
                                                ->from(
                                                    'kpis as org_kpis'
                                                )
                                                ->where(
                                                    'org_kpis.user_id',
                                                    $ownerId
                                                )
                                                ->whereNull(
                                                    'org_kpis.deleted_at'
                                                )
                                                ->where(
                                                    function (
                                                        $match
                                                    ): void {
                                                        $match
                                                            ->where(
                                                                function (
                                                                    $bySource
                                                                ): void {
                                                                    $bySource
                                                                        ->whereNotNull(
                                                                            'kpis.source_key'
                                                                        )
                                                                        ->whereColumn(
                                                                            'org_kpis.source_key',
                                                                            'kpis.source_key'
                                                                        );
                                                                }
                                                            )
                                                            ->orWhere(
                                                                function (
                                                                    $byName
                                                                ): void {
                                                                    $byName
                                                                        ->whereNull(
                                                                            'kpis.source_key'
                                                                        )
                                                                        ->whereColumn(
                                                                            'org_kpis.name',
                                                                            'kpis.name'
                                                                        );
                                                                }
                                                            );
                                                    }
                                                );
                                        }
                                    );
                            }
                        );
                }
            );
        }

        /*
         * Bez ownera jedino superadmin smije vidjeti
         * KPI definicije.
         */
        if ($this->isSuperAdmin()) {
            return $query;
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Tenant scope za modele koji imaju vlastiti user_id.
     */
    protected function userScopedQuery(
        Builder $query
    ): Builder {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        if (
            Schema::hasColumn(
                $query->getModel()->getTable(),
                'user_id'
            )
        ) {
            return $query->where(
                'user_id',
                $ownerId
            );
        }

        /*
         * Fail-closed:
         * ako model nema user_id, ne smije slučajno
         * vratiti podatke svih organizacija.
         */
        return $query->whereRaw('1 = 0');
    }

    /**
     * InspectionFinding nema user_id.
     *
     * Ownership ide:
     *
     * InspectionFinding
     * -> Inspection
     * -> user_id
     */
    protected function inspectionFindingScopedQuery(
        Builder $query
    ): Builder {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'inspection',
            fn (Builder $inspectionQuery) =>
                $inspectionQuery->where(
                    'user_id',
                    $ownerId
                )
        );
    }

    protected function dateRange(
        int $month,
        int $year
    ): array {
        $start = Carbon::create(
            $year,
            $month,
            1
        )->startOfMonth();

        $end = Carbon::create(
            $year,
            $month,
            1
        )->endOfMonth();

        return [
            $start,
            $end,
        ];
    }

    protected function daysWithoutLta(
        int $month,
        int $year
    ): ?float {
        if (
            ! class_exists(Incident::class)
            || ! Schema::hasTable('incidents')
        ) {
            return null;
        }

        $lastLta = $this
            ->userScopedQuery(
                Incident::query()
            )
            ->where(
                'type_of_incident',
                'LTA'
            )
            ->whereNotNull(
                'date_occurred'
            )
            ->orderByDesc(
                'date_occurred'
            )
            ->first();

        if (! $lastLta?->date_occurred) {
            return null;
        }

        return (float) Carbon::parse(
            $lastLta->date_occurred
        )
            ->startOfDay()
            ->diffInDays(
                now()->startOfDay()
            );
    }

    protected function ltaCount(
        int $month,
        int $year
    ): ?float {
        if (
            ! class_exists(Incident::class)
            || ! Schema::hasTable('incidents')
        ) {
            return null;
        }

        [$start, $end] =
            $this->dateRange(
                $month,
                $year
            );

        return (float) $this
            ->userScopedQuery(
                Incident::query()
            )
            ->where(
                'type_of_incident',
                'LTA'
            )
            ->whereBetween(
                'date_occurred',
                [
                    $start->toDateString(),
                    $end->toDateString(),
                ]
            )
            ->count();
    }

    protected function ltaLostDays(
        int $month,
        int $year
    ): ?float {
        if (
            ! class_exists(Incident::class)
            || ! Schema::hasTable('incidents')
        ) {
            return null;
        }

        [$start, $end] =
            $this->dateRange(
                $month,
                $year
            );

        return (float) $this
            ->userScopedQuery(
                Incident::query()
            )
            ->where(
                'type_of_incident',
                'LTA'
            )
            ->whereBetween(
                'date_occurred',
                [
                    $start->toDateString(),
                    $end->toDateString(),
                ]
            )
            ->sum(
                'working_days_lost'
            );
    }

    protected function nearMissCount(
        int $month,
        int $year
    ): ?float {
        if (
            ! class_exists(Observation::class)
            || ! Schema::hasTable('observations')
        ) {
            return null;
        }

        [$start, $end] =
            $this->dateRange(
                $month,
                $year
            );

        return (float) $this
            ->userScopedQuery(
                Observation::query()
            )
            ->whereBetween(
                'incident_date',
                [
                    $start->toDateString(),
                    $end->toDateString(),
                ]
            )
            ->where(
                'observation_type',
                'near_miss'
            )
            ->count();
    }

    protected function negativeObservationCount(
        int $month,
        int $year
    ): ?float {
        if (
            ! class_exists(Observation::class)
            || ! Schema::hasTable('observations')
        ) {
            return null;
        }

        [$start, $end] =
            $this->dateRange(
                $month,
                $year
            );

        return (float) $this
            ->userScopedQuery(
                Observation::query()
            )
            ->whereBetween(
                'incident_date',
                [
                    $start->toDateString(),
                    $end->toDateString(),
                ]
            )
            ->where(
                'observation_type',
                'negative'
            )
            ->count();
    }

    protected function inspectionCount(
        int $month,
        int $year
    ): ?float {
        if (
            ! class_exists(Inspection::class)
            || ! Schema::hasTable('inspections')
        ) {
            return null;
        }

        [$start, $end] =
            $this->dateRange(
                $month,
                $year
            );

        return (float) $this
            ->userScopedQuery(
                Inspection::query()
            )
            ->whereBetween(
                'performed_at',
                [
                    $start->toDateString(),
                    $end->toDateString(),
                ]
            )
            ->count();
    }

    protected function correctiveActionCount(
        int $month,
        int $year,
        array $statuses
    ): ?float {
        if (
            ! class_exists(
                InspectionFinding::class
            )
            || ! Schema::hasTable(
                'inspection_findings'
            )
        ) {
            return null;
        }

        [$start, $end] =
            $this->dateRange(
                $month,
                $year
            );

        return (float) $this
            ->inspectionFindingScopedQuery(
                InspectionFinding::query()
            )
            ->whereBetween(
                'created_at',
                [
                    $start,
                    $end,
                ]
            )
            ->whereIn(
                'workflow_status',
                $statuses
            )
            ->count();
    }

    protected function correctiveActionDelayDays(
        int $month,
        int $year
    ): ?float {
        if (
            ! class_exists(
                InspectionFinding::class
            )
            || ! Schema::hasTable(
                'inspection_findings'
            )
        ) {
            return null;
        }

        [$start, $end] =
            $this->dateRange(
                $month,
                $year
            );

        $records = $this
            ->inspectionFindingScopedQuery(
                InspectionFinding::query()
            )
            ->whereBetween(
                'created_at',
                [
                    $start,
                    $end,
                ]
            )
            ->whereNotNull(
                'due_date'
            )
            ->get([
                'due_date',
                'workflow_status',
            ]);

        $delay = 0;

        foreach ($records as $record) {
            if (
                in_array(
                    $record->workflow_status,
                    [
                        'closed',
                        'resolved_no_action',
                        'converted_to_observation',
                    ],
                    true
                )
            ) {
                continue;
            }

            $due = Carbon::parse(
                $record->due_date
            )->startOfDay();

            $today = now()->startOfDay();

            if ($due->lt($today)) {
                $delay += $due->diffInDays(
                    $today
                );
            }
        }

        return (float) $delay;
    }

    protected function nonHazardousWasteKg(
        int $month,
        int $year
    ): ?float {
        return $this->sumOntoWasteOutput(
            $month,
            $year,
            function (
                string $normalizedCode,
                bool $hasStar
            ): bool {
                return ! $hasStar
                    && $normalizedCode !== '200301';
            }
        );
    }

    protected function hazardousWasteKg(
        int $month,
        int $year
    ): ?float {
        return $this->sumOntoWasteOutput(
            $month,
            $year,
            function (
                string $normalizedCode,
                bool $hasStar
            ): bool {
                return $hasStar;
            }
        );
    }

    protected function municipalWasteKg(
        int $month,
        int $year
    ): ?float {
        return $this->sumOntoWasteOutput(
            $month,
            $year,
            function (
                string $normalizedCode,
                bool $hasStar
            ): bool {
                return $normalizedCode === '200301';
            }
        );
    }

    protected function sumOntoWasteOutput(
        int $month,
        int $year,
        callable $filter
    ): ?float {
        if (
            ! class_exists(OntoEntry::class)
            || ! Schema::hasTable('onto_entries')
        ) {
            return null;
        }

        [$start, $end] =
            $this->dateRange(
                $month,
                $year
            );

        $entries = $this
            ->userScopedQuery(
                OntoEntry::query()
                    ->with([
                        'ontoRecord.wasteType',
                    ])
            )
            ->whereDate(
                'entry_date',
                '>=',
                $start->toDateString()
            )
            ->whereDate(
                'entry_date',
                '<=',
                $end->toDateString()
            )
            ->get();

        $sum = 0.0;

        foreach ($entries as $entry) {
            $code = (string) (
                $entry
                    ->ontoRecord
                    ?->wasteType
                    ?->waste_code
                ?? ''
            );

            $normalizedCode =
                preg_replace(
                    '/[^0-9]/',
                    '',
                    $code
                );

            $hasStar =
                str_contains(
                    $code,
                    '*'
                );

            if (
                $filter(
                    $normalizedCode,
                    $hasStar
                )
            ) {
                $sum += (float) (
                    $entry->output_kg
                    ?: 0
                );
            }
        }

        return round(
            $sum,
            2
        );
    }

    protected function manualLinkedValue(
        string $kpiName,
        int $month,
        int $year
    ): ?float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return null;
        }

        $kpi = Kpi::query()
            ->where(
                'name',
                $kpiName
            )
            ->where(
                function (
                    Builder $query
                ) use (
                    $ownerId
                ): void {
                    $query
                        ->where(
                            'user_id',
                            $ownerId
                        )
                        ->orWhereNull(
                            'user_id'
                        );
                }
            )
            /*
             * Organizacijski KPI ima prednost
             * pred globalnim KPI-jem istog naziva.
             */
            ->orderByRaw(
                'CASE WHEN user_id IS NULL THEN 1 ELSE 0 END'
            )
            ->first();

        if (! $kpi) {
            return null;
        }

        return $kpi->valueFor(
            $month,
            $year,
            $ownerId
        )?->value;
    }

    protected function calculateAfr(
        int $month,
        int $year
    ): ?float {
        $lta = $this->ltaCount(
            $month,
            $year
        );

        $hours = $this->manualLinkedValue(
            'Ukupan broj odrađenih radnih sati',
            $month,
            $year
        );

        if (
            $lta === null
            || $hours === null
            || $hours <= 0
        ) {
            return null;
        }

        return round(
            ($lta * 1000000)
            / $hours,
            4
        );
    }

    protected function calculateAsr(
        int $month,
        int $year
    ): ?float {
        $lostDays = $this->ltaLostDays(
            $month,
            $year
        );

        $hours = $this->manualLinkedValue(
            'Ukupan broj odrađenih radnih sati',
            $month,
            $year
        );

        if (
            $lostDays === null
            || $hours === null
            || $hours <= 0
        ) {
            return null;
        }

        return round(
            ($lostDays * 1000000)
            / $hours,
            4
        );
    }
}
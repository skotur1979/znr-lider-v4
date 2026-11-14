<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Inspection;
use App\Models\Kpi;
use Illuminate\Support\Facades\DB;
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

    public function managementMetrics(
        int $month,
        int $year
    ): array {
        return [
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
                $this->openCorrectiveActionCount(
                    $month,
                    $year
                ),

            'corrective_actions_closed' =>
                $this->closedCorrectiveActionCount(
                    $month,
                    $year
                ),

            'corrective_actions_in_progress' =>
                $this->inProgressCorrectiveActionCount(
                    $month,
                    $year
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
                $this->openCorrectiveActionCount(
                    $month,
                    $year
                ),

            'corrective_actions_closed' =>
                $this->closedCorrectiveActionCount(
                    $month,
                    $year
                ),

            'corrective_actions_in_progress' =>
                $this->inProgressCorrectiveActionCount(
                    $month,
                    $year
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
                => 'Zapažanja',

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
                $current = $kpi->valueFor(
                    $month,
                    $year,
                    $ownerId
                )?->value;

                $compare = null;

                if (
                    $compareMonth !== null
                    && $compareYear !== null
                ) {
                    $compare = $kpi->valueFor(
                        $compareMonth,
                        $compareYear,
                        $ownerId
                    )?->value;
                }

                $difference = null;

                if (
                    $current !== null
                    && $compare !== null
                ) {
                    $difference =
                        (float) $current
                        - (float) $compare;
                }

                $target =
                $kpi->effectiveTargetValueForPeriod(
                    $month,
                    $year,
                    $ownerId
                );

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'current_value' => $current,
                    'compare_value' => $compare,
                    'difference' => $difference,
                    'delta' => $difference,
                    'target' => $target,
                    'status' =>
                    $kpi->evaluateStatusForPeriod(
                        $current,
                        $month,
                        $year,
                        $ownerId
                    ),
                    'formatted_current' =>
                        $kpi->formatNumberOnly(
                            $current
                        ),
                    'formatted_compare' =>
                        $kpi->formatNumberOnly(
                            $compare
                        ),
                    'formatted_difference' =>
                        $kpi->formatNumberOnly(
                            $difference
                        ),
                    'formatted_target' =>
                        $kpi->formatNumberOnly(
                            $target
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
                $currentValues = [];

                for ($month = 1; $month <= 12; $month++) {
                    $value = $kpi->valueFor(
                        $month,
                        $year,
                        $ownerId
                    )?->value;

                    if ($value !== null) {
                        $currentValues[] =
                            (float) $value;
                    }
                }

                $current =
                    $current =
                        count($currentValues) > 0
                            ? $this->yearlyAggregateValue(
                                $kpi,
                                $currentValues,
                                $year
                            )
                            : null;

                $compare = null;

                if ($compareYear !== null) {
                    $compareValues = [];

                    for (
                        $month = 1;
                        $month <= 12;
                        $month++
                    ) {
                        $value = $kpi->valueFor(
                            $month,
                            $compareYear,
                            $ownerId
                        )?->value;

                        if ($value !== null) {
                            $compareValues[] =
                                (float) $value;
                        }
                    }

                    if (count($compareValues) > 0) {
                        $compare =
                        $this->yearlyAggregateValue(
                            $kpi,
                            $compareValues,
                            $compareYear
                        );
                    }
                }

                $difference = null;

                if (
                    $current !== null
                    && $compare !== null
                ) {
                    $difference =
                        (float) $current
                        - (float) $compare;
                }

                $target =
                    $kpi->effectiveTargetValueForPeriod(
                        12,
                        $year,
                        $ownerId
                    );

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'current_value' => $current,
                    'compare_value' => $compare,
                    'difference' => $difference,
                    'delta' => $difference,
                    'target' => $target,
                    'status' =>
                        $kpi->evaluateStatusForPeriod(
                            $current,
                            12,
                            $year,
                            $ownerId
                        ),
                    'formatted_current' =>
                        $kpi->formatNumberOnly(
                            $current
                        ),
                    'formatted_compare' =>
                        $kpi->formatNumberOnly(
                            $compare
                        ),
                    'formatted_difference' =>
                        $kpi->formatNumberOnly(
                            $difference
                        ),
                    'formatted_target' =>
                        $kpi->formatNumberOnly(
                            $target
                        ),
                ];
            })
            ->groupBy('category');
    }

    protected function yearlyAggregateValue(
        Kpi $kpi,
        array $values,
        int $year
    ): ?float {
        if (empty($values)) {
            return null;
        }

        $sourceKey = (string) $kpi->source_key;

        /*
        * Snapshot KPI-jevi predstavljaju stanje na kraju
        * posljednjeg mjeseca za koji postoji vrijednost.
        *
        * Njihove mjesečne vrijednosti ne smiju se zbrajati.
        */
        if (
            in_array(
                $sourceKey,
                [
                    'days_without_lta',
                    'corrective_actions_open',
                    'corrective_actions_in_progress',
                    'corrective_actions_delay_days',
                ],
                true
            )
        ) {
            $lastKey = array_key_last($values);

            return round(
                (float) $values[$lastKey],
                4
            );
        }

        /*
        * AFR i ASR se na godišnjoj razini ponovno računaju
        * iz godišnjih osnovnih podataka.
        *
        * Ne smije se koristiti prosjek mjesečnih stopa.
        */
        if (
            in_array(
                $sourceKey,
                [
                    'afr',
                    'asr',
                ],
                true
            )
        ) {
            $workedHours = 0.0;
            $ltaCount = 0.0;
            $lostDays = 0.0;

            for ($month = 1; $month <= 12; $month++) {
                $monthlyHours = $this->workedHours(
                    $month,
                    $year
                );

                if (
                    $monthlyHours !== null
                    && $monthlyHours > 0
                ) {
                    $workedHours += $monthlyHours;
                }

                if ($sourceKey === 'afr') {
                    $ltaCount += $this->ltaCount(
                        $month,
                        $year
                    );
                }

                if ($sourceKey === 'asr') {
                    $lostDays += $this->ltaLostDays(
                        $month,
                        $year
                    );
                }
            }

            if ($workedHours <= 0) {
                return null;
            }

            $numerator = $sourceKey === 'afr'
                ? $ltaCount
                : $lostDays;

            return round(
                ($numerator * 1000000)
                / $workedHours,
                4
            );
        }

        /*
        * KPI-jevi koji predstavljaju broj ili količinu
        * tijekom godine zbrajaju se.
        *
        * Primjeri:
        * - LTA
        * - izgubljeni dani
        * - Near Miss
        * - negativna zapažanja
        * - nadzori
        * - zatvorene korektivne radnje
        * - količine otpada
        * - ručni KPI-jevi koji se zbrajaju
        */
        return round(
            array_sum($values),
            4
        );
    }
        /**
     * Godišnji KPI izvještaj grupiran po kategorijama.
     */
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
                $values = [];
                $statuses = [];
                $targets = [];

                for ($month = 1; $month <= 12; $month++) {
                    $value = $kpi->valueFor(
                        $month,
                        $year,
                        $ownerId
                    )?->value;

                    $value = $value !== null
                        ? (float) $value
                        : null;

                    $values[$month] = $value;

                    $targets[$month] =
                        $kpi->effectiveTargetValueForPeriod(
                            $month,
                            $year,
                            $ownerId
                        );

                    $statuses[$month] =
                        $kpi->evaluateStatusForPeriod(
                            $value,
                            $month,
                            $year,
                            $ownerId
                        );
                }

                $existingValues = collect($values)
                    ->filter(
                        fn ($value): bool =>
                            $value !== null
                    )
                    ->values();

                $average = $existingValues->isNotEmpty()
                    ? round(
                        (float) $existingValues->avg(),
                        4
                    )
                    : null;

                /*
                * Godišnja vrijednost mora koristiti istu poslovnu
                * logiku kao godišnji dashboard:
                *
                * - sum za količine/brojeve
                * - latest za snapshot KPI-jeve
                * - godišnju formulu za AFR/ASR
                */
                $yearlyValue = $existingValues->isNotEmpty()
                    ? $this->yearlyAggregateValue(
                        $kpi,
                        $existingValues->all(),
                        $year
                    )
                    : null;

                                $target =
                                    $kpi->effectiveTargetValueForPeriod(
                                        12,
                                        $year,
                                        $ownerId
                                    );

                return [
                    'id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'direction' => $kpi->direction,
                    'calculation_type' =>
                        $kpi->calculation_type,
                    'source_key' => $kpi->source_key,
                    'values' => $values,
                    'statuses' => $statuses,
                    'targets' => $targets,

                    'average' => $average,

                    'yearly_value' => $yearlyValue,

                    /*
                    * Kompatibilni alias za postojeće
                    * prikaze/exporte.
                    */
                    'total' => $yearlyValue,

                    'target' => $target,

                    'formatted_yearly_value' =>
                        $kpi->formatNumberOnly(
                            $yearlyValue
                        ),

                    'formatted_target' =>
                        $kpi->formatNumberOnly(
                            $target
                        ),
                ];
            })
            ->groupBy('category');
    }

    /**
     * KPI definicije koje organizacija smije koristiti.
     *
     * Organizacija vidi:
     * - svoje KPI definicije
     * - globalne KPI definicije za koje nema svoju kopiju
     *
     * Time sprječavamo duplikate istog KPI-ja.
     */
    protected function baseKpiQuery(): Builder
    {
        $ownerId = $this->resolveOwnerId();

        /*
        * Superadmin bez prisilno zadane organizacije
        * vidi samo globalne KPI definicije.
        *
        * Ako je forcedOwnerId postavljen preko generateForOwner(),
        * moramo nastaviti kroz organizacijsku shadow/fallback logiku.
        */
        if (
            $this->forcedOwnerId === null
            && $this->isSuperAdmin()
        ) {
            return Kpi::query()
                ->whereNull('user_id');
        }

        if (! $ownerId) {
            return Kpi::query()
                ->whereRaw('1 = 0');
        }

        return Kpi::query()
            ->where(function (Builder $query) use (
                $ownerId
            ): void {
                $query
                    ->where(
                        'user_id',
                        $ownerId
                    )
                    ->orWhere(function (
                        Builder $global
                    ) use ($ownerId): void {
                        $global
                            ->whereNull('user_id')
                            ->whereNotExists(
                                function ($sub) use (
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
                                        ->where(function (
                                            $match
                                        ): void {
                                            $match
                                                ->where(function (
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
                                                })
                                                ->orWhere(function (
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
                                                });
                                        });
                                }
                            );
                    });
            });
    }

    /**
     * Početak i kraj mjeseca.
     */
    protected function monthRange(
        int $month,
        int $year
    ): array {
        $from = Carbon::create(
            $year,
            $month,
            1
        )->startOfDay();

        $to = $from
            ->copy()
            ->endOfMonth()
            ->endOfDay();

        return [
            $from,
            $to,
        ];
    }

    /**
     * Broj LTA ozljeda u mjesecu.
     */
    protected function ltaCount(
        int $month,
        int $year
    ): float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return 0.0;
        }

        [$from, $to] = $this->monthRange(
            $month,
            $year
        );

        $model = new Incident();
        $table = $model->getTable();

        $query = Incident::query();

        $this->applyOwnerScope(
            $query,
            $table,
            $ownerId
        );

        $dateColumn = $this->firstExistingColumn(
            $table,
            [
                'date_occurred',
                'incident_date',
                'date',
            ]
        );

        if (! $dateColumn) {
            return 0.0;
        }

        $query->whereBetween(
            $dateColumn,
            [
                $from,
                $to,
            ]
        );

        $typeColumn = $this->firstExistingColumn(
            $table,
            [
                'type_of_incident',
                'incident_type',
                'type',
                'category',
            ]
        );

        if (! $typeColumn) {
            return 0.0;
        }

        $query->whereRaw(
            'LOWER(TRIM(' . $typeColumn . ')) = ?',
            ['lta']
        );

        return (float) $query->count();
    }

    /**
     * Izgubljeni radni dani zbog LTA.
     */
    protected function ltaLostDays(
        int $month,
        int $year
    ): float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return 0.0;
        }

        [$from, $to] = $this->monthRange(
            $month,
            $year
        );

        $model = new Incident();
        $table = $model->getTable();

        $query = Incident::query();

        $this->applyOwnerScope(
            $query,
            $table,
            $ownerId
        );

        $dateColumn = $this->firstExistingColumn(
            $table,
            [
                'date_occurred',
                'incident_date',
                'date',
            ]
        );

        if (! $dateColumn) {
            return 0.0;
        }

        $query->whereBetween(
            $dateColumn,
            [
                $from,
                $to,
            ]
        );

        $typeColumn = $this->firstExistingColumn(
            $table,
            [
                'type_of_incident',
                'incident_type',
                'type',
                'category',
            ]
        );

        if (! $typeColumn) {
                return 0.0;
            }

            $query->whereRaw(
                'LOWER(TRIM(' . $typeColumn . ')) = ?',
                ['lta']
            );

        $lostDaysColumn =
            $this->firstExistingColumn(
                $table,
                [
                    'working_days_lost',
                    'lost_working_days',
                    'lost_days',
                ]
            );

        if (! $lostDaysColumn) {
            return 0.0;
        }

        return (float) $query->sum(
            $lostDaysColumn
        );
    }

    /**
     * Broj dana bez LTA.
     *
     * Ovaj source_key ostavljamo podržan zbog starih
     * KPI definicija, iako ga više ne moramo prikazivati.
     */
    protected function daysWithoutLta(
        int $month,
        int $year
    ): float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return 0.0;
        }

        [, $to] = $this->monthRange(
            $month,
            $year
        );

        $model = new Incident();
        $table = $model->getTable();

        $query = Incident::query();

        $this->applyOwnerScope(
            $query,
            $table,
            $ownerId
        );

        $dateColumn = $this->firstExistingColumn(
            $table,
            [
                'date_occurred',
                'incident_date',
                'date',
            ]
        );

        $typeColumn = $this->firstExistingColumn(
            $table,
            [
                'type_of_incident',
                'incident_type',
                'type',
                'category',
            ]
        );

        if (
            ! $dateColumn
            || ! $typeColumn
        ) {
            return 0.0;
        }

        $lastLta = $query
            ->whereRaw(
                'LOWER(TRIM(' . $typeColumn . ')) = ?',
                ['lta']
            )
            ->where(
                $dateColumn,
                '<=',
                $to
            )
            ->orderByDesc(
                $dateColumn
            )
            ->first();

        if (! $lastLta) {
            return 0.0;
        }

        $lastDate = Carbon::parse(
            $lastLta->{$dateColumn}
        )->startOfDay();

        return (float) max(
            0,
            $lastDate->diffInDays(
                $to->copy()->startOfDay()
            )
        );
    }

    /**
 * Broj Near Miss zapažanja u odabranom mjesecu.
 */
    protected function nearMissCount(
        int $month,
        int $year
    ): float {
        return $this->observationCount(
            $month,
            $year,
            [
                'near miss',
                'near_miss',
                'nm',
            ]
        );
    }

    /**
     * Broj negativnih zapažanja u odabranom mjesecu.
     */
    protected function negativeObservationCount(
        int $month,
        int $year
    ): float {
        return $this->observationCount(
            $month,
            $year,
            [
                'negative observation',
                'negative_observation',
                'negative',
                'negativno',
                'negativno zapažanje',
            ]
        );
    }

    /**
     * Broji zapažanja određene vrste u odabranom mjesecu.
     *
     * Usporedba vrste je case-insensitive i ignorira
     * početne/završne razmake.
     */
    protected function observationCount(
        int $month,
        int $year,
        array $types
    ): float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return 0.0;
        }

        [$from, $to] = $this->monthRange(
            $month,
            $year
        );

        $model = new Observation();
        $table = $model->getTable();

        $query = Observation::query();

        $this->applyOwnerScope(
            $query,
            $table,
            $ownerId
        );

        $dateColumn = $this->firstExistingColumn(
            $table,
            [
                'incident_date',
                'observation_date',
                'date',
            ]
        );

        $typeColumn = $this->firstExistingColumn(
            $table,
            [
                'observation_type',
                'type',
            ]
        );

        if (
            ! $dateColumn
            || ! $typeColumn
        ) {
            return 0.0;
        }

        $normalizedTypes = collect($types)
            ->map(
                fn ($type): string =>
                    mb_strtolower(
                        trim((string) $type)
                    )
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($normalizedTypes)) {
            return 0.0;
        }

        $query
            ->whereBetween(
                $dateColumn,
                [
                    $from,
                    $to,
                ]
            )
            ->whereIn(
                DB::raw(
                    'LOWER(TRIM(' . $typeColumn . '))'
                ),
                $normalizedTypes
            );

        return (float) $query->count();
    }

    /**
     * Interni nadzori u mjesecu.
     */
    protected function inspectionCount(
        int $month,
        int $year
    ): float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return 0.0;
        }

        [$from, $to] = $this->monthRange(
            $month,
            $year
        );

        $model = new Inspection();
        $table = $model->getTable();

        $query = Inspection::query();

        $this->applyOwnerScope(
            $query,
            $table,
            $ownerId
        );

        $dateColumn = $this->firstExistingColumn(
            $table,
            [
                'inspection_date',
                'date',
                'performed_at',
                'created_at',
            ]
        );

        if (! $dateColumn) {
            return 0.0;
        }

        $query->whereBetween(
            $dateColumn,
            [
                $from,
                $to,
            ]
        );

        return (float) $query->count();
    }

    /**
     * Broj otvorenih korektivnih radnji na kraju odabranog mjeseca.
     *
     * Otvorenom smatramo svako zapažanje koje ima potrebnu radnju
     * i koje do kraja odabranog mjeseca nije završeno.
     */
    protected function openCorrectiveActionCount(
        int $month,
        int $year
    ): float {
        $query = $this->openObservationCorrectiveActionsQuery(
            $month,
            $year
        );

        return $query
            ? (float) $query->count()
            : 0.0;
    }

    /**
     * Broj korektivnih radnji koje su zatvorene baš u
     * odabranom mjesecu.
     */
    protected function closedCorrectiveActionCount(
        int $month,
        int $year
    ): float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return 0.0;
        }

        [$from, $to] = $this->monthRange(
            $month,
            $year
        );

        $model = new Observation();
        $table = $model->getTable();

        $query = Observation::query();

        $this->applyOwnerScope(
            $query,
            $table,
            $ownerId
        );

        $this->applyCorrectiveActionPresenceFilter(
            $query,
            $table
        );

        $statusColumn = $this->firstExistingColumn(
            $table,
            [
                'status',
                'workflow_status',
            ]
        );

        $closedDateColumn = $this->firstExistingColumn(
            $table,
            [
                'completed_at',
                'completion_date',
                'completed_date',
                'closed_at',
                'closed_date',
                'date_closed',
                'finished_at',
                'finished_date',
            ]
        );

        /*
         * Najtočnija varijanta: koristimo stvarni datum zatvaranja.
         */
        if ($closedDateColumn) {
            $query->whereBetween(
                $closedDateColumn,
                [
                    $from,
                    $to,
                ]
            );

            return (float) $query->count();
        }

        /*
         * Ako stara baza još nema poseban datum zatvaranja,
         * kao sigurnosni fallback koristimo updated_at, ali samo
         * za zapise koji su trenutno završeni.
         */
        if (
            ! $statusColumn
            || ! Schema::hasColumn(
                $table,
                'updated_at'
            )
        ) {
            return 0.0;
        }

        $query
            ->whereIn(
                $statusColumn,
                $this->observationClosedStatusVariants()
            )
            ->whereBetween(
                'updated_at',
                [
                    $from,
                    $to,
                ]
            );

        return (float) $query->count();
    }

    /**
     * Broj korektivnih radnji koje su na kraju odabranog
     * mjeseca bile u statusu "U tijeku".
     */
    protected function inProgressCorrectiveActionCount(
        int $month,
        int $year
    ): float {
        $query = $this->openObservationCorrectiveActionsQuery(
            $month,
            $year
        );

        if (! $query) {
            return 0.0;
        }

        $table = (new Observation())->getTable();

        $statusColumn = $this->firstExistingColumn(
            $table,
            [
                'status',
                'workflow_status',
            ]
        );

        if (! $statusColumn) {
            return 0.0;
        }

        $query->whereIn(
            $statusColumn,
            $this->observationInProgressStatusVariants()
        );

        return (float) $query->count();
    }

    /**
     * Ukupan broj dana svih korektivnih radnji koje su još
     * otvorene na kraju odabranog mjeseca.
     *
     * Primjer:
     * - radnja A otvorena 10 dana
     * - radnja B otvorena 20 dana
     * - radnja C otvorena 35 dana
     * KPI vrijednost = 65 dana.
     *
     * Dani se računaju od datuma zapažanja / nastanka radnje,
     * a ne samo od prekoračenja roka.
     */
    protected function correctiveActionDelayDays(
        int $month,
        int $year
    ): float {
        $query = $this->openObservationCorrectiveActionsQuery(
            $month,
            $year
        );

        if (! $query) {
            return 0.0;
        }

        [, $to] = $this->monthRange(
            $month,
            $year
        );

        $table = (new Observation())->getTable();

        $startDateColumn = $this->firstExistingColumn(
            $table,
            [
                'incident_date',
                'observation_date',
                'date',
                'created_at',
            ]
        );

        if (! $startDateColumn) {
            return 0.0;
        }

        $findings = $query->get([
            'id',
            $startDateColumn,
        ]);

        $endDate = $to->copy()->startOfDay();

        $days = $findings->sum(
            function (
                Observation $observation
            ) use (
                $startDateColumn,
                $endDate
            ): int {
                if (! $observation->{$startDateColumn}) {
                    return 0;
                }

                $startDate = Carbon::parse(
                    $observation->{$startDateColumn}
                )->startOfDay();

                if ($startDate->greaterThan($endDate)) {
                    return 0;
                }

                return $startDate->diffInDays(
                    $endDate
                );
            }
        );

        return (float) $days;
    }

    /**
     * Osnovni query za korektivne radnje iz modula Zapažanja
     * koje su otvorene na kraju odabranog mjeseca.
     */
    protected function openObservationCorrectiveActionsQuery(
        int $month,
        int $year
    ): ?Builder {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return null;
        }

        [, $to] = $this->monthRange(
            $month,
            $year
        );

        $model = new Observation();
        $table = $model->getTable();

        $query = Observation::query();

        $this->applyOwnerScope(
            $query,
            $table,
            $ownerId
        );

        $this->applyCorrectiveActionPresenceFilter(
            $query,
            $table
        );

        $startDateColumn = $this->firstExistingColumn(
            $table,
            [
                'incident_date',
                'observation_date',
                'date',
                'created_at',
            ]
        );

        if ($startDateColumn) {
            $query->where(
                $startDateColumn,
                '<=',
                $to
            );
        }

        $statusColumn = $this->firstExistingColumn(
            $table,
            [
                'status',
                'workflow_status',
            ]
        );

        $closedDateColumn = $this->firstExistingColumn(
            $table,
            [
                'completed_at',
                'completion_date',
                'completed_date',
                'closed_at',
                'closed_date',
                'date_closed',
                'finished_at',
                'finished_date',
            ]
        );

        /*
         * Ako imamo datum zatvaranja, možemo korektno rekonstruirati
         * stanje i za stare mjesece: radnja je bila otvorena ako nije
         * zatvorena ili je zatvorena tek nakon kraja tog mjeseca.
         */
        if ($closedDateColumn) {
            $query->where(
                function (Builder $subQuery) use (
                    $closedDateColumn,
                    $to
                ): void {
                    $subQuery
                        ->whereNull(
                            $closedDateColumn
                        )
                        ->orWhere(
                            $closedDateColumn,
                            '>',
                            $to
                        );
                }
            );

            return $query;
        }

        /*
         * Fallback za staru strukturu bez datuma zatvaranja:
         * gledamo trenutačni status.
         */
        if ($statusColumn) {
            $query->whereNotIn(
                $statusColumn,
                $this->observationClosedStatusVariants()
            );
        }

        return $query;
    }

    /**
     * U KPI korektivnih radnji ulaze samo zapažanja koja stvarno
     * imaju upisanu potrebnu/korektivnu radnju.
     */
    protected function applyCorrectiveActionPresenceFilter(
        Builder $query,
        string $table
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Pozitivna zapažanja nisu korektivne radnje
        |--------------------------------------------------------------------------
        |
        | Polje "action" kod pozitivnog zapažanja koristimo kao
        | "Napomena / prijedlog".
        |
        | Zbog toga pozitivno zapažanje nikada ne smije ući u KPI:
        | - otvorene korektivne radnje
        | - zatvorene korektivne radnje
        | - korektivne radnje u tijeku
        | - dane otvorenih korektivnih radnji
        |
        */

        $typeColumn = $this->firstExistingColumn(
            $table,
            [
                'observation_type',
                'type',
            ]
        );

        if ($typeColumn) {
            $query->where(
                function (Builder $subQuery) use (
                    $typeColumn
                ): void {
                    $subQuery
                        ->whereNull(
                            $typeColumn
                        )
                        ->orWhere(
                            $typeColumn,
                            '<>',
                            'Positive Observation'
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Mora postojati stvarna potrebna / korektivna radnja
        |--------------------------------------------------------------------------
        */

        $actionColumn = $this->firstExistingColumn(
            $table,
            [
                'corrective_action',
                'required_action',
                'action_required',
                'action',
                'needed_action',
            ]
        );

        if ($actionColumn) {
            $query
                ->whereNotNull(
                    $actionColumn
                )
                ->whereRaw(
                    'TRIM('
                    . $actionColumn
                    . ") <> ''"
                );
        }
    }

    protected function observationClosedStatusVariants(): array
    {
        return [
            'Complete',
            'complete',
            'Completed',
            'completed',
            'Closed',
            'closed',
            'Završeno',
            'završeno',
            'Zatvoreno',
            'zatvoreno',
            'resolved',
            'Resolved',
        ];
    }

    protected function observationInProgressStatusVariants(): array
    {
        return [
            'In progress',
            'in progress',
            'in_progress',
            'U tijeku',
            'u tijeku',
            'u_tijeku',
            'ongoing',
            'Ongoing',
        ];
    }

    /**
     * AFR = broj LTA × 1.000.000 / broj odrađenih sati.
     *
     * Ključna izmjena:
     * broj sati tražimo po organizaciji, mjesecu i godini,
     * ne samo preko konkretnog KPI ID-a.
     *
     * Tako formula radi i kada je ručni KPI organizacijska
     * kopija globalnog KPI-ja.
     */
    protected function calculateAfr(
        int $month,
        int $year
    ): ?float {
        $hours = $this->workedHours(
            $month,
            $year
        );

        if (
            $hours === null
            || $hours <= 0
        ) {
            return null;
        }

        /*
         * LTA uzimamo izravno iz Incidenata.
         * Time AFR ne može koristiti staru spremljenu KPI vrijednost.
         */
        $lta = $this->ltaCount(
            $month,
            $year
        );

        return round(
            (
                $lta
                * 1000000
            )
            / $hours,
            4
        );
    }

    /**
     * ASR = izgubljeni radni dani × 1.000.000
     *       / broj odrađenih sati.
     */
    protected function calculateAsr(
        int $month,
        int $year
    ): ?float {
        $hours = $this->workedHours(
            $month,
            $year
        );

        if (
            $hours === null
            || $hours <= 0
        ) {
            return null;
        }

        /*
         * Izgubljene dane uzimamo izravno iz Incidenata.
         */
        $lostDays = $this->ltaLostDays(
            $month,
            $year
        );

        return round(
            (
                $lostDays
                * 1000000
            )
            / $hours,
            4
        );
    }

    /**
     * Dohvaća ručno uneseni broj odrađenih sati.
     *
     * Ovo je važno i za Dashboard i za AFR/ASR.
     */
    protected function workedHours(
        int $month,
        int $year
    ): ?float {
        return $this->metricValue(
            null,
            [
                'Ukupan broj odrađenih radnih sati',
                'Ukupno odrađenih radnih sati',
            ],
            $month,
            $year
        );
    }

    /**
     * Pronalazi vrijednost KPI-ja neovisno o tome je li
     * KPI globalni ili organizacijska kopija.
     *
     * Time AFR/ASR više nisu vezani za jedan fiksni kpi_id.
     */
    protected function metricValue(
        ?string $sourceKey,
        array $names,
        int $month,
        int $year
    ): ?float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return null;
        }

        if (
            $sourceKey === null
            && empty($names)
        ) {
            return null;
        }

        /*
        * Prvo pronađemo KPI definicije koje odgovaraju
        * traženom source_key-u ili nazivu.
        */
        $definitions = Kpi::query()
            ->where(
                function (
                    Builder $query
                ) use (
                    $sourceKey,
                    $names
                ): void {
                    if (
                        $sourceKey !== null
                        && ! empty($names)
                    ) {
                        $query
                            ->where(
                                'source_key',
                                $sourceKey
                            )
                            ->orWhereIn(
                                'name',
                                $names
                            );

                        return;
                    }

                    if ($sourceKey !== null) {
                        $query->where(
                            'source_key',
                            $sourceKey
                        );

                        return;
                    }

                    $query->whereIn(
                        'name',
                        $names
                    );
                }
            );

        /*
        * Organizacijska definicija ima prednost.
        *
        * Ako postoji lokalna kopija KPI-ja,
        * globalna definicija više nije fallback.
        */
        $localIds = (clone $definitions)
            ->where(
                'user_id',
                $ownerId
            )
            ->pluck('id');

        $kpiIds = $localIds->isNotEmpty()
            ? $localIds
            : (clone $definitions)
                ->whereNull('user_id')
                ->pluck('id');

        if ($kpiIds->isEmpty()) {
            return null;
        }

        $value = KpiValue::query()
            ->where(
                'user_id',
                $ownerId
            )
            ->whereIn(
                'kpi_id',
                $kpiIds
            )
            ->where(
                'month',
                $month
            )
            ->where(
                'year',
                $year
            )
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        return $value?->value !== null
            ? (float) $value->value
            : null;
    }
    /**
     * Neopasni otpad predan u odabranom mjesecu.
     *
     * Miješani komunalni otpad 20 03 01 vodi se zasebno
     * i zato se ne uključuje u ovaj KPI.
     */
    protected function nonHazardousWasteKg(
        int $month,
        int $year
    ): float {
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

    /**
     * Opasni otpad predan u odabranom mjesecu.
     *
     * Zvjezdica u ključnom broju otpada označava
     * opasni otpad.
     */
    protected function hazardousWasteKg(
        int $month,
        int $year
    ): float {
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

    /**
     * Miješani komunalni otpad – ključni broj 20 03 01.
     */
    protected function municipalWasteKg(
        int $month,
        int $year
    ): float {
        return $this->sumOntoWasteOutput(
            $month,
            $year,
            function (
                string $normalizedCode,
                bool $hasStar
            ): bool {
                return ! $hasStar
                    && $normalizedCode === '200301';
            }
        );
    }

    /**
     * Zbraja izlaz otpada iz ONTO evidencije.
     *
     * Tenant se određuje preko roditeljskog OntoRecord zapisa,
     * jer OntoEntry pripada ONTO obrascu preko onto_record_id.
     *
     * Vrsta otpada također se određuje preko:
     * OntoEntry -> OntoRecord -> WasteType.
     */
    protected function sumOntoWasteOutput(
        int $month,
        int $year,
        callable $filter
    ): float {
        $ownerId = $this->resolveOwnerId();

        if (! $ownerId) {
            return 0.0;
        }

        if (
            ! class_exists(OntoEntry::class)
            || ! Schema::hasTable('onto_entries')
        ) {
            return 0.0;
        }

        [$from, $to] = $this->monthRange(
            $month,
            $year
        );

        $query = OntoEntry::query()
            ->with([
                'ontoRecord.wasteType',
            ])
            ->whereHas(
                'ontoRecord',
                function (
                    Builder $query
                ) use (
                    $ownerId
                ): void {
                    $query->where(
                        'user_id',
                        $ownerId
                    );
                }
            )
            ->whereBetween(
                'entry_date',
                [
                    $from,
                    $to,
                ]
            );

        /*
        * KPI prati samo stvarni izlaz otpada.
        */
        if (
            Schema::hasColumn(
                'onto_entries',
                'entry_type'
            )
        ) {
            $query->where(
                'entry_type',
                'output'
            );
        }

        $entries = $query->get();

        $sum = 0.0;

        foreach ($entries as $entry) {
            $wasteCode = (string) (
                $entry
                    ->ontoRecord
                    ?->wasteType
                    ?->waste_code
                ?? ''
            );

            if ($wasteCode === '') {
                continue;
            }

            $normalizedCode = (string) preg_replace(
                '/[^0-9]/',
                '',
                $wasteCode
            );

            $hasStar = str_contains(
                $wasteCode,
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
                    ?? 0
                );
            }
        }

        return round(
            $sum,
            4
        );
    }
    /**
     * Primjenjuje tenant/organization ograničenje na model.
     *
     * Prioritet:
     * 1. user_id
     * 2. owner_id
     *
     * Ako model nema nijedan od tih stupaca, query ostaje
     * nepromijenjen jer neki child modeli pripadaju
     * organizaciji preko roditeljskog zapisa.
     */
    protected function applyOwnerScope(
        Builder $query,
        string $table,
        int $ownerId
    ): Builder {
        if (
            Schema::hasColumn(
                $table,
                'user_id'
            )
        ) {
            return $query->where(
                "{$table}.user_id",
                $ownerId
            );
        }

        if (
            Schema::hasColumn(
                $table,
                'owner_id'
            )
        ) {
            return $query->where(
                "{$table}.owner_id",
                $ownerId
            );
        }

        return $query;
    }

    /**
     * Vraća prvi postojeći stupac iz liste kandidata.
     */
    protected function firstExistingColumn(
        string $table,
        array $columns
    ): ?string {
        foreach ($columns as $column) {
            if (
                Schema::hasColumn(
                    $table,
                    $column
                )
            ) {
                return $column;
            }
        }

        return null;
    }
}
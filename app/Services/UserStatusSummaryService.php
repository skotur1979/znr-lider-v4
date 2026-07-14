<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Fire;
use App\Models\Incident;
use App\Models\Inspection;
use App\Models\InspectionFinding;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\Observation;
use App\Models\User;
use App\Models\WasteTrackingForm;
use App\Models\WorkPermit;
use App\Models\WorkTask;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserStatusSummaryService
{
    public function getDailySummary(User $user): array
    {
        $today = now()->startOfDay();
        $future30 = now()->addDays(30)->endOfDay();

        $summary = $this->buildSummary($user, $today, $future30);

        return [
            'type' => 'daily',
            'title' => 'Dnevni status',
            'period_label' => now()->format('d.m.Y.'),
            'generated_at' => now(),
            'user' => $user,
            'summary' => $summary,
            'insight_text' => $this->generateDailyInsightText($summary),
        ];
    }

    public function getWeeklySummary(User $user): array
    {
        $start = now()->subWeek()->startOfWeek(Carbon::MONDAY);
        $end = now()->subWeek()->endOfWeek(Carbon::SUNDAY);

        $summary = $this->buildWeeklySummary($user, $start, $end);

        return [
            'type' => 'weekly',
            'title' => 'Tjedni status',
            'period_label' => $start->format('d.m.Y.') . ' - ' . $end->format('d.m.Y.'),
            'generated_at' => now(),
            'user' => $user,
            'summary' => $summary,
            'insight_text' => $this->generateWeeklyInsightText($summary),
        ];
    }

    protected function buildSummary(User $user, Carbon $today, Carbon $future30): array
    {
        $employees = $this->employeeQuery($user);
        $machines = $this->machineQuery($user);
        $fires = $this->fireQuery($user);
        $miscellaneous = $this->miscellaneousQuery($user);
        $observations = $this->observationQuery($user);
        $incidents = $this->incidentQuery($user);
        $findings = $this->inspectionFindingQuery($user);
        $wasteForms = $this->wasteTrackingFormsQuery($user);
        $workTasks = $this->workTaskQuery($user);
        $workPermits = $this->workPermitQuery($user);
        $firstAidItems = $this->firstAidItemsQuery($user);

        $employeeCertificates = $employees->clone()
            ->with('certificates')
            ->get()
            ->flatMap(fn ($employee) => $employee->certificates ?? collect());

        $znrExpired = $this->countZnrExpired($user, $today);

        $znrExpiring30 = $this->countZnrExpiring(
            $user,
            $today,
            $future30
        );

        $closedWorkTasksTotal = $workTasks->clone()
            ->where('is_done', true)
            ->count();

        $safetyMetrics = $this->safetyMetrics($user);

        return [
            'deadlines' => [

                'employees_expired' => $employees->clone()
                    ->whereNotNull('medical_examination_valid_until')
                    ->whereDate('medical_examination_valid_until', '<', $today)
                    ->count(),

                'employees_expiring_30' => $employees->clone()
                    ->whereNotNull('medical_examination_valid_until')
                    ->whereBetween('medical_examination_valid_until', [$today, $future30])
                    ->count(),

                'employee_certificates_expired' => $employeeCertificates
                    ->filter(fn ($certificate) =>
                        $certificate->valid_until &&
                        Carbon::parse($certificate->valid_until)->lt($today)
                    )
                    ->count() + $znrExpired,

                'employee_certificates_expiring_30' => $employeeCertificates
                    ->filter(fn ($certificate) =>
                        $certificate->valid_until &&
                        Carbon::parse($certificate->valid_until)->between($today, $future30)
                    )
                    ->count() + $znrExpiring30,

                'znr_training_expired' => $znrExpired,

                'znr_training_expiring_30' => $znrExpiring30,

                'machines_expired' => $machines->clone()
                    ->whereNotNull('examination_valid_until')
                    ->whereDate('examination_valid_until', '<', $today)
                    ->count(),

                'machines_expiring_30' => $machines->clone()
                    ->whereNotNull('examination_valid_until')
                    ->whereBetween('examination_valid_until', [$today, $future30])
                    ->count(),

                'fires_expired' => $fires->clone()
                    ->whereNotNull('examination_valid_until')
                    ->whereDate('examination_valid_until', '<', $today)
                    ->count(),

                'fires_expiring_30' => $fires->clone()
                    ->whereNotNull('examination_valid_until')
                    ->whereBetween('examination_valid_until', [$today, $future30])
                    ->count(),

                'miscellaneous_expired' => $miscellaneous->clone()
                    ->whereNotNull('examination_valid_until')
                    ->whereDate('examination_valid_until', '<', $today)
                    ->count(),

                'miscellaneous_expiring_30' => $miscellaneous->clone()
                    ->whereNotNull('examination_valid_until')
                    ->whereBetween('examination_valid_until', [$today, $future30])
                    ->count(),

                'work_permits_expired' => $workPermits->clone()
                    ->whereNotNull('valid_until')
                    ->whereDate('valid_until', '<', $today)
                    ->count(),

                'work_permits_expiring_30' => $workPermits->clone()
                    ->whereNotNull('valid_until')
                    ->whereBetween('valid_until', [$today, $future30])
                    ->count(),

                'first_aid_expired' => $firstAidItems
                    ->filter(fn ($item) =>
                        $item->valid_until &&
                        Carbon::parse($item->valid_until)->lt($today)
                    )
                    ->count(),

                'first_aid_expiring_30' => $firstAidItems
                    ->filter(fn ($item) =>
                        $item->valid_until &&
                        Carbon::parse($item->valid_until)->between($today, $future30)
                    )
                    ->count(),

                'observations_expired' => $observations->clone()
                    ->whereIn('status', ['Not started', 'In progress'])
                    ->whereNotNull('target_date')
                    ->whereDate('target_date', '<', $today)
                    ->count(),

                'observations_expiring_30' => $observations->clone()
                    ->whereIn('status', ['Not started', 'In progress'])
                    ->whereNotNull('target_date')
                    ->whereBetween('target_date', [$today, $future30])
                    ->count(),
            ],

            'actions' => [
                'observations_not_started' => $observations->clone()
                    ->where('status', 'Not started')
                    ->count(),

                'observations_in_progress' => $observations->clone()
                    ->where('status', 'In progress')
                    ->count(),

                'observations_open_total' => $observations->clone()
                    ->whereIn('status', ['Not started', 'In progress'])
                    ->count(),

                'incidents_open' => $incidents->clone()
                    ->where('active', 1)
                    ->count(),

                'inspection_findings_open' => $findings->clone()
                    ->whereIn('workflow_status', ['open', 'in_progress'])
                    ->count(),

                'waste_forms_open' => $wasteForms->clone()
                    ->whereIn('status', ['draft', 'open', 'pending'])
                    ->count(),

                'work_tasks_open' => $workTasks->clone()
                    ->where('is_done', false)
                    ->count(),

                'work_permits_open' => $workPermits->clone()
                    ->where(function ($query) use ($today) {
                        $query->whereNull('works_finished')
                            ->orWhere('works_finished', false)
                            ->orWhereDate('valid_until', '>=', $today);
                    })
                    ->count(),
            ],

            'closed' => [
                'work_tasks_closed_total' => $closedWorkTasksTotal,
            ],

            'totals' => [
                'employees' => $employees->clone()->count(),
                'machines' => $machines->clone()->count(),
                'fires' => $fires->clone()->count(),
                'miscellaneous' => $miscellaneous->clone()->count(),
                'observations' => $observations->clone()->count(),
                'incidents' => $incidents->clone()->count(),
                'inspection_findings' => $findings->clone()->count(),
                'waste_forms' => $wasteForms->clone()->count(),
                'work_tasks' => $workTasks->clone()->count(),
                'work_permits' => $workPermits->clone()->count(),
                'first_aid_items' => $firstAidItems->count(),
            ],

            'safety_metrics' => $safetyMetrics,
        ];
    }

    protected function countZnrExpired(User $user, Carbon $today): int
    {
        return $this->employeeQuery($user)
            ->whereNull('occupational_safety_valid_from')
            ->whereNotNull('employeed_at')
            ->whereDate(
                'employeed_at',
                '<',
                $today->copy()->subDays(60)
            )
            ->count();
    }

    protected function countZnrExpiring(
        User $user,
        Carbon $today,
        Carbon $future30
    ): int {
        return $this->employeeQuery($user)
            ->whereNull('occupational_safety_valid_from')
            ->whereNotNull('employeed_at')

            ->whereDate(
                'employeed_at',
                '>=',
                $today->copy()->subDays(60)
            )

            ->whereDate(
                'employeed_at',
                '<=',
                $today->copy()->subDays(30)
            )

            ->count();
    }

    protected function safetyMetrics(User $user): array
    {
        $today = Carbon::today();

        $incidents = $this->incidentQuery($user);

        $lastLtaDate = $incidents->clone()
            ->where('type_of_incident', 'LTA')
            ->whereNotNull('date_occurred')
            ->max('date_occurred');

        $firstIncidentDate = $incidents->clone()
            ->whereNotNull('date_occurred')
            ->min('date_occurred');

        if ($lastLtaDate) {
            $daysWithoutLta = Carbon::parse($lastLtaDate)
                ->startOfDay()
                ->diffInDays($today);
        } elseif ($firstIncidentDate) {
            $daysWithoutLta = Carbon::parse($firstIncidentDate)
                ->startOfDay()
                ->diffInDays($today);
        } else {
            $daysWithoutLta = 0;
        }

        $ltaDates = $incidents->clone()
            ->where('type_of_incident', 'LTA')
            ->whereNotNull('date_occurred')
            ->orderBy('date_occurred')
            ->pluck('date_occurred')
            ->map(fn ($date) => Carbon::parse($date)->startOfDay())
            ->values();

        $recordLtaDays = $daysWithoutLta;

        if ($ltaDates->count() >= 2) {

            foreach ($ltaDates as $index => $date) {

                if ($index === 0) {
                    continue;
                }

                $diff = $ltaDates[$index - 1]
                    ->diffInDays($date);

                if ($diff > $recordLtaDays) {
                    $recordLtaDays = $diff;
                }
            }
        }

        return [
            'days_without_lta' => (int) $daysWithoutLta,

            'record_lta_days' => (int) $recordLtaDays,

            'lta_count' => $incidents->clone()
                ->where('type_of_incident', 'LTA')
                ->count(),

            'mta_count' => $incidents->clone()
                ->where('type_of_incident', 'MTA')
                ->count(),

            'faa_count' => $incidents->clone()
                ->where('type_of_incident', 'FAA')
                ->count(),
        ];
    }

    protected function buildWeeklySummary(User $user, Carbon $start, Carbon $end): array
    {
        $employees = $this->employeeQuery($user);
        $machines = $this->machineQuery($user);
        $fires = $this->fireQuery($user);
        $miscellaneous = $this->miscellaneousQuery($user);
        $observations = $this->observationQuery($user);
        $incidents = $this->incidentQuery($user);
        $findings = $this->inspectionFindingQuery($user);
        $wasteForms = $this->wasteTrackingFormsQuery($user);
        $workTasks = $this->workTaskQuery($user);
        $workPermits = $this->workPermitQuery($user);

        return [
            'range' => [
                'from' => $start,
                'to' => $end,
            ],

            'created_last_week' => [
                'employees' => $employees->clone()->whereBetween('created_at', [$start, $end])->count(),
                'machines' => $machines->clone()->whereBetween('created_at', [$start, $end])->count(),
                'fires' => $fires->clone()->whereBetween('created_at', [$start, $end])->count(),
                'miscellaneous' => $miscellaneous->clone()->whereBetween('created_at', [$start, $end])->count(),
                'observations' => $observations->clone()->whereBetween('created_at', [$start, $end])->count(),
                'incidents' => $incidents->clone()->whereBetween('created_at', [$start, $end])->count(),
                'inspection_findings' => $findings->clone()->whereBetween('created_at', [$start, $end])->count(),
                'waste_forms' => $wasteForms->clone()->whereBetween('created_at', [$start, $end])->count(),
                'work_tasks' => $workTasks->clone()->whereBetween('created_at', [$start, $end])->count(),
                'work_permits' => $workPermits->clone()->whereBetween('created_at', [$start, $end])->count(),
            ],

            'closed_last_week' => [
                'observations_complete' => $observations->clone()
                    ->where('status', 'Complete')
                    ->whereBetween('updated_at', [$start, $end])
                    ->count(),

                'inspection_findings_closed' => $findings->clone()
                    ->whereIn('workflow_status', [
                        'closed',
                        'resolved_no_action',
                        'converted_to_observation',
                    ])
                    ->whereBetween('updated_at', [$start, $end])
                    ->count(),

                'work_tasks_closed' => $workTasks->clone()
                    ->where('is_done', true)
                    ->whereBetween('completed_at', [$start, $end])
                    ->count(),

                'work_permits_finished' => $workPermits->clone()
                    ->where('works_finished', true)
                    ->whereBetween('updated_at', [$start, $end])
                    ->count(),
            ],

            'current_state' => $this->buildSummary(
                $user,
                now()->startOfDay(),
                now()->addDays(30)->endOfDay()
            ),
        ];
    }

    protected function generateDailyInsightText(array $summary): string
    {
        $expired =
            ($summary['deadlines']['employees_expired'] ?? 0) +
            ($summary['deadlines']['employee_certificates_expired'] ?? 0) +
            ($summary['deadlines']['machines_expired'] ?? 0) +
            ($summary['deadlines']['fires_expired'] ?? 0) +
            ($summary['deadlines']['miscellaneous_expired'] ?? 0) +
            ($summary['deadlines']['work_permits_expired'] ?? 0) +
            ($summary['deadlines']['first_aid_expired'] ?? 0) +
            ($summary['deadlines']['observations_expired'] ?? 0);

        $soon =
            ($summary['deadlines']['employees_expiring_30'] ?? 0) +
            ($summary['deadlines']['employee_certificates_expiring_30'] ?? 0) +
            ($summary['deadlines']['machines_expiring_30'] ?? 0) +
            ($summary['deadlines']['fires_expiring_30'] ?? 0) +
            ($summary['deadlines']['miscellaneous_expiring_30'] ?? 0) +
            ($summary['deadlines']['work_permits_expiring_30'] ?? 0) +
            ($summary['deadlines']['first_aid_expiring_30'] ?? 0) +
            ($summary['deadlines']['observations_expiring_30'] ?? 0);

        $actions =
            ($summary['actions']['observations_open_total'] ?? 0) +
            ($summary['actions']['incidents_open'] ?? 0) +
            ($summary['actions']['inspection_findings_open'] ?? 0) +
            ($summary['actions']['waste_forms_open'] ?? 0) +
            ($summary['actions']['work_tasks_open'] ?? 0) +
            ($summary['actions']['work_permits_open'] ?? 0);

        $daysWithoutLta = $summary['safety_metrics']['days_without_lta'] ?? 0;

        if ($expired > 0) {
            return "Danas je prioritet rješavanje isteklih evidencija. Trenutno imate {$expired} isteklih stavki koje zahtijevaju hitnu reakciju, uz {$actions} otvorenih aktivnosti u sustavu. Trenutno stanje sigurnosti: {$daysWithoutLta} dana bez LTA.";
        }

        if ($soon > 0 && $actions > 0) {
            return "Sustav je stabilan, ali traži pravovremenu obradu. U sljedećih 30 dana istječe {$soon} stavki, a trenutno je otvoreno {$actions} aktivnosti koje je preporučeno riješiti što prije. Trenutno je {$daysWithoutLta} dana bez LTA.";
        }

        if ($soon > 0) {
            return "Trenutno nema kritičnih isteklih stavki. Fokus je na preventivi jer u sljedećih 30 dana istječe {$soon} evidencija. Trenutno je {$daysWithoutLta} dana bez LTA.";
        }

        if ($actions > 0) {
            return "Valjanosti su trenutno pod kontrolom, ali i dalje imate {$actions} otvorenih aktivnosti koje čekaju obradu u sustavu. Trenutno je {$daysWithoutLta} dana bez LTA.";
        }

        return "Trenutno stanje izgleda uredno. Nema kritičnih isteklih stavki ni otvorenih obveza koje traže hitnu reakciju. Trenutno je {$daysWithoutLta} dana bez LTA.";
    }

    protected function generateWeeklyInsightText(array $summary): string
    {
        $created = array_sum($summary['created_last_week'] ?? []);
        $closed = array_sum($summary['closed_last_week'] ?? []);

        $current = $summary['current_state'] ?? [];

        $expired =
            ($current['deadlines']['employees_expired'] ?? 0) +
            ($current['deadlines']['employee_certificates_expired'] ?? 0) +
            ($current['deadlines']['machines_expired'] ?? 0) +
            ($current['deadlines']['fires_expired'] ?? 0) +
            ($current['deadlines']['miscellaneous_expired'] ?? 0) +
            ($current['deadlines']['work_permits_expired'] ?? 0) +
            ($current['deadlines']['first_aid_expired'] ?? 0) +
            ($current['deadlines']['observations_expired'] ?? 0);

        $actions =
            ($current['actions']['observations_open_total'] ?? 0) +
            ($current['actions']['incidents_open'] ?? 0) +
            ($current['actions']['inspection_findings_open'] ?? 0) +
            ($current['actions']['waste_forms_open'] ?? 0) +
            ($current['actions']['work_tasks_open'] ?? 0) +
            ($current['actions']['work_permits_open'] ?? 0);

        $daysWithoutLta = $current['safety_metrics']['days_without_lta'] ?? 0;

        if ($created > $closed && $expired > 0) {
            return "Tijekom prošlog tjedna otvoreno je više stavki nego što ih je zatvoreno. Uz to trenutno postoji {$expired} isteklih evidencija, pa je preporuka fokusirati se na zatvaranje zaostataka i usklađivanje rokova. Trenutno je {$daysWithoutLta} dana bez LTA.";
        }

        if ($closed >= $created && $actions > 0) {
            return "Tjedan pokazuje dobar operativni ritam jer je zatvoreno {$closed} stavki, uz {$created} novootvorenih. Ipak, u sustavu je još uvijek aktivno {$actions} otvorenih aktivnosti koje traže daljnju obradu. Trenutno je {$daysWithoutLta} dana bez LTA.";
        }

        if ($closed >= $created && $expired === 0 && $actions === 0) {
            return "Prošli tjedan pokazuje uredno i stabilno stanje. Zatvaranje aktivnosti prati ili nadmašuje novi unos, a trenutno nema kritičnih evidencija ni otvorenih obveza. Trenutno je {$daysWithoutLta} dana bez LTA.";
        }

        return "Prošli tjedan donio je {$created} novih i {$closed} zatvorenih stavki. Trenutno stanje sustava traži praćenje otvorenih aktivnosti i pravovremeno zatvaranje obveza. Trenutno je {$daysWithoutLta} dana bez LTA.";
    }

        /**
     * ID glavnog korisnika organizacije.
     *
     * Glavni korisnik dobiva vlastiti ID.
     * Podkorisnik dobiva parent_user_id glavnog korisnika.
     */
    protected function ownerId(User $user): int
    {
        return (int) $user->ownerId();
    }

    protected function employeeQuery(User $user): Builder
    {
        return Employee::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function machineQuery(User $user): Builder
    {
        return Machine::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function fireQuery(User $user): Builder
    {
        return Fire::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function miscellaneousQuery(User $user): Builder
    {
        return Miscellaneous::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function observationQuery(User $user): Builder
    {
        return Observation::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function incidentQuery(User $user): Builder
    {
        return Incident::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function inspectionFindingQuery(User $user): Builder
    {
        $ownerId = $this->ownerId($user);

        return InspectionFinding::query()
            ->whereIn(
                'inspection_id',
                Inspection::query()
                    ->where('user_id', $ownerId)
                    ->select('id')
            );
    }

    protected function wasteTrackingFormsQuery(User $user): Builder
    {
        return WasteTrackingForm::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function workTaskQuery(User $user): Builder
    {
        return WorkTask::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function workPermitQuery(User $user): Builder
    {
        return WorkPermit::query()
            ->where('user_id', $this->ownerId($user));
    }

    protected function firstAidItemsQuery(User $user): Collection
    {
        if (! class_exists(\App\Models\FirstAidItem::class)) {
            return collect();
        }

        $ownerId = $this->ownerId($user);

        return \App\Models\FirstAidItem::query()
            ->whereHas('kit', function ($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })
            ->get();
    }
}
}
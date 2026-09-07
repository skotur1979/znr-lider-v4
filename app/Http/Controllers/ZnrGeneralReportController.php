<?php

namespace App\Http\Controllers;

use App\Filament\Widgets\TopSystemStatusBarWidget;
use App\Models\Budget;
use App\Models\Expense;
use App\Models\Incident;
use App\Models\PPEItem;
use App\Models\PPELog;
use App\Services\UserStatusSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ZnrGeneralReportController extends Controller
{
    public function pdf(Request $request)
    {
        $user = Auth::user();

        abort_unless($user, 403);

        $ownerId = (int) $user->ownerId();

        abort_if($ownerId <= 0, 403);

        /*
        |--------------------------------------------------------------------------
        | PRAVA PRISTUPA MODULIMA
        |--------------------------------------------------------------------------
        |
        | Superadmin vidi sve module u izvještaju.
        | Organizacijski korisnici i podkorisnici moraju imati view pravo.
        |
        */

        $canView = function (string $moduleKey) use ($user): bool {
            if ($user->isSuperAdmin()) {
                return true;
            }

            return $user->hasModulePermission(
                $moduleKey,
                'view'
            );
        };

        $access = [
            'employees' => $canView('employees'),
            'machines' => $canView('machines'),
            'fires' => $canView('fires'),
            'miscellaneous' => $canView('miscellaneous'),
            'first_aid' => $canView('first_aid'),
            'ppe_logs' => $canView('ppe_logs'),
            'observations' => $canView('observations'),
            'inspections' => $canView('inspections'),
            'incidents' => $canView('incidents'),
            'work_tasks' => $canView('work_tasks'),
            'expenses' => $canView('expenses'),
            'budgets' => $canView('budgets'),
        ];

        /*
        |--------------------------------------------------------------------------
        | GLOBALNI STATUS - FILTRIRAN PREMA PRAVIMA
        |--------------------------------------------------------------------------
        */

        $statusData =
            TopSystemStatusBarWidget::makeSystemStatusData(
                $user
            );

        $statusRowModule = function (string $label): ?string {
            $normalized = mb_strtolower(
                trim($label)
            );

            return match (true) {
                str_contains($normalized, 'liječnič') => 'employees',
                str_contains($normalized, 'edukacij') => 'employees',
                str_contains($normalized, 'osposoblj') => 'employees',
                str_contains($normalized, 'radna oprema') => 'machines',
                str_contains($normalized, 'aparat') => 'fires',
                str_contains($normalized, 'ostala ispit') => 'miscellaneous',
                str_contains($normalized, 'ozo') => 'ppe_logs',
                str_contains($normalized, 'prva pomoć') => 'first_aid',
                str_contains($normalized, 'zapažanj') => 'observations',
                str_contains($normalized, 'nadzor') => 'inspections',
                str_contains($normalized, 'radni zadac') => 'work_tasks',
                default => null,
            };
        };

        $rows = collect(
            $statusData['rows'] ?? []
        )
            ->filter(function (array $row) use (
                $statusRowModule,
                $access
            ): bool {
                $moduleKey = $statusRowModule(
                    (string) ($row['label'] ?? '')
                );

                /*
                 * Nepoznatu stavku ne prikazujemo jer joj ne možemo
                 * pouzdano odrediti modul i pravo pristupa.
                 */
                if (! $moduleKey) {
                    return false;
                }

                return $access[$moduleKey] ?? false;
            })
            ->map(
                fn (array $row): array => [
                    'label' => $row['label'] ?? '-',
                    'expired' => (int) ($row['expired_count'] ?? 0),
                    'soon' => (int) ($row['soon_count'] ?? 0),
                ]
            )
            ->values()
            ->all();

        /*
         * Ukupni status mora se računati samo iz dopuštenih redaka,
         * nikako iz nefiltriranih totalExpired / totalSoon vrijednosti widgeta.
         */
        $totalExpired = (int) collect($rows)->sum('expired');
        $totalSoon = (int) collect($rows)->sum('soon');

        $systemState = $totalExpired > 0
            ? 'critical'
            : ($totalSoon > 0 ? 'warning' : 'ok');

        $systemStatus = match ($systemState) {
            'critical' => 'KRITIČNO',
            'warning' => 'ZAHTIJEVA PAŽNJU',
            default => 'SVE U REDU',
        };

        /*
        |--------------------------------------------------------------------------
        | DNEVNI SAŽETAK
        |--------------------------------------------------------------------------
        */

        $daily = app(
            UserStatusSummaryService::class
        )->getDailySummary($user);

        $summaryData = $daily['summary'] ?? [];
        $deadlines = $summaryData['deadlines'] ?? [];
        $activityData = $summaryData['actions'] ?? [];
        $closedData = $summaryData['closed'] ?? [];
        $totals = $summaryData['totals'] ?? [];
        $safetyMetrics = $summaryData['safety_metrics'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | ZAPOSLENICI I OSPOSOBLJAVANJA
        |--------------------------------------------------------------------------
        */

        $employeesTotal = $access['employees']
            ? (int) ($totals['employees'] ?? 0)
            : 0;

        $medicalExpired = $access['employees']
            ? (int) ($deadlines['employees_expired'] ?? 0)
            : 0;

        $medicalSoon = $access['employees']
            ? (int) ($deadlines['employees_expiring_30'] ?? 0)
            : 0;

        $medicalValid = $access['employees']
            ? max(
                0,
                $employeesTotal - $medicalExpired - $medicalSoon
            )
            : 0;

        $znrExpired = $access['employees']
            ? (int) ($deadlines['znr_training_expired'] ?? 0)
            : 0;

        $znrSoon = $access['employees']
            ? (int) ($deadlines['znr_training_expiring_30'] ?? 0)
            : 0;

        $znrRecorded = $access['employees']
            ? max(
                0,
                $employeesTotal - $znrExpired - $znrSoon
            )
            : 0;

        $otherTrainingExpired = $access['employees']
            ? max(
                0,
                (int) ($deadlines['employee_certificates_expired'] ?? 0)
                - $znrExpired
            )
            : 0;

        $otherTrainingSoon = $access['employees']
            ? max(
                0,
                (int) ($deadlines['employee_certificates_expiring_30'] ?? 0)
                - $znrSoon
            )
            : 0;

        /*
        |--------------------------------------------------------------------------
        | VATROGASNI APARATI
        |--------------------------------------------------------------------------
        */

        $firesTotal = $access['fires']
            ? (int) ($totals['fires'] ?? 0)
            : 0;

        $firesExpired = $access['fires']
            ? (int) ($deadlines['fires_expired'] ?? 0)
            : 0;

        $firesSoon = $access['fires']
            ? (int) ($deadlines['fires_expiring_30'] ?? 0)
            : 0;

        $firesValid = $access['fires']
            ? max(
                0,
                $firesTotal - $firesExpired - $firesSoon
            )
            : 0;

        /*
        |--------------------------------------------------------------------------
        | ZAPAŽANJA / NADZORI / RADNI ZADACI
        |--------------------------------------------------------------------------
        */

        $openObservations = $access['observations']
            ? (int) ($activityData['observations_open_total'] ?? 0)
            : 0;

        $openInspectionFindings = $access['inspections']
            ? (int) ($activityData['inspection_findings_open'] ?? 0)
            : 0;

        $openWorkTasks = $access['work_tasks']
            ? (int) ($activityData['work_tasks_open'] ?? 0)
            : 0;

        $closedWorkTasks = $access['work_tasks']
            ? (int) ($closedData['work_tasks_closed_total'] ?? 0)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | OZO
        |--------------------------------------------------------------------------
        */

        $today = Carbon::today();
        $future30 = Carbon::today()->addDays(30);

        $ppeActive = 0;
        $ppeExpired = 0;
        $ppeSoon = 0;
        $ppeValid = 0;

        if ($access['ppe_logs']) {
            $ppeLogIds = PPELog::query()
                ->where('user_id', $ownerId)
                ->pluck('id');

            $ppeBase = PPEItem::query()
                ->whereIn(
                    'personal_protective_equipment_log_id',
                    $ppeLogIds
                )
                ->whereNull('return_date');

            $ppeActive = (clone $ppeBase)->count();

            $ppeExpired = (clone $ppeBase)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', $today)
                ->count();

            $ppeSoon = (clone $ppeBase)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', $future30)
                ->count();

            $ppeValid = max(
                0,
                $ppeActive - $ppeExpired - $ppeSoon
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SIGURNOSNI DOGAĐAJI
        |--------------------------------------------------------------------------
        */

        $daysWithoutLta = $access['incidents']
            ? (int) (
                $safetyMetrics['days_without_lta']
                ?? $statusData['daysWithoutLta']
                ?? 0
            )
            : 0;

        $ltaRecordDays = $access['incidents']
            ? (int) (
                $safetyMetrics['record_lta_days']
                ?? $statusData['recordDaysWithoutLta']
                ?? 0
            )
            : 0;

        $ltaCount = $access['incidents']
            ? (int) ($safetyMetrics['lta_count'] ?? 0)
            : 0;

        $mtaCount = $access['incidents']
            ? (int) ($safetyMetrics['mta_count'] ?? 0)
            : 0;

        $faaCount = $access['incidents']
            ? (int) ($safetyMetrics['faa_count'] ?? 0)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | TREND OZLJEDA
        |--------------------------------------------------------------------------
        */

        $currentYear = (int) now()->year;
        $previousYear = $currentYear - 1;

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

        $incidentTrend = [];
        $currentYearIncidents = 0;
        $previousYearIncidents = 0;
        $incidentDifference = 0;

        if ($access['incidents']) {
            $currentIncidentRows = Incident::query()
                ->where('user_id', $ownerId)
                ->whereYear('date_occurred', $currentYear)
                ->selectRaw('
                    MONTH(date_occurred) as month_no,
                    type_of_incident,
                    COUNT(*) as total
                ')
                ->groupBy(
                    'month_no',
                    'type_of_incident'
                )
                ->get();

            $previousIncidentRows = Incident::query()
                ->where('user_id', $ownerId)
                ->whereYear('date_occurred', $previousYear)
                ->selectRaw('
                    MONTH(date_occurred) as month_no,
                    COUNT(*) as total
                ')
                ->groupBy('month_no')
                ->get()
                ->keyBy('month_no');

            foreach ($monthNames as $monthNo => $monthName) {
                $monthRows = $currentIncidentRows
                    ->where('month_no', $monthNo);

                $currentTotal = (int) $monthRows->sum('total');

                $previousTotal = (int) (
                    $previousIncidentRows[$monthNo]?->total
                    ?? 0
                );

                $incidentTrend[] = [
                    'month_no' => $monthNo,
                    'month' => $monthName,
                    'current_total' => $currentTotal,
                    'previous_total' => $previousTotal,
                    'lta' => (int) $monthRows
                        ->where('type_of_incident', 'LTA')
                        ->sum('total'),
                    'mta' => (int) $monthRows
                        ->where('type_of_incident', 'MTA')
                        ->sum('total'),
                    'faa' => (int) $monthRows
                        ->where('type_of_incident', 'FAA')
                        ->sum('total'),
                ];
            }

            $currentYearIncidents = (int) collect($incidentTrend)
                ->sum('current_total');

            $previousYearIncidents = (int) collect($incidentTrend)
                ->sum('previous_total');

            $incidentDifference =
                $currentYearIncidents - $previousYearIncidents;
        }

        /*
        |--------------------------------------------------------------------------
        | TROŠKOVI I BUDŽET
        |--------------------------------------------------------------------------
        */

        $budgetAmount = 0.0;
        $expensesRealized = 0.0;
        $budgetRemaining = 0.0;
        $budgetUsagePercent = 0.0;
        $topExpenseCategories = [];

        if ($access['budgets']) {
            $budget = Budget::query()
                ->where('user_id', $ownerId)
                ->where('godina', $currentYear)
                ->first();

            $budgetAmount = (float) (
                $budget?->ukupni_budget
                ?? 0
            );
        }

        if ($access['expenses']) {
            $expenseBase = Expense::query()
                ->where('user_id', $ownerId)
                ->where('realizirano', true)
                ->whereHas(
                    'budget',
                    fn (Builder $query) =>
                        $query->where('godina', $currentYear)
                );

            $expensesRealized = (float) (clone $expenseBase)
                ->sum('iznos');

            $topExpenseCategories = (clone $expenseBase)
                ->with('category')
                ->get()
                ->groupBy(
                    fn (Expense $expense): string =>
                        trim(
                            (string) (
                                $expense->category?->name
                                ?? 'Bez kategorije'
                            )
                        )
                )
                ->map(
                    fn ($items, $label): array => [
                        'label' => $label,
                        'total' => (float) $items->sum('iznos'),
                        'count' => $items->count(),
                    ]
                )
                ->sortByDesc('total')
                ->take(5)
                ->values()
                ->all();
        }

        if ($access['budgets'] && $access['expenses']) {
            $budgetRemaining =
                $budgetAmount - $expensesRealized;

            $budgetUsagePercent = $budgetAmount > 0
                ? round(
                    ($expensesRealized / $budgetAmount) * 100,
                    1
                )
                : 0;
        }

        /*
        |--------------------------------------------------------------------------
        | PREPORUČENE AKCIJE
        |--------------------------------------------------------------------------
        */

        $actions = [];

        foreach ($rows as $row) {
            if (($row['expired'] ?? 0) > 0) {
                $actions[] =
                    "Pregledati i riješiti istekle stavke: {$row['label']} ({$row['expired']}).";
            }
        }

        if ($totalSoon > 0) {
            $actions[] =
                "Planirati aktivnosti za stavke koje istječu u sljedećih 30 dana ({$totalSoon}).";
        }

        if ($access['employees'] && $medicalExpired > 0) {
            $actions[] =
                "Prioritetno organizirati liječničke preglede za {$medicalExpired} zaposlenika s isteklom valjanošću.";
        }

        if ($access['employees'] && $znrExpired > 0) {
            $actions[] =
                "Osigurati ZNR osposobljavanje za {$znrExpired} zaposlenika kojima je prekoračen rok.";
        }

        if ($access['ppe_logs'] && $ppeExpired > 0) {
            $actions[] =
                "Pregledati {$ppeExpired} aktivnih OZO stavki kojima je istekao evidentirani rok uporabe.";
        }

        if ($access['fires'] && $firesExpired > 0) {
            $actions[] =
                "Organizirati pregled ili servis za {$firesExpired} vatrogasnih aparata s isteklom valjanošću.";
        }

        if ($access['inspections'] && $openInspectionFindings > 0) {
            $actions[] =
                "Pregledati i zatvoriti {$openInspectionFindings} otvorenih nalaza nadzora.";
        }

        if (
            $access['budgets']
            && $access['expenses']
            && $budgetAmount > 0
            && $budgetUsagePercent >= 90
        ) {
            $actions[] =
                "Budžet zaštite na radu iskorišten je {$budgetUsagePercent} %. Provjeriti planirane troškove do kraja godine.";
        }

        if (empty($actions)) {
            $actions[] =
                'Nastaviti redovito praćenje dostupnih pokazatelja i preventivno održavati sustav.';
        }

        /*
        |--------------------------------------------------------------------------
        | ZAKLJUČAK
        |--------------------------------------------------------------------------
        */

        $summary = match ($systemState) {
            'critical' =>
                "Sustav je kritičan zbog {$totalExpired} isteklih stavki u modulima kojima korisnik ima pristup. Potrebno je prioritetno riješiti istekle obveze kako bi se osigurala usklađenost i smanjili rizici.",

            'warning' =>
                "Sustav zahtijeva pažnju jer {$totalSoon} stavki u dostupnim modulima istječe unutar 30 dana. Potrebno je pravovremeno planirati aktivnosti.",

            default =>
                'Sustav je trenutno uredan u modulima kojima korisnik ima pristup. Nema isteklih stavki ni kritičnih rokova u sljedećih 30 dana.',
        };

        $data = [
            'reportDate' => now()->format('d.m.Y. H:i'),
            'access' => $access,
            'systemStatus' => $systemStatus,
            'systemState' => $systemState,
            'totalExpired' => $totalExpired,
            'totalSoon' => $totalSoon,
            'summary' => $summary,
            'rows' => $rows,
            'actions' => $actions,

            'daysWithoutLta' => $daysWithoutLta,
            'ltaRecordDays' => $ltaRecordDays,
            'ltaCount' => $ltaCount,
            'mtaCount' => $mtaCount,
            'faaCount' => $faaCount,

            'openObservations' => $openObservations,
            'openInspectionFindings' => $openInspectionFindings,
            'openWorkTasks' => $openWorkTasks,
            'closedWorkTasks' => $closedWorkTasks,

            'employeesTotal' => $employeesTotal,
            'medicalExpired' => $medicalExpired,
            'medicalSoon' => $medicalSoon,
            'medicalValid' => $medicalValid,
            'znrExpired' => $znrExpired,
            'znrSoon' => $znrSoon,
            'znrRecorded' => $znrRecorded,
            'otherTrainingExpired' => $otherTrainingExpired,
            'otherTrainingSoon' => $otherTrainingSoon,

            'ppeActive' => $ppeActive,
            'ppeExpired' => $ppeExpired,
            'ppeSoon' => $ppeSoon,
            'ppeValid' => $ppeValid,

            'firesTotal' => $firesTotal,
            'firesExpired' => $firesExpired,
            'firesSoon' => $firesSoon,
            'firesValid' => $firesValid,

            'currentYear' => $currentYear,
            'previousYear' => $previousYear,
            'budgetAmount' => $budgetAmount,
            'expensesRealized' => $expensesRealized,
            'budgetRemaining' => $budgetRemaining,
            'budgetUsagePercent' => $budgetUsagePercent,
            'topExpenseCategories' => $topExpenseCategories,

            'incidentTrend' => $incidentTrend,
            'currentYearIncidents' => $currentYearIncidents,
            'previousYearIncidents' => $previousYearIncidents,
            'incidentDifference' => $incidentDifference,
        ];

        $pdf = Pdf::loadView(
            'pdf.znr-general-report',
            $data
        )
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => false,
            ]);

        return $pdf->stream(
            'ZNR-izvjestaj-o-stanju-sustava.pdf'
        );
    }
}
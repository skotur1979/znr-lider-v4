<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Fire;
use App\Models\FirstAidItem;
use App\Models\Incident;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\Observation;
use App\Models\PPEItem;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class TopSystemStatusBarWidget extends Widget
{
    protected string $view = 'filament.widgets.top-system-status-bar-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -100;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    public const CRITICAL_EXPIRED_THRESHOLD = 10;

    protected function getViewData(): array
    {
        $user = Auth::user();

        $cacheKey = 'top_system_status_bar_' . ($user?->id ?? 'guest') . '_' . Carbon::today()->format('Y_m_d');

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($user): array {
            return static::makeSystemStatusData($user);
        });
    }

    public static function makeSystemStatusData(?User $user = null): array
    {
        $today = Carbon::today();
        $soonDate = Carbon::today()->addDays(30);

        $self = app(static::class);

        $rows = [
            [
                'label' => 'Liječnički pregledi',
                'icon' => '🩺',
                'expired_url' => $self->resolveEmployeesMedicalExpiredUrl(),
                'soon_url' => $self->resolveEmployeesMedicalSoonUrl(),
                ...$self->countEmployeeMedicalDeadlines($today, $soonDate, $user),
            ],
            [
                'label' => 'Edukacije',
                'icon' => '🎓',
                'expired_url' => $self->resolveEmployeesCertificatesExpiredUrl(),
                'soon_url' => $self->resolveEmployeesCertificatesSoonUrl(),
                ...$self->countEmployeeCertificateDeadlines($today, $soonDate, $user),
            ],
            [
                'label' => 'Radna oprema',
                'icon' => '⚙️',
                'expired_url' => $self->resolveMachinesExpiredUrl(),
                'soon_url' => $self->resolveMachinesSoonUrl(),
                ...$self->countSimpleDateDeadline(Machine::class, ['examination_valid_until'], $today, $soonDate, $user),
            ],
            [
                'label' => 'Vatrogasni aparati',
                'icon' => '🧯',
                'expired_url' => $self->resolveFiresExpiredUrl(),
                'soon_url' => $self->resolveFiresSoonUrl(),
                ...$self->countFireDeadlines($today, $soonDate, $user),
            ],
            [
                'label' => 'Ostala ispitavanja',
                'icon' => '🛠️',
                'expired_url' => $self->resolveMiscellaneousExpiredUrl(),
                'soon_url' => $self->resolveMiscellaneousSoonUrl(),
                ...$self->countSimpleDateDeadline(Miscellaneous::class, ['examination_valid_until'], $today, $soonDate, $user),
            ],
            [
                'label' => 'OZO - Osobna zaštitna oprema',
                'icon' => '🦺',
                'expired_url' => $self->resolvePpeExpiredUrl(),
                'soon_url' => $self->resolvePpeSoonUrl(),
                ...$self->countPpeDeadline($today, $soonDate, $user),
            ],
            [
                'label' => 'Prva pomoć - materijali',
                'icon' => '➕',
                'expired_url' => $self->resolveFirstAidExpiredUrl(),
                'soon_url' => $self->resolveFirstAidSoonUrl(),
                ...$self->countFirstAidDeadline($today, $soonDate, $user),
            ],
            [
                'label' => 'Zapažanja',
                'icon' => '👁️',
                'expired_url' => $self->resolveObservationsExpiredUrl(),
                'soon_url' => $self->resolveObservationsSoonUrl(),
                ...$self->countObservationDeadline($today, $soonDate, $user),
            ],
        ];

        $rows = collect($rows)
            ->filter(fn (array $row) => ($row['supported'] ?? true) === true)
            ->values()
            ->all();

        $totalExpired = collect($rows)->sum('expired_count');
        $totalSoon = collect($rows)->sum('soon_count');

        if ($totalExpired >= static::CRITICAL_EXPIRED_THRESHOLD) {
            $state = 'critical';
            $title = 'KRITIČNO';
            $message = "Isteklo: {$totalExpired}";
        } elseif ($totalExpired > 0 || $totalSoon > 0) {
            $state = 'warning';
            $title = 'UPOZORENJE';
            $message = $totalExpired > 0
                ? "Isteklo: {$totalExpired}"
                : "Uskoro istječe: {$totalSoon}";
        } else {
            $state = 'ok';
            $title = 'SVE U REDU';
            $message = 'Nema isteklih ni uskoro isteklih rokova';
        }

        $lta = $self->calculateLtaMetrics($user);

        return [
            'state' => $state,
            'title' => $title,
            'message' => $message,
            'totalExpired' => $totalExpired,
            'totalSoon' => $totalSoon,
            'rows' => $rows,
            'blink' => $totalExpired >= static::CRITICAL_EXPIRED_THRESHOLD,
            'daysWithoutLta' => $lta['days_without_lta'],
            'recordDaysWithoutLta' => $lta['record_days_without_lta'],
            'recordIsActive' => $lta['record_is_active'],
        ];
    }

    protected function organizationUserIds(?User $user): ?array
    {
        if (! $user) {
            return [];
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return null;
        }

        $ownerId = method_exists($user, 'ownerId')
            ? $user->ownerId()
            : ($user->parent_user_id ?: $user->id);

        return User::query()
            ->where('id', $ownerId)
            ->orWhere('parent_user_id', $ownerId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function applyOrganizationScope(Builder $query, string $table, ?User $user): bool
    {
        $userIds = $this->organizationUserIds($user);

        if ($userIds === null) {
            return true;
        }

        if (! $userIds) {
            $query->whereRaw('1 = 0');

            return true;
        }

        if (Schema::hasColumn($table, 'user_id')) {
            $query->whereIn($table . '.user_id', $userIds);

            return true;
        }

        return false;
    }

    protected function applyCommonScopes(Builder $query, object $model): void
    {
        $table = $model->getTable();

        if (Schema::hasColumn($table, 'active')) {
            $query->where($table . '.active', true);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }
    }

    protected function countEmployeeMedicalDeadlines(Carbon $today, Carbon $soonDate, ?User $user): array
    {
        if (! class_exists(Employee::class)) {
            return $this->unsupportedRow();
        }

        $model = new Employee();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'medical_examination_valid_until')) {
            return $this->unsupportedRow();
        }

        $query = Employee::query()->whereNotNull($table . '.medical_examination_valid_until');

        $this->applyCommonScopes($query, $model);
        $this->applyOrganizationScope($query, $table, $user);

        return $this->countDateQuery($query, $table . '.medical_examination_valid_until', $today, $soonDate);
    }

    protected function countEmployeeCertificateDeadlines(Carbon $today, Carbon $soonDate, ?User $user): array
    {
        if (! class_exists(Employee::class)) {
            return $this->unsupportedRow();
        }

        $employeeModel = new Employee();
        $employeeTable = $employeeModel->getTable();

        if (! Schema::hasTable($employeeTable)) {
            return $this->unsupportedRow();
        }

        $expiredCount = 0;
        $soonCount = 0;

        try {
            if (method_exists($employeeModel, 'certificates')) {
                $relatedModel = $employeeModel->certificates()->getRelated();
                $certTable = $relatedModel->getTable();

                if (Schema::hasTable($certTable) && Schema::hasColumn($certTable, 'valid_until')) {
                    $query = $relatedModel::query()->whereNotNull($certTable . '.valid_until');

                    if (Schema::hasColumn($certTable, 'active')) {
                        $query->where($certTable . '.active', true);
                    }

                    if (Schema::hasColumn($certTable, 'deleted_at')) {
                        $query->whereNull($certTable . '.deleted_at');
                    }

                    $userIds = $this->organizationUserIds($user);

                    if ($userIds !== null && method_exists($relatedModel, 'employee')) {
                        $query->whereHas('employee', function (Builder $q) use ($userIds): void {
                            $employeeTable = $q->getModel()->getTable();

                            if (Schema::hasColumn($employeeTable, 'user_id')) {
                                $q->whereIn($employeeTable . '.user_id', $userIds);
                            }

                            if (Schema::hasColumn($employeeTable, 'active')) {
                                $q->where($employeeTable . '.active', true);
                            }

                            if (Schema::hasColumn($employeeTable, 'deleted_at')) {
                                $q->whereNull($employeeTable . '.deleted_at');
                            }
                        });
                    }

                    $certificateCounts = $this->countDateQuery($query, $certTable . '.valid_until', $today, $soonDate);

                    $expiredCount += (int) $certificateCounts['expired_count'];
                    $soonCount += (int) $certificateCounts['soon_count'];
                }
            }

            $znrQuery = Employee::query()
                ->whereNull($employeeTable . '.occupational_safety_valid_from')
                ->whereNotNull($employeeTable . '.employeed_at');

            $this->applyCommonScopes($znrQuery, $employeeModel);
            $this->applyOrganizationScope($znrQuery, $employeeTable, $user);

            $expiredCount += (clone $znrQuery)
                ->whereDate($employeeTable . '.employeed_at', '<', $today->copy()->subDays(60))
                ->count();

            $soonCount += (clone $znrQuery)
                ->whereDate($employeeTable . '.employeed_at', '>=', $today->copy()->subDays(60))
                ->whereDate($employeeTable . '.employeed_at', '<=', $today->copy()->subDays(30))
                ->count();

            $deadlineColumns = [
                'first_aid_valid_until',
                'toxicology_valid_until',
                'handling_flammable_materials_valid_until',
                'employers_authorization_valid_until',
            ];

            foreach ($deadlineColumns as $column) {
                if (! Schema::hasColumn($employeeTable, $column)) {
                    continue;
                }

                $query = Employee::query()->whereNotNull($employeeTable . '.' . $column);

                $this->applyCommonScopes($query, $employeeModel);
                $this->applyOrganizationScope($query, $employeeTable, $user);

                $counts = $this->countDateQuery($query, $employeeTable . '.' . $column, $today, $soonDate);

                $expiredCount += (int) $counts['expired_count'];
                $soonCount += (int) $counts['soon_count'];
            }

            return [
                'expired_count' => $expiredCount,
                'soon_count' => $soonCount,
                'supported' => true,
            ];
        } catch (\Throwable $e) {
            return $this->unsupportedRow();
        }
    }

    protected function countSimpleDateDeadline(
        string $modelClass,
        array $dateColumns,
        Carbon $today,
        Carbon $soonDate,
        ?User $user,
    ): array {
        if (! class_exists($modelClass)) {
            return $this->unsupportedRow();
        }

        $model = new $modelClass();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return $this->unsupportedRow();
        }

        $dateColumn = $this->resolveDateColumn($table, $dateColumns);

        if (! $dateColumn) {
            return $this->unsupportedRow();
        }

        $query = $modelClass::query()->whereNotNull($table . '.' . $dateColumn);

        $this->applyCommonScopes($query, $model);
        $this->applyOrganizationScope($query, $table, $user);

        return $this->countDateQuery($query, $table . '.' . $dateColumn, $today, $soonDate);
    }

    protected function countFireDeadlines(Carbon $today, Carbon $soonDate, ?User $user): array
{
    $model = new Fire();
    $table = $model->getTable();

    if (! Schema::hasTable($table)) {
        return $this->unsupportedRow();
    }

    $query = Fire::query();

    $this->applyCommonScopes($query, $model);
    $this->applyOrganizationScope($query, $table, $user);

    $expiredPeriodic = (clone $query)
        ->whereNotNull('examination_valid_until')
        ->whereDate('examination_valid_until', '<', $today)
        ->count();

    $soonPeriodic = (clone $query)
        ->whereNotNull('examination_valid_until')
        ->whereDate('examination_valid_until', '>=', $today)
        ->whereDate('examination_valid_until', '<=', $soonDate)
        ->count();

    $expiredRegular = (clone $query)
        ->whereNotNull('regular_examination_valid_from')
        ->whereDate(\DB::raw('DATE_ADD(regular_examination_valid_from, INTERVAL 3 MONTH)'), '<', $today)
        ->count();

    $soonRegular = (clone $query)
        ->whereNotNull('regular_examination_valid_from')
        ->whereDate(\DB::raw('DATE_ADD(regular_examination_valid_from, INTERVAL 3 MONTH)'), '>=', $today)
        ->whereDate(\DB::raw('DATE_ADD(regular_examination_valid_from, INTERVAL 3 MONTH)'), '<=', $soonDate)
        ->count();

    return [
        'expired_count' => $expiredPeriodic + $expiredRegular,
        'soon_count' => $soonPeriodic + $soonRegular,
        'supported' => true,
    ];
}
    protected function countPpeDeadline(Carbon $today, Carbon $soonDate, ?User $user): array
    {
        if (! class_exists(PPEItem::class)) {
            return $this->unsupportedRow();
        }

        $model = new PPEItem();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'end_date')) {
            return $this->unsupportedRow();
        }

        $query = PPEItem::query()
            ->whereNull($table . '.return_date')
            ->whereNotNull($table . '.end_date');

        $this->applyCommonScopes($query, $model);

        if (! $this->applyOrganizationScope($query, $table, $user)) {
            $userIds = $this->organizationUserIds($user);

            if ($userIds !== null) {
                $query->whereHas('log', function (Builder $relatedQuery) use ($userIds): void {
                    $relatedTable = $relatedQuery->getModel()->getTable();

                    if (Schema::hasColumn($relatedTable, 'user_id')) {
                        $relatedQuery->whereIn($relatedTable . '.user_id', $userIds);
                    }
                });
            }
        }

        return $this->countDateQuery($query, $table . '.end_date', $today, $soonDate);
    }

    protected function countFirstAidDeadline(Carbon $today, Carbon $soonDate, ?User $user): array
    {
        if (! class_exists(FirstAidItem::class)) {
            return $this->unsupportedRow();
        }

        $model = new FirstAidItem();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'valid_until')) {
            return $this->unsupportedRow();
        }

        $query = FirstAidItem::query()->whereNotNull($table . '.valid_until');

        $this->applyCommonScopes($query, $model);

        if (! $this->applyOrganizationScope($query, $table, $user)) {
            $userIds = $this->organizationUserIds($user);

            if ($userIds !== null) {
                $query->whereHas('kit', function (Builder $relatedQuery) use ($userIds): void {
                    $relatedTable = $relatedQuery->getModel()->getTable();

                    if (Schema::hasColumn($relatedTable, 'user_id')) {
                        $relatedQuery->whereIn($relatedTable . '.user_id', $userIds);
                    }
                });
            }
        }

        return $this->countDateQuery($query, $table . '.valid_until', $today, $soonDate);
    }

    protected function countObservationDeadline(Carbon $today, Carbon $soonDate, ?User $user): array
    {
        if (! class_exists(Observation::class)) {
            return $this->unsupportedRow();
        }

        $model = new Observation();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'target_date')) {
            return $this->unsupportedRow();
        }

        $query = Observation::query()
            ->whereNotNull($table . '.target_date')
            ->whereIn($table . '.status', ['Not started', 'In progress']);

        $this->applyCommonScopes($query, $model);
        $this->applyOrganizationScope($query, $table, $user);

        return $this->countDateQuery($query, $table . '.target_date', $today, $soonDate);
    }

    protected function countDateQuery(
        Builder $query,
        string $dateColumn,
        Carbon $today,
        Carbon $soonDate
    ): array {
        $todayStart = $today->copy()->startOfDay();
        $soonEnd = $soonDate->copy()->endOfDay();

        $result = (clone $query)
            ->selectRaw("
                SUM(CASE WHEN {$dateColumn} < ? THEN 1 ELSE 0 END) as expired_count,
                SUM(CASE WHEN {$dateColumn} >= ? AND {$dateColumn} <= ? THEN 1 ELSE 0 END) as soon_count
            ", [
                $todayStart,
                $todayStart,
                $soonEnd,
            ])
            ->first();

        return [
            'expired_count' => (int) ($result->expired_count ?? 0),
            'soon_count' => (int) ($result->soon_count ?? 0),
            'supported' => true,
        ];
    }

    protected function calculateLtaMetrics(?User $user): array
    {
        $baseQuery = Incident::query()->withoutTrashed();

        $userIds = $this->organizationUserIds($user);

        if ($userIds !== null) {
            $baseQuery->whereIn('user_id', $userIds);
        }

        $ltaDates = (clone $baseQuery)
            ->where('type_of_incident', 'like', '%LTA%')
            ->whereNotNull('date_occurred')
            ->orderBy('date_occurred')
            ->pluck('date_occurred')
            ->map(fn ($date) => Carbon::parse($date)->startOfDay())
            ->values();

        $daysWithoutLta = null;
        $recordDaysWithoutLta = null;

        $lastLtaDate = $ltaDates->last();

        if ($lastLtaDate) {
            $daysWithoutLta = $lastLtaDate->diffInDays(Carbon::today());
        }

        if ($ltaDates->count() > 0) {
            $record = 0;

            for ($i = 0; $i < $ltaDates->count() - 1; $i++) {
                $daysBetween = $ltaDates[$i]->diffInDays($ltaDates[$i + 1]);

                if ($daysBetween > $record) {
                    $record = $daysBetween;
                }
            }

            if ($daysWithoutLta !== null && $daysWithoutLta > $record) {
                $record = $daysWithoutLta;
            }

            $recordDaysWithoutLta = $record;
        }

        $recordIsActive = $daysWithoutLta !== null
            && $recordDaysWithoutLta !== null
            && $daysWithoutLta >= $recordDaysWithoutLta;

        return [
            'days_without_lta' => $daysWithoutLta,
            'record_days_without_lta' => $recordDaysWithoutLta,
            'record_is_active' => $recordIsActive,
        ];
    }

    protected function resolveDateColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function unsupportedRow(): array
    {
        return [
            'expired_count' => 0,
            'soon_count' => 0,
            'supported' => false,
        ];
    }

    protected function resolveEmployeesMedicalExpiredUrl(): string
    {
        return url('/admin/employees?pregled=medical_expired');
    }

    protected function resolveEmployeesMedicalSoonUrl(): string
    {
        return url('/admin/employees?pregled=medical_expiring');
    }

    protected function resolveEmployeesCertificatesExpiredUrl(): string
    {
        return url('/admin/employees?pregled=certificates_expired');
    }

    protected function resolveEmployeesCertificatesSoonUrl(): string
    {
        return url('/admin/employees?pregled=certificates_expiring');
    }

    protected function resolveMachinesExpiredUrl(): string
    {
        return url('/admin/machines?pregled=isteklo');
    }

    protected function resolveMachinesSoonUrl(): string
    {
        return url('/admin/machines?pregled=uskoro');
    }

    protected function resolveFiresExpiredUrl(): string
    {
        return url('/admin/fires?pregled=isteklo');
    }

    protected function resolveFiresSoonUrl(): string
    {
        return url('/admin/fires?pregled=uskoro');
    }

    protected function resolveMiscellaneousExpiredUrl(): string
    {
        return url('/admin/miscellaneouses?pregled=isteklo');
    }

    protected function resolveMiscellaneousSoonUrl(): string
    {
        return url('/admin/miscellaneouses?pregled=uskoro');
    }

    protected function resolvePpeExpiredUrl(): string
    {
        if (class_exists(\App\Filament\Resources\PPELogs\PPELogResource::class)) {
            return \App\Filament\Resources\PPELogs\PPELogResource::getUrl('index', [
                'pregled' => 'isteklo',
            ]);
        }

        return url('/admin/ppe-logs?pregled=isteklo');
    }

    protected function resolvePpeSoonUrl(): string
    {
        if (class_exists(\App\Filament\Resources\PPELogs\PPELogResource::class)) {
            return \App\Filament\Resources\PPELogs\PPELogResource::getUrl('index', [
                'pregled' => 'uskoro',
            ]);
        }

        return url('/admin/ppe-logs?pregled=uskoro');
    }

    protected function resolveFirstAidExpiredUrl(): string
    {
        return url('/admin/first-aid-kits?pregled=isteklo');
    }

    protected function resolveFirstAidSoonUrl(): string
    {
        return url('/admin/first-aid-kits?pregled=uskoro');
    }

    protected function resolveObservationsExpiredUrl(): string
    {
        return url('/admin/observations?pregled=isteklo');
    }

    protected function resolveObservationsSoonUrl(): string
    {
        return url('/admin/observations?pregled=uskoro');
    }
}
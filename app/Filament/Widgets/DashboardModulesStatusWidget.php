<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Fires\FireResource;
use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use App\Filament\Resources\Machines\MachineResource;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Filament\Resources\Observations\ObservationResource;
use App\Filament\Resources\PPELogs\PPELogResource;
use App\Filament\Resources\WorkTasks\WorkTaskResource;
use App\Models\Employee;
use App\Models\Fire;
use App\Models\FirstAidItem;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\Observation;
use App\Models\PPEItem;
use App\Models\User;
use App\Models\WorkTask;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class DashboardModulesStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-modules-status-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -95;

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected function getViewData(): array
    {
        $user = Auth::user();

        $cacheKey = 'dashboard_modules_status_' . ($user?->id ?? 'guest') . '_' . Carbon::today()->format('Y_m_d');

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($user): array {
            $today = Carbon::today();
            $soonDate = Carbon::today()->addDays(30);

            return [
                'rows' => [
                    [
                        'label' => 'Liječnički',
                        'display_label' => 'Zaposlenici - Liječnički pregledi',
                        'icon' => '🩺',
                        'total_label' => 'Ukupno zaposlenika',
                        'total_count' => $this->countEmployees($user),
                        'total_url' => EmployeeResource::getUrl('index'),
                        'expired_url' => $this->resolveEmployeesMedicalExpiredUrl(),
                        'soon_url' => $this->resolveEmployeesMedicalSoonUrl(),
                        ...$this->countEmployeeMedicalDeadlines($today, $soonDate, $user),
                    ],
                    [
                        'label' => 'Edukacije',
                        'display_label' => 'Zaposlenici - Edukacije',
                        'icon' => '🎓',
                        'total_label' => 'Ukupno zaposlenika',
                        'total_count' => $this->countEmployees($user),
                        'total_url' => EmployeeResource::getUrl('index'),
                        'expired_url' => $this->resolveEmployeesCertificatesExpiredUrl(),
                        'soon_url' => $this->resolveEmployeesCertificatesSoonUrl(),
                        ...$this->countEmployeeEducationDeadlines($today, $soonDate, $user),
                    ],
                    [
                        'label' => 'Radna oprema',
                        'display_label' => 'Radna oprema',
                        'icon' => '⚙️',
                        'total_label' => 'Ukupno',
                        'total_count' => $this->countSimpleModel(Machine::class, $user),
                        'total_url' => MachineResource::getUrl('index'),
                        'expired_url' => $this->resolveMachinesExpiredUrl(),
                        'soon_url' => $this->resolveMachinesSoonUrl(),
                        ...$this->countSimpleDateDeadline(Machine::class, ['examination_valid_until'], $today, $soonDate, $user),
                    ],
                    [
                        'label' => 'Aparati',
                        'display_label' => 'Vatrogasni aparati',
                        'icon' => '🧯',
                        'total_label' => 'Ukupno',
                        'total_count' => $this->countSimpleModel(Fire::class, $user),
                        'total_url' => FireResource::getUrl('index'),
                        'expired_url' => $this->resolveFiresExpiredUrl(),
                        'soon_url' => $this->resolveFiresSoonUrl(),
                        ...$this->countFireDeadlines($today, $soonDate, $user),
                    ],
                    [
                        'label' => 'Ostala ispit.',
                        'display_label' => 'Ostala ispitivanja',
                        'icon' => '🛠️',
                        'total_label' => 'Ukupno',
                        'total_count' => $this->countSimpleModel(Miscellaneous::class, $user),
                        'total_url' => MiscellaneousResource::getUrl('index'),
                        'expired_url' => $this->resolveMiscellaneousExpiredUrl(),
                        'soon_url' => $this->resolveMiscellaneousSoonUrl(),
                        ...$this->countSimpleDateDeadline(Miscellaneous::class, ['examination_valid_until'], $today, $soonDate, $user),
                    ],
                    [
                        'label' => 'OZO',
                        'display_label' => 'OZO - Osobna zaštitna oprema',
                        'icon' => '🦺',
                        'total_label' => 'Ukupno',
                        'total_count' => $this->countPpeItems($user),
                        'total_url' => PPELogResource::getUrl('index'),
                        'expired_url' => $this->resolvePpeExpiredUrl(),
                        'soon_url' => $this->resolvePpeSoonUrl(),
                        ...$this->countPpeDeadline($today, $soonDate, $user),
                    ],
                    [
                        'label' => 'Prva pomoć',
                        'display_label' => 'Prva pomoć materijali',
                        'icon' => '➕',
                        'total_label' => 'Ukupno',
                        'total_count' => $this->countFirstAidItems($user),
                        'total_url' => FirstAidKitResource::getUrl('index'),
                        'expired_url' => $this->resolveFirstAidExpiredUrl(),
                        'soon_url' => $this->resolveFirstAidSoonUrl(),
                        ...$this->countFirstAidDeadline($today, $soonDate, $user),
                    ],
                    [
                        'label' => 'Zapažanja',
                        'display_label' => 'Zapažanja',
                        'icon' => '👁️',
                        'total_label' => 'Ukupno',
                        'total_count' => $this->countOpenObservations($user),
                        'total_url' => ObservationResource::getUrl('index'),
                        'expired_url' => $this->resolveObservationsExpiredUrl(),
                        'soon_url' => $this->resolveObservationsSoonUrl(),
                        ...$this->countObservationDeadline($today, $soonDate, $user),
                    ],
                    [
                        'label' => 'Radni zadaci',
                        'display_label' => 'Radni zadaci',
                        'icon' => '✅',
                        'total_label' => 'Ukupno',
                        'total_count' => $this->countWorkTasks($user),
                        'expired_count' => $this->countOpenWorkTasks($user),
                        'soon_count' => $this->countClosedWorkTasks($user),
                        'expired_label' => 'Otvoreni',
                        'soon_label' => 'Zatvoreni',
                        'expired_url' => WorkTaskResource::getUrl('index', ['status' => 'open']),
                        'soon_url' => WorkTaskResource::getUrl('index', ['status' => 'closed']),
                        'total_url' => WorkTaskResource::getUrl('index', ['status' => 'all']),
                        'supported' => true,
                        'is_work_tasks' => true,
                    ],
                ],
            ];
        });
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

    protected function countSimpleModel(string $modelClass, ?User $user): int
    {
        if (! class_exists($modelClass)) {
            return 0;
        }

        $model = new $modelClass();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = $modelClass::query();

        $this->applyCommonScopes($query, $model);
        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
    }

    protected function countEmployees(?User $user): int
    {
        return $this->countSimpleModel(Employee::class, $user);
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

    protected function countEmployeeEducationDeadlines(Carbon $today, Carbon $soonDate, ?User $user): array
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
    protected function countPpeItems(?User $user): int
    {
        if (! class_exists(PPEItem::class)) {
            return 0;
        }

        $model = new PPEItem();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = PPEItem::query();

        if (Schema::hasColumn($table, 'return_date')) {
            $query->whereNull($table . '.return_date');
        }

        if (! $this->applyOrganizationScope($query, $table, $user)) {
            $userIds = $this->organizationUserIds($user);

            if ($userIds !== null) {
                $query->whereHas('log', function (Builder $q) use ($userIds): void {
                    $relatedTable = $q->getModel()->getTable();

                    if (Schema::hasColumn($relatedTable, 'user_id')) {
                        $q->whereIn($relatedTable . '.user_id', $userIds);
                    }
                });
            }
        }

        return $query->count();
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

    protected function countFirstAidItems(?User $user): int
    {
        if (! class_exists(FirstAidItem::class)) {
            return 0;
        }

        $model = new FirstAidItem();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = FirstAidItem::query();

        if (! $this->applyOrganizationScope($query, $table, $user)) {
            $userIds = $this->organizationUserIds($user);

            if ($userIds !== null) {
                $query->whereHas('kit', function (Builder $q) use ($userIds): void {
                    $relatedTable = $q->getModel()->getTable();

                    if (Schema::hasColumn($relatedTable, 'user_id')) {
                        $q->whereIn($relatedTable . '.user_id', $userIds);
                    }
                });
            }
        }

        return $query->count();
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

    protected function countOpenObservations(?User $user): int
    {
        if (! class_exists(Observation::class)) {
            return 0;
        }

        $model = new Observation();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = Observation::query()
            ->whereIn($table . '.status', ['Not started', 'In progress']);

        $this->applyCommonScopes($query, $model);
        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
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

    protected function countWorkTasks(?User $user): int
    {
        return $this->countSimpleModel(WorkTask::class, $user);
    }

    protected function countOpenWorkTasks(?User $user): int
    {
        if (! class_exists(WorkTask::class)) {
            return 0;
        }

        $model = new WorkTask();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = WorkTask::query()->where($table . '.is_done', false);

        $this->applyCommonScopes($query, $model);
        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
    }

    protected function countClosedWorkTasks(?User $user): int
    {
        if (! class_exists(WorkTask::class)) {
            return 0;
        }

        $model = new WorkTask();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = WorkTask::query()->where($table . '.is_done', true);

        $this->applyCommonScopes($query, $model);
        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
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
        return PPELogResource::getUrl('index', [
            'pregled' => 'isteklo',
        ]);
    }

    protected function resolvePpeSoonUrl(): string
    {
        return PPELogResource::getUrl('index', [
            'pregled' => 'uskoro',
        ]);
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
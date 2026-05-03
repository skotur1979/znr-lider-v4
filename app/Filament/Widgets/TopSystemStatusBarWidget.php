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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class TopSystemStatusBarWidget extends Widget
{
    protected string $view = 'filament.widgets.top-system-status-bar-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -100;

    public const CRITICAL_EXPIRED_THRESHOLD = 10;

    protected function getViewData(): array
    {
        return static::makeSystemStatusData(Auth::user());
    }

    public static function makeSystemStatusData(?User $user = null): array
    {
        $today = Carbon::today();
        $soonDate = Carbon::today()->addDays(30);

        $self = app(static::class);

        $rows = [
            [
                'label' => 'Liječnički',
                'icon' => '🩺',
                'expired_url' => $self->resolveEmployeesMedicalExpiredUrl(),
                ...$self->countEmployeeMedicalDeadlines($today, $soonDate, $user),
            ],
            [
                'label' => 'Edukacije',
                'icon' => '🎓',
                'expired_url' => $self->resolveEmployeesCertificatesExpiredUrl(),
                ...$self->countEmployeeCertificateDeadlines($today, $soonDate, $user),
            ],
            [
                'label' => 'Radna oprema',
                'icon' => '⚙️',
                'expired_url' => $self->resolveMachinesExpiredUrl(),
                ...$self->countSimpleDateDeadline(Machine::class, ['examination_valid_until'], $today, $soonDate, $user),
            ],
            [
                'label' => 'Aparati',
                'icon' => '🧯',
                'expired_url' => $self->resolveFiresExpiredUrl(),
                ...$self->countSimpleDateDeadline(Fire::class, ['examination_valid_until'], $today, $soonDate, $user),
            ],
            [
                'label' => 'Ostala ispit.',
                'icon' => '🛠️',
                'expired_url' => $self->resolveMiscellaneousExpiredUrl(),
                ...$self->countSimpleDateDeadline(Miscellaneous::class, ['examination_valid_until'], $today, $soonDate, $user),
            ],
            [
                'label' => 'OZO',
                'icon' => '🦺',
                'expired_url' => $self->resolvePpeExpiredUrl(),
                ...$self->countPpeDeadline($today, $soonDate, $user),
            ],
            [
                'label' => 'Prva pomoć',
                'icon' => '🩹',
                'expired_url' => $self->resolveFirstAidExpiredUrl(),
                ...$self->countFirstAidDeadline($today, $soonDate, $user),
            ],
            [
                'label' => 'Zapažanja',
                'icon' => '👁️',
                'expired_url' => $self->resolveObservationsExpiredUrl(),
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

        if (! method_exists($employeeModel, 'certificates')) {
            return $this->unsupportedRow();
        }

        try {
            $relatedModel = $employeeModel->certificates()->getRelated();
            $certTable = $relatedModel->getTable();

            if (! Schema::hasTable($certTable) || ! Schema::hasColumn($certTable, 'valid_until')) {
                return $this->unsupportedRow();
            }

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
                });
            }

            return $this->countDateQuery($query, $certTable . '.valid_until', $today, $soonDate);
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

        /** @var Model $model */
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

        $query = PPEItem::query()->whereNotNull($table . '.end_date');

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

    protected function countDateQuery(Builder $query, string $dateColumn, Carbon $today, Carbon $soonDate): array
    {
        $expired = (clone $query)
            ->whereDate($dateColumn, '<', $today)
            ->count();

        $soon = (clone $query)
            ->whereDate($dateColumn, '>=', $today)
            ->whereDate($dateColumn, '<=', $soonDate)
            ->count();

        return [
            'expired_count' => (int) $expired,
            'soon_count' => (int) $soon,
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

    protected function resolveEmployeesCertificatesExpiredUrl(): string
    {
        return url('/admin/employees?pregled=certificates_expired');
    }

    protected function resolveMachinesExpiredUrl(): string
    {
        return url('/admin/machines?pregled=isteklo');
    }

    protected function resolveFiresExpiredUrl(): string
    {
        return url('/admin/fires?pregled=isteklo');
    }

    protected function resolveMiscellaneousExpiredUrl(): string
    {
        return url('/admin/miscellaneouses?pregled=isteklo');
    }

    protected function resolvePpeExpiredUrl(): string
    {
        if (class_exists(\App\Filament\Resources\PpeLogs\PPELogResource::class)) {
            return \App\Filament\Resources\PpeLogs\PPELogResource::getUrl('index', [
                'pregled' => 'isteklo',
            ]);
        }

        return url('/admin/ppe-logs?pregled=isteklo');
    }

    protected function resolveFirstAidExpiredUrl(): string
    {
        return url('/admin/first-aid-kits?pregled=isteklo');
    }

    protected function resolveObservationsExpiredUrl(): string
    {
        return url('/admin/observations?pregled=isteklo');
    }
}
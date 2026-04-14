<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Fire;
use App\Models\FirstAidItem;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\Observation;
use App\Models\PPEItem;
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

    protected function getViewData(): array
    {
        $today = Carbon::today();
        $soonDate = Carbon::today()->addDays(30);

        $rows = [
            [
                'label' => 'Liječnički',
                'icon' => '🩺',
                'expired_url' => $this->resolveEmployeesMedicalExpiredUrl(),
                ...$this->countEmployeeMedicalDeadlines($today, $soonDate),
            ],
            [
                'label' => 'Edukacije',
                'icon' => '🎓',
                'expired_url' => $this->resolveEmployeesCertificatesExpiredUrl(),
                ...$this->countEmployeeCertificateDeadlines($today, $soonDate),
            ],
            [
                'label' => 'Radna oprema',
                'icon' => '⚙️',
                'expired_url' => $this->resolveMachinesExpiredUrl(),
                ...$this->countSimpleDateDeadline(
                    Machine::class,
                    ['examination_valid_until'],
                    $today,
                    $soonDate
                ),
            ],
            [
                'label' => 'Aparati',
                'icon' => '🧯',
                'expired_url' => $this->resolveFiresExpiredUrl(),
                ...$this->countSimpleDateDeadline(
                    Fire::class,
                    ['examination_valid_until'],
                    $today,
                    $soonDate
                ),
            ],
            [
                'label' => 'Ostala ispit.',
                'icon' => '🛠️',
                'expired_url' => $this->resolveMiscellaneousExpiredUrl(),
                ...$this->countSimpleDateDeadline(
                    Miscellaneous::class,
                    ['examination_valid_until'],
                    $today,
                    $soonDate
                ),
            ],
            [
                'label' => 'OZO',
                'icon' => '🦺',
                'expired_url' => $this->resolvePpeExpiredUrl(),
                ...$this->countPpeDeadline($today, $soonDate),
            ],
            [
                'label' => 'Prva pomoć',
                'icon' => '🩹',
                'expired_url' => $this->resolveFirstAidExpiredUrl(),
                ...$this->countFirstAidDeadline($today, $soonDate),
            ],
            [
                'label' => 'Zapažanja',
                'icon' => '👁️',
                'expired_url' => $this->resolveObservationsExpiredUrl(),
                ...$this->countObservationDeadline($today, $soonDate),
            ],
        ];

        $rows = collect($rows)
            ->filter(fn (array $row) => ($row['supported'] ?? true) === true)
            ->values()
            ->all();

        $totalExpired = collect($rows)->sum('expired_count');
        $totalSoon = collect($rows)->sum('soon_count');

        if ($totalExpired > 0) {
            $state = 'critical';
            $title = 'KRITIČNO';
            $message = "Isteklo: {$totalExpired}";
        } elseif ($totalSoon > 0) {
            $state = 'warning';
            $title = 'UPOZORENJE';
            $message = "Uskoro istječe: {$totalSoon}";
        } else {
            $state = 'ok';
            $title = 'SVE U REDU';
            $message = 'Nema isteklih ni uskoro isteklih rokova';
        }

        return [
            'state' => $state,
            'title' => $title,
            'message' => $message,
            'totalExpired' => $totalExpired,
            'totalSoon' => $totalSoon,
            'rows' => $rows,
            'blink' => $totalExpired > 0,
        ];
    }

    protected function countEmployeeMedicalDeadlines(Carbon $today, Carbon $soonDate): array
    {
        if (! class_exists(Employee::class)) {
            return $this->unsupportedRow();
        }

        $model = new Employee();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'medical_examination_valid_until')) {
            return $this->unsupportedRow();
        }

        $query = Employee::query()
            ->whereNotNull($table . '.medical_examination_valid_until');

        $this->applyCommonScopes($query, $model);
        $this->applyDirectUserScope($query, $table);

        $expired = (clone $query)
            ->whereDate($table . '.medical_examination_valid_until', '<', $today)
            ->count();

        $soon = (clone $query)
            ->whereDate($table . '.medical_examination_valid_until', '>=', $today)
            ->whereDate($table . '.medical_examination_valid_until', '<=', $soonDate)
            ->count();

        return [
            'expired_count' => (int) $expired,
            'soon_count' => (int) $soon,
            'supported' => true,
        ];
    }

    protected function countEmployeeCertificateDeadlines(Carbon $today, Carbon $soonDate): array
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

            $query = $relatedModel::query()
                ->whereNotNull($certTable . '.valid_until');

            if (Schema::hasColumn($certTable, 'active')) {
                $query->where($certTable . '.active', true);
            }

            if (Schema::hasColumn($certTable, 'deleted_at')) {
                $query->whereNull($certTable . '.deleted_at');
            }

            $user = Auth::user();

            if ($user && (! method_exists($user, 'isAdmin') || ! $user->isAdmin())) {
                if (method_exists($relatedModel, 'employee')) {
                    $query->whereHas('employee', function (Builder $q) use ($user): void {
                        $employeeTable = $q->getModel()->getTable();

                        if (Schema::hasColumn($employeeTable, 'user_id')) {
                            $q->where($employeeTable . '.user_id', $user->id);
                        }
                    });
                }
            }

            $expired = (clone $query)
                ->whereDate($certTable . '.valid_until', '<', $today)
                ->count();

            $soon = (clone $query)
                ->whereDate($certTable . '.valid_until', '>=', $today)
                ->whereDate($certTable . '.valid_until', '<=', $soonDate)
                ->count();

            return [
                'expired_count' => (int) $expired,
                'soon_count' => (int) $soon,
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

        $query = $modelClass::query()
            ->whereNotNull($table . '.' . $dateColumn);

        $this->applyCommonScopes($query, $model);
        $this->applyDirectUserScope($query, $table);

        $expired = (clone $query)
            ->whereDate($table . '.' . $dateColumn, '<', $today)
            ->count();

        $soon = (clone $query)
            ->whereDate($table . '.' . $dateColumn, '>=', $today)
            ->whereDate($table . '.' . $dateColumn, '<=', $soonDate)
            ->count();

        return [
            'expired_count' => (int) $expired,
            'soon_count' => (int) $soon,
            'supported' => true,
        ];
    }

    protected function countPpeDeadline(Carbon $today, Carbon $soonDate): array
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
            ->whereNotNull($table . '.end_date');

        if (! $this->applyDirectUserScope($query, $table)) {
            $query->whereHas('log', function (Builder $relatedQuery): void {
                $relatedTable = $relatedQuery->getModel()->getTable();

                if (Schema::hasColumn($relatedTable, 'user_id')) {
                    $relatedQuery->where($relatedTable . '.user_id', Auth::id());
                }
            });
        }

        $expired = (clone $query)
            ->whereDate($table . '.end_date', '<', $today)
            ->count();

        $soon = (clone $query)
            ->whereDate($table . '.end_date', '>=', $today)
            ->whereDate($table . '.end_date', '<=', $soonDate)
            ->count();

        return [
            'expired_count' => (int) $expired,
            'soon_count' => (int) $soon,
            'supported' => true,
        ];
    }

    protected function countFirstAidDeadline(Carbon $today, Carbon $soonDate): array
    {
        if (! class_exists(FirstAidItem::class)) {
            return $this->unsupportedRow();
        }

        $model = new FirstAidItem();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'valid_until')) {
            return $this->unsupportedRow();
        }

        $query = FirstAidItem::query()
            ->whereNotNull($table . '.valid_until');

        if (! $this->applyDirectUserScope($query, $table)) {
            $query->whereHas('kit', function (Builder $relatedQuery): void {
                $relatedTable = $relatedQuery->getModel()->getTable();

                if (Schema::hasColumn($relatedTable, 'user_id')) {
                    $relatedQuery->where($relatedTable . '.user_id', Auth::id());
                }
            });
        }

        $expired = (clone $query)
            ->whereDate($table . '.valid_until', '<', $today)
            ->count();

        $soon = (clone $query)
            ->whereDate($table . '.valid_until', '>=', $today)
            ->whereDate($table . '.valid_until', '<=', $soonDate)
            ->count();

        return [
            'expired_count' => (int) $expired,
            'soon_count' => (int) $soon,
            'supported' => true,
        ];
    }

    protected function countObservationDeadline(Carbon $today, Carbon $soonDate): array
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
        $this->applyDirectUserScope($query, $table);

        $expired = (clone $query)
            ->whereDate($table . '.target_date', '<', $today)
            ->count();

        $soon = (clone $query)
            ->whereDate($table . '.target_date', '>=', $today)
            ->whereDate($table . '.target_date', '<=', $soonDate)
            ->count();

        return [
            'expired_count' => (int) $expired,
            'soon_count' => (int) $soon,
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

    protected function applyDirectUserScope(Builder $query, string $table): bool
    {
        $user = Auth::user();

        if (! $user) {
            return true;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        if (Schema::hasColumn($table, 'user_id')) {
            $query->where($table . '.user_id', $user->id);
            return true;
        }

        return false;
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
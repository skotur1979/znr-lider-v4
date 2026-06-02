<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Fires\FireResource;
use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use App\Filament\Resources\Machines\MachineResource;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Filament\Resources\Observations\ObservationResource;
use App\Filament\Resources\WorkTasks\WorkTaskResource;
use App\Filament\Resources\PPELogs\PPELogResource;
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
            $statusData = TopSystemStatusBarWidget::makeSystemStatusData($user);

            $rows = collect($statusData['rows'] ?? [])
                ->map(function (array $row) use ($user): array {
                    $label = $row['label'] ?? '';

                    $row['total_count'] = match ($label) {
                    'Liječnički' => $this->countEmployees($user),
                    'Edukacije' => $this->countEmployees($user),
                    'Radna oprema' => $this->countSimpleModel(Machine::class, $user),
                    'Aparati' => $this->countSimpleModel(Fire::class, $user),
                    'Ostala ispit.' => $this->countSimpleModel(Miscellaneous::class, $user),
                    'OZO' => $this->countPpeItems($user),
                    'Prva pomoć' => $this->countFirstAidItems($user),
                    'Zapažanja' => $this->countOpenObservations($user),
                    default => 0,
                };

                $row['display_label'] = match ($label) {
                    'Liječnički' => 'Zaposlenici - Liječnički pregledi',
                    'Edukacije' => 'Zaposlenici - Edukacije',
                    'Radna oprema' => 'Radna oprema',
                    'Aparati' => 'Vatrogasni aparati',
                    'Ostala ispit.' => 'Ostala ispitivanja',
                    'OZO' => 'OZO - Osobna zaštitna oprema',
                    'Prva pomoć' => 'Prva pomoć materijali',
                    'Zapažanja' => 'Zapažanja',
                    default => $label,
                };

                $row['total_label'] = match ($label) {
                    'Liječnički', 'Edukacije' => 'Ukupno zaposlenika',
                    default => 'Ukupno',
                };

                $row['total_url'] = match ($label) {
                'Liječnički', 'Edukacije' => EmployeeResource::getUrl('index'),
                'Radna oprema' => MachineResource::getUrl('index'),
                'Aparati' => FireResource::getUrl('index'),
                'Ostala ispit.' => MiscellaneousResource::getUrl('index'),
                'OZO' => PPELogResource::getUrl('index'),
                'Prva pomoć' => FirstAidKitResource::getUrl('index'),
                'Zapažanja' => ObservationResource::getUrl('index'),
                default => null,
            };

                    return $row;
                })
                ->values()
                ->all();

            $rows[] = [
                'label' => 'Radni zadaci',
                'icon' => '✅',
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
            ];

            return [
                'rows' => $rows,
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

    protected function applyOrganizationScope(Builder $query, string $table, ?User $user): Builder
    {
        $userIds = $this->organizationUserIds($user);

        if ($userIds === null) {
            return $query;
        }

        if (! $userIds) {
            return $query->whereRaw('1 = 0');
        }

        if (Schema::hasColumn($table, 'user_id')) {
            return $query->whereIn($table . '.user_id', $userIds);
        }

        return $query;
    }

    protected function applyCommonScopes(Builder $query, string $table): Builder
    {
        if (Schema::hasColumn($table, 'active')) {
            $query->where($table . '.active', true);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }

        return $query;
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

        $this->applyCommonScopes($query, $table);
        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
    }

    protected function countEmployees(?User $user): int
    {
        return $this->countSimpleModel(Employee::class, $user);
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

        if (! Schema::hasColumn($table, 'user_id')) {
            $userIds = $this->organizationUserIds($user);

            if ($userIds !== null) {
                $query->whereHas('log', function (Builder $q) use ($userIds): void {
                    $relatedTable = $q->getModel()->getTable();

                    if (Schema::hasColumn($relatedTable, 'user_id')) {
                        $q->whereIn($relatedTable . '.user_id', $userIds);
                    }
                });
            }

            return $query->count();
        }

        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
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

        if (! Schema::hasColumn($table, 'user_id')) {
            $userIds = $this->organizationUserIds($user);

            if ($userIds !== null) {
                $query->whereHas('kit', function (Builder $q) use ($userIds): void {
                    $relatedTable = $q->getModel()->getTable();

                    if (Schema::hasColumn($relatedTable, 'user_id')) {
                        $q->whereIn($relatedTable . '.user_id', $userIds);
                    }
                });
            }

            return $query->count();
        }

        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
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

        $this->applyCommonScopes($query, $table);
        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
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

        $query = WorkTask::query()->where($table . '.is_done', false);

        $this->applyCommonScopes($query, $table);
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

        $query = WorkTask::query()->where($table . '.is_done', true);

        $this->applyCommonScopes($query, $table);
        $this->applyOrganizationScope($query, $table, $user);

        return $query->count();
    }
}
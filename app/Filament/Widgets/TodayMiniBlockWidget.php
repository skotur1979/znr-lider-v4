<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\Fire;
use App\Models\FirstAidItem;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\Observation;
use App\Models\PPEItem;
use App\Models\WorkTask;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Cache;

class TodayMiniBlockWidget extends Widget
{
    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.today-mini-block-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 40;

    #[Url(as: 'selected_date', history: true)]
    public ?string $selected_date = null;

    public function mount(): void
    {
        $this->selected_date = request()->query('selected_date');
    }

    protected function getViewData(): array
{
    $userId = Auth::id() ?? 'guest';

    return Cache::remember(
        'today_mini_block_' . $userId . '_' . ($this->selected_date ?? 'today'),
        now()->addMinutes(3),
        function (): array {

            $day = $this->selected_date
                ? Carbon::parse($this->selected_date)->startOfDay()
                : Carbon::today();

            $tasksForDay = $this->getTasksForDay($day);
            $deadlines = $this->getDeadlinesBreakdownForDay($day);

            $taskCount = $tasksForDay->count();
            $deadlineCount = collect($deadlines)->sum();

            $taskTitles = $tasksForDay
                ->take(3)
                ->pluck('title')
                ->map(fn (?string $title) => Str::limit((string) $title, 24))
                ->values()
                ->all();

            $extraTasksCount = max($taskCount - count($taskTitles), 0);

            return [
                'dayLabel' => $day->isToday() ? 'Danas' : 'Odabrani datum',
                'dayDateLabel' => $day->format('d.m.Y.'),
                'taskCount' => $taskCount,
                'deadlineCount' => $deadlineCount,
                'taskTitles' => $taskTitles,
                'extraTasksCount' => $extraTasksCount,
                'deadlines' => $deadlines,
                'hasAnything' => $taskCount > 0 || $deadlineCount > 0,
                'tasksUrl' => $this->resolveTasksDayUrl($day),
                'calendarUrl' => $this->resolveCalendarUrl($day),
            ];
        }
    );
}

    protected function getTasksForDay(Carbon $day)
    {
        if (! class_exists(WorkTask::class)) {
            return collect();
        }

        $model = new WorkTask();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'due_date')) {
            return collect();
        }

        $query = WorkTask::query()
            ->whereDate('due_date', $day->toDateString())
            ->where('is_done', false);

        $this->applyDirectUserScope($query, $table);

        return $query
            ->orderBy('due_date')
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    protected function getDeadlinesBreakdownForDay(Carbon $day): array
    {
        return array_filter([
            'Liječnički' => $this->countEmployeeMedicalForDay($day),
            'Edukacije' => $this->countEmployeeCertificatesForDay($day),
            'Radna oprema' => $this->countSimpleDateForDay(Machine::class, ['examination_valid_until'], $day),
            'Aparati' => $this->countSimpleDateForDay(Fire::class, ['examination_valid_until'], $day),
            'Ostala ispit.' => $this->countSimpleDateForDay(Miscellaneous::class, ['examination_valid_until'], $day),
            'OZO' => $this->countPpeForDay($day),
            'Prva pomoć' => $this->countFirstAidForDay($day),
            'Zapažanja' => $this->countObservationsForDay($day),
        ], fn ($count) => $count > 0);
    }

    protected function countEmployeeMedicalForDay(Carbon $day): int
    {
        if (! class_exists(Employee::class)) {
            return 0;
        }

        $model = new Employee();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'medical_examination_valid_until')) {
            return 0;
        }

        $query = Employee::query()
            ->whereNotNull($table . '.medical_examination_valid_until')
            ->whereDate($table . '.medical_examination_valid_until', $day->toDateString());

        $this->applyCommonScopes($query, $model);
        $this->applyDirectUserScope($query, $table);

        return (int) $query->count();
    }

    protected function countEmployeeCertificatesForDay(Carbon $day): int
    {
        if (! class_exists(Employee::class)) {
            return 0;
        }

        $employeeModel = new Employee();

        if (! method_exists($employeeModel, 'certificates')) {
            return 0;
        }

        try {
            $relatedModel = $employeeModel->certificates()->getRelated();
            $certTable = $relatedModel->getTable();

            if (! Schema::hasTable($certTable) || ! Schema::hasColumn($certTable, 'valid_until')) {
                return 0;
            }

            $query = $relatedModel::query()
                ->whereNotNull($certTable . '.valid_until')
                ->whereDate($certTable . '.valid_until', $day->toDateString());

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

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    protected function countSimpleDateForDay(string $modelClass, array $dateColumns, Carbon $day): int
    {
        if (! class_exists($modelClass)) {
            return 0;
        }

        /** @var Model $model */
        $model = new $modelClass();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return 0;
        }

        $dateColumn = $this->resolveDateColumn($table, $dateColumns);

        if (! $dateColumn) {
            return 0;
        }

        $query = $modelClass::query()
            ->whereNotNull($table . '.' . $dateColumn)
            ->whereDate($table . '.' . $dateColumn, $day->toDateString());

        $this->applyCommonScopes($query, $model);
        $this->applyDirectUserScope($query, $table);

        return (int) $query->count();
    }

    protected function countPpeForDay(Carbon $day): int
    {
        if (! class_exists(PPEItem::class)) {
            return 0;
        }

        $model = new PPEItem();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'end_date')) {
            return 0;
        }

        $query = PPEItem::query()
            ->whereNotNull($table . '.end_date')
            ->whereDate($table . '.end_date', $day->toDateString());

        if (! $this->applyDirectUserScope($query, $table)) {
            $query->whereHas('log', function (Builder $relatedQuery): void {
                $relatedTable = $relatedQuery->getModel()->getTable();

                if (Schema::hasColumn($relatedTable, 'user_id')) {
                    $relatedQuery->where($relatedTable . '.user_id', Auth::id());
                }
            });
        }

        return (int) $query->count();
    }

    protected function countFirstAidForDay(Carbon $day): int
    {
        if (! class_exists(FirstAidItem::class)) {
            return 0;
        }

        $model = new FirstAidItem();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'valid_until')) {
            return 0;
        }

        $query = FirstAidItem::query()
            ->whereNotNull($table . '.valid_until')
            ->whereDate($table . '.valid_until', $day->toDateString());

        if (! $this->applyDirectUserScope($query, $table)) {
            $query->whereHas('kit', function (Builder $relatedQuery): void {
                $relatedTable = $relatedQuery->getModel()->getTable();

                if (Schema::hasColumn($relatedTable, 'user_id')) {
                    $relatedQuery->where($relatedTable . '.user_id', Auth::id());
                }
            });
        }

        return (int) $query->count();
    }

    protected function countObservationsForDay(Carbon $day): int
    {
        if (! class_exists(Observation::class)) {
            return 0;
        }

        $model = new Observation();
        $table = $model->getTable();

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'target_date')) {
            return 0;
        }

        $query = Observation::query()
            ->whereNotNull($table . '.target_date')
            ->whereIn($table . '.status', ['Not started', 'In progress'])
            ->whereDate($table . '.target_date', $day->toDateString());

        $this->applyCommonScopes($query, $model);
        $this->applyDirectUserScope($query, $table);

        return (int) $query->count();
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

    protected function resolveTasksDayUrl(Carbon $day): string
    {
        if (class_exists(\App\Filament\Resources\WorkTasks\WorkTaskResource::class)) {
            return \App\Filament\Resources\WorkTasks\WorkTaskResource::getUrl('index', [
                'pregled' => 'dan',
                'datum' => $day->toDateString(),
            ]);
        }

        return url('/admin/work-tasks?pregled=dan&datum=' . $day->toDateString());
    }

    protected function resolveCalendarUrl(Carbon $day): string
    {
        return url('/admin?calendar_month=' . $day->month
            . '&calendar_year=' . $day->year
            . '&selected_date=' . $day->toDateString()) . '#today-mini-block';
    }
}
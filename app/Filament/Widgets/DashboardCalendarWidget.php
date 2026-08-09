<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Fire;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\WorkTask;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Url;

class DashboardCalendarWidget extends Widget
{
    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.dashboard-calendar-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 50;

    #[Url(as: 'calendar_month', history: true)]
    public int $calendar_month;

    #[Url(as: 'calendar_year', history: true)]
    public int $calendar_year;

    #[Url(as: 'selected_date', history: true)]
    public ?string $selected_date = null;

    public bool $showTaskModal = false;
    public ?int $editingTaskId = null;
    public ?string $taskDate = null;
    public string $taskTitle = '';
    public ?string $taskDescription = null;

    protected function cacheKey(): string
    {
        return 'dashboard-calendar-' . (auth()->id() ?? 'guest')
            . '-' . $this->calendar_month
            . '-' . $this->calendar_year
            . '-' . ($this->selected_date ?: 'none')
            . '-' . now()->format('Y-m-d-H');
    }

    protected function clearCalendarCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    protected function ownerId(): ?int
    {
        return Auth::user()?->ownerId();
    }

    protected function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    protected function applyOwnerScope(Builder $query): Builder
    {
        if ($this->isSuperAdmin()) {
            return $query;
        }

        $ownerId = $this->ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('user_id', $ownerId);
    }

    protected function applyActiveScope(Builder $query, string $table): Builder
    {
        if (Schema::hasColumn($table, 'active')) {
            $query->where($table . '.active', true);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }

        return $query;
    }

    public function mount(): void
    {
        $this->calendar_month = (int) request()->query('calendar_month', now()->month);
        $this->calendar_year = (int) request()->query('calendar_year', now()->year);
        $this->selected_date = request()->query('selected_date');
    }

    public function previousMonth(): void
    {
        $current = Carbon::create($this->calendar_year, $this->calendar_month, 1)->subMonth();

        $this->calendar_month = (int) $current->month;
        $this->calendar_year = (int) $current->year;
        $this->selected_date = null;
    }

    public function nextMonth(): void
    {
        $current = Carbon::create($this->calendar_year, $this->calendar_month, 1)->addMonth();

        $this->calendar_month = (int) $current->month;
        $this->calendar_year = (int) $current->year;
        $this->selected_date = null;
    }

    public function openTaskCreateModal(?string $date = null): void
    {
        $this->editingTaskId = null;
        $this->taskDate = $date ?: now()->toDateString();
        $this->taskTitle = '';
        $this->taskDescription = null;
        $this->showTaskModal = true;
    }

    public function openTaskEditModal(int $taskId): void
    {
        $query = WorkTask::query()->whereKey($taskId);
        $this->applyOwnerScope($query);

        $task = $query->first();

        if (! $task) {
            return;
        }

        $this->editingTaskId = $task->id;
        $this->taskDate = $task->due_date?->toDateString();
        $this->taskTitle = $task->title;
        $this->taskDescription = $task->description;
        $this->showTaskModal = true;
    }

    public function closeTaskModal(): void
    {
        $this->editingTaskId = null;
        $this->taskDate = null;
        $this->taskTitle = '';
        $this->taskDescription = null;
        $this->showTaskModal = false;
        $this->resetValidation();
    }

    public function saveTask(): void
    {
        $data = $this->validate([
            'taskTitle' => ['required', 'string', 'max:120'],
            'taskDescription' => ['nullable', 'string', 'max:1000'],
            'taskDate' => ['required', 'date'],
        ]);

        if ($this->editingTaskId) {
            $query = WorkTask::query()->whereKey($this->editingTaskId);
            $this->applyOwnerScope($query);

            $task = $query->first();

            if ($task) {
                $task->update([
                    'title' => $data['taskTitle'],
                    'description' => $data['taskDescription'],
                    'due_date' => $data['taskDate'],
                ]);
            }
        } else {
            WorkTask::create([
                'user_id' => $this->ownerId(),
                'title' => $data['taskTitle'],
                'description' => $data['taskDescription'],
                'due_date' => $data['taskDate'],
                'is_done' => false,
                'completed_at' => null,
            ]);
        }

        $this->clearCalendarCache();

        $this->dispatch('work-task-updated');
        $this->closeTaskModal();
    }

    public function completeTask(int $taskId): void
    {
        $query = WorkTask::query()->whereKey($taskId);
        $this->applyOwnerScope($query);

        $task = $query->first();

        if (! $task || $task->is_done) {
            return;
        }

        $task->update([
            'is_done' => true,
            'completed_at' => now(),
        ]);

        $this->clearCalendarCache();

        $this->dispatch('work-task-updated');
    }

    public function reopenTask(int $taskId): void
    {
        $query = WorkTask::query()->whereKey($taskId);
        $this->applyOwnerScope($query);

        $task = $query->first();

        if (! $task || ! $task->is_done) {
            return;
        }

        $task->update([
            'is_done' => false,
            'completed_at' => null,
        ]);

        $this->clearCalendarCache();

        $this->dispatch('work-task-updated');
    }

    public function deleteTask(int $taskId): void
    {
        $query = WorkTask::query()->whereKey($taskId);
        $this->applyOwnerScope($query);

        $task = $query->first();

        if (! $task) {
            return;
        }

        $task->delete();

        if ($this->editingTaskId === $taskId) {
            $this->closeTaskModal();
        }

        $this->clearCalendarCache();

        $this->dispatch('work-task-updated');
    }

    public function getOpenWorkTasksCountProperty(): int
    {
        $query = WorkTask::query()->where('is_done', false);
        $this->applyOwnerScope($query);

        return $query->count();
    }

    public function getClosedWorkTasksCountProperty(): int
    {
        $query = WorkTask::query()->where('is_done', true);
        $this->applyOwnerScope($query);

        return $query->count();
    }

    public function getViewData(): array
    {
        return Cache::remember(
            $this->cacheKey(),
            now()->addMinutes(5),
            function (): array {
                $current = Carbon::create($this->calendar_year, $this->calendar_month, 1)->startOfMonth();

                $start = $current->copy()->startOfWeek(Carbon::MONDAY);
                $end = $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

                $selectedDate = $this->selected_date
                    ? Carbon::parse($this->selected_date)->toDateString()
                    : null;

                $itemsByDate = $this->buildItems($start, $end)
                    ->groupBy(fn (array $item) => $item['date']->format('Y-m-d'));

                $days = collect(CarbonPeriod::create($start, $end))
                    ->map(function (Carbon $day) use ($current, $selectedDate, $itemsByDate) {
                        $key = $day->format('Y-m-d');
                        $collection = $itemsByDate->get($key, collect());

                        return [
                            'date' => $day->copy(),
                            'in_month' => $day->month === $current->month,
                            'is_selected' => $selectedDate === $day->toDateString(),
                            'items' => $collection->take(5)->values()->all(),
                            'all_items' => $collection->values()->all(),
                            'extra_count' => max(0, $collection->count() - 5),
                            'total_count' => $collection->count(),
                            'day_url' => url('/admin?calendar_month=' . $this->calendar_month
                                . '&calendar_year=' . $this->calendar_year
                                . '&selected_date=' . $day->toDateString()),
                        ];
                    })
                    ->values();

                return [
                    'monthLabel' => $current->translatedFormat('F Y'),
                    'days' => $days->chunk(7),
                    'weekdays' => ['pon.', 'uto.', 'sri.', 'čet.', 'pet.', 'sub.', 'ned.'],
                    'selectedDate' => $selectedDate,
                ];
            }
        );
    }

    protected function buildItems(Carbon $start, Carbon $end): Collection
    {
        return collect()
            ->merge($this->employeeMedicalItems($start, $end))
            ->merge($this->employeeCertificateItems($start, $end))
            ->merge($this->machineItems($start, $end))
            ->merge($this->fireItems($start, $end))
            ->merge($this->miscellaneousItems($start, $end))
            ->merge($this->workTaskItems($start, $end))
            ->sortBy([
                fn ($a, $b) => strcmp($a['date']->format('Y-m-d'), $b['date']->format('Y-m-d')),
                fn ($a, $b) => (($a['sort'] ?? 50) <=> ($b['sort'] ?? 50)),
                fn ($a, $b) => strcmp($a['title'], $b['title']),
            ])
            ->values();
    }

    protected function employeeBaseQuery(): Builder
    {
        return $this->applyActiveScope(
            $this->applyOwnerScope(Employee::query()),
            (new Employee())->getTable()
        );
    }

    protected function machineBaseQuery(): Builder
    {
        return $this->applyActiveScope(
            $this->applyOwnerScope(Machine::query()),
            (new Machine())->getTable()
        );
    }

    protected function fireBaseQuery(): Builder
    {
        return $this->applyActiveScope(
            $this->applyOwnerScope(Fire::query()),
            (new Fire())->getTable()
        );
    }

    protected function miscellaneousBaseQuery(): Builder
    {
        return $this->applyActiveScope(
            $this->applyOwnerScope(Miscellaneous::query()),
            (new Miscellaneous())->getTable()
        );
    }

    protected function workTaskBaseQuery(): Builder
    {
        return $this->applyActiveScope(
            $this->applyOwnerScope(WorkTask::query()),
            (new WorkTask())->getTable()
        );
    }

    protected function employeeMedicalItems(Carbon $start, Carbon $end): Collection
    {
        return $this->employeeBaseQuery()
            ->select(['id', 'name', 'medical_examination_valid_until'])
            ->whereNotNull('medical_examination_valid_until')
            ->whereBetween('medical_examination_valid_until', [$start->toDateString(), $end->toDateString()])
            ->orderBy('medical_examination_valid_until')
            ->get()
            ->map(function (Employee $employee) {
                return [
                    'date' => Carbon::parse($employee->medical_examination_valid_until),
                    'title' => 'Liječnički: ' . $employee->name,
                    'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('view', [
                        'record' => $employee,
                    ]),
                    'class' => 'medical',
                    'type' => 'default',
                    'sort' => 10,
                ];
            });
    }

    protected function employeeCertificateItems(Carbon $start, Carbon $end): Collection
    {
        $query = EmployeeCertificate::query()
            ->select(['id', 'employee_id', 'valid_until'])
            ->with(['employee:id,name,user_id'])
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [$start->toDateString(), $end->toDateString()])
            ->orderBy('valid_until');

        $this->applyActiveScope($query, (new EmployeeCertificate())->getTable());

        if (! $this->isSuperAdmin()) {
            $ownerId = $this->ownerId();

            if (! $ownerId) {
                return collect();
            }

            $query->whereHas('employee', function (Builder $q) use ($ownerId): void {
                $q->where('user_id', $ownerId);
            });
        }

        return $query->get()
            ->filter(fn (EmployeeCertificate $certificate) => $certificate->employee)
            ->map(function (EmployeeCertificate $certificate) {
                return [
                    'date' => Carbon::parse($certificate->valid_until),
                    'title' => 'Edukacija: ' . $certificate->employee->name,
                    'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('view', [
                        'record' => $certificate->employee,
                    ]),
                    'class' => 'certificate',
                    'type' => 'default',
                    'sort' => 20,
                ];
            });
    }

    protected function machineItems(Carbon $start, Carbon $end): Collection
    {
        return $this->machineBaseQuery()
            ->select(['id', 'name', 'examination_valid_until'])
            ->whereNotNull('examination_valid_until')
            ->whereBetween('examination_valid_until', [$start->toDateString(), $end->toDateString()])
            ->orderBy('examination_valid_until')
            ->get()
            ->map(function (Machine $machine) {
                return [
                    'date' => Carbon::parse($machine->examination_valid_until),
                    'title' => 'Stroj: ' . $machine->name,
                    'url' => \App\Filament\Resources\Machines\MachineResource::getUrl('view', [
                        'record' => $machine,
                    ]),
                    'class' => 'machine',
                    'type' => 'default',
                    'sort' => 30,
                ];
            });
    }

        protected function fireItems(Carbon $start, Carbon $end): Collection
    {
        $periodic = $this->fireBaseQuery()
            ->select(['id', 'place', 'examination_valid_until'])
            ->whereNotNull('examination_valid_until')
            ->whereBetween('examination_valid_until', [$start->toDateString(), $end->toDateString()])
            ->orderBy('examination_valid_until')
            ->get()
            ->map(function (Fire $fire) {
                return [
                    'date' => Carbon::parse($fire->examination_valid_until),
                    'title' => 'Vatrogasni aparat - periodički: ' . $fire->place,
                    'url' => \App\Filament\Resources\Fires\FireResource::getUrl('view', [
                        'record' => $fire,
                    ]),
                    'class' => 'fire',
                    'type' => 'default',
                    'sort' => 40,
                ];
            });

        $regular = $this->fireBaseQuery()
            ->select(['id', 'place', 'regular_examination_valid_from'])
            ->whereNotNull('regular_examination_valid_from')
            ->whereBetween('regular_examination_valid_from', [
                $start->copy()->subMonthsNoOverflow(3)->toDateString(),
                $end->copy()->subMonthsNoOverflow(3)->toDateString(),
            ])
            ->orderBy('regular_examination_valid_from')
            ->get()
            ->map(function (Fire $fire) {
                $regularUntil = Carbon::parse($fire->regular_examination_valid_from)
                    ->addMonthsNoOverflow(3);

                return [
                    'date' => $regularUntil,
                    'title' => 'Vatrogasni aparat - redovni pregled: ' . $fire->place,
                    'url' => \App\Filament\Resources\Fires\FireResource::getUrl('view', [
                        'record' => $fire,
                    ]),
                    'class' => 'fire',
                    'type' => 'default',
                    'sort' => 41,
                ];
            })
            ->filter(fn (array $item) => $item['date']->betweenIncluded($start, $end))
            ->values();

        return collect($periodic->all())
            ->merge($regular->all())
            ->values();
    }

    protected function miscellaneousItems(Carbon $start, Carbon $end): Collection
    {
        return $this->miscellaneousBaseQuery()
            ->select(['id', 'name', 'examination_valid_until'])
            ->whereNotNull('examination_valid_until')
            ->whereBetween('examination_valid_until', [$start->toDateString(), $end->toDateString()])
            ->orderBy('examination_valid_until')
            ->get()
            ->map(function (Miscellaneous $misc) {
                return [
                    'date' => Carbon::parse($misc->examination_valid_until),
                    'title' => 'Ispitivanje: ' . $misc->name,
                    'url' => \App\Filament\Resources\Miscellaneouses\MiscellaneousResource::getUrl('view', [
                        'record' => $misc,
                    ]),
                    'class' => 'misc',
                    'type' => 'default',
                    'sort' => 50,
                ];
            });
    }

    protected function workTaskItems(Carbon $start, Carbon $end): Collection
    {
        return $this->workTaskBaseQuery()
            ->select(['id', 'title', 'description', 'due_date', 'is_done'])
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('due_date')
            ->orderBy('is_done')
            ->orderBy('id')
            ->get()
            ->map(function (WorkTask $task) {
                $dueDate = Carbon::parse($task->due_date);

                $class = $task->is_done
                    ? 'znr-task-done'
                    : ($dueDate->isPast() ? 'znr-task-overdue' : 'znr-task');

                return [
                    'id' => $task->id,
                    'date' => $dueDate,
                    'title' => $task->title,
                    'description' => $task->description,
                    'url' => null,
                    'class' => $class,
                    'type' => 'task',
                    'is_done' => (bool) $task->is_done,
                    'sort' => $task->is_done ? 65 : 60,
                ];
            });
    }
}
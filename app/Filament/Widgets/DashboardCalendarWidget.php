<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Fire;
use App\Models\Machine;
use App\Models\Miscellaneous;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

class DashboardCalendarWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-calendar-widget';

    protected int|string|array $columnSpan = 'full';

    #[Url(as: 'calendar_month', history: true)]
    public int $calendar_month;

    #[Url(as: 'calendar_year', history: true)]
    public int $calendar_year;

    public function mount(): void
    {
        $this->calendar_month = (int) request()->query('calendar_month', now()->month);
        $this->calendar_year = (int) request()->query('calendar_year', now()->year);
    }

    public function previousMonth(): void
    {
        $current = Carbon::create($this->calendar_year, $this->calendar_month, 1)->subMonth();

        $this->calendar_month = (int) $current->month;
        $this->calendar_year = (int) $current->year;
    }

    public function nextMonth(): void
    {
        $current = Carbon::create($this->calendar_year, $this->calendar_month, 1)->addMonth();

        $this->calendar_month = (int) $current->month;
        $this->calendar_year = (int) $current->year;
    }

    public function getViewData(): array
    {
        $current = Carbon::create($this->calendar_year, $this->calendar_month, 1)->startOfMonth();

        $start = $current->copy()->startOfWeek(Carbon::MONDAY);
        $end = $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = collect(CarbonPeriod::create($start, $end))
            ->map(function (Carbon $day) use ($current) {
                return [
                    'date' => $day->copy(),
                    'in_month' => $day->month === $current->month,
                    'items' => [],
                ];
            })
            ->values();

        $itemsByDate = $this->buildItems($start, $end)
            ->groupBy(fn (array $item) => $item['date']->format('Y-m-d'));

        $days = $days->map(function (array $day) use ($itemsByDate) {
            $key = $day['date']->format('Y-m-d');
            $collection = $itemsByDate->get($key, collect());

            $day['items'] = $collection->take(5)->values()->all();
            $day['extra_count'] = max(0, $collection->count() - 5);

            return $day;
        });

        return [
            'monthLabel' => $current->translatedFormat('F Y'),
            'days' => $days->chunk(7),
            'weekdays' => ['pon.', 'uto.', 'sri.', 'čet.', 'pet.', 'sub.', 'ned.'],
        ];
    }

    protected function buildItems(Carbon $start, Carbon $end): Collection
    {
        return collect()
            ->merge($this->employeeMedicalItems($start, $end))
            ->merge($this->employeeCertificateItems($start, $end))
            ->merge($this->machineItems($start, $end))
            ->merge($this->fireItems($start, $end))
            ->merge($this->miscellaneousItems($start, $end))
            ->sortBy([
                fn ($a, $b) => strcmp($a['date']->format('Y-m-d'), $b['date']->format('Y-m-d')),
                fn ($a, $b) => strcmp($a['title'], $b['title']),
            ])
            ->values();
    }

    protected function employeeBaseQuery(): Builder
    {
        $query = Employee::query();

        if (! Auth::user()?->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    protected function machineBaseQuery(): Builder
    {
        $query = Machine::query();

        if (! Auth::user()?->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    protected function fireBaseQuery(): Builder
    {
        $query = Fire::query();

        if (! Auth::user()?->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    protected function miscellaneousBaseQuery(): Builder
    {
        $query = Miscellaneous::query();

        if (! Auth::user()?->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query;
    }

    protected function employeeMedicalItems(Carbon $start, Carbon $end): Collection
    {
        return $this->employeeBaseQuery()
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
                ];
            });
    }

    protected function employeeCertificateItems(Carbon $start, Carbon $end): Collection
    {
        $query = EmployeeCertificate::query()
            ->with('employee')
            ->whereNotNull('valid_until')
            ->whereBetween('valid_until', [$start->toDateString(), $end->toDateString()])
            ->orderBy('valid_until');

        if (! Auth::user()?->isAdmin()) {
            $query->whereHas('employee', function (Builder $q) {
                $q->where('user_id', Auth::id());
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
                ];
            });
    }

    protected function machineItems(Carbon $start, Carbon $end): Collection
    {
        return $this->machineBaseQuery()
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
                ];
            });
    }

    protected function fireItems(Carbon $start, Carbon $end): Collection
    {
        return $this->fireBaseQuery()
            ->whereNotNull('examination_valid_until')
            ->whereBetween('examination_valid_until', [$start->toDateString(), $end->toDateString()])
            ->orderBy('examination_valid_until')
            ->get()
            ->map(function (Fire $fire) {
                return [
                    'date' => Carbon::parse($fire->examination_valid_until),
                    'title' => 'Aparat: ' . $fire->place,
                    'url' => \App\Filament\Resources\Fires\FireResource::getUrl('view', [
                        'record' => $fire,
                    ]),
                    'class' => 'fire',
                ];
            });
    }

    protected function miscellaneousItems(Carbon $start, Carbon $end): Collection
    {
        return $this->miscellaneousBaseQuery()
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
                ];
            });
    }
}
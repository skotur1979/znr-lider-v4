<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Fire;
use App\Models\Machine;
use App\Models\Miscellaneous;
use App\Models\WorkTask;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\On;

class DashboardDeadlinesGrid extends Widget
{
    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;

    protected string $view = 'filament.widgets.dashboard-deadlines-grid';

    protected int|string|array $columnSpan = 'full';

    #[On('work-task-updated')]
    public function refreshWorkTaskCards(): void
    {
        Cache::forget($this->cacheKey());
    }

    protected function cacheKey(): string
    {
        return 'dashboard-deadlines-grid-' . (auth()->id() ?? 'guest') . '-' . now()->format('Y-m-d-H');
    }

    protected function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    protected function ownerId(): ?int
    {
        return Auth::user()?->ownerId();
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

    public function getViewData(): array
    {
        return Cache::remember(
            $this->cacheKey(),
            now()->addMinutes(5),
            function (): array {
                $today = Carbon::today();
                $soon = Carbon::today()->addDays(30);

                return [
                    'cards' => [
                        [
                            'title' => 'Zaposlenici',
                            'items' => [
                                [
                                    'value' => $this->countEmployees(),
                                    'label' => 'Ukupan broj',
                                    'color' => 'success',
                                    'icon' => 'heroicon-m-users',
                                    'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('index'),
                                ],
                                [
                                    'value' => $this->countMedicalSoon($today, $soon),
                                    'label' => 'Liječnički uskoro ističe',
                                    'color' => 'warning',
                                    'icon' => 'heroicon-m-users',
                                    'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('index', [
                                        'pregled' => 'medical_expiring',
                                    ]),
                                ],
                                [
                                    'value' => $this->countMedicalExpired($today),
                                    'label' => 'Liječnički isteklo',
                                    'color' => 'danger',
                                    'icon' => 'heroicon-m-users',
                                    'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('index', [
                                        'pregled' => 'medical_expired',
                                    ]),
                                ],
                            ],
                        ],

                        [
                            'title' => 'Zaposlenici',
                            'items' => [
                                [
                                    'value' => $this->countEmployees(),
                                    'label' => 'Ukupan broj',
                                    'color' => 'success',
                                    'icon' => 'heroicon-m-users',
                                    'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('index'),
                                ],
                                [
                                    'value' => $this->countCertificatesSoon($today, $soon),
                                    'label' => 'Edukacije uskoro ističu',
                                    'color' => 'warning',
                                    'icon' => 'heroicon-m-users',
                                    'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('index', [
                                        'pregled' => 'certificates_expiring',
                                    ]),
                                ],
                                [
                                    'value' => $this->countCertificatesExpired($today),
                                    'label' => 'Edukacije istekle',
                                    'color' => 'danger',
                                    'icon' => 'heroicon-m-users',
                                    'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('index', [
                                        'pregled' => 'certificates_expired',
                                    ]),
                                ],
                            ],
                        ],

                        [
                            'title' => 'Strojevi',
                            'items' => [
                                [
                                    'value' => $this->countMachines(),
                                    'label' => 'Ukupan broj',
                                    'color' => 'success',
                                    'icon' => 'heroicon-m-cog-6-tooth',
                                    'url' => \App\Filament\Resources\Machines\MachineResource::getUrl('index'),
                                ],
                                [
                                    'value' => $this->countMachinesSoon($today, $soon),
                                    'label' => 'Ispitivanje uskoro ističe',
                                    'color' => 'warning',
                                    'icon' => 'heroicon-m-cog-6-tooth',
                                    'url' => \App\Filament\Resources\Machines\MachineResource::getUrl('index', [
                                        'pregled' => 'uskoro',
                                    ]),
                                ],
                                [
                                    'value' => $this->countMachinesExpired($today),
                                    'label' => 'Ispitivanje isteklo',
                                    'color' => 'danger',
                                    'icon' => 'heroicon-m-cog-6-tooth',
                                    'url' => \App\Filament\Resources\Machines\MachineResource::getUrl('index', [
                                        'pregled' => 'isteklo',
                                    ]),
                                ],
                            ],
                        ],

                        [
                            'title' => 'Vatrogasni aparati',
                            'items' => [
                                [
                                    'value' => $this->countFires(),
                                    'label' => 'Ukupan broj',
                                    'color' => 'success',
                                    'icon' => 'heroicon-m-fire',
                                    'url' => \App\Filament\Resources\Fires\FireResource::getUrl('index'),
                                ],
                                [
                                    'value' => $this->countFiresSoon($today, $soon),
                                    'label' => 'Uskoro ističe',
                                    'color' => 'warning',
                                    'icon' => 'heroicon-m-fire',
                                    'url' => \App\Filament\Resources\Fires\FireResource::getUrl('index', [
                                        'pregled' => 'uskoro',
                                    ]),
                                ],
                                [
                                    'value' => $this->countFiresExpired($today),
                                    'label' => 'Isteklo',
                                    'color' => 'danger',
                                    'icon' => 'heroicon-m-fire',
                                    'url' => \App\Filament\Resources\Fires\FireResource::getUrl('index', [
                                        'pregled' => 'isteklo',
                                    ]),
                                ],
                            ],
                        ],

                        [
                            'title' => 'Ostala ispitivanja',
                            'items' => [
                                [
                                    'value' => $this->countMiscellaneous(),
                                    'label' => 'Ukupan broj',
                                    'color' => 'success',
                                    'icon' => 'heroicon-m-wrench-screwdriver',
                                    'url' => \App\Filament\Resources\Miscellaneouses\MiscellaneousResource::getUrl('index'),
                                ],
                                [
                                    'value' => $this->countMiscellaneousSoon($today, $soon),
                                    'label' => 'Uskoro ističe',
                                    'color' => 'warning',
                                    'icon' => 'heroicon-m-wrench-screwdriver',
                                    'url' => \App\Filament\Resources\Miscellaneouses\MiscellaneousResource::getUrl('index', [
                                        'pregled' => 'uskoro',
                                    ]),
                                ],
                                [
                                    'value' => $this->countMiscellaneousExpired($today),
                                    'label' => 'Isteklo',
                                    'color' => 'danger',
                                    'icon' => 'heroicon-m-wrench-screwdriver',
                                    'url' => \App\Filament\Resources\Miscellaneouses\MiscellaneousResource::getUrl('index', [
                                        'pregled' => 'isteklo',
                                    ]),
                                ],
                            ],
                        ],

                        [
                            'title' => 'Radni zadaci',
                            'items' => [
                                [
                                    'value' => $this->countWorkTasks(),
                                    'label' => 'Ukupan broj',
                                    'color' => 'success',
                                    'icon' => 'heroicon-m-clipboard-document-check',
                                    'url' => \App\Filament\Resources\WorkTasks\WorkTaskResource::getUrl('index', [
                                        'status' => 'all',
                                    ]),
                                ],
                                [
                                    'value' => $this->countWorkTasksOpen(),
                                    'label' => 'Otvoreni',
                                    'color' => 'warning',
                                    'icon' => 'heroicon-m-clipboard-document-check',
                                    'url' => \App\Filament\Resources\WorkTasks\WorkTaskResource::getUrl('index', [
                                        'status' => 'open',
                                    ]),
                                ],
                                [
                                    'value' => $this->countWorkTasksClosed(),
                                    'label' => 'Zatvoreni',
                                    'color' => 'danger',
                                    'icon' => 'heroicon-m-clipboard-document-check',
                                    'url' => \App\Filament\Resources\WorkTasks\WorkTaskResource::getUrl('index', [
                                        'status' => 'closed',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ];
            }
        );
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

    protected function countEmployees(): int
    {
        return $this->employeeBaseQuery()->count();
    }

    protected function countMedicalSoon(Carbon $today, Carbon $soon): int
    {
        return $this->employeeBaseQuery()
            ->whereNotNull('medical_examination_valid_until')
            ->whereDate('medical_examination_valid_until', '>=', $today)
            ->whereDate('medical_examination_valid_until', '<=', $soon)
            ->count();
    }

    protected function countMedicalExpired(Carbon $today): int
    {
        return $this->employeeBaseQuery()
            ->whereNotNull('medical_examination_valid_until')
            ->whereDate('medical_examination_valid_until', '<', $today)
            ->count();
    }

    protected function countCertificatesSoon(Carbon $today, Carbon $soon): int
    {
        $certificatesQuery = EmployeeCertificate::query()
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', $today)
            ->whereDate('valid_until', '<=', $soon);

        $this->applyActiveScope($certificatesQuery, (new EmployeeCertificate())->getTable());

        if (! $this->isSuperAdmin()) {
            $ownerId = $this->ownerId();

            if (! $ownerId) {
                return 0;
            }

            $certificatesQuery->whereHas('employee', function (Builder $q) use ($ownerId): void {
                $q->where('user_id', $ownerId);
            });
        }

        $znrCount = $this->employeeBaseQuery()
            ->whereNull('occupational_safety_valid_from')
            ->whereNotNull('employeed_at')
            ->whereDate('employeed_at', '>=', $today->copy()->subDays(60))
            ->whereDate('employeed_at', '<=', $today->copy()->subDays(30))
            ->count();

        return $certificatesQuery->count() + $znrCount;
    }

    protected function countCertificatesExpired(Carbon $today): int
    {
        $certificatesQuery = EmployeeCertificate::query()
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', $today);

        $this->applyActiveScope($certificatesQuery, (new EmployeeCertificate())->getTable());

        if (! $this->isSuperAdmin()) {
            $ownerId = $this->ownerId();

            if (! $ownerId) {
                return 0;
            }

            $certificatesQuery->whereHas('employee', function (Builder $q) use ($ownerId): void {
                $q->where('user_id', $ownerId);
            });
        }

        $znrCount = $this->employeeBaseQuery()
            ->whereNull('occupational_safety_valid_from')
            ->whereNotNull('employeed_at')
            ->whereDate('employeed_at', '<', $today->copy()->subDays(60))
            ->count();

        return $certificatesQuery->count() + $znrCount;
    }

    protected function countMachines(): int
    {
        return $this->machineBaseQuery()->count();
    }

    protected function countMachinesSoon(Carbon $today, Carbon $soon): int
    {
        return $this->machineBaseQuery()
            ->whereNotNull('examination_valid_until')
            ->whereDate('examination_valid_until', '>=', $today)
            ->whereDate('examination_valid_until', '<=', $soon)
            ->count();
    }

    protected function countMachinesExpired(Carbon $today): int
    {
        return $this->machineBaseQuery()
            ->whereNotNull('examination_valid_until')
            ->whereDate('examination_valid_until', '<', $today)
            ->count();
    }

    protected function countFires(): int
    {
        return $this->fireBaseQuery()->count();
    }

    protected function countFiresSoon(Carbon $today, Carbon $soon): int
    {
        return $this->fireBaseQuery()
            ->whereNotNull('examination_valid_until')
            ->whereDate('examination_valid_until', '>=', $today)
            ->whereDate('examination_valid_until', '<=', $soon)
            ->count();
    }

    protected function countFiresExpired(Carbon $today): int
    {
        return $this->fireBaseQuery()
            ->whereNotNull('examination_valid_until')
            ->whereDate('examination_valid_until', '<', $today)
            ->count();
    }

    protected function countMiscellaneous(): int
    {
        return $this->miscellaneousBaseQuery()->count();
    }

    protected function countMiscellaneousSoon(Carbon $today, Carbon $soon): int
    {
        return $this->miscellaneousBaseQuery()
            ->whereNotNull('examination_valid_until')
            ->whereDate('examination_valid_until', '>=', $today)
            ->whereDate('examination_valid_until', '<=', $soon)
            ->count();
    }

    protected function countMiscellaneousExpired(Carbon $today): int
    {
        return $this->miscellaneousBaseQuery()
            ->whereNotNull('examination_valid_until')
            ->whereDate('examination_valid_until', '<', $today)
            ->count();
    }

    protected function countWorkTasks(): int
    {
        return $this->workTaskBaseQuery()->count();
    }

    protected function countWorkTasksOpen(): int
    {
        return $this->workTaskBaseQuery()
            ->where('is_done', false)
            ->count();
    }

    protected function countWorkTasksClosed(): int
    {
        return $this->workTaskBaseQuery()
            ->where('is_done', true)
            ->count();
    }
}
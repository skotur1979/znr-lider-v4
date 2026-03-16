<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use App\Models\EmployeeCertificate;
use App\Models\Machine;
use App\Models\Fire;
use App\Models\Miscellaneous;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DashboardDeadlinesGrid extends Widget
{
    protected string $view = 'filament.widgets.dashboard-deadlines-grid';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
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
                            'label' => 'Ostali rokovi uskoro ističu',
                            'color' => 'warning',
                            'icon' => 'heroicon-m-users',
                            'url' => \App\Filament\Resources\Employees\EmployeeResource::getUrl('index', [
                                'pregled' => 'certificates_expiring',
                            ]),
                        ],
                        [
                            'value' => $this->countCertificatesExpired($today),
                            'label' => 'Ostali rokovi istekli',
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
            ],
        ];
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
        $query = EmployeeCertificate::query()
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '>=', $today)
            ->whereDate('valid_until', '<=', $soon);

        if (! Auth::user()?->isAdmin()) {
            $query->whereHas('employee', function (Builder $q) {
                $q->where('user_id', Auth::id());
            });
        }

        return $query->count();
    }

    protected function countCertificatesExpired(Carbon $today): int
    {
        $query = EmployeeCertificate::query()
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', $today);

        if (! Auth::user()?->isAdmin()) {
            $query->whereHas('employee', function (Builder $q) {
                $q->where('user_id', Auth::id());
            });
        }

        return $query->count();
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
}
<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\GlobalSearch;
use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Incidents\IncidentResource;
use App\Filament\Resources\Machines\MachineResource;
use App\Filament\Resources\MedicalReferrals\MedicalReferralResource;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use App\Filament\Resources\Observations\ObservationResource;
use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Filament\Resources\PersonalProtectiveEquipmentLogs\PersonalProtectiveEquipmentLogResource;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Filament\Resources\WorkTasks\WorkTaskResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class QuickActionsWidget extends Widget
{
    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = null;
    
    protected string $view = 'filament.widgets.quick-actions-widget';

    protected int|string|array $columnSpan = 'full';

    public array $selectedActionKeys = [];

    public array $editorSelection = [];

    public bool $showEditor = false;

    protected int $maxQuickActions = 4;

    public function mount(): void
    {
        $user = Auth::user();

        $default = [
            'employee',
            'ra1',
            'machine',
            'miscellaneous',
        ];

        $saved = is_array($user?->quick_actions) ? $user->quick_actions : [];

        $availableKeys = array_keys($this->getAllActions());

        $saved = array_values(array_filter(
            $saved,
            fn ($key) => in_array($key, $availableKeys, true)
        ));

        $this->selectedActionKeys = array_slice(
            ! empty($saved) ? $saved : $default,
            0,
            $this->maxQuickActions
        );

        $this->editorSelection = $this->selectedActionKeys;
    }

    protected function getAllActions(): array
    {
        $actions = [
            'operational_log' => [
                'label' => 'Operativni dnevnik',
                'description' => 'Brzi zapis, bilješka ili dnevni unos',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => $this->resourceCreateUrl(OperationalLogResource::class),
                'color' => 'blue',
            ],

            'employee' => [
                'label' => 'Novi zaposlenik',
                'description' => 'Dodaj novog zaposlenika',
                'icon' => 'heroicon-o-users',
                'url' => $this->resourceCreateUrl(EmployeeResource::class),
                'color' => 'sky',
            ],
            'ra1' => [
                'label' => 'Nova uputnica RA1',
                'description' => 'Dodaj novu liječničku uputnicu',
                'icon' => 'heroicon-o-document-text',
                'url' => $this->resourceCreateUrl(MedicalReferralResource::class),
                'color' => 'indigo',
            ],
            'machine' => [
                'label' => 'Nova radna oprema',
                'description' => 'Dodaj novi stroj / opremu',
                'icon' => 'heroicon-o-cog-6-tooth',
                'url' => $this->resourceCreateUrl(MachineResource::class),
                'color' => 'amber',
            ],
            'miscellaneous' => [
                'label' => 'Nova ostala ispitivanja',
                'description' => 'Dodaj novo ostalo ispitivanje',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'url' => $this->resourceCreateUrl(MiscellaneousResource::class),
                'color' => 'emerald',
            ],
            'work_task' => [
                'label' => 'Novi radni zadatak',
                'description' => 'Dodaj novi osobni zadatak / podsjetnik',
                'icon' => 'heroicon-o-clipboard-document-check',
                'url' => $this->resourceCreateUrl(WorkTaskResource::class),
                'color' => 'violet',
            ],
            'expense' => [
                'label' => 'Novi trošak',
                'description' => 'Dodaj novi trošak',
                'icon' => 'heroicon-o-banknotes',
                'url' => $this->resourceCreateUrl(ExpenseResource::class),
                'color' => 'rose',
            ],
            'waste_tracking' => [
                'label' => 'Novi prateći list',
                'description' => 'Dodaj novi prateći list otpada',
                'icon' => 'heroicon-o-clipboard-document-list',
                'url' => $this->resourceCreateUrl(WasteTrackingFormResource::class),
                'color' => 'violet',
            ],
            'ozo' => [
                'label' => 'Novi upisnik OZO',
                'description' => 'Dodaj novi zapis upisnika OZO',
                'icon' => 'heroicon-o-shield-check',
                'url' => $this->resourceCreateUrl(PersonalProtectiveEquipmentLogResource::class),
                'color' => 'teal',
            ],
            'incident' => [
                'label' => 'Novi incident',
                'description' => 'Dodaj novi incident',
                'icon' => 'heroicon-o-exclamation-triangle',
                'url' => $this->resourceCreateUrl(IncidentResource::class),
                'color' => 'orange',
            ],
            'observation' => [
                'label' => 'Novo zapažanje',
                'description' => 'Dodaj novo zapažanje',
                'icon' => 'heroicon-o-eye',
                'url' => $this->resourceCreateUrl(ObservationResource::class),
                'color' => 'lime',
            ],
            'global_search' => [
                'label' => 'Globalna pretraga',
                'description' => 'Pretraži sve module',
                'icon' => 'heroicon-o-magnifying-glass',
                'url' => class_exists(GlobalSearch::class) ? GlobalSearch::getUrl() : null,
                'color' => 'blue',
            ],
            'chemical' => [
                'label' => 'Nova kemikalija',
                'description' => 'Dodaj novu kemikaliju',
                'icon' => 'heroicon-o-beaker',
                'url' => $this->resourceCreateUrl(ChemicalResource::class),
                'color' => 'purple',
            ],
        ];

        return array_filter($actions, fn ($action) => filled($action['url'] ?? null));
    }

    protected function resourceCreateUrl(string $resourceClass): ?string
    {
        if (! class_exists($resourceClass)) {
            return null;
        }

        try {
            return $resourceClass::getUrl('create');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getSelectedActionsProperty(): array
    {
        $all = $this->getAllActions();

        return collect($this->selectedActionKeys)
            ->filter(fn ($key) => isset($all[$key]))
            ->map(fn ($key) => $all[$key] + ['key' => $key])
            ->values()
            ->all();
    }

    public function getAvailableActionsProperty(): array
    {
        return collect($this->getAllActions())
            ->map(fn ($action, $key) => $action + ['key' => $key])
            ->values()
            ->all();
    }

    public function openEditor(): void
    {
        $this->editorSelection = $this->selectedActionKeys;
        $this->showEditor = true;
    }

    public function closeEditor(): void
    {
        $this->showEditor = false;
    }

    public function toggleAction(string $key): void
    {
        $selection = $this->editorSelection;

        if (in_array($key, $selection, true)) {
            $this->editorSelection = array_values(array_filter(
                $selection,
                fn ($item) => $item !== $key
            ));

            return;
        }

        if (count($selection) >= $this->maxQuickActions) {
            return;
        }

        $selection[] = $key;

        $this->editorSelection = array_values(array_unique($selection));
    }

    public function saveQuickActions(): void
    {
        $availableKeys = array_keys($this->getAllActions());

        $selection = array_values(array_filter(
            $this->editorSelection,
            fn ($key) => in_array($key, $availableKeys, true)
        ));

        $selection = array_slice($selection, 0, $this->maxQuickActions);

        $user = Auth::user();
        $user->quick_actions = $selection;
        $user->save();

        $this->selectedActionKeys = $selection;
        $this->showEditor = false;
    }
}
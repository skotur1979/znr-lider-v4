<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use App\Models\Kpi;
use Illuminate\Database\Eloquent\Builder;
use App\Models\KpiTargetOverride;
use App\Services\KpiCalculationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class KpiDashboard extends Page
{
    protected static string $resource = KpiResource::class;

    protected string $view = 'filament.resources.kpis.pages.kpi-dashboard';

    public string $viewMode = 'month';

    public int $month;
    public int $year;

    public ?int $compareMonth = null;
    public ?int $compareYear = null;

    public array $groups = [];
    public array $chartRows = [];
    public array $availableKpis = [];
    public array $selectedChartKpis = [];

    public bool $showTargetModal = false;
    public ?int $targetKpiId = null;
    public string $targetKpiName = '';
    public $targetValue = null;
    public $warningOffset = null;

    public ?int $targetMonth = null;
    public ?int $targetYear = null;

    public bool $targetUsesOverride = false;
    public $globalTargetValue = null;
    public $globalWarningOffset = null;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year = now()->year;
        $this->compareMonth = null;
        $this->compareYear = null;

        $this->loadData();
    }

    public function updatedViewMode(): void
    {
        if ($this->viewMode === 'year') {
            $this->compareMonth = null;
        }

        $this->loadData();
    }

    public function updatedMonth(): void
    {
        $this->loadData();
    }

    public function updatedYear(): void
    {
        $this->loadData();
    }

    public function updatedCompareMonth(): void
    {
        if (blank($this->compareMonth)) {
            $this->compareMonth = null;
        }

        $this->loadData();
    }

    public function updatedCompareYear(): void
    {
        if (blank($this->compareYear)) {
            $this->compareYear = null;
        }

        $this->loadData();
    }

    public function updatedSelectedChartKpis(): void
    {
        $this->buildChartRows();
    }

    public function selectAllKpis(): void
    {
        $this->selectedChartKpis = collect($this->availableKpis)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->buildChartRows();
    }

    public function clearSelectedKpis(): void
    {
        $this->selectedChartKpis = [];
        $this->buildChartRows();
    }

    public function selectCategory(string $category): void
    {
        $this->selectedChartKpis = collect($this->availableKpis)
            ->where('category', $category)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        $this->buildChartRows();
    }

    public function openTargetModal(int $kpiId): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            return;
        }

        $ownerId = KpiResource::resolveOwnerId();

        if (! $ownerId) {
            return;
        }

        $kpi = Kpi::query()->findOrFail($kpiId);

        if (filled($kpi->user_id) && (int) $kpi->user_id !== (int) $ownerId) {
            abort(403);
        }

        $this->targetMonth = $this->viewMode === 'month'
            ? (int) $this->month
            : now()->month;

        $this->targetYear = (int) $this->year;

        $override = KpiTargetOverride::query()
            ->where('kpi_id', $kpi->id)
            ->where('user_id', $ownerId)
            ->where('month', $this->targetMonth)
            ->where('year', $this->targetYear)
            ->first();

        $this->targetKpiId = $kpi->id;
        $this->targetKpiName = $kpi->name;
        $this->targetValue = $override?->target_value ?? $kpi->effectiveTargetValueForPeriod($this->targetMonth, $this->targetYear, $ownerId);
        $this->warningOffset = $override?->warning_offset ?? $kpi->effectiveWarningOffsetForPeriod($this->targetMonth, $this->targetYear, $ownerId);

        $this->targetUsesOverride = (bool) $override;
        $this->globalTargetValue = $kpi->target_value;
        $this->globalWarningOffset = $kpi->warning_offset;

        $this->showTargetModal = true;
    }

    public function closeTargetModal(): void
    {
        $this->showTargetModal = false;
        $this->targetKpiId = null;
        $this->targetKpiName = '';
        $this->targetValue = null;
        $this->warningOffset = null;
        $this->targetMonth = null;
        $this->targetYear = null;
        $this->targetUsesOverride = false;
        $this->globalTargetValue = null;
        $this->globalWarningOffset = null;
    }

    public function saveTargetOverride(): void
    {
        $this->validate([
            'targetKpiId' => ['required', 'integer', 'exists:kpis,id'],
            'targetValue' => ['nullable', 'numeric'],
            'warningOffset' => ['nullable', 'numeric'],
            'targetMonth' => ['required', 'integer', 'min:1', 'max:12'],
            'targetYear' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        if (auth()->user()?->isSuperAdmin()) {
            abort(403);
        }

        $ownerId = KpiResource::resolveOwnerId();

        if (! $ownerId) {
            abort(403);
        }

        $kpi = Kpi::query()->findOrFail($this->targetKpiId);

        if (filled($kpi->user_id) && (int) $kpi->user_id !== (int) $ownerId) {
            abort(403);
        }

        KpiTargetOverride::updateOrCreate(
            [
                'kpi_id' => $kpi->id,
                'user_id' => $ownerId,
                'month' => (int) $this->targetMonth,
                'year' => (int) $this->targetYear,
            ],
            [
                'target_value' => $this->targetValue,
                'warning_offset' => $this->warningOffset,
            ]
        );

        Notification::make()
            ->title('Cilj je spremljen.')
            ->body('Promjena cilja vrijedi od ' . str_pad((string) $this->targetMonth, 2, '0', STR_PAD_LEFT) . '/' . $this->targetYear . '.')
            ->success()
            ->send();

        $this->closeTargetModal();
        $this->loadData();
    }

    public function resetTargetOverride(): void
{
    $this->validate([
        'targetKpiId' => [
            'required',
            'integer',
            'exists:kpis,id',
        ],
        'targetMonth' => [
            'required',
            'integer',
            'min:1',
            'max:12',
        ],
        'targetYear' => [
            'required',
            'integer',
            'min:2000',
            'max:2100',
        ],
    ]);

    $user = auth()->user();

    if (! $user || $user->isSuperAdmin()) {
        abort(403);
    }

    $ownerId = KpiResource::resolveOwnerId();

    if (! $ownerId) {
        abort(403);
    }

    /*
     * KPI mora biti:
     * - globalni
     * - ili KPI iste organizacije.
     */
    $kpi = Kpi::query()
        ->whereKey($this->targetKpiId)
        ->where(function (Builder $query) use ($ownerId): void {
            $query
                ->whereNull('user_id')
                ->orWhere('user_id', $ownerId);
        })
        ->firstOrFail();

    KpiTargetOverride::query()
        ->where('kpi_id', $kpi->id)
        ->where('user_id', $ownerId)
        ->where('month', $this->targetMonth)
        ->where('year', $this->targetYear)
        ->delete();

    Notification::make()
        ->title(
            'Cilj za odabrani period je uklonjen.'
        )
        ->success()
        ->send();

    $this->closeTargetModal();
    $this->loadData();
}

    protected function loadData(): void
    {
        $service = app(KpiCalculationService::class);

        if ($this->viewMode === 'year') {
            $collection = $service->dashboardGroupsYearly(
                $this->year,
                $this->compareYear,
            );
        } else {
            $collection = $service->dashboardGroups(
                $this->month,
                $this->year,
                $this->compareMonth,
                $this->compareYear,
            );
        }

        $this->groups = $collection->toArray();

        $flat = $collection->flatten(1)->values();

        $this->availableKpis = $flat
            ->map(fn (array $row) => [
                'id' => (string) $row['id'],
                'name' => $row['name'],
                'category' => $row['category'] ?? '',
            ])
            ->all();

        if (empty($this->selectedChartKpis)) {
            $this->selectedChartKpis = $flat
                ->take(8)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        } else {
            $validIds = $flat->pluck('id')->map(fn ($id) => (string) $id)->all();
            $this->selectedChartKpis = array_values(array_intersect($this->selectedChartKpis, $validIds));
        }

        $this->buildChartRows($flat);
    }

    protected function buildChartRows(?Collection $flat = null): void
    {
        $flat ??= collect($this->groups)->flatten(1);

        $selectedIds = collect($this->selectedChartKpis)
            ->map(fn ($id) => (string) $id)
            ->all();

        $this->chartRows = $flat
            ->filter(fn (array $row) => in_array((string) $row['id'], $selectedIds, true))
            ->map(function (array $row) {
                return [
                    'id' => (string) $row['id'],
                    'name' => $row['name'],
                    'category' => $row['category'] ?? '',
                    'current_value' => $row['current_value'] ?? null,
                    'compare_value' => $row['compare_value'] ?? null,
                    'formatted_current' => $row['formatted_current'] ?? '-',
                    'formatted_compare' => $row['formatted_compare'] ?? '-',
                ];
            })
            ->sortByDesc(function (array $row) {
                return max(
                    (float) ($row['current_value'] ?? 0),
                    (float) ($row['compare_value'] ?? 0)
                );
            })
            ->values()
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reports')
                ->label('Izvještaji')
                ->icon('heroicon-o-document-chart-bar')
                ->color('warning')
                ->url(KpiResource::getUrl('reports')),

            Action::make('bulk_entry')
                ->label('Bulk unos')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->visible(
                    fn (): bool =>
                        auth()->user()?->isSuperAdmin() !== true
                )
                ->url(KpiResource::getUrl('bulk-entry')),
        ];
    }

    public function getTitle(): string
    {
        return 'KPI Dashboard';
    }
}
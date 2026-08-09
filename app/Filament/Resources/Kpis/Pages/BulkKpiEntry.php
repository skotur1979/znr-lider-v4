<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use App\Models\Kpi;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class BulkKpiEntry extends Page
{
    protected static string $resource = KpiResource::class;

    protected string $view =
        'filament.resources.kpis.pages.bulk-kpi-entry';

    public int $month;

    public int $year;

    public array $rows = [];

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            abort(403);
        }

        $this->month = now()->month;
        $this->year = now()->year;

        $this->loadRows();
    }

    public function updatedMonth(): void
    {
        $this->loadRows();
    }

    public function updatedYear(): void
    {
        $this->loadRows();
    }

    public function loadRows(): void
    {
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            $this->rows = [];

            return;
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            $this->rows = [];

            return;
        }

        $query = Kpi::query()
            ->where('is_active', true)
            ->where('calculation_type', 'manual')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name');

        /*
         * Organizacija vidi:
         *
         * - svoje ručne KPI-e
         * - globalne ručne KPI-e
         *
         * Ako postoji organizacijska kopija globalnog KPI-ja,
         * globalni se više ne prikazuje.
         */
        $query->where(function (Builder $q) use ($ownerId): void {
            $q->where('user_id', $ownerId)
                ->orWhere(function (Builder $global) use ($ownerId): void {
                    $global
                        ->whereNull('user_id')
                        ->whereNotExists(
                            function ($sub) use ($ownerId): void {
                                $sub->selectRaw('1')
                                    ->from('kpis as org_kpis')
                                    ->where(
                                        'org_kpis.user_id',
                                        $ownerId
                                    )
                                    ->whereNull(
                                        'org_kpis.deleted_at'
                                    )
                                    ->where(
                                        function ($match): void {
                                            $match
                                                ->where(
                                                    function ($bySource): void {
                                                        $bySource
                                                            ->whereNotNull(
                                                                'kpis.source_key'
                                                            )
                                                            ->whereColumn(
                                                                'org_kpis.source_key',
                                                                'kpis.source_key'
                                                            );
                                                    }
                                                )
                                                ->orWhere(
                                                    function ($byName): void {
                                                        $byName
                                                            ->whereNull(
                                                                'kpis.source_key'
                                                            )
                                                            ->whereColumn(
                                                                'org_kpis.name',
                                                                'kpis.name'
                                                            );
                                                    }
                                                );
                                        }
                                    );
                            }
                        );
                });
        });

        $this->rows = $query
            ->get()
            ->map(function (Kpi $kpi): array {
                $existing = $kpi->valueFor(
                    $this->month,
                    $this->year
                );

                return [
                    'kpi_id' => $kpi->id,
                    'name' => $kpi->name,
                    'category' => $kpi->category,
                    'unit' => $kpi->unit,
                    'value' => $existing?->value,
                    'source_label' => 'Ručno',
                    'note' => $existing?->note,
                ];
            })
            ->values()
            ->all();
    }

    public function save(): void
    {
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            abort(403);
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            abort(403);
        }

        foreach ($this->rows as $row) {
            $kpiId = (int) ($row['kpi_id'] ?? 0);

            if ($kpiId <= 0) {
                continue;
            }

            /*
             * Serverski ponovno provjeravamo da je KPI
             * aktivan i ručni.
             */
            $kpi = Kpi::query()
                ->whereKey($kpiId)
                ->where('is_active', true)
                ->where('calculation_type', 'manual')
                ->first();

            if (! $kpi) {
                continue;
            }

            /*
             * Dozvoljeni su:
             *
             * - globalni KPI
             * - KPI iste organizacije.
             */
            $canUseKpi =
                blank($kpi->user_id)
                || (int) $kpi->user_id === (int) $ownerId;

            if (! $canUseKpi) {
                continue;
            }

            $value = $row['value'] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $kpi->values()->updateOrCreate(
                [
                    'month' => $this->month,
                    'year' => $this->year,
                    'user_id' => $ownerId,
                ],
                [
                    'value' => (float) $value,
                    'auto_generated' => false,
                    'source_label' => 'Ručno',
                    'note' => $row['note'] ?? null,
                ]
            );
        }

        Notification::make()
            ->title(
                'Bulk unos ručnih KPI-eva je spremljen.'
            )
            ->success()
            ->send();

        $this->loadRows();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Spremi sve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
        ];
    }

    public function getTitle(): string
    {
        return 'Bulk unos ručnih KPI-eva';
    }
}
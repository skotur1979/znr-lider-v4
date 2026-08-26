<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use App\Models\Kpi;
use App\Services\KpiCalculationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class BulkKpiEntry extends Page
{
    protected static string $resource =
        KpiResource::class;

    protected string $view =
        'filament.resources.kpis.pages.bulk-kpi-entry';

    public int $month;

    public int $year;

    public array $rows = [];

    public function mount(): void
    {
        $user = auth()->user();

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            abort(403);
        }

        $this->month =
            now()->month;

        $this->year =
            now()->year;

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

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            $this->rows = [];

            return;
        }

        $ownerId =
            $user->ownerId();

        if (! $ownerId) {
            $this->rows = [];

            return;
        }

        $query = Kpi::query()
            ->where(
                'is_active',
                true
            )
            ->where(
                'calculation_type',
                'manual'
            )
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name');

        /*
        |--------------------------------------------------------------------------
        | KPI koje organizacija vidi u ručnom unosu
        |--------------------------------------------------------------------------
        |
        | Organizacija vidi:
        |
        | - svoje ručne KPI-je
        | - globalne ručne KPI-je
        |
        | Ako postoji organizacijska kopija globalnog KPI-ja,
        | globalni se više ne prikazuje.
        |
        */

        $query->where(
            function (
                Builder $query
            ) use (
                $ownerId
            ): void {
                $query
                    ->where(
                        'user_id',
                        $ownerId
                    )
                    ->orWhere(
                        function (
                            Builder $global
                        ) use (
                            $ownerId
                        ): void {
                            $global
                                ->whereNull(
                                    'user_id'
                                )
                                ->whereNotExists(
                                    function (
                                        $sub
                                    ) use (
                                        $ownerId
                                    ): void {
                                        $sub
                                            ->selectRaw('1')
                                            ->from(
                                                'kpis as org_kpis'
                                            )
                                            ->where(
                                                'org_kpis.user_id',
                                                $ownerId
                                            )
                                            ->whereNull(
                                                'org_kpis.deleted_at'
                                            )
                                            ->where(
                                                function (
                                                    $match
                                                ): void {
                                                    $match
                                                        ->where(
                                                            function (
                                                                $bySource
                                                            ): void {
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
                                                            function (
                                                                $byName
                                                            ): void {
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
                        }
                    );
            }
        );

        $this->rows = $query
            ->get()
            ->map(
                function (
                    Kpi $kpi
                ): array {
                    $existing =
                        $kpi->valueFor(
                            $this->month,
                            $this->year
                        );

                    return [
                        'kpi_id' =>
                            $kpi->id,

                        'name' =>
                            $kpi->name,

                        'category' =>
                            $kpi->category,

                        'unit' =>
                            $kpi->unit,

                        'value' =>
                            $existing?->value,

                        'source_label' =>
                            'Ručno',

                        'note' =>
                            $existing?->note,
                    ];
                }
            )
            ->values()
            ->all();
    }

    public function save(): void
    {
        $user = auth()->user();

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            abort(403);
        }

        $ownerId =
            $user->ownerId();

        if (! $ownerId) {
            abort(403);
        }

        $saved = 0;

        foreach (
            $this->rows
            as $row
        ) {
            $kpiId =
                (int) (
                    $row['kpi_id']
                    ?? 0
                );

            if ($kpiId <= 0) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Serverska provjera KPI-ja
            |--------------------------------------------------------------------------
            */

            $kpi = Kpi::query()
                ->whereKey(
                    $kpiId
                )
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'calculation_type',
                    'manual'
                )
                ->first();

            if (! $kpi) {
                continue;
            }

            /*
             * Dozvoljeni su:
             *
             * - globalni ručni KPI
             * - KPI iste organizacije
             */
            $canUseKpi =
                blank(
                    $kpi->user_id
                )
                || (int) $kpi->user_id
                    === (int) $ownerId;

            if (! $canUseKpi) {
                continue;
            }

            $value =
                $row['value']
                ?? null;

            /*
             * Prazno polje ne briše postojeću vrijednost.
             */
            if (
                $value === null
                || $value === ''
            ) {
                continue;
            }

            $kpi->values()
                ->updateOrCreate(
                    [
                        'month' =>
                            $this->month,

                        'year' =>
                            $this->year,

                        'user_id' =>
                            $ownerId,
                    ],
                    [
                        'value' =>
                            (float) $value,

                        'auto_generated' =>
                            false,

                        'source_label' =>
                            'Ručno',

                        'note' =>
                            filled(
                                $row['note']
                                ?? null
                            )
                                ? trim(
                                    (string) $row['note']
                                )
                                : null,
                    ]
                );

            $saved++;
        }

        /*
        |--------------------------------------------------------------------------
        | Ponovni izračun automatskih KPI-ja
        |--------------------------------------------------------------------------
        |
        | Nakon ručnog unosa odmah ponovno računamo:
        |
        | - AFR
        | - ASR
        | - nadzore
        | - zapažanja
        | - korektivne radnje
        | - ostale automatske KPI-je
        |
        | za isti mjesec i godinu.
        |
        | Ovo je posebno važno za:
        |
        | Ukupan broj odrađenih radnih sati
        |
        | jer AFR i ASR ovise o toj vrijednosti.
        |
        */

        $result = app(
            KpiCalculationService::class
        )->generateForMonth(
            $this->month,
            $this->year
        );

        Notification::make()
            ->title(
                'Ručni unos KPI vrijednosti je spremljen.'
            )
            ->body(
                'Spremljeno ručnih vrijednosti: '
                . $saved
                . ' | Automatski kreirano: '
                . ($result['generated'] ?? 0)
                . ' | Automatski ažurirano: '
                . ($result['updated'] ?? 0)
                . ' | Preskočeno: '
                . ($result['skipped'] ?? 0)
            )
            ->success()
            ->send();

        /*
         * Ponovno učitamo vrijednosti kako bi se forma
         * odmah uskladila s bazom.
         */
        $this->loadRows();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Spremi sve')
                ->icon(
                    'heroicon-o-check'
                )
                ->color('success')
                ->action('save'),

            Action::make('back')
                ->label('Povratak na KPI')
                ->icon(
                    'heroicon-o-arrow-left'
                )
                ->color('gray')
                ->url(
                    KpiResource::getUrl(
                        'index'
                    )
                ),
        ];
    }

    public function getTitle(): string
    {
        return 'Ručni unos KPI vrijednosti';
    }

    public function getHeading(): string
    {
        return 'Ručni unos KPI vrijednosti';
    }
}
<?php

namespace App\Filament\Widgets;

use App\Models\FirstAidItem;
use App\Models\Observation;
use App\Models\PPEItem;
use App\Models\WasteTrackingForm;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SystemStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.system-status-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $today = Carbon::today();

        return [
            'title' => 'Zahtijeva radnju',
            'cards' => [
                [
                    'label' => 'OZO isteklo',
                    'count' => $this->countExpiredPpe($today),
                    'icon' => 'heroicon-o-shield-exclamation',
                    'tone' => 'danger',
                    'hint' => 'Otvorit će istekle OZO zapise',
                    'url' => $this->resolvePpeUrl(),
                ],
                [
                    'label' => 'Zapažanja',
                    'count' => $this->countOpenObservations(),
                    'icon' => 'heroicon-o-eye',
                    'tone' => 'warning',
                    'hint' => 'Nije započeto ili u tijeku',
                    'url' => $this->resolveObservationsUrl(),
                ],
                [
                    'label' => 'Prateći listovi',
                    'count' => $this->countDraftWasteTrackingForms(),
                    'icon' => 'heroicon-o-document-text',
                    'tone' => 'gray',
                    'hint' => 'Otvorit će nacrte',
                    'url' => $this->resolveWasteTrackingUrl(),
                ],
                [
                    'label' => 'Ormarići prve pomoći',
                    'count' => $this->countExpiredFirstAidItems($today),
                    'icon' => 'heroicon-o-heart',
                    'tone' => 'danger',
                    'hint' => 'Istekle stavke u ormarićima',
                    'url' => $this->resolveFirstAidUrl(),
                ],
            ],
        ];
    }

    protected function countExpiredPpe(Carbon $today): int
    {
        if (! class_exists(PPEItem::class)) {
            return 0;
        }

        $model = new PPEItem();
        $table = $model->getTable();

        if (! Schema::hasColumn($table, 'end_date')) {
            return 0;
        }

        $query = PPEItem::query()
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today);

        $this->applyCommonScopes($query, $model);

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

    protected function countOpenObservations(): int
    {
        if (! class_exists(Observation::class)) {
            return 0;
        }

        $model = new Observation();
        $table = $model->getTable();

        if (! Schema::hasColumn($table, 'status')) {
            return 0;
        }

        $query = Observation::query()->whereIn('status', [
            'Not started',
            'In progress',
        ]);

        $this->applyCommonScopes($query, $model);
        $this->applyDirectUserScope($query, $table);

        return (int) $query->count();
    }

    protected function countDraftWasteTrackingForms(): int
    {
        if (! class_exists(WasteTrackingForm::class)) {
            return 0;
        }

        $model = new WasteTrackingForm();
        $table = $model->getTable();

        if (! Schema::hasColumn($table, 'status')) {
            return 0;
        }

        $query = WasteTrackingForm::query()->whereIn('status', [
            'draft',
            'Draft',
            'nacrt',
            'Nacrt',
        ]);

        $this->applyCommonScopes($query, $model);
        $this->applyDirectUserScope($query, $table);

        return (int) $query->count();
    }

    protected function countExpiredFirstAidItems(Carbon $today): int
    {
        if (! class_exists(FirstAidItem::class)) {
            return 0;
        }

        $model = new FirstAidItem();
        $table = $model->getTable();

        if (! Schema::hasColumn($table, 'valid_until')) {
            return 0;
        }

        $query = FirstAidItem::query()
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', $today);

        $this->applyCommonScopes($query, $model);

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

    protected function resolvePpeUrl(): string
    {
        if (class_exists(\App\Filament\Resources\PpeLogs\PPELogResource::class)) {
            return \App\Filament\Resources\PpeLogs\PPELogResource::getUrl('index', [
                'tableFilters' => [
                    'pregled' => [
                        'value' => 'isteklo',
                    ],
                ],
            ]);
        }

        return url('/admin/ppe-logs');
    }

    protected function resolveObservationsUrl(): string
    {
        if (class_exists(\App\Filament\Resources\Observations\ObservationResource::class)) {
            return \App\Filament\Resources\Observations\ObservationResource::getUrl('index', [
                'tableFilters' => [
                    'status_action' => [
                        'value' => 'open_action',
                    ],
                ],
            ]);
        }

        return url('/admin/observations');
    }

    protected function resolveWasteTrackingUrl(): string
    {
        if (class_exists(\App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource::class)) {
            return \App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource::getUrl('index', [
                'tableFilters' => [
                    'status' => [
                        'value' => 'draft',
                    ],
                ],
            ]);
        }

        return url('/admin/waste-tracking-forms');
    }

    protected function resolveFirstAidUrl(): string
    {
        if (class_exists(\App\Filament\Resources\FirstAidKits\FirstAidKitResource::class)) {
            return \App\Filament\Resources\FirstAidKits\FirstAidKitResource::getUrl('index', [
                'tableFilters' => [
                    'expired_items' => [
                        'value' => 'expired',
                    ],
                ],
            ]);
        }

        return url('/admin/first-aid-kits');
    }
}
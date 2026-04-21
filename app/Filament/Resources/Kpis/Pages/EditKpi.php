<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use Filament\Resources\Pages\EditRecord;

class EditKpi extends EditRecord
{
    protected static string $resource = KpiResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (blank($this->record->user_id) && ! auth()->user()?->isSuperAdmin()) {
            abort(403, 'Nemate pravo uređivati globalni KPI.');
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($this->record->user_id) && ! auth()->user()?->isSuperAdmin()) {
            abort(403, 'Nemate pravo uređivati globalni KPI.');
        }

        if (! auth()->user()?->isSuperAdmin()) {
            $data['user_id'] = KpiResource::defaultUserId();
        }

        return $data;
    }
}
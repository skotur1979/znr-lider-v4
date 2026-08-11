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

        /*
         * Organizacija ne smije mijenjati globalni KPI.
         */
        if (
            blank($this->record->user_id)
            && ! auth()->user()?->isSuperAdmin()
        ) {
            abort(
                403,
                'Nemate pravo uređivati globalni KPI.'
            );
        }
    }

    protected function mutateFormDataBeforeSave(
    array $data
    ): array {
        unset($data['user_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}
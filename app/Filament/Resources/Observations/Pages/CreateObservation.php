<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Resources\Observations\ObservationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateObservation extends CreateRecord
{
    protected static string $resource = ObservationResource::class;

    protected function getFormContentGrid(): ?array
    {
        return [
            'default' => 1,
            'lg' => 1,
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isAdmin()) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    /**
     * Nakon spremanja vrati na listu (sigurno, bez 404).
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');

        // Ako želiš nakon spremanja na edit:
        // return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
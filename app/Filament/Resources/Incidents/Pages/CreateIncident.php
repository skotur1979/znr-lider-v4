<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Filament\Resources\Incidents\IncidentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

    /**
     * ✅ Osiguraj da user_id uvijek bude postavljen
     * (jer inače nakon create-a tvoj getEloquentQuery() ne pronađe zapis -> 404)
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isAdmin()) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    /**
     * ✅ Ne idi na /admin/incidents/{record} (view) ako ti zna vraćati 404,
     * nego sigurno vrati na listu (ili promijeni na edit ako želiš).
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');

        // Ako želiš nakon spremanja odmah na edit:
        // return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
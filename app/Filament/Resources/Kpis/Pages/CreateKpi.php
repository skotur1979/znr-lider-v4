<?php

namespace App\Filament\Resources\Kpis\Pages;

use App\Filament\Resources\Kpis\KpiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKpi extends CreateRecord
{
    protected static string $resource = KpiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            /*
             * Superadmin kreira globalnu KPI definiciju.
             */
            $data['user_id'] = null;
        } else {
            /*
             * Glavni korisnik i podkorisnik kreiraju
             * KPI svoje organizacije.
             */
            $data['user_id'] = $user->ownerId();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
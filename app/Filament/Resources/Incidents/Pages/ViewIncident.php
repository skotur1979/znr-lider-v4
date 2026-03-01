<?php

namespace App\Filament\Resources\Incidents\Pages;

use App\Filament\Resources\Incidents\IncidentResource;
use App\Models\Incident;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ViewIncident extends ViewRecord
{
    protected static string $resource = IncidentResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        $query = Incident::query()->withTrashed();

        if (! Auth::user()?->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $query->whereKey($key)->firstOrFail();
    }
}
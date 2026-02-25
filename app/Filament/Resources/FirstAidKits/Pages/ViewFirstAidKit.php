<?php

namespace App\Filament\Resources\FirstAidKits\Pages;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFirstAidKit extends ViewRecord
{
    protected static string $resource = FirstAidKitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Uredi'),
            DeleteAction::make()
                ->label('Obriši')
                ->modalHeading('Obriši Prvu pomoć')
                ->modalSubheading('Jeste li sigurni da želite obrisati ovu Prvu pomoć?')
                ->successNotificationTitle('Prva pomoć je obrisana.'),
        ];
    }
}
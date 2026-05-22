<?php

namespace App\Filament\Resources\LegalAcceptances\Pages;

use App\Filament\Resources\LegalAcceptances\LegalAcceptanceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLegalAcceptance extends EditRecord
{
    protected static string $resource = LegalAcceptanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

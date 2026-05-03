<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOperationalLog extends EditRecord
{
    protected static string $resource = OperationalLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Obriši'),
        ];
    }
}
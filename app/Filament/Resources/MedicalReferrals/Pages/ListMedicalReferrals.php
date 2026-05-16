<?php

namespace App\Filament\Resources\MedicalReferrals\Pages;

use App\Filament\Resources\MedicalReferrals\MedicalReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicalReferrals extends ListRecords
{
    protected static string $resource = MedicalReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novi RA-1 Uputnica')
                ->icon('heroicon-o-plus'),
        ];
    }
}
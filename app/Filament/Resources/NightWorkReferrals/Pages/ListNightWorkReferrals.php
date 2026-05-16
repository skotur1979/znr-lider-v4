<?php

namespace App\Filament\Resources\NightWorkReferrals\Pages;

use App\Filament\Resources\NightWorkReferrals\NightWorkReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNightWorkReferrals extends ListRecords
{
    protected static string $resource = NightWorkReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova NR-1 Uputnica')
                ->icon('heroicon-o-plus'),
        ];
    }
}

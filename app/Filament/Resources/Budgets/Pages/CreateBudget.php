<?php

namespace App\Filament\Resources\Budgets\Pages;

use App\Filament\Resources\Budgets\BudgetResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /*
         * Standardna BaseResource ownership logika:
         *
         * glavni korisnik -> njegov ownerId()
         * podkorisnik     -> ownerId() glavnog korisnika
         * superadmin      -> standardno nema pravo createa
         */
        return BudgetResource::fillOwnershipData($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
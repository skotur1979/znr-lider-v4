<?php

namespace App\Filament\Resources\Budgets\Pages;

use App\Filament\Resources\Budgets\BudgetResource;
use Filament\Resources\Pages\EditRecord;

class EditBudget extends EditRecord
{
    protected static string $resource = BudgetResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /*
         * Ownership postojećeg zapisa nikada se ne mijenja
         * kroz edit formu.
         *
         * user_id nije dio forme, ali ga dodatno uklanjamo
         * kao serversku zaštitu.
         */
        unset($data['user_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
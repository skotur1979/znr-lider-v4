<?php

namespace App\Filament\Resources\Budgets\Pages;

use App\Filament\Resources\Budgets\BudgetResource;
use Filament\Resources\Pages\EditRecord;

class EditBudget extends EditRecord
{
    protected static string $resource = BudgetResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
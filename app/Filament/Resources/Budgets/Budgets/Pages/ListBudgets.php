<?php

namespace App\Filament\Resources\Budgets\Budgets\Pages;

use App\Filament\Resources\Budgets\Budgets\BudgetResource;
use Filament\Resources\Pages\ListRecords;

class ListBudgets extends ListRecords
{
    protected static string $resource = BudgetResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }
}
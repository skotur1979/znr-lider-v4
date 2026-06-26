<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;

abstract class BaseListRecords extends ListRecords
{
    public function getDefaultTableRecordsPerPageSelectOption(): string|int
    {
        $perPage = request()->query('tableRecordsPerPage');

        if ($perPage === 'all') {
            return 'all';
        }

        if (in_array((int) $perPage, [10, 25, 50, 100], true)) {
            return (int) $perPage;
        }

        return parent::getDefaultTableRecordsPerPageSelectOption();
    }
}
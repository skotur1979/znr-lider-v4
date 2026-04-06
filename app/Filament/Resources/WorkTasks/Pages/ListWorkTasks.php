<?php

namespace App\Filament\Resources\WorkTasks\Pages;

use App\Filament\Resources\WorkTasks\WorkTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkTasks extends ListRecords
{
    protected static string $resource = WorkTaskResource::class;

    public function mount(): void
    {
        parent::mount();

        $status = request()->query('status');

        if ($status === 'open') {
            $this->tableFilters['status']['value'] = 'open';
            return;
        }

        if ($status === 'closed') {
            $this->tableFilters['status']['value'] = 'closed';
            return;
        }

        unset($this->tableFilters['status']);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novi radni zadatak'),
        ];
    }
}
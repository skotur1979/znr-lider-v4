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

        if (in_array($status, ['open', 'closed'], true)) {
            $this->tableFilters['status']['value'] = $status;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novi radni zadatak'),
        ];
    }
}
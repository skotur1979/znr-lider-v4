<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Models\WorkTask;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewOperationalLog extends ViewRecord
{
    protected static string $resource = OperationalLogResource::class;

    protected string $view = 'filament.resources.operational-logs.pages.view-operational-log';

    protected Width|string|null $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Uredi'),
        ];
    }

    public function getViewData(): array
    {
        $items = collect($this->record->items ?? [])->values();

        $taskIds = $items
            ->pluck('task_id')
            ->filter()
            ->values()
            ->all();

        $tasks = WorkTask::query()
            ->whereIn('id', $taskIds)
            ->get()
            ->keyBy('id');

        return [
            'items' => $items,
            'tasks' => $tasks,
            'totalItems' => $items->count(),
            'taskItems' => $items->filter(fn ($item) => ! empty($item['create_task']))->count(),
            'createdTasks' => $items->filter(fn ($item) => ! empty($item['task_id']))->count(),
        ];
    }
}
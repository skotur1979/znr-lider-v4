<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Models\WorkTask;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewOperationalLog extends ViewRecord
{
    protected static string $resource =
        OperationalLogResource::class;

    protected string $view =
        'filament.resources.operational-logs.pages.view-operational-log';

    protected Width|string|null $maxContentWidth = '7xl';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Uredi')
                ->visible(
                    fn (): bool =>
                        ! $this->record->trashed()
                        && OperationalLogResource::canEdit(
                            $this->record
                        )
                ),
        ];
    }

    public function getViewData(): array
    {
        $this->record->loadMissing('user');

        $items = collect(
            $this->record->items ?? []
        )->values();

        $taskIds = $items
            ->pluck('task_id')
            ->filter()
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->values()
            ->all();

        /*
         * Autor dnevnika određuje organizaciju
         * kojoj pripadaju povezani WorkTask zapisi.
         */
        $taskOwnerId =
            $this->record->user?->ownerId();

        $tasks = WorkTask::query()
            ->when(
                $taskOwnerId,
                fn ($query) =>
                    $query->where(
                        'user_id',
                        $taskOwnerId
                    ),
                fn ($query) =>
                    $query->whereRaw(
                        '1 = 0'
                    )
            )
            ->whereIn(
                'id',
                $taskIds
            )
            ->get()
            ->keyBy('id');

        return [
            'items' => $items,

            'tasks' => $tasks,

            'totalItems' =>
                $items->count(),

            'taskItems' =>
                $items
                    ->filter(
                        fn ($item): bool =>
                            ! empty(
                                $item['create_task']
                            )
                    )
                    ->count(),

            'createdTasks' =>
                $items
                    ->filter(
                        fn ($item): bool =>
                            ! empty(
                                $item['task_id']
                            )
                    )
                    ->count(),
        ];
    }
}
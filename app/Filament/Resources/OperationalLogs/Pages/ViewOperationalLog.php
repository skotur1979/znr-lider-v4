<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Models\WorkTask;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

            Action::make('create_missing_tasks')
                ->label('Kreiraj označene zadatke')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->action(function (): void {
                    $record = $this->record;

                    $items = collect($record->items ?? [])->values()->toArray();

                    $created = 0;

                    foreach ($items as $index => $item) {
                        if (! empty($item['create_task']) && empty($item['task_id'])) {
                            $task = WorkTask::create([
                                'user_id' => Auth::user()?->isSuperAdmin()
                                    ? $record->user_id
                                    : Auth::user()?->ownerId(),
                                'title' => Str::limit($item['note'], 80),
                                'description' => $item['note'],
                                'due_date' => $record->log_date,
                                'is_done' => false,
                                'completed_at' => null,
                            ]);

                            $items[$index]['task_id'] = $task->id;
                            $created++;
                        }
                    }

                    $record->update([
                        'items' => $items,
                        'converted_type' => collect($items)->whereNotNull('task_id')->count() > 0 ? WorkTask::class : null,
                        'status' => collect($items)->whereNotNull('task_id')->count() > 0 ? 'converted' : 'recorded',
                    ]);

                    Notification::make()
                        ->title($created > 0 ? 'Radni zadaci su kreirani.' : 'Nema novih zadataka za kreiranje.')
                        ->success()
                        ->send();
                }),
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
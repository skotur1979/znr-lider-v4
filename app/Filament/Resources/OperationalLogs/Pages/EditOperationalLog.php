<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Models\OperationalLog;
use App\Models\WorkTask;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EditOperationalLog extends EditRecord
{
    protected static string $resource = OperationalLogResource::class;

    protected Width|string|null $maxContentWidth = '7xl';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::id();
        }

        $data['items'] = collect($data['items'] ?? [])
            ->filter(fn (array $item): bool => filled($item['note'] ?? null))
            ->map(function (array $item): array {
                return [
                    'note' => trim((string) ($item['note'] ?? '')),
                    'create_task' => (bool) ($item['create_task'] ?? false),
                    'task_id' => $item['task_id'] ?? null,
                ];
            })
            ->values()
            ->toArray();

        $data['note'] = collect($data['items'])->pluck('note')->implode("\n");
        $data['type'] = 'note';

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var OperationalLog $record */
        $record = $this->record;

        $taskUserId = Auth::user()?->isSuperAdmin()
            ? $record->user_id
            : Auth::user()?->ownerId();

        $items = collect($record->items ?? [])->values()->toArray();

        $createdTasks = 0;

        foreach ($items as $index => $item) {
            if (! empty($item['create_task']) && empty($item['task_id'])) {
                $task = WorkTask::create([
                    'user_id' => $taskUserId,
                    'title' => Str::limit($item['note'], 80),
                    'description' => $item['note'],
                    'due_date' => $record->log_date,
                    'is_done' => false,
                    'completed_at' => null,
                ]);

                $items[$index]['task_id'] = $task->id;
                $createdTasks++;
            }
        }

        $record->updateQuietly([
            'items' => $items,
            'note' => collect($items)->pluck('note')->implode("\n"),
            'converted_type' => collect($items)->whereNotNull('task_id')->count() > 0 ? WorkTask::class : null,
            'converted_id' => null,
            'status' => collect($items)->whereNotNull('task_id')->count() > 0 ? 'converted' : 'recorded',
        ]);

        if ($createdTasks > 0) {
            Notification::make()
                ->title('Kreirani su novi radni zadaci.')
                ->body('Broj novih radnih zadataka: ' . $createdTasks)
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Obriši'),
        ];
    }
}
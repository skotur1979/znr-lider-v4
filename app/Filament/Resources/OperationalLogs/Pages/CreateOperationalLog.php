<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Models\OperationalLog;
use App\Models\WorkTask;
use App\Services\ActivityLogger;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CreateOperationalLog extends CreateRecord
{
    protected static string $resource = OperationalLogResource::class;

    protected Width|string|null $maxContentWidth = '7xl';

    protected function handleRecordCreation(array $data): Model
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        /*
         * Operativni dnevnik je OSOBNI zapis.
         * Superadmin ne izrađuje dnevnik u ime korisnika.
         */
        if ($user->isSuperAdmin()) {
            abort(403);
        }

        $logUserId = $user->id;
        $taskUserId = $user->ownerId();

        if (! $taskUserId) {
            abort(403);
        }

        $logDate = $data['log_date']
            ?? now()->toDateString();

        $items = collect($data['items'] ?? [])
            ->filter(
                fn (array $item): bool =>
                    filled($item['note'] ?? null)
            )
            ->map(function (array $item): array {
                return [
                    'note' => trim(
                        (string) ($item['note'] ?? '')
                    ),

                    'create_task' => (bool) (
                        $item['create_task'] ?? false
                    ),

                    'task_id' => null,
                ];
            })
            ->values()
            ->toArray();

        $log = OperationalLog::create([
            /*
             * Dnevnik pripada točno prijavljenom korisniku.
             */
            'user_id' => $logUserId,

            'log_date' => $logDate,

            'title' =>
                'Operativni dnevnik - '
                . Carbon::parse($logDate)->format('d.m.Y.'),

            'note' => collect($items)
                ->pluck('note')
                ->implode("\n"),

            'items' => $items,

            'type' => 'note',

            'status' => 'recorded',
        ]);

        $createdTasks = 0;
        $updatedItems = $items;

        foreach ($updatedItems as $index => $item) {
            if (empty($item['create_task'])) {
                continue;
            }

            /*
             * Radni zadatak nije osoban.
             * On pripada organizaciji autora dnevnika.
             */
            $task = WorkTask::create([
                'user_id' => $taskUserId,

                'title' => Str::limit(
                    $item['note'],
                    80
                ),

                'description' => $item['note'],

                'due_date' => $logDate,

                'is_done' => false,

                'completed_at' => null,
            ]);

            $updatedItems[$index]['task_id'] = $task->id;

            $createdTasks++;
        }

        $log->update([
            'items' => $updatedItems,

            'note' => collect($updatedItems)
                ->pluck('note')
                ->implode("\n"),

            'converted_type' =>
                $createdTasks > 0
                    ? WorkTask::class
                    : null,

            'converted_id' => null,

            'status' =>
                $createdTasks > 0
                    ? 'converted'
                    : 'recorded',
        ]);

        if ($createdTasks > 0) {
            ActivityLogger::status(
                module: 'Operativni dnevnik',

                title:
                    'Kreirani radni zadaci iz operativnog dnevnika',

                description:
                    'Iz operativnog dnevnika kreirano radnih zadataka: '
                    . $createdTasks
                    . '. Datum dnevnika: '
                    . Carbon::parse($logDate)->format('d.m.Y.'),

                record: $log,
            );
        }

        Notification::make()
            ->title('Operativni dnevnik je spremljen.')
            ->body(
                'Bilješki: '
                . count($updatedItems)
                . ' | Radnih zadataka: '
                . $createdTasks
            )
            ->success()
            ->send();

        return $log;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'view',
            [
                'record' => $this->record,
            ]
        );
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}

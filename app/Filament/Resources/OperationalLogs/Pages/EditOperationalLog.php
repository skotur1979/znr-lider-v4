<?php

namespace App\Filament\Resources\OperationalLogs\Pages;

use App\Filament\Resources\OperationalLogs\OperationalLogResource;
use App\Models\OperationalLog;
use App\Models\WorkTask;
use App\Services\ActivityLogger;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EditOperationalLog extends EditRecord
{
    protected static string $resource =
        OperationalLogResource::class;

    protected Width|string|null $maxContentWidth =
        '7xl';

    public function mount(
        int|string $record
    ): void {
        parent::mount($record);

        if (
            Auth::user()?->isSuperAdmin()
            || ! OperationalLogResource::canEdit(
                $this->record
            )
        ) {
            $this->redirect(
                OperationalLogResource::getUrl(
                    'view',
                    [
                        'record' =>
                            $this->record,
                    ]
                ),
                navigate: true
            );

            return;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POPUNJAVANJE FORME
    |--------------------------------------------------------------------------
    |
    | Kod starih dnevnika JSON još nema
    | share_task_with_organization.
    |
    | Zato stanje učitavamo iz stvarnog WorkTask zapisa.
    |
    */

    protected function mutateFormDataBeforeFill(
        array $data
    ): array {
        $this->record
            ->loadMissing('user');

        $author =
            $this->record->user;

        $taskOwnerId =
            $author?->ownerId();

        $items =
            collect(
                $data['items']
                ?? []
            );

        if (
            ! $author
            || ! $taskOwnerId
        ) {
            $data['items'] =
                $items
                    ->map(
                        function (
                            array $item
                        ): array {
                            $item[
                                'share_task_with_organization'
                            ] =
                                (bool) (
                                    $item[
                                        'share_task_with_organization'
                                    ]
                                    ?? false
                                );

                            return $item;
                        }
                    )
                    ->values()
                    ->toArray();

            return $data;
        }

        $taskIds =
            $items
                ->pluck('task_id')
                ->filter()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values();

        /*
         * Namjerno uklanjamo visibility scope
         * samo za ove već spremljene task_id vrijednosti,
         * ali ownership organizacije i dalje provjeravamo.
         */
        $tasks =
            WorkTask::query()
                ->withoutGlobalScope(
                    'work_task_visibility'
                )
                ->where(
                    'user_id',
                    $taskOwnerId
                )
                ->whereIn(
                    'id',
                    $taskIds
                )
                ->get()
                ->keyBy('id');

        $data['items'] =
            $items
                ->map(
                    function (
                        array $item
                    ) use (
                        $tasks
                    ): array {
                        $taskId =
                            ! empty(
                                $item['task_id']
                            )
                                ? (int)
                                    $item['task_id']
                                : null;

                        $task =
                            $taskId
                                ? $tasks->get(
                                    $taskId
                                )
                                : null;

                        $item[
                            'share_task_with_organization'
                        ] =
                            $task
                                ? (bool)
                                    $task
                                        ->is_shared_with_organization
                                : (bool) (
                                    $item[
                                        'share_task_with_organization'
                                    ]
                                    ?? false
                                );

                        return $item;
                    }
                )
                ->values()
                ->toArray();

        return $data;
    }

    protected function beforeSave(): void
    {
        if (
            ! OperationalLogResource::canEdit(
                $this->record
            )
        ) {
            $this->halt();
        }
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Autor dnevnika se ne mijenja.
         */
        $data['user_id'] =
            $this->record->user_id;

        $existingTaskIds =
            collect(
                $this->record->items
                ?? []
            )
                ->pluck('task_id')
                ->filter()
                ->map(
                    fn ($id): int =>
                        (int) $id
                )
                ->unique()
                ->values();

        $data['items'] =
            collect(
                $data['items']
                ?? []
            )
                ->filter(
                    fn (
                        array $item
                    ): bool =>
                        filled(
                            $item['note']
                            ?? null
                        )
                )
                ->map(
                    function (
                        array $item
                    ) use (
                        $existingTaskIds
                    ): array {
                        $taskId = null;

                        if (
                            isset(
                                $item[
                                    'task_id'
                                ]
                            )
                            && filled(
                                $item[
                                    'task_id'
                                ]
                            )
                            && $existingTaskIds
                                ->contains(
                                    (int)
                                    $item[
                                        'task_id'
                                    ]
                                )
                        ) {
                            $taskId =
                                (int)
                                $item[
                                    'task_id'
                                ];
                        }

                        /*
                         * Postojeći WorkTask ostaje
                         * povezan i ako se checkbox
                         * Radni zadatak naknadno makne.
                         */
                        $createTask =
                            $taskId !== null
                            || (bool) (
                                $item[
                                    'create_task'
                                ]
                                ?? false
                            );

                        return [
                            'note' =>
                                trim(
                                    (string) (
                                        $item[
                                            'note'
                                        ]
                                        ?? ''
                                    )
                                ),

                            'create_task' =>
                                $createTask,

                            'share_task_with_organization' =>
                                (bool) (
                                    $item[
                                        'share_task_with_organization'
                                    ]
                                    ?? false
                                ),

                            'task_id' =>
                                $taskId,
                        ];
                    }
                )
                ->values()
                ->toArray();

        $data['note'] =
            collect(
                $data['items']
            )
                ->pluck('note')
                ->implode("\n");

        $data['type'] =
            'note';

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var OperationalLog $record */
        $record =
            $this->record;

        $record
            ->loadMissing('user');

        $author =
            $record->user;

        if (! $author) {
            return;
        }

        $taskOwnerId =
            $author->ownerId();

        if (! $taskOwnerId) {
            return;
        }

        $items =
            collect(
                $record->items
                ?? []
            )
                ->values()
                ->toArray();

        $createdTasks = 0;
        $updatedTasks = 0;

        foreach (
            $items
            as $index => $item
        ) {
            $note =
                trim(
                    (string) (
                        $item['note']
                        ?? ''
                    )
                );

            if ($note === '') {
                continue;
            }

            $taskId =
                ! empty(
                    $item['task_id']
                )
                    ? (int)
                        $item['task_id']
                    : null;

            /*
            |--------------------------------------------------------------------------
            | POSTOJEĆI RADNI ZADATAK
            |--------------------------------------------------------------------------
            */

            if ($taskId !== null) {
                $task =
                    WorkTask::query()
                        ->withoutGlobalScope(
                            'work_task_visibility'
                        )
                        ->where(
                            'user_id',
                            $taskOwnerId
                        )
                        ->whereKey(
                            $taskId
                        )
                        ->first();

                if ($task) {
                    $newTitle =
                        Str::limit(
                            $note,
                            80
                        );

                    $newDueDate =
                        $record->log_date;

                    $updateData = [
                        'title' =>
                            $newTitle,

                        'description' =>
                            $note,

                        'due_date' =>
                            $newDueDate,
                    ];

                    /*
                     * Samo autor WorkTaska može
                     * promijeniti vidljivost.
                     *
                     * Legacy zadaci imaju
                     * created_by_user_id = null
                     * i ostaju organizacijski.
                     */
                    if (
                        (int) (
                            $task
                                ->created_by_user_id
                            ?? 0
                        )
                        ===
                        (int) $author->id
                    ) {
                        $updateData[
                            'is_shared_with_organization'
                        ] =
                            (bool) (
                                $item[
                                    'share_task_with_organization'
                                ]
                                ?? false
                            );
                    }

                    $changed =
                        $task->title
                            !==
                            $updateData['title']
                        ||
                        $task->description
                            !==
                            $updateData[
                                'description'
                            ]
                        ||
                        optional(
                            $task->due_date
                        )->toDateString()
                            !==
                            optional(
                                $newDueDate
                            )->toDateString()
                        ||
                        (
                            array_key_exists(
                                'is_shared_with_organization',
                                $updateData
                            )
                            &&
                            (bool)
                            $task
                                ->is_shared_with_organization
                            !==
                            (bool)
                            $updateData[
                                'is_shared_with_organization'
                            ]
                        );

                    if ($changed) {
                        $task->update(
                            $updateData
                        );

                        $updatedTasks++;
                    }
                }

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | NOVI RADNI ZADATAK
            |--------------------------------------------------------------------------
            */

            if (
                empty(
                    $item[
                        'create_task'
                    ]
                )
            ) {
                continue;
            }

            $task =
                WorkTask::create([
                    'user_id' =>
                        $taskOwnerId,

                    'created_by_user_id' =>
                        $author->id,

                    'is_shared_with_organization' =>
                        (bool) (
                            $item[
                                'share_task_with_organization'
                            ]
                            ?? false
                        ),

                    'title' =>
                        Str::limit(
                            $note,
                            80
                        ),

                    'description' =>
                        $note,

                    'due_date' =>
                        $record->log_date,

                    'is_done' =>
                        false,

                    'completed_at' =>
                        null,
                ]);

            $items[
                $index
            ]['task_id'] =
                $task->id;

            $items[
                $index
            ]['create_task'] =
                true;

            $createdTasks++;
        }

        $hasTasks =
            collect($items)
                ->pluck('task_id')
                ->filter()
                ->isNotEmpty();

        $record->updateQuietly([
            'items' =>
                $items,

            'note' =>
                collect($items)
                    ->pluck('note')
                    ->implode("\n"),

            'converted_type' =>
                $hasTasks
                    ? WorkTask::class
                    : null,

            'converted_id' =>
                null,

            'status' =>
                $hasTasks
                    ? 'converted'
                    : 'recorded',
        ]);

        if ($createdTasks > 0) {
            ActivityLogger::status(
                module:
                    'Operativni dnevnik',

                title:
                    'Naknadno kreirani radni zadaci iz operativnog dnevnika',

                description:
                    'Naknadno kreirano radnih zadataka: '
                    . $createdTasks
                    . '. Datum dnevnika: '
                    . optional(
                        $record->log_date
                    )->format(
                        'd.m.Y.'
                    ),

                record:
                    $record,
            );
        }

        if ($createdTasks > 0) {
            Notification::make()
                ->title(
                    'Kreirani su novi radni zadaci.'
                )
                ->body(
                    'Broj novih radnih zadataka: '
                    . $createdTasks
                )
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Obriši')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        ! $this->record->trashed()
                        && OperationalLogResource::canDelete(
                            $this->record
                        )
                ),

            RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this->record->trashed()
                        && OperationalLogResource::canRestore(
                            $this->record
                        )
                ),

            ForceDeleteAction::make()
                ->label(
                    'Trajno izbriši'
                )
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this->record->trashed()
                        && OperationalLogResource::canForceDelete(
                            $this->record
                        )
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'view',
            [
                'record' =>
                    $this->record,
            ]
        );
    }
}
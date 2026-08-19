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

    protected Width|string|null $maxContentWidth = '7xl';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /*
         * Superadmin može pregledavati osobne dnevnike,
         * ali ih ne uređuje.
         *
         * Autor dnevnika može uređivati samo vlastiti zapis.
         */
        if (
            Auth::user()?->isSuperAdmin()
            || ! OperationalLogResource::canEdit($this->record)
        ) {
            $this->redirect(
                OperationalLogResource::getUrl(
                    'view',
                    [
                        'record' => $this->record,
                    ]
                ),
                navigate: true
            );

            return;
        }
    }

    protected function beforeSave(): void
    {
        /*
         * Dodatna serverska provjera neposredno
         * prije spremanja.
         */
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
         * Vlasnik osobnog dnevnika nikada se
         * ne mijenja kroz edit formu.
         */
        $data['user_id'] =
            $this->record->user_id;

        /*
         * task_id prihvaćamo samo ako je već bio
         * spremljen u ovom dnevniku.
         *
         * Time korisnik ne može kroz Livewire
         * podmetnuti ID nekog drugog radnog zadatka.
         */
        $existingTaskIds = collect(
            $this->record->items ?? []
        )
            ->pluck('task_id')
            ->filter()
            ->map(
                fn ($id): int =>
                    (int) $id
            )
            ->unique()
            ->values();

        $data['items'] = collect(
            $data['items'] ?? []
        )
            ->filter(
                fn (array $item): bool =>
                    filled($item['note'] ?? null)
            )
            ->map(
                function (array $item) use (
                    $existingTaskIds
                ): array {
                    $taskId = null;

                    if (
                        isset($item['task_id'])
                        && filled($item['task_id'])
                        && $existingTaskIds->contains(
                            (int) $item['task_id']
                        )
                    ) {
                        $taskId =
                            (int) $item['task_id'];
                    }

                    /*
                     * Ako je radni zadatak već kreiran,
                     * poveznica ostaje aktivna.
                     *
                     * Uklanjanjem kvačice ne brišemo
                     * postojeći WorkTask niti gubimo vezu.
                     */
                    $createTask =
                        $taskId !== null
                        || (bool) (
                            $item['create_task']
                            ?? false
                        );

                    return [
                        'note' => trim(
                            (string) (
                                $item['note']
                                ?? ''
                            )
                        ),

                        'create_task' =>
                            $createTask,

                        'task_id' =>
                            $taskId,
                    ];
                }
            )
            ->values()
            ->toArray();

        /*
         * Glavno note polje ostaje sinkronizirano
         * sa svim bilješkama iz repeatera.
         */
        $data['note'] = collect(
            $data['items']
        )
            ->pluck('note')
            ->implode("\n");

        $data['type'] = 'note';

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var OperationalLog $record */
        $record = $this->record;

        $record->loadMissing('user');

        $author = $record->user;

        if (! $author) {
            return;
        }

        /*
         * Radni zadaci pripadaju organizaciji autora.
         */
        $taskUserId =
            $author->ownerId();

        if (! $taskUserId) {
            return;
        }

        $items = collect(
            $record->items ?? []
        )
            ->values()
            ->toArray();

        $createdTasks = 0;
        $updatedTasks = 0;

        foreach ($items as $index => $item) {
            $note = trim(
                (string) (
                    $item['note']
                    ?? ''
                )
            );

            if ($note === '') {
                continue;
            }

            $taskId =
                ! empty($item['task_id'])
                    ? (int) $item['task_id']
                    : null;

            /*
             * POSTOJEĆI RADNI ZADATAK
             *
             * Ako bilješka već ima task_id,
             * ne radimo novi WorkTask.
             *
             * Umjesto toga ažuriramo postojeći.
             */
            if ($taskId !== null) {
                $task = WorkTask::query()
                    ->where(
                        'user_id',
                        $taskUserId
                    )
                    ->whereKey($taskId)
                    ->first();

                if ($task) {
                    $newTitle = Str::limit(
                        $note,
                        80
                    );

                    $newDueDate =
                        $record->log_date;

                    $changed =
                        $task->title !== $newTitle
                        || $task->description !== $note
                        || optional(
                            $task->due_date
                        )->toDateString()
                            !== optional(
                                $newDueDate
                            )->toDateString();

                    if ($changed) {
                        $task->update([
                            'title' =>
                                $newTitle,

                            'description' =>
                                $note,

                            'due_date' =>
                                $newDueDate,
                        ]);

                        $updatedTasks++;
                    }
                }

                continue;
            }

            /*
             * NOVI RADNI ZADATAK
             *
             * Kreiramo ga samo ako:
             *
             * - bilješka je označena kao Radni zadatak
             * - još nema task_id.
             */
            if (
                empty(
                    $item['create_task']
                )
            ) {
                continue;
            }

            $task = WorkTask::create([
                'user_id' =>
                    $taskUserId,

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

            /*
             * Spremamo ID upravo napravljenog
             * zadatka natrag u JSON dnevnika.
             *
             * Kod sljedećeg uređivanja taj ID će
             * kroz Hidden::make('task_id') ponovno
             * doći u formu.
             */
            $items[$index]['task_id'] =
                $task->id;

            $items[$index]['create_task'] =
                true;

            $createdTasks++;
        }

        $hasTasks = collect($items)
            ->pluck('task_id')
            ->filter()
            ->isNotEmpty();

        /*
         * Quiet update kako ne bismo ponovno
         * pokretali Filament save ciklus.
         */
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

        /*
         * Logiramo samo stvarno NOVO kreirane
         * radne zadatke.
         */
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
                    )->format('d.m.Y.'),

                record:
                    $record,
            );
        }

        /*
         * Obavijest prikazujemo samo ako je
         * stvarno nastao novi zadatak.
         */
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

        /*
         * Ako smo samo izmijenili postojeće
         * zadatke, nije potrebna posebna poruka
         * jer Filament već javlja da je zapis spremljen.
         */
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
                ->label('Trajno izbriši')
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
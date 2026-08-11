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
        /*
         * Prvo Filament učitava zapis kroz Resource query.
         *
         * Obični korisnik već kroz Resource može dohvatiti
         * samo vlastiti osobni dnevnik.
         */
        parent::mount($record);

        /*
         * Superadmin smije pregledavati osobne dnevnike,
         * ali ih ne smije uređivati.
         *
         * Ovo štiti i direktan /edit URL.
         */
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
         * Serverska zaštita neposredno prije spremanja.
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
        * ne može promijeniti kroz uređivanje.
        */
        $data['user_id'] = $this->record->user_id;

        /*
        * task_id ne smijemo vjerovati izravno
        * Livewire formi.
        *
        * Pri uređivanju prihvaćamo samo task_id
        * koji je već bio spremljen u ovom dnevniku.
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
                    $taskId =
                        isset($item['task_id'])
                        && $existingTaskIds->contains(
                            (int) $item['task_id']
                        )
                            ? (int) $item['task_id']
                            : null;

                    /*
                    * Ako je iz bilješke već nastao
                    * WorkTask, ta poveznica ostaje.
                    *
                    * Ne dopuštamo da se kasnijim
                    * uklanjanjem kvačice izgubi podatak
                    * da je zadatak već kreiran.
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

                        'create_task' => $createTask,

                        'task_id' => $taskId,
                    ];
                }
            )
            ->values()
            ->toArray();

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

        /*
         * Dohvaćamo autora dnevnika.
         *
         * U ovom trenutku zapis može uređivati
         * samo sam autor dnevnika.
         */
        $record->loadMissing('user');

        $author = $record->user;

        if (! $author) {
            return;
        }

        /*
         * WorkTask pripada organizaciji autora dnevnika.
         */
        $taskUserId = $author->ownerId();

        if (! $taskUserId) {
            return;
        }

        $items = collect(
            $record->items ?? []
        )
            ->values()
            ->toArray();

        $createdTasks = 0;

        foreach ($items as $index => $item) {
            if (
                empty($item['create_task'])
                || ! empty($item['task_id'])
            ) {
                continue;
            }

            $task = WorkTask::create([
                'user_id' => $taskUserId,

                'title' => Str::limit(
                    $item['note'],
                    80
                ),

                'description' => $item['note'],

                'due_date' => $record->log_date,

                'is_done' => false,

                'completed_at' => null,
            ]);

            $items[$index]['task_id'] =
                $task->id;

            $createdTasks++;
        }

        $hasTasks = collect($items)
            ->pluck('task_id')
            ->filter()
            ->isNotEmpty();

        $record->updateQuietly([
            'items' => $items,

            'note' => collect($items)
                ->pluck('note')
                ->implode("\n"),

            'converted_type' =>
                $hasTasks
                    ? WorkTask::class
                    : null,

            'converted_id' => null,

            'status' =>
                $hasTasks
                    ? 'converted'
                    : 'recorded',
        ]);

        if ($createdTasks > 0) {
            ActivityLogger::status(
                module: 'Operativni dnevnik',

                title:
                    'Naknadno kreirani radni zadaci iz operativnog dnevnika',

                description:
                    'Naknadno kreirano radnih zadataka: '
                    . $createdTasks
                    . '. Datum dnevnika: '
                    . optional(
                        $record->log_date
                    )->format('d.m.Y.'),

                record: $record,
            );

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
                        OperationalLogResource::canDelete(
                            $this->record
                        )
                        && ! $this->record->trashed()
                ),

            RestoreAction::make()
                ->label('Vrati')
                ->visible(
                    fn (): bool =>
                        OperationalLogResource::canRestore(
                            $this->record
                        )
                ),

            ForceDeleteAction::make()
                ->label('Trajno izbriši')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        OperationalLogResource::canForceDelete(
                            $this->record
                        )
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}
<?php

namespace App\Filament\Resources\WorkTasks\Pages;

use App\Filament\Resources\WorkTasks\WorkTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditWorkTask extends EditRecord
{
    protected static string $resource =
        WorkTaskResource::class;

    public function mount(
        int|string $record
    ): void {
        /*
         * Resource query već primjenjuje:
         *
         * - organizaciju
         * - privatnu / organizacijsku vidljivost
         */
        parent::mount($record);

        if (
            ! WorkTaskResource::canManageTask(
                $this->getRecord()
            )
        ) {
            abort(403);
        }
    }

    protected function beforeSave(): void
    {
        if (
            ! WorkTaskResource::canManageTask(
                $this->getRecord()
            )
        ) {
            $this->halt();

            abort(403);
        }
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Organizacija se nikada ne mijenja.
         */
        $data['user_id'] =
            $this->record->user_id;

        /*
         * Autor zadatka se nikada ne mijenja.
         */
        $data['created_by_user_id'] =
            $this->record
                ->created_by_user_id;

        /*
         * Samo stvarni autor zadatka
         * smije mijenjati:
         *
         * privatno <-> cijela organizacija.
         *
         * Drugi korisnik organizacije može
         * uređivati/zatvoriti shared zadatak,
         * ali ne može promijeniti njegovu
         * vidljivost.
         */
        if (
            (int) (
                $this->record
                    ->created_by_user_id
                ?? 0
            )
            !==
            (int) Auth::id()
        ) {
            $data[
                'is_shared_with_organization'
            ] =
                (bool)
                $this->record
                    ->is_shared_with_organization;
        } else {
            $data[
                'is_shared_with_organization'
            ] =
                (bool) (
                    $data[
                        'is_shared_with_organization'
                    ]
                    ?? false
                );
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Obriši')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        WorkTaskResource::canManageTask(
                            $this->getRecord()
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
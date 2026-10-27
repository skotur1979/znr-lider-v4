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
        $user =
            Auth::user();

        abort_unless(
            $user,
            403
        );

        /*
        * Organizacija se nikada ne mijenja.
        */
        $data['user_id'] =
            $this->record
                ->user_id;

        $createdBy =
            (int) (
                $this->record
                    ->created_by_user_id
                ?? 0
            );

        /*
        |--------------------------------------------------------------------------
        | POSTOJEĆI AUTOR
        |--------------------------------------------------------------------------
        */

        if ($createdBy > 0) {
            $data[
                'created_by_user_id'
            ] =
                $createdBy;

            /*
            * Samo autor smije promijeniti
            * privatno / organizacija.
            */
            if (
                $createdBy
                !==
                (int) $user->id
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

        /*
        |--------------------------------------------------------------------------
        | LEGACY ZADATAK BEZ AUTORA
        |--------------------------------------------------------------------------
        |
        | Stari zadaci napravljeni kroz kalendar
        | nemaju created_by_user_id.
        |
        | Glavni korisnik organizacije može ga
        | preuzeti i definirati vidljivost.
        |
        */

        if (
            (int) $user->id
            ===
            (int) $user->ownerId()
        ) {
            $data[
                'created_by_user_id'
            ] =
                $user->id;

            $data[
                'is_shared_with_organization'
            ] =
                (bool) (
                    $data[
                        'is_shared_with_organization'
                    ]
                    ?? false
                );

            return $data;
        }

        /*
        * Podkorisnik ne može preuzeti
        * stari legacy zadatak.
        */
        $data[
            'created_by_user_id'
        ] =
            null;

        $data[
            'is_shared_with_organization'
        ] =
            (bool)
            $this->record
                ->is_shared_with_organization;

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
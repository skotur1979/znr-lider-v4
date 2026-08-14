<?php

namespace App\Filament\Resources\WorkTasks\Pages;

use App\Filament\Resources\WorkTasks\WorkTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkTask extends EditRecord
{
    protected static string $resource =
        WorkTaskResource::class;

    public function mount(
        int|string $record
    ): void {
        /*
         * Filament prvo mora učitati zapis kroz
         * Resource query.
         *
         * Time organizacijski korisnik već ne može
         * učitati zapis druge organizacije.
         */
        parent::mount($record);

        /*
         * Dodatna poslovna zaštita:
         *
         * - superadmin ne uređuje organizacijske
         *   radne zadatke
         *
         * - organizacijski korisnik može uređivati
         *   samo zadatak svoje organizacije.
         */
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
        /*
         * Ponovna serverska provjera neposredno
         * prije spremanja.
         */
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
         * Ownership postojećeg radnog zadatka
         * nikada se ne mijenja kroz edit formu.
         *
         * Ne vjerujemo user_id vrijednosti
         * poslanoj iz browsera.
         */
        $data['user_id'] =
            $this->record->user_id;

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
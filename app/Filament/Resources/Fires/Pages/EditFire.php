<?php

namespace App\Filament\Resources\Fires\Pages;

use App\Filament\Resources\Fires\FireResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFire extends EditRecord
{
    protected static string $resource =
        FireResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst liste PRIJE
         * parent::mount().
         */
        $pregled = request()->query('pregled');

        if (
            in_array(
                $pregled,
                ['isteklo', 'uskoro'],
                true
            )
        ) {
            $this->pregled = $pregled;
        }

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Pregled')
                ->url(function (): string {
                    $parameters = [
                        'record' =>
                            $this->getRecord(),
                    ];

                    if (
                        in_array(
                            $this->pregled,
                            ['isteklo', 'uskoro'],
                            true
                        )
                    ) {
                        $parameters['pregled'] =
                            $this->pregled;
                    }

                    return FireResource::getUrl(
                        'view',
                        $parameters
                    );
                }),

            Actions\DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation(),

            Actions\RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation(),

            Actions\ForceDeleteAction::make()
                ->label('Trajno obriši')
                ->requiresConfirmation(),
        ];
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership vatrogasnog aparata nikada se
         * ne mijenja kroz edit formu, uključujući
         * administraciju od strane superadmina.
         */
        unset($data['user_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        /*
         * Ako smo uređivanje otvorili iz liste
         * "Isteklo" ili "Uskoro", nakon spremanja
         * vraćamo se na isti pregled.
         */
        if (
            in_array(
                $this->pregled,
                ['isteklo', 'uskoro'],
                true
            )
        ) {
            return static::getResource()::getUrl(
                'index',
                [
                    'pregled' =>
                        $this->pregled,
                ]
            );
        }

        /*
         * Kod običnog uređivanja zadržavamo
         * standardno ponašanje.
         */
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}
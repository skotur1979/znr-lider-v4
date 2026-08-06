<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWasteTrackingForm extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        WasteTrackingFormResource::class;

    public function mount(int|string $record): void
    {
        /*
         * Filament prvo mora učitati stvarni model.
         */
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation()
                ->before(
                    WasteTrackingFormResource::beforeModulePermission(
                        'delete'
                    )
                ),

            RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation()
                ->before(
                    WasteTrackingFormResource::beforeModulePermission(
                        'delete'
                    )
                ),

            ForceDeleteAction::make()
                ->label('Trajno izbriši')
                ->requiresConfirmation()
                ->before(
                    WasteTrackingFormResource::beforeModulePermission(
                        'delete'
                    )
                ),
        ];
    }

    protected function beforeSave(): void
    {
        $this->haltIfMissingModulePermission(
            'update'
        );
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Organizacijski korisnik ne smije promijeniti
         * vlasnika postojećeg zapisa.
         */
        if (! auth()->user()?->isSuperAdmin()) {
            unset($data['user_id']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl(
            'index'
        );
    }
}
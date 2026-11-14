<?php

namespace App\Filament\Resources\PPEEquipment\Pages;

use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPPEEquipment extends EditRecord
{
    protected static string $resource =
        PPEEquipmentResource::class;

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        return PPEEquipmentResource::prepareData(
            $data,
            $this->record
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Trajno izbriši')
                ->requiresConfirmation()
                ->modalHeading(
                    'Trajno izbriši OZO opremu'
                )
                ->modalDescription(
                    'OZO se može trajno izbrisati samo ako nikada nije korišten u Upisniku OZO. Ako se više ne koristi, postavi polje Aktivno na Ne.'
                )
                ->modalSubmitActionLabel(
                    'Trajno izbriši'
                )
                ->modalCancelActionLabel(
                    'Odustani'
                )
                ->visible(
                    fn (): bool =>
                        PPEEquipmentResource::canDelete(
                            $this->record
                        )
                )
                ->before(
                    function (): void {
                        abort_unless(
                            PPEEquipmentResource::canDelete(
                                $this->record
                            ),
                            403
                        );
                    }
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
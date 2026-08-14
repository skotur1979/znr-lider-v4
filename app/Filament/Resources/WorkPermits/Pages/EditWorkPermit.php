<?php

namespace App\Filament\Resources\WorkPermits\Pages;

use App\Filament\Resources\WorkPermits\WorkPermitResource;
use App\Services\WorkPermitPdfGenerator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkPermit extends EditRecord
{
    protected static string $resource = WorkPermitResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /*
        * Ownership postojećeg zapisa ne smije se
        * mijenjati prilikom uređivanja.
        */
        $data['user_id'] = $this->record->user_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('Izvoz PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $path = WorkPermitPdfGenerator::generate(
                        $this->record
                    );

                    return response()
                        ->download(
                            $path,
                            basename($path),
                            [
                                'Content-Type' => 'application/pdf',
                            ]
                        )
                        ->deleteFileAfterSend(true);
                }),

            Actions\ViewAction::make()
                ->label('Prikaži'),

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

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl('index');
    }
}
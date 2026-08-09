<?php

namespace App\Filament\Resources\WorkPermits\Pages;

use App\Filament\Resources\WorkPermits\WorkPermitResource;
use App\Services\WorkPermitPdfGenerator;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkPermit extends ViewRecord
{
    protected static string $resource = WorkPermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('Izvoz PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
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

            Actions\EditAction::make()
                ->label('Uredi'),

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

    public function getView(): string
    {
        return 'filament.resources.work-permits.pages.view-work-permit';
    }
}
<?php

namespace App\Filament\Resources\WorkPermits\Pages;

use App\Filament\Resources\WorkPermits\WorkPermitResource;
use App\Services\WorkPermitPdfGenerator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkPermit extends EditRecord
{
    protected static string $resource = WorkPermitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('Izvoz PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $path = WorkPermitPdfGenerator::generate($this->record);

                    return response()->download(
                        $path,
                        basename($path),
                        ['Content-Type' => 'application/pdf']
                    )->deleteFileAfterSend(true);
                }),

            Actions\ViewAction::make()->label('Prikaži'),
            Actions\DeleteAction::make()->label('Deaktiviraj'),
            Actions\RestoreAction::make()->label('Vrati'),
            Actions\ForceDeleteAction::make()->label('Trajno obriši'),
        ];
    }
}
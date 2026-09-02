<?php

namespace App\Filament\Resources\DocumentationItems\Pages;

use App\Filament\Resources\DocumentationItems\DocumentationItemResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentationItem extends ViewRecord
{
    protected static string $resource =
        DocumentationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('qrCode')
                ->label('QR kod')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->url(
                    fn (): string =>
                        route(
                            'documentation.qr.admin',
                            [
                                'documentationItem' =>
                                    $this->getRecord(),
                            ]
                        )
                )
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('Uredi'),
        ];
    }
}
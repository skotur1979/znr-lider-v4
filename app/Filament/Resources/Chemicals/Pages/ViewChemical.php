<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChemical extends ViewRecord
{
    protected static string $resource =
        ChemicalResource::class;

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
                            'chemical.qr.admin',
                            [
                                'chemical' =>
                                    $this->getRecord(),
                            ]
                        )
                )
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('Uredi')
                ->color('warning'),
        ];
    }
}

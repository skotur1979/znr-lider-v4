<?php

namespace App\Filament\Resources\FirstAidKits\Pages;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFirstAidKit extends ViewRecord
{
    protected static string $resource =
        FirstAidKitResource::class;

    public function getTitle(): string
    {
        return 'Pregled Prva pomoć';
    }

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
                            'first-aid-kit.qr.admin',
                            [
                                'firstAidKit' =>
                                    $this->getRecord(),
                            ]
                        )
                )
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('Uredi'),

            DeleteAction::make()
                ->label('Obriši')
                ->modalHeading(
                    'Obriši Prvu pomoć'
                )
                ->modalDescription(
                    'Jeste li sigurni da želite obrisati ovu Prvu pomoć?'
                )
                ->successNotificationTitle(
                    'Prva pomoć je obrisana.'
                ),
        ];
    }
}
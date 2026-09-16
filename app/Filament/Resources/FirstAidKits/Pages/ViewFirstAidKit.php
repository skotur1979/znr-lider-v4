<?php

namespace App\Filament\Resources\FirstAidKits\Pages;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFirstAidKit extends ViewRecord
{
    protected static string $resource =
        FirstAidKitResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst dashboard pregleda
         * prije parent::mount().
         */
        $pregled = request()->query('pregled');

        if (
            in_array(
                $pregled,
                [
                    'isteklo',
                    'uskoro',
                ],
                true
            )
        ) {
            $this->pregled = $pregled;
        }

        parent::mount($record);
    }

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

            Action::make('editFirstAidKit')
                ->label('Uredi')
                ->color('warning')
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->url(function (): string {
                    $parameters = [
                        'record' =>
                            $this->getRecord(),
                    ];

                    /*
                     * Prenosimo dashboard kontekst
                     * na Edit stranicu.
                     */
                    if (
                        in_array(
                            $this->pregled,
                            [
                                'isteklo',
                                'uskoro',
                            ],
                            true
                        )
                    ) {
                        $parameters['pregled'] =
                            $this->pregled;
                    }

                    return FirstAidKitResource::getUrl(
                        'edit',
                        $parameters
                    );
                }),

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
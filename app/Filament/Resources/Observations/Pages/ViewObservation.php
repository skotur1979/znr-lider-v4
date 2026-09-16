<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Observations\ObservationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewObservation extends ViewRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        ObservationResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst dashboard pregleda.
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

        /*
         * Učitavamo stvarni Observation model.
         */
        parent::mount($record);

        /*
         * Provjera prava pregleda.
         */
        $this->redirectIfMissingModulePermission(
            'view'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editObservation')
                ->label('Uredi')
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->color('warning')
                ->action(function () {
                    if (
                        ! ObservationResource
                            ::allowsModulePermission(
                                'update'
                            )
                    ) {
                        return;
                    }

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

                    return redirect(
                        ObservationResource::getUrl(
                            'edit',
                            $parameters
                        )
                    );
                }),
        ];
    }
}
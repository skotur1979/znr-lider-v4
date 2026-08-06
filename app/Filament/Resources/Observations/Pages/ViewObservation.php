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

    public function mount(int|string $record): void
    {
        /*
         * Prvo učitaj stvarni Observation model.
         */
        parent::mount($record);

        /*
         * Zatim provjeri pravo pregleda.
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
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->action(function () {
                    if (
                        ! ObservationResource::allowsModulePermission(
                            'update'
                        )
                    ) {
                        return;
                    }

                    return redirect(
                        ObservationResource::getUrl(
                            'edit',
                            [
                                'record' =>
                                    $this->getRecord(),
                            ]
                        )
                    );
                }),
        ];
    }
}
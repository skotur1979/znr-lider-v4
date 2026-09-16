<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Filament\Resources\PPELogs\PPELogResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPPELog extends ViewRecord
{
    protected static string $resource =
        PPELogResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst dashboard
         * pregleda prije parent::mount().
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editPPELog')
                ->label('Uredi')
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->color('warning')
                ->url(function (): string {
                    $parameters = [
                        'record' =>
                            $this->getRecord(),
                    ];

                    /*
                     * Prenosimo dashboard
                     * kontekst na Edit stranicu.
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

                    return PPELogResource::getUrl(
                        'edit',
                        $parameters
                    );
                }),
        ];
    }
}

<?php

namespace App\Filament\Resources\Fires\Pages;

use App\Filament\Resources\Fires\FireResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewFire extends ViewRecord
{
    protected static string $resource =
        FireResource::class;

    public ?string $pregled = null;

    public function mount(
        int|string $record
    ): void {
        /*
         * Spremamo kontekst liste PRIJE
         * parent::mount().
         */
        $pregled = request()->query('pregled');

        if (
            in_array(
                $pregled,
                ['isteklo', 'uskoro'],
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
            Action::make('fireQr')
                ->label('QR kod')
                ->icon(
                    'heroicon-o-qr-code'
                )
                ->color('success')
                ->visible(
                    fn (): bool =>
                        ! $this
                            ->getRecord()
                            ->trashed()
                )
                ->url(
                    fn (): string =>
                        route(
                            'fire.qr.admin',
                            [
                                'fire' =>
                                    $this
                                        ->getRecord(),
                            ]
                        )
                )
                ->openUrlInNewTab(),

            /*
             * Ne koristimo standardni EditAction jer
             * moramo prenijeti ?pregled=isteklo/uskoro
             * na Edit stranicu.
             */
            Action::make('editFire')
                ->label('Uredi')
                ->color('warning')
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->visible(
                    fn (): bool =>
                        ! $this
                            ->getRecord()
                            ->trashed()
                )
                ->action(function () {
                    $parameters = [
                        'record' =>
                            $this->getRecord(),
                    ];

                    if (
                        in_array(
                            $this->pregled,
                            ['isteklo', 'uskoro'],
                            true
                        )
                    ) {
                        $parameters['pregled'] =
                            $this->pregled;
                    }

                    return redirect(
                        FireResource::getUrl(
                            'edit',
                            $parameters
                        )
                    );
                }),

            Actions\DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        ! $this
                            ->getRecord()
                            ->trashed()
                ),

            Actions\RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this
                            ->getRecord()
                            ->trashed()
                ),

            Actions\ForceDeleteAction::make()
                ->label('Trajno obriši')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this
                            ->getRecord()
                            ->trashed()
                ),
        ];
    }
}
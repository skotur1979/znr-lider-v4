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

            Actions\EditAction::make()
                ->label('Uredi')
                ->visible(
                    fn (): bool =>
                        ! $this
                            ->getRecord()
                            ->trashed()
                ),

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
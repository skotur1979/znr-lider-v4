<?php

namespace App\Filament\Resources\LearningMaterials\Pages;

use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLearningMaterial extends ViewRecord
{
    protected static string $resource =
        LearningMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('qrCode')
                ->label('QR kod')
                ->icon('heroicon-o-qr-code')
                ->color('success')
                ->visible(
                    fn (): bool =>
                        LearningMaterialResource::canManageQr(
                            $this->getRecord()
                        )
                )
                ->url(
                    fn (): string =>
                        route(
                            'learning-material.qr.admin',
                            [
                                'learningMaterial' =>
                                    $this->getRecord(),
                            ]
                        )
                )
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('Uredi')
                ->visible(
                    fn (): bool =>
                        LearningMaterialResource::canEdit(
                            $this->getRecord()
                        )
                ),
        ];
    }
}
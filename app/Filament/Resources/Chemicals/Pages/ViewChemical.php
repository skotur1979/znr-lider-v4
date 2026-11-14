<?php

namespace App\Filament\Resources\Chemicals\Pages;

use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Models\Chemical;
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
                ->icon(
                    'heroicon-o-qr-code'
                )
                ->color('success')
                ->visible(
                    function (): bool {
                        /** @var Chemical $record */
                        $record =
                            $this
                                ->getRecord();

                        return
                            ! $record
                                ->trashed()
                            && $record
                                ->user_id
                                !== null
                            && ChemicalResource::
                                allowsModulePermission(
                                    'view'
                                );
                    }
                )
                ->url(
                    fn (): string =>
                        route(
                            'chemical.qr.admin',
                            [
                                'chemical' =>
                                    $this
                                        ->getRecord(),
                            ]
                        )
                )
                ->openUrlInNewTab(),

            EditAction::make()
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
                        && ChemicalResource::
                            canEdit(
                                $this
                                    ->getRecord()
                            )
                ),
        ];
    }
}
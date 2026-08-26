<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewMachine extends ViewRecord
{
    protected static string $resource = MachineResource::class;

    public function mount(
        int|string $record
    ): void {
        parent::mount($record);

        if (
            ! MachineResource::ensureModulePermission(
                'view'
            )
        ) {
            $this->redirect(
                MachineResource::getUrl('index'),
                navigate: true
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('machineQr')
                ->label('QR kod')
                ->icon(
                    'heroicon-o-qr-code'
                )
                ->color('success')
                ->url(
                    fn (): string =>
                        route(
                            'machine.qr.admin',
                            [
                                'machine' =>
                                    $this->getRecord(),
                            ]
                        )
                )
                ->openUrlInNewTab(),

            Action::make('editMachine')
                ->label('Uredi')
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->color('warning')
                ->action(function () {
                    if (
                        ! MachineResource::
                            ensureModulePermission(
                                'update'
                            )
                    ) {
                        return;
                    }

                    return redirect(
                        MachineResource::getUrl(
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
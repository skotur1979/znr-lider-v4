<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewMachine extends ViewRecord
{
    protected static string $resource = MachineResource::class;

    public function mount(int|string $record): void
    {
        /*
         * Prvo učitaj stvarni Machine model.
         */
        parent::mount($record);

        /*
         * Zatim provjeri dozvolu pregleda.
         */
        if (! MachineResource::ensureModulePermission('view')) {
            $this->redirect(
                MachineResource::getUrl('index'),
                navigate: true
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editMachine')
                ->label('Uredi')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->action(function () {
                    if (! MachineResource::ensureModulePermission('update')) {
                        return;
                    }

                    return redirect(
                        MachineResource::getUrl('edit', [
                            'record' => $this->getRecord(),
                        ])
                    );
                }),
        ];
    }
}
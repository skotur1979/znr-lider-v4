<?php

namespace App\Filament\Resources\Machines\Pages;

use App\Filament\Resources\Machines\MachineResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMachine extends ViewRecord
{
    protected static string $resource = MachineResource::class;

    public function mount(int|string $record): void
    {
        if (! MachineResource::ensureModulePermission('view')) {
            $this->redirect(
                MachineResource::getUrl('index')
            );

            return;
        }

        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Uredi')
                ->before(function ($action): void {

                    if (! MachineResource::ensureModulePermission('update')) {
                        $action->halt();
                    }

                }),
        ];
    }
}

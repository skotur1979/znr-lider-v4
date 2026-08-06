<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployee extends ViewRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        EmployeeResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'view'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editEmployee')
                ->label('Uredi')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->action(function () {
                    if (
                        ! EmployeeResource::allowsModulePermission(
                            'update'
                        )
                    ) {
                        return;
                    }

                    return redirect(
                        EmployeeResource::getUrl(
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

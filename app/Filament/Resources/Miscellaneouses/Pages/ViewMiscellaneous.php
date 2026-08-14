<?php

namespace App\Filament\Resources\Miscellaneouses\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Miscellaneouses\MiscellaneousResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewMiscellaneous extends ViewRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        MiscellaneousResource::class;

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
            Action::make('editMiscellaneous')
                ->label('Uredi')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->action(function () {
                    if (
                        ! MiscellaneousResource::allowsModulePermission(
                            'update'
                        )
                    ) {
                        return;
                    }

                    return redirect(
                        MiscellaneousResource::getUrl(
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

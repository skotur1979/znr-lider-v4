<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        CategoryResource::class;

    public function mount(int|string $record): void
    {
        /*
         * Prvo učitaj Category model.
         */
        parent::mount($record);

        /*
         * Zatim provjeri pravo pregleda.
         */
        $this->redirectIfMissingModulePermission(
            'view'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editCategory')
                ->label('Uredi')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->action(function () {
                    if (
                        ! CategoryResource::allowsModulePermission(
                            'update'
                        )
                    ) {
                        return;
                    }

                    return redirect(
                        CategoryResource::getUrl(
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
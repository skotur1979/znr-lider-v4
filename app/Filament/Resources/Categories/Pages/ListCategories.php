<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Exports\CategoriesExport;
use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova kategorija')
                ->icon('heroicon-o-plus')
                ->color('warning'),

            Action::make('exportExcel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new CategoriesExport(),
                        'kategorije-ispitivanja-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}
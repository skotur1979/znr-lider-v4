<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Exports\InspectionsExport;
use App\Filament\Resources\Inspections\InspectionResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListInspections extends ListRecords
{
    protected static string $resource = InspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi nadzor')
                ->icon('heroicon-o-plus')
                ->visible(
                    fn (): bool =>
                        InspectionResource::canCreate()
                ),
                
            Action::make('create_five_s')
                ->label('Novi 5S nadzor')
                ->icon('heroicon-o-squares-2x2')
                ->color('success')
                ->visible(
                    fn (): bool =>
                        InspectionResource::canCreate()
                )
                ->url(fn () => static::getResource()::getUrl(
                    'create',
                    [
                        'inspection_type' => 'five_s',
                    ]
                )),

            Action::make('exportExcel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new InspectionsExport(),
                        'nadzori-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}
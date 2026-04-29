<?php

namespace App\Filament\Resources\Users\Pages;

use App\Exports\UsersExport;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novi korisnik'),

            Actions\Action::make('exportExcel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return Excel::download(
                        new UsersExport(),
                        'korisnici-' . now()->format('Y-m-d') . '.xlsx'
                    );
                }),
        ];
    }
}
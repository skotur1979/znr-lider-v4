<?php

namespace App\Filament\Resources\PpeLogs\PPELogResource\Pages;

use App\Exports\PpeItemsAllExport;
use App\Filament\Resources\PpeLogs\PPELogResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListPPELogs extends ListRecords
{
    protected static string $resource = PPELogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi OZO'),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->action(fn () => Excel::download(
                    new PpeItemsAllExport(),
                    'OZO-SVI-' . now()->format('d-m-Y') . '.xlsx'
                )),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $pregled =
            request()->query('pregled')
            ?? data_get(request()->query(), 'tableFilters.pregled.value')
            ?? data_get(request()->query(), 'filters.pregled.value');

        return match ($pregled) {
            'uskoro' => $query->whereHas('items', function (Builder $q): void {
                $q->whereNotNull('end_date')
                    ->whereDate('end_date', '>=', Carbon::today())
                    ->whereDate('end_date', '<=', Carbon::today()->addDays(30));
            }),

            'isteklo' => $query->whereHas('items', function (Builder $q): void {
                $q->whereNotNull('end_date')
                    ->whereDate('end_date', '<', Carbon::today());
            }),

            default => $query,
        };
    }
}
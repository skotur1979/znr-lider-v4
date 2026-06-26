<?php

namespace App\Filament\Resources\PPELogs\Pages;

use App\Exports\PpeItemsAllExport;
use App\Filament\Resources\PPELogs\PPELogResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Resources\Pages\BaseListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListPPELogs extends BaseListRecords
{
    protected static string $resource = PPELogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi OZO')
                ->icon('heroicon-o-plus')
                ->color('warning'),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
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
                $q->whereNull('return_date')
                    ->whereNotNull('end_date')
                    ->where('end_date', '>=', Carbon::today()->startOfDay())
                    ->where('end_date', '<=', Carbon::today()->addDays(30)->endOfDay());
            }),

            'isteklo' => $query->whereHas('items', function (Builder $q): void {
                $q->whereNull('return_date')
                    ->whereNotNull('end_date')
                    ->where('end_date', '<', Carbon::today()->startOfDay());
            }),

            default => $query,
        };
    }
}
<?php

namespace App\Filament\Resources\FirstAidKits\Pages;

use App\Exports\FirstAidKitsExport;
use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ListFirstAidKits extends ListRecords
{
    protected static string $resource = FirstAidKitResource::class;

    public function getTitle(): string
    {
        return 'Prva pomoć';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nova prva pomoć')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    $kits = $this->getFilteredSortedTableQuery()
                        ->with(['items' => fn ($q) => $q->orderBy('valid_until')])
                        ->withCount('items')
                        ->get();

                    $pdf = Pdf::loadView('pdf.first-aid-kits', [
                        'kits' => $kits,
                    ])
                        ->setPaper('a4', 'landscape')
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'isPhpEnabled' => true,
                            'dpi' => 96,
                            'defaultFont' => 'DejaVu Sans',
                        ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'prva-pomoc-ormarici-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),

            Actions\Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(fn () => Excel::download(
                    new FirstAidKitsExport(),
                    'prva-pomoc-ormarici-' . now()->format('Y-m-d') . '.xlsx'
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
                $q->whereNotNull('valid_until')
                    ->whereDate('valid_until', '>=', Carbon::today())
                    ->whereDate('valid_until', '<=', Carbon::today()->addDays(30));
            }),

            'isteklo' => $query->whereHas('items', function (Builder $q): void {
                $q->whereNotNull('valid_until')
                    ->whereDate('valid_until', '<', Carbon::today());
            }),

            default => $query,
        };
    }
}
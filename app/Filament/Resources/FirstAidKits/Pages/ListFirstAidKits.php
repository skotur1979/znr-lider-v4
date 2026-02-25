<?php

namespace App\Filament\Resources\FirstAidKits\Pages;

use App\Filament\Resources\FirstAidKits\FirstAidKitResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

// prilagodi ako ti je naziv export klase drugačiji
use App\Exports\FirstAidKitsExport;

class ListFirstAidKits extends ListRecords
{
    protected static string $resource = FirstAidKitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novi zapis'),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->action(function () {
                    // ✅ isti query kao tablica (user scope preko Resource query-ja)
                    $kits = FirstAidKitResource::getEloquentQuery()
                        ->with(['items' => fn ($q) => $q->orderBy('valid_until')])
                        ->withCount('items')
                        ->orderByDesc('inspected_at')
                        ->get();

                    $pdf = Pdf::loadView('pdf.first-aid-kits', [
                        'kits' => $kits,
                    ])->setPaper('a4', 'landscape');

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
}
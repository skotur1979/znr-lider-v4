<?php

namespace App\Filament\Resources\InspectionZones\Pages;

use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\InspectionZone5sExport;
use Maatwebsite\Excel\Facades\Excel;

class EditInspectionZone extends EditRecord
{
    protected static string $resource = InspectionZoneResource::class;

    public function getTitle(): string
    {
        return 'Ocjenjivanje zone';
    }

    protected function getReturnUrl(): string
    {
        return request()->query('return_url')
            ?: '/admin/inspections';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_zone_results')
                ->label('Vidi rezultate zona')
                ->icon('heroicon-o-chart-bar-square')
                ->color('info')
                ->url($this->getReturnUrl())
                ->extraAttributes([
                    'type' => 'button',
                ]),

                Action::make('export_5s_pdf')
    ->label('Izvoz u PDF')
    ->icon('heroicon-o-arrow-down-tray')
    ->color('warning')
    ->action(function () {
        $zone = $this->record->load([
            'questions',
            'answers.question',
        ]);

        $pdf = Pdf::loadView('pdf.inspection-zone-5s', [
            'zone' => $zone,
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
            '5s-zona-' . str($zone->name)->slug() . '-' . now()->format('Y-m-d') . '.pdf'
        );
    }),
        Action::make('export_5s_excel')
        ->label('Izvoz u Excel')
        ->icon('heroicon-o-document-arrow-down')
        ->color('success')
        ->action(function () {
            $zone = $this->record->load([
                'questions',
                'answers.question',
            ]);

            return Excel::download(
                new InspectionZone5sExport($zone),
                '5s-zona-' . str($zone->name)->slug() . '-' . now()->format('Y-m-d') . '.xlsx'
            );
        }),
            Action::make('back')
                ->label('Povratak')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getReturnUrl())
                ->extraAttributes([
                    'type' => 'button',
                ]),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getReturnUrl();
    }
}
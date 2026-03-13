<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Services\WasteTrackingPdfGenerator;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewWasteTrackingForm extends ViewRecord
{
    protected static string $resource = WasteTrackingFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Izvoz PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $record = $this->getRecord();

                    $record->loadMissing([
                        'ontoRecord.organizationLocation',
                        'ontoRecord.wasteType',
                    ]);

                    $pdfContent = app(WasteTrackingPdfGenerator::class)->generate($record);

                    $doc = $record->document_number ?: $record->id;
                    $doc = str_replace(['+', ' '], '', $doc);

                    $fileName = 'PLO-' . $doc . '.pdf';

                    return response()->streamDownload(
                        fn () => print($pdfContent),
                        $fileName
                    );
                }),

            Action::make('edit')
                ->label('Uredi')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => static::getResource()::getUrl('edit', ['record' => $this->getRecord()]))
                ->visible(fn () => ! $this->getRecord()->isLocked()),
        ];
    }
}
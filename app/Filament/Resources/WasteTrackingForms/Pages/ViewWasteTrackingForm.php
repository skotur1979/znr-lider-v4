<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Services\WasteTrackingPdfGenerator;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewWasteTrackingForm extends ViewRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        WasteTrackingFormResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'view'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Izvoz PDF')
                ->icon(
                    'heroicon-o-document-arrow-down'
                )
                ->color('danger')
                ->action(function () {
                    if (
                        ! WasteTrackingFormResource::allowsModulePermission(
                            'view'
                        )
                    ) {
                        return null;
                    }

                    $record = $this->getRecord();

                    $record->loadMissing([
                        'ontoRecord.organizationLocation',
                        'ontoRecord.wasteType',
                    ]);

                    $filePath = app(
                        WasteTrackingPdfGenerator::class
                    )->generate($record);

                    $doc = trim(
                        (string) (
                            $record->document_number
                            ?: $record->id
                        )
                    );

                    $doc = str_replace(
                        [
                            '*',
                            '+',
                            ' ',
                            '/',
                            '\\',
                        ],
                        '-',
                        $doc
                    );

                    $fileName =
                        'PLO-' . $doc . '.pdf';

                    return response()
                        ->download(
                            $filePath,
                            $fileName,
                            [
                                'Content-Type' =>
                                    'application/pdf',
                            ]
                        )
                        ->deleteFileAfterSend(true);
                }),

            Action::make('editWasteTrackingForm')
                ->label('Uredi')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(
                    fn (): bool =>
                        WasteTrackingFormResource::canEdit(
                            $this->getRecord()
                        )
                )
                ->action(function () {
                    if (
                        ! WasteTrackingFormResource::allowsModulePermission(
                            'update'
                        )
                    ) {
                        return;
                    }

                    return redirect(
                        WasteTrackingFormResource::getUrl(
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
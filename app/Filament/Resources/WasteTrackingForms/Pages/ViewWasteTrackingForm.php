<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Services\ActivityLogger;
use App\Services\OntoService;
use App\Services\WasteTrackingPdfGenerator;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use RuntimeException;

class ViewWasteTrackingForm extends ViewRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        WasteTrackingFormResource::class;

    public function mount(
        int|string $record
    ): void {
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
                        ! WasteTrackingFormResource::
                            allowsModulePermission(
                                'view'
                            )
                    ) {
                        return null;
                    }

                    $record =
                        $this->getRecord();

                    $record->loadMissing([
                        'ontoRecord.organizationLocation',
                        'ontoRecord.wasteType',
                    ]);

                    $filePath =
                        app(
                            WasteTrackingPdfGenerator::class
                        )->generate(
                            $record
                        );

                    $doc =
                        trim(
                            (string) (
                                $record
                                    ->document_number
                                ?: $record->id
                            )
                        );

                    $doc =
                        str_replace(
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
                        'PLO-'
                        . $doc
                        . '.pdf';

                    return response()
                        ->download(
                            $filePath,
                            $fileName,
                            [
                                'Content-Type' =>
                                    'application/pdf',
                            ]
                        )
                        ->deleteFileAfterSend(
                            true
                        );
                }),

            /*
             * OTKLJUČAJ
             */
            Action::make(
                'unlockWasteTrackingForm'
            )
                ->label('Otključaj')
                ->icon(
                    'heroicon-o-lock-open'
                )
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(
                    'Otključaj prateći list'
                )
                ->modalDescription(
                    'Prateći list će ponovno biti moguće uređivati. '
                    . 'ONTO izlaz ostaje evidentiran i neće se ponovno '
                    . 'skidati sa stanja kod sljedećeg zaključavanja.'
                )
                ->visible(
                    function (): bool {
                        $user =
                            auth()->user();

                        if (
                            ! $user
                            || $user
                                ->isSuperAdmin()
                        ) {
                            return false;
                        }

                        $record =
                            $this
                                ->getRecord();

                        return
                            $record
                                ->isLocked()
                            && ! $record
                                ->trashed();
                    }
                )
                ->action(
                    function (): void {
                        if (
                            ! WasteTrackingFormResource::
                                allowsModulePermission(
                                    'update'
                                )
                        ) {
                            return;
                        }

                        $record =
                            $this
                                ->getRecord();

                        try {
                            app(
                                OntoService::class
                            )->unlockTrackingForm(
                                $record
                            );

                            ActivityLogger::status(
                                module:
                                    'Prateći listovi otpada',

                                title:
                                    'Prateći list otključan',

                                description:
                                    'Otključan je prateći list radi ispravka: '
                                    . (
                                        $record
                                            ->document_number
                                        ?: $record
                                            ->display_name
                                    ),

                                record:
                                    $record,
                            );

                            Notification::make()
                                ->title(
                                    'Prateći list je otključan.'
                                )
                                ->body(
                                    'Sada ga možete urediti.'
                                )
                                ->warning()
                                ->send();

                            $this->redirect(
                                WasteTrackingFormResource::
                                    getUrl(
                                        'edit',
                                        [
                                            'record' =>
                                                $record,
                                        ]
                                    ),
                                navigate: true
                            );
                        } catch (
                            RuntimeException $e
                        ) {
                            Notification::make()
                                ->title(
                                    $e
                                        ->getMessage()
                                )
                                ->danger()
                                ->send();
                        }
                    }
                ),

            /*
             * UREDI
             */
            Action::make(
                'editWasteTrackingForm'
            )
                ->label('Uredi')
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->color('warning')
                ->visible(
                    fn (): bool =>
                        WasteTrackingFormResource::
                            canEdit(
                                $this
                                    ->getRecord()
                            )
                )
                ->action(function () {
                    if (
                        ! WasteTrackingFormResource::
                            allowsModulePermission(
                                'update'
                            )
                    ) {
                        return;
                    }

                    return redirect(
                        WasteTrackingFormResource::
                            getUrl(
                                'edit',
                                [
                                    'record' =>
                                        $this
                                            ->getRecord(),
                                ]
                            )
                    );
                }),
        ];
    }
}
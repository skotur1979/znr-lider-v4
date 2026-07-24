<?php

namespace App\Filament\Resources\OntoRecords\Pages;

use App\Filament\Resources\OntoRecords\OntoRecordResource;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Models\WasteTrackingForm;
use App\Services\OntoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use RuntimeException;


class ViewOntoRecord extends ViewRecord
{
    protected static string $resource = OntoRecordResource::class;

    protected string $view = 'filament.resources.onto-records.pages.view-onto-record';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Uredi'),

            Action::make('add_input')
                ->label('Unesi ulaz')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn () => ! $this->record->is_closed)
                ->form([
                    DatePicker::make('date')
                        ->label('Datum')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->format('Y-m-d')
                        ->native(false)
                        ->default(now()),

                    TextInput::make('quantity_kg')
                        ->label('Količina (kg)')
                        ->required()
                        ->numeric()
                        ->minValue(0.01),

                    TextInput::make('method')
                        ->label('Način')
                        ->default('UVL')
                        ->maxLength(100)
                        ->helperText('Primjer: UVL - Ulaz otpada, K - Korekcija stanja'),

                    Textarea::make('note')
                        ->label('Napomena')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        app(OntoService::class)->addInput(
                        $this->record,
                        $data['entry_date'] ?? $data['date'] ?? now()->format('Y-m-d'),
                        (float) $data['quantity_kg'],
                        $data['method'] ?? 'UVL',
                        $data['note'] ?? null,
                    );

                        Notification::make()
                            ->title('Ulaz otpada je uspješno evidentiran.')
                            ->success()
                            ->send();

                        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (RuntimeException $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('add_output')
                ->label('Unesi izlaz')
                ->icon('heroicon-o-minus-circle')
                ->color('warning')
                ->visible(fn () => ! $this->record->is_closed)
                ->form([
            DatePicker::make('entry_date')
                ->label('Datum')
                ->required()
                ->displayFormat('d.m.Y.')
                ->format('Y-m-d')
                ->native(false)
                ->default(now()),

                    TextInput::make('quantity_kg')
                        ->label('Količina (kg)')
                        ->required()
                        ->numeric()
                        ->minValue(0.01),

                    TextInput::make('method')
                        ->label('Način')
                        ->default('IP')
                        ->maxLength(100)
                        ->helperText('Primjer: IP-PL-001/2026, IVP, K'),

                    Textarea::make('note')
                        ->label('Napomena')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        app(OntoService::class)->addOutput(
                            $this->record,
                            $data['entry_date'],
                            (float) $data['quantity_kg'],
                            $data['method'] ?? 'IP',
                            $data['note'] ?? null,
                        );

                        Notification::make()
                            ->title('Izlaz otpada je uspješno evidentiran.')
                            ->success()
                            ->send();

                        $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                    } catch (RuntimeException $e) {
                        Notification::make()
                            ->title($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('export_pdf')
                ->label('Izvoz u PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    $record = $this->record->load([
                        'wasteType',
                        'entries',
                        'organization',
                        'organizationLocation',
                    ]);

                    $pdf = Pdf::loadView('pdf.onto-record', [
            'record' => $record,
        ])->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'onto-obrazac-' . $record->id . '.pdf'
                    );
                }),

            Action::make('create_tracking_form')
    ->label('Novi prateći list')
    ->icon('heroicon-o-document-text')
    ->color('info')
    ->visible(fn () => ! $this->record->is_closed)

    ->fillForm(function (): array {
        $record = $this->record->loadMissing([
            'organization',
            'organizationLocation',
            'wasteType',
        ]);

        return [
            'document_number' =>
                WasteTrackingFormResource::generateDocumentNumberFromOnto($record),

            'handover_date' => now(),
            'quantity_kg' => null,
            'description' => $record->wasteType?->name ?? '',
            'note' => null,
        ];
    })

    ->form([
        TextInput::make('document_number')
            ->label('Broj PL-O')
            ->required()
            ->maxLength(255)
            ->helperText('Broj je automatski predložen, ali ga možete ručno promijeniti.'),

        DatePicker::make('handover_date')
            ->label('Datum predaje')
            ->native(false)
            ->displayFormat('d.m.Y.')
            ->required(),

        TextInput::make('quantity_kg')
            ->label('Količina (kg)')
            ->required()
            ->numeric()
            ->minValue(0.01),

        Textarea::make('description')
            ->label('Opis otpada')
            ->rows(2)
            ->required(),

        Textarea::make('note')
            ->label('Napomena')
            ->rows(3),
    ])

    ->action(function (array $data): void {
        $record = $this->record->loadMissing([
            'organization',
            'organizationLocation',
            'wasteType',
        ]);

        $rawWasteCode = (string) ($record->wasteType?->waste_code ?? '');

        $wasteCodeDigits = preg_replace(
            '/\D/',
            '',
            str_replace('*', '', $rawWasteCode)
        );

        $displayWasteCode = '';

        if ($wasteCodeDigits !== '') {
            $displayWasteCode = trim(
                chunk_split($wasteCodeDigits, 2, ' ')
            );

            if (str_contains($rawWasteCode, '*')) {
                $displayWasteCode .= '*';
            }
        }

        $description = filled($data['description'] ?? null)
            ? trim((string) $data['description'])
            : (string) ($record->wasteType?->name ?? '');

        $trackingForm = WasteTrackingForm::create([
            'user_id' => Auth::user()?->ownerId() ?? Auth::id(),
            'onto_record_id' => $record->id,

            'document_number' => filled($data['document_number'] ?? null)
                ? trim((string) $data['document_number'])
                : WasteTrackingFormResource::generateDocumentNumberFromOnto($record),

            'handover_date' => $data['handover_date'] ?? now(),
            'quantity_kg' => $data['quantity_kg'],

            'waste_code_manual' => $displayWasteCode,
            'waste_description' => $description,
            'description' => $description,

            'waste_kind' => str_contains($rawWasteCode, '*')
                ? 'opasni'
                : 'neopasni',

            'sender_name' => $record->organization?->company_name
                ?? $record->organization?->name,

            'sender_person_name' => $record->organization?->company_name
                ?? $record->organization?->name,

            'sender_oib' => $record->organization?->oib,
            'sender_nkd_code' => $record->organization?->nkd_code,
            'sender_contact_person' => $record->organization?->contact_person,
            'sender_contact_data' => $record->organization?->contact_data,

            'sender_address' => $record->organizationLocation?->address
                ?? $record->organization?->address,

            'dispatch_point' => $record->organizationLocation?->address
                ?? $record->organization?->address,

            'note' => $data['note'] ?? null,
            'status' => 'draft',
        ]);

        Notification::make()
            ->title('Prateći list je kreiran.')
            ->body('Broj PL-O, ključni broj i opis otpada automatski su popunjeni.')
            ->success()
            ->send();

        $this->redirect(
            WasteTrackingFormResource::getUrl('edit', [
                'record' => $trackingForm,
            ])
        );
    }),
        ];
    }

      protected function getViewData(): array
    {
        $record = $this->getRecord()->load([
            'organization',
            'organizationLocation',
            'wasteType',
            'entries' => fn ($query) => $query->orderBy('entry_no'),
        ]);

        return [
            'record' => $record,
            'entries' => $record->entries,
        ];
    }
}
<?php

namespace App\Filament\Resources\OntoRecords\Pages;

use App\Filament\Resources\OntoRecords\OntoRecordResource;
use App\Models\WasteTrackingForm;
use App\Services\OntoService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
            EditAction::make(),

            Action::make('add_input')
                ->label('Unesi ulaz')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->visible(fn () => ! $this->record->is_closed)
                ->form([
                    DatePicker::make('entry_date')
                        ->label('Datum')
                        ->native(false)
                        ->required()
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
                        ->helperText('Primjer: UVL, UP, K'),

                    Textarea::make('note')
                        ->label('Napomena')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        app(OntoService::class)->addInput(
                            $this->record,
                            $data['entry_date'],
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
                        ->native(false)
                        ->required()
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

            Action::make('create_tracking_form')
                ->label('Novi prateći list')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn () => ! $this->record->is_closed)
                ->form([
                    TextInput::make('document_number')
                        ->label('Broj PL-O')
                        ->maxLength(255),

                    DatePicker::make('handover_date')
                        ->label('Datum predaje')
                        ->native(false)
                        ->default(now()),

                    TextInput::make('quantity_kg')
                        ->label('Količina (kg)')
                        ->required()
                        ->numeric()
                        ->minValue(0.01),

                    Textarea::make('description')
                        ->label('Opis')
                        ->rows(2)
                        ->default($this->record->wasteType?->name),

                    Textarea::make('note')
                        ->label('Napomena')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    WasteTrackingForm::create([
                        'user_id' => Auth::id(),
                        'onto_record_id' => $this->record->id,
                        'document_number' => $data['document_number'] ?? null,
                        'handover_date' => $data['handover_date'] ?? now()->format('Y-m-d'),
                        'quantity_kg' => $data['quantity_kg'],
                        'description' => $data['description'] ?? $this->record->wasteType?->name,
                        'sender_name' => $this->record->organization?->company_name,
                        'sender_oib' => $this->record->organization?->oib,
                        'sender_address' => $this->record->location?->address,
                        'note' => $data['note'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Prateći list je kreiran.')
                        ->body('Otvoren je kao nacrt u modulu Prateći listovi.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    protected function getViewData(): array
    {
        $record = $this->getRecord()->load([
            'organization',
            'location',
            'wasteType',
            'entries' => fn ($query) => $query->orderBy('entry_no'),
        ]);

        return [
            'record' => $record,
            'entries' => $record->entries,
        ];
    }
}
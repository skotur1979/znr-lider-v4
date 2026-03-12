<?php

namespace App\Filament\Resources\WasteTrackingForms;

use App\Filament\Resources\WasteTrackingForms\Pages\CreateWasteTrackingForm;
use App\Filament\Resources\WasteTrackingForms\Pages\EditWasteTrackingForm;
use App\Filament\Resources\WasteTrackingForms\Pages\ListWasteTrackingForms;
use App\Filament\Resources\WasteTrackingForms\Pages\ViewWasteTrackingForm;
use App\Models\OntoRecord;
use App\Models\WasteTrackingForm;
use App\Services\OntoService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Radio;

class WasteTrackingFormResource extends Resource
{
    protected static ?string $model = WasteTrackingForm::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Prateći listovi';
    protected static ?string $modelLabel = 'Prateći list';
    protected static ?string $pluralModelLabel = 'Prateći listovi';
    protected static string | \UnitEnum | null $navigationGroup = 'Zaštita okoliša';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
{
    return $schema->components([
        Hidden::make('user_id')
            ->default(fn () => Auth::id()),

        FormSection::make('POŠILJKA OTPADA (A)')
            ->schema([
                Select::make('onto_record_id')
                    ->label('ONTO obrazac')
                    ->options(function () {
                        $query = OntoRecord::query()
                            ->with(['organizationLocation', 'wasteType']);

                        if (! Auth::user()?->isAdmin()) {
                            $query->where('user_id', Auth::id());
                        }

                        return $query->get()->mapWithKeys(fn (OntoRecord $record) => [
                            $record->id => $record->display_name,
                        ])->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $record = OntoRecord::with('wasteType')->find($state);

                        if ($record?->wasteType) {
                            $set('waste_code_manual', $record->wasteType->waste_code);
                            $set('description', $record->wasteType->name);
                            $set('waste_description', $record->wasteType->name);
                            $set(
                                'waste_kind',
                                str_ends_with((string) $record->wasteType->waste_code, '*') ? 'opasni' : 'neopasni'
                            );
                        }
                    }),

                TextInput::make('document_number')
                    ->label('Broj PL-O')
                    ->maxLength(255),

                TextInput::make('waste_code_manual')
                    ->label('Ključni broj')
                    ->maxLength(50),

                Radio::make('waste_kind')
                    ->label('Vrsta otpada')
                    ->options([
                        'opasni' => 'Opasni',
                        'neopasni' => 'Neopasni',
                    ])
                    ->inline()
                    ->inlineLabel(false),

                CheckboxList::make('waste_source_types')
                    ->label('Izvor otpada')
                    ->options([
                        'komunalni' => 'Komunalni',
                        'proizvodni' => 'Proizvodni',
                    ])
                    ->columns(2),

                CheckboxList::make('hazard_properties')
                    ->label('Opasna svojstva')
                    ->options([
                        'HP1' => 'HP1',
                        'HP2' => 'HP2',
                        'HP3' => 'HP3',
                        'HP4' => 'HP4',
                        'HP5' => 'HP5',
                        'HP6' => 'HP6',
                        'HP7' => 'HP7',
                        'HP8' => 'HP8',
                        'HP9' => 'HP9',
                        'HP10' => 'HP10',
                        'HP11' => 'HP11',
                        'HP12' => 'HP12',
                        'HP13' => 'HP13',
                        'HP14' => 'HP14',
                        'HP15' => 'HP15',
                    ])
                    ->columns(5)
                    ->columnSpanFull(),

                CheckboxList::make('physical_properties')
                    ->label('Fizikalna svojstva')
                    ->options([
                        'kruto' => 'Kruto',
                        'muljevito' => 'Muljevito',
                        'prasina' => 'Prašina',
                        'tekucina' => 'Tekućina',
                        'plinovito' => 'Plinovito',
                        'ostalo' => 'Ostalo',
                    ])
                    ->columns(6)
                    ->columnSpanFull()
                    ->live(),

                TextInput::make('physical_properties_other')
                    ->label('Fizikalna svojstva - ostalo')
                    ->visible(fn (callable $get) => in_array('ostalo', $get('physical_properties') ?? [])),

                CheckboxList::make('packaging_types')
                    ->label('Pakiranje otpada')
                    ->options([
                        'rasuto' => 'Rasuto',
                        'posude' => 'Posude',
                        'kanta' => 'Kanta',
                        'kutija' => 'Kutija',
                        'kanister' => 'Kanister',
                        'kontejner' => 'Kontejner',
                        'bacva' => 'Bačva',
                        'vreca' => 'Vreća',
                        'ostalo' => 'Ostalo',
                    ])
                    ->columns(5)
                    ->columnSpanFull()
                    ->live(),

                TextInput::make('packaging_other')
                    ->label('Pakiranje otpada - ostalo')
                    ->visible(fn (callable $get) => in_array('ostalo', $get('packaging_types') ?? [])),

                TextInput::make('package_count')
                    ->label('Broj pakiranja'),

                Textarea::make('waste_description')
                    ->label('Opis otpada')
                    ->rows(2)
                    ->columnSpanFull(),

                Textarea::make('municipal_origin_note')
                    ->label('Porijeklo komunalnog otpada')
                    ->rows(2)
                    ->helperText('Ispunjava samo davatelj javne usluge')
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->columnSpanFull(),

        FormSection::make('POŠILJATELJ (B)')
            ->schema([
                TextInput::make('sender_person_name')
                    ->label('Naziv osobe'),

                TextInput::make('sender_oib')
                    ->label('OIB / P.'),

                TextInput::make('sender_nkd_code')
                    ->label('NKD razred (2007)'),

                TextInput::make('sender_contact_person')
                    ->label('Kontakt osoba'),

                TextInput::make('sender_contact_data')
                    ->label('Kontakt podaci'),
            ])
            ->columns(1)
            ->columnSpan(1),

        FormSection::make('TOK OTPADA (F)')
            ->schema([
                TextInput::make('waste_owner_at_handover')
                    ->label('Vlasnik otpada pri predaji'),

                Radio::make('report_choice')
                    ->label('Izvješće')
                    ->options([
                        'da' => 'Da',
                        'ne' => 'Ne',
                    ])
                    ->inline()
                    ->inlineLabel(false),

                Radio::make('purpose_choice')
                    ->label('Namjena')
                    ->options([
                        'oporaba' => 'Oporaba',
                        'zbrinjavanje' => 'Zbrinjavanje',
                    ])
                    ->inline()
                    ->inlineLabel(false),

                TextInput::make('dispatch_point')
                    ->label('Polazište'),

                TextInput::make('destination_point')
                    ->label('Odredište'),

                TextInput::make('quantity_m3')
                    ->label('Količina (m³)')
                    ->numeric(),

                TextInput::make('quantity_kg')
                    ->label('Količina (kg)')
                    ->required()
                    ->numeric(),

                Radio::make('quantity_determination_choice')
                    ->label('Količina određena')
                    ->options([
                        'vaganje' => 'Vaganje',
                        'procjena' => 'Procjena',
                    ])
                    ->inline()
                    ->inlineLabel(false),

                DateTimePicker::make('handover_datetime')
                    ->label('Vrijeme predaje')
                    ->native(false),

                TextInput::make('handed_over_by')
                    ->label('Predao'),
            ])
            ->columns(2)
            ->columnSpan(1),

        FormSection::make('PRIJEVOZNIK (C)')
            ->schema([
                Grid::make(2)
                    ->schema([
                        FormSection::make('')
                            ->schema([
                                TextInput::make('carrier_name')
                                    ->label('TVRTKA'),

                                TextInput::make('carrier_oib')
                                    ->label('OIB'),

                                TextInput::make('carrier_authorization')
                                    ->label('OVLAST ZA PRIJEVOZ'),

                                TextInput::make('carrier_contact_person')
                                    ->label('KONTAKT OSOBA'),

                                TextInput::make('carrier_contact_data')
                                    ->label('KONTAKT PODACI'),
                            ])
                            ->columns(1),

                        FormSection::make('')
                            ->schema([
                                CheckboxList::make('transport_modes')
                                    ->label('NAČIN PRIJEVOZA')
                                    ->options([
                                        'cestovni' => 'cestovni',
                                        'zeljeznicki' => 'željeznički',
                                        'morski' => 'morski',
                                        'zracni' => 'zračni',
                                        'unutarnji_plovni_put' => 'unutarnjim plovnim putem',
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                TextInput::make('carrier_vehicle_registration')
                                    ->label('REGISTARSKA OZNAKA'),

                                TextInput::make('carrier_taken_over_by')
                                    ->label('PREUZEO'),

                                DateTimePicker::make('carrier_taken_over_at')
                                    ->label('VRIJEME PREDAJE')
                                    ->native(false),

                                TextInput::make('carrier_delivered_by')
                                    ->label('PREDAO'),
                            ])
                            ->columns(1),
                    ]),
            ])
            ->columnSpanFull(),

        FormSection::make('PRIMATELJ (D)')
            ->schema([
                Grid::make(2)
                    ->schema([
                        FormSection::make('')
                            ->schema([
                                TextInput::make('receiver_name')
                                    ->label('TVRTKA'),

                                TextInput::make('receiver_oib')
                                    ->label('OIB'),

                                TextInput::make('receiver_authorization')
                                    ->label('OVLAST ZA PREUZIMANJE'),

                                TextInput::make('receiver_contact_person')
                                    ->label('KONTAKT OSOBA'),

                                TextInput::make('receiver_contact_data')
                                    ->label('KONTAKT PODACI'),
                            ])
                            ->columns(1),

                        FormSection::make('')
                            ->schema([
                                TextInput::make('receiver_taken_over_by')
                                    ->label('PREUZEO'),

                                DateTimePicker::make('receiver_weighing_time')
                                    ->label('VRIJEME VAGANJA')
                                    ->native(false),

                                TextInput::make('receiver_measured_quantity_kg')
                                    ->label('PREUZETA KOLIČINA (kg)')
                                    ->numeric(),
                            ])
                            ->columns(1),
                    ]),
            ])
            ->columnSpanFull(),

        FormSection::make('POSREDNIK ILI TRGOVAC (E)')
            ->schema([
                TextInput::make('trader_name')
                    ->label('TVRTKA'),

                TextInput::make('trader_oib')
                    ->label('OIB'),

                TextInput::make('trader_authorization')
                    ->label('OVLAST'),

                TextInput::make('trader_contact_person')
                    ->label('KONTAKT OSOBA'),

                TextInput::make('trader_contact_data')
                    ->label('KONTAKT PODACI'),
            ])
            ->columns(1)
            ->columnSpan(1),

        FormSection::make('OBRAĐIVAČ (G)')
            ->schema([
                TextInput::make('processor_name')
                    ->label('TVRTKA'),

                TextInput::make('processor_oib')
                    ->label('OIB'),

                TextInput::make('processor_authorization')
                    ->label('OVLAST ZA OBRADU'),

                DatePicker::make('processing_completed_at')
                    ->label('OBRADA ZAVRŠENA DANA')
                    ->native(false),

                TextInput::make('final_processing_method')
                    ->label('POSTUPAK OBRADE'),

                TextInput::make('processor_confirmed_by')
                    ->label('POTVRDIO'),
            ])
            ->columns(1)
            ->columnSpan(1),

        FormSection::make('NAPOMENE I PRILOZI (H)')
            ->schema([
                Textarea::make('note')
                    ->label('NAPOMENE')
                    ->rows(6)
                    ->columnSpanFull(),

                FileUpload::make('attachments')
                    ->label('PRILOZI')
                    ->multiple()
                    ->downloadable()
                    ->openable()
                    ->directory('waste-tracking-forms')
                    ->columnSpanFull(),
            ])
            ->columns(1)
            ->columnSpanFull(),
    ])->columns(2);
}

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('handover_date', 'desc')
            ->columns([
                TextColumn::make('document_number')
                    ->label('Broj PL-O')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('handover_date')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable(),

                TextColumn::make('ontoRecord.organizationLocation.name')
                    ->label('Lokacija')
                    ->formatStateUsing(fn ($state, WasteTrackingForm $record) =>
                        $record->ontoRecord?->organizationLocation?->display_name
                            ?? $record->ontoRecord?->organizationLocation?->name
                            ?? $record->ontoRecord?->organizationLocation?->location_name
                            ?? '-'
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ontoRecord.wasteType.waste_code')
                    ->label('K.B.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ontoRecord.wasteType.name')
                    ->label('Naziv otpada')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('quantity_kg')
                    ->label('Količina (kg)')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.')),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state) => $state === 'locked' ? 'Zaključen' : 'Nacrt')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'locked',
                    ]),

                TextColumn::make('locked_at')
                    ->label('Zaključan')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('deleted_at')
                    ->label('Deaktivirano')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Nacrt',
                        'locked' => 'Zaključen',
                    ]),

                SelectFilter::make('onto_record_id')
                    ->label('ONTO obrazac')
                    ->relationship('ontoRecord', 'id')
                    ->searchable()
                    ->preload(),

                Filter::make('year')
                    ->form([
                        TextInput::make('year')
                            ->label('Godina')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['year'] ?? null),
                            fn (Builder $query) => $query->whereHas(
                                'ontoRecord',
                                fn (Builder $q) => $q->where('year', $data['year'])
                            )
                        );
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaz'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (WasteTrackingForm $record) => ! $record->isLocked()),

                    Action::make('lock')
                        ->label('Zaključi')
                        ->icon('heroicon-o-lock-closed')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Zaključi prateći list')
                        ->modalDescription('Zaključavanjem će se automatski evidentirati izlaz u ONTO obrascu i skinuti količina sa stanja.')
                        ->visible(fn (WasteTrackingForm $record) => ! $record->isLocked())
                        ->action(function (WasteTrackingForm $record): void {
                            try {
                                app(OntoService::class)->lockTrackingForm($record);

                                Notification::make()
                                    ->title('Prateći list je zaključen.')
                                    ->success()
                                    ->send();
                            } catch (RuntimeException $e) {
                                Notification::make()
                                    ->title($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->modalHeading('Deaktiviraj prateći list')
                        ->modalDescription('Jesi li siguran/a da želiš deaktivirati ovaj prateći list?'),

                    RestoreAction::make()->label('Vrati'),

                    ForceDeleteAction::make()
                        ->label('Trajno izbriši')
                        ->visible(fn (WasteTrackingForm $record) => ! $record->isLocked())
                        ->modalHeading('Trajno izbriši prateći list')
                        ->modalDescription('Jesi li siguran/a? Ova radnja je nepovratna.'),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->modalHeading('Deaktiviraj odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?'),

                RestoreBulkAction::make()->label('Vrati označeno'),

                ForceDeleteBulkAction::make()
                    ->label('Trajno izbriši označeno')
                    ->modalHeading('Trajno izbriši odabrano')
                    ->modalDescription('Jesi li siguran/a? Ova radnja je nepovratna.'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['ontoRecord.organizationLocation', 'ontoRecord.wasteType'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if (Auth::user()?->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::id());
    }

    public static function canCreate(): bool
    {
        return Auth::check();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWasteTrackingForms::route('/'),
            'create' => CreateWasteTrackingForm::route('/create'),
            'view' => ViewWasteTrackingForm::route('/{record}'),
            'edit' => EditWasteTrackingForm::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $q = static::getModel()::query();

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return (string) $q->count();
    }
}
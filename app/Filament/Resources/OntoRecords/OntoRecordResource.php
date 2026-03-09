<?php

namespace App\Filament\Resources\OntoRecords;

use App\Filament\Resources\OntoRecords\Pages\CreateOntoRecord;
use App\Filament\Resources\OntoRecords\Pages\EditOntoRecord;
use App\Filament\Resources\OntoRecords\Pages\ListOntoRecords;
use App\Filament\Resources\OntoRecords\Pages\ViewOntoRecord;
use App\Models\OntoRecord;
use App\Models\WasteOrganization;
use App\Models\WasteTrackingForm;
use App\Models\WasteType;
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
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class OntoRecordResource extends Resource
{
    protected static ?string $model = OntoRecord::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'ONTO obrasci';
    protected static ?string $modelLabel = 'ONTO obrazac';
    protected static ?string $pluralModelLabel = 'ONTO obrasci';
    protected static string | \UnitEnum | null $navigationGroup = 'Zaštita okoliša';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => Auth::id()),

            FormSection::make('Podaci o ONTO obrascu')
                ->schema([
                    Select::make('waste_organization_id')
                        ->label('Organizacija')
                        ->relationship(
                            name: 'organization',
                            titleAttribute: 'company_name',
                            modifyQueryUsing: fn (Builder $query) => static::applyOrganizationScope($query)
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

                    Select::make('waste_organization_location_id')
                        ->label('Lokacija')
                        ->options(function (callable $get) {
                            $organizationId = $get('waste_organization_id');

                            if (! $organizationId) {
                                return [];
                            }

                            return \App\Models\WasteOrganizationLocation::query()
                                ->where('waste_organization_id', $organizationId)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($location) => [
                                    $location->id => $location->display_name
                                ])
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('waste_type_id')
                        ->label('Vrsta otpada')
                        ->relationship('wasteType', 'name')
                        ->getOptionLabelFromRecordUsing(fn (WasteType $record) => $record->display_name)
                        ->searchable(['waste_code', 'name'])
                        ->preload()
                        ->required(),

                    TextInput::make('year')
                        ->label('Godina')
                        ->required()
                        ->numeric()
                        ->default(now()->year)
                        ->minValue(2020)
                        ->maxValue(2100),

                    TextInput::make('responsible_person')
                        ->label('Odgovorna osoba')
                        ->maxLength(255),

                    DatePicker::make('opening_date')
                        ->label('Datum otvaranja')
                        ->native(false)
                        ->default(now()),

                    DatePicker::make('closing_date')
                        ->label('Datum zatvaranja')
                        ->native(false),

                    TextInput::make('current_balance_kg')
                        ->label('Trenutno stanje (kg)')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false),

                    Toggle::make('is_closed')
                        ->label('Zatvoren')
                        ->default(false)
                        ->inline(false),

                    Textarea::make('notes')
                        ->label('Napomena')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('organization.company_name')
                    ->label('Organizacija')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('location.name')
                    ->label('Lokacija')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('wasteType.waste_code')
                    ->label('K.B.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('wasteType.name')
                    ->label('Naziv otpada')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('year')
                    ->label('Godina')
                    ->sortable(),

                TextColumn::make('current_balance_kg')
                    ->label('Stanje (kg)')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.')),

                TextColumn::make('entries_count')
                    ->label('Stavke')
                    ->counts('entries')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_closed')
                    ->label('Zatvoren')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('opening_date')
                    ->label('Otvoren')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('closing_date')
                    ->label('Zatvoren datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deaktivirano')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('waste_organization_id')
                    ->label('Organizacija')
                    ->relationship(
                        'organization',
                        'company_name',
                        fn (Builder $query) => static::applyOrganizationScope($query)
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('waste_organization_location_id')
                    ->label('Lokacija')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('waste_type_id')
                    ->label('Vrsta otpada')
                    ->relationship('wasteType', 'name')
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
                            fn (Builder $query) => $query->where('year', $data['year'])
                        );
                    }),

                SelectFilter::make('is_closed')
                    ->label('Status')
                    ->options([
                        '0' => 'Otvoreni',
                        '1' => 'Zatvoreni',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query) => $query->where('is_closed', (bool) $data['value'])
                        );
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaz'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (OntoRecord $record) => ! $record->is_closed),

                    Action::make('add_input')
                        ->label('Unesi ulaz')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->visible(fn (OntoRecord $record) => ! $record->is_closed)
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
                                ->maxLength(100),

                            Textarea::make('note')
                                ->label('Napomena')
                                ->rows(3),
                        ])
                        ->action(function (OntoRecord $record, array $data): void {
                            try {
                                app(OntoService::class)->addInput(
                                    $record,
                                    $data['entry_date'],
                                    (float) $data['quantity_kg'],
                                    $data['method'] ?? 'UVL',
                                    $data['note'] ?? null,
                                );

                                Notification::make()
                                    ->title('Ulaz otpada je uspješno evidentiran.')
                                    ->success()
                                    ->send();
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
                        ->visible(fn (OntoRecord $record) => ! $record->is_closed)
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
                                ->maxLength(100),

                            Textarea::make('note')
                                ->label('Napomena')
                                ->rows(3),
                        ])
                        ->action(function (OntoRecord $record, array $data): void {
                            try {
                                app(OntoService::class)->addOutput(
                                    $record,
                                    $data['entry_date'],
                                    (float) $data['quantity_kg'],
                                    $data['method'] ?? 'IP',
                                    $data['note'] ?? null,
                                );

                                Notification::make()
                                    ->title('Izlaz otpada je uspješno evidentiran.')
                                    ->success()
                                    ->send();
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
                        ->visible(fn (OntoRecord $record) => ! $record->is_closed)
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
                                ->default(fn (OntoRecord $record) => $record->wasteType?->name),

                            Textarea::make('note')
                                ->label('Napomena')
                                ->rows(3),
                        ])
                        ->action(function (OntoRecord $record, array $data): void {
                            $trackingForm = WasteTrackingForm::create([
                                'user_id' => Auth::id(),
                                'onto_record_id' => $record->id,
                                'document_number' => $data['document_number'] ?? null,
                                'handover_date' => $data['handover_date'] ?? now()->format('Y-m-d'),
                                'quantity_kg' => $data['quantity_kg'],
                                'description' => $data['description'] ?? $record->wasteType?->name,
                                'sender_name' => $record->organization?->company_name,
                                'sender_oib' => $record->organization?->oib,
                                'sender_address' => $record->location?->address,
                                'note' => $data['note'] ?? null,
                            ]);

                            Notification::make()
                                ->title('Prateći list je kreiran.')
                                ->body('Otvoren je kao nacrt i možeš ga dalje urediti u modulu Prateći listovi.')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->visible(fn (OntoRecord $record) => $record->entries()->count() === 0)
                        ->modalHeading('Deaktiviraj ONTO obrazac')
                        ->modalDescription('Jesi li siguran/a da želiš deaktivirati ovaj ONTO obrazac?'),

                    RestoreAction::make()->label('Vrati'),

                    ForceDeleteAction::make()
                        ->label('Trajno izbriši')
                        ->visible(fn (OntoRecord $record) => $record->entries()->count() === 0)
                        ->modalHeading('Trajno izbriši ONTO obrazac')
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
            ->with(['organization', 'location', 'wasteType'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if (Auth::user()?->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::id());
    }

    protected static function applyOrganizationScope(Builder $query): Builder
    {
        if (Auth::user()?->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::id());
    }

    public static function canCreate(): bool
    {
        return Auth::check();
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isAdmin()) {
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOntoRecords::route('/'),
            'create' => CreateOntoRecord::route('/create'),
            'view' => ViewOntoRecord::route('/{record}'),
            'edit' => EditOntoRecord::route('/{record}/edit'),
        ];
    }
}
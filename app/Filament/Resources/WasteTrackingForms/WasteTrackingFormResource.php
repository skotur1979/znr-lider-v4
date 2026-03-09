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
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Forms\Components\Select;
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

            FormSection::make('Osnovni podaci')
                ->schema([
                    Select::make('onto_record_id')
                        ->label('ONTO obrazac')
                        ->options(function () {
                            $query = OntoRecord::query()
                                ->with(['location', 'wasteType']);

                            if (! Auth::user()?->isAdmin()) {
                                $query->where('user_id', Auth::id());
                            }

                            return $query->get()->mapWithKeys(fn (OntoRecord $record) => [
                                $record->id => $record->display_name,
                            ])->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required(),

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
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('note')
                        ->label('Napomena')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            FormSection::make('Pošiljatelj')
                ->schema([
                    TextInput::make('sender_name')
                        ->label('Naziv')
                        ->maxLength(255),

                    TextInput::make('sender_oib')
                        ->label('OIB')
                        ->maxLength(20),

                    TextInput::make('sender_address')
                        ->label('Adresa')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),

            FormSection::make('Prijevoznik')
                ->schema([
                    TextInput::make('carrier_name')
                        ->label('Naziv')
                        ->maxLength(255),

                    TextInput::make('carrier_oib')
                        ->label('OIB')
                        ->maxLength(20),

                    TextInput::make('carrier_authorization')
                        ->label('Ovlast za prijevoz')
                        ->maxLength(255),

                    TextInput::make('carrier_vehicle_registration')
                        ->label('Registarska oznaka')
                        ->maxLength(100),
                ])
                ->columns(2)
                ->collapsible(),

            FormSection::make('Primatelj')
                ->schema([
                    TextInput::make('receiver_name')
                        ->label('Naziv')
                        ->maxLength(255),

                    TextInput::make('receiver_oib')
                        ->label('OIB')
                        ->maxLength(20),

                    TextInput::make('receiver_authorization')
                        ->label('Ovlast za preuzimanje')
                        ->maxLength(255),

                    TextInput::make('receiver_address')
                        ->label('Adresa')
                        ->maxLength(255),
                ])
                ->columns(2)
                ->collapsible(),

            FormSection::make('Obrada')
                ->schema([
                    TextInput::make('processing_method')
                        ->label('Postupak obrade')
                        ->maxLength(255),

                    TextInput::make('status')
                        ->label('Status')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => $state === 'locked' ? 'Zaključen' : 'Nacrt'),

                    TextInput::make('locked_at')
                        ->label('Zaključan')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => $state ? \Illuminate\Support\Carbon::parse($state)->format('d.m.Y. H:i') : '-'),
                ])
                ->columns(3)
                ->collapsible(),
        ]);
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

                TextColumn::make('ontoRecord.location.name')
                    ->label('Lokacija')
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
            ->with(['ontoRecord.location', 'ontoRecord.wasteType'])
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
}
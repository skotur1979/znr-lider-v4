<?php

namespace App\Filament\Resources\WasteOrganizations;

use App\Filament\Resources\WasteOrganizations\Pages\CreateWasteOrganization;
use App\Filament\Resources\WasteOrganizations\Pages\EditWasteOrganization;
use App\Filament\Resources\WasteOrganizations\Pages\ListWasteOrganizations;
use App\Filament\Resources\WasteOrganizations\Pages\ViewWasteOrganization;
use App\Models\WasteOrganization;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WasteOrganizationResource extends Resource
{
    protected static ?string $model = WasteOrganization::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Organizacije otpada';
    protected static ?string $modelLabel = 'Organizacija otpada';
    protected static ?string $pluralModelLabel = 'Organizacije otpada';
    protected static string | \UnitEnum | null $navigationGroup = 'Zaštita okoliša';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => Auth::id()),

            FormSection::make('Podaci o organizaciji')
                ->schema([
                    TextInput::make('company_name')
                        ->label('Tvrtka')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('oib')
                        ->label('OIB')
                        ->maxLength(20)
                        ->numeric()
                        ->minLength(11)
                        ->maxLength(11),

                    TextInput::make('nkd_code')
                        ->label('NKD razred')
                        ->maxLength(50),

                    TextInput::make('contact_person')
                        ->label('Kontakt osoba')
                        ->maxLength(255),

                    TextInput::make('contact_details')
                        ->label('Kontakt podaci')
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('registered_office')
                        ->label('Sjedište')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('Aktivna')
                        ->default(true)
                        ->inline(false),
                ])
                ->columns(3),

            FormSection::make('Lokacije / organizacijske jedinice')
                ->description('Jedna organizacija može imati više lokacija. Za svaku lokaciju kasnije će se voditi zaseban ONTO.')
                ->schema([
                    Repeater::make('locations')
                        ->label('Lokacije')
                        ->relationship()
                        ->defaultItems(0)
                        ->addActionLabel('Dodaj lokaciju')
                        ->reorderable(true)
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(function (array $state): ?string {
                            $name = $state['name'] ?? null;
                            $internal = $state['internal_code'] ?? null;

                            if ($name && $internal) {
                                return "{$name} ({$internal})";
                            }

                            return $name ?: 'Nova lokacija';
                        })
                        ->schema([
                            TextInput::make('name')
                                ->label('Naziv lokacije')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),

                            TextInput::make('unit_code')
                                ->label('Oznaka organizacijske jedinice')
                                ->helperText('Ako nije određena, kasnije možeš koristiti 000.')
                                ->maxLength(20),

                            TextInput::make('internal_code')
                                ->label('Interni broj')
                                ->placeholder('npr. 001')
                                ->maxLength(20),

                            TextInput::make('address')
                                ->label('Adresa / polazište')
                                ->maxLength(255)
                                ->columnSpan(2),

                            Toggle::make('is_active')
                                ->label('Aktivna lokacija')
                                ->default(true)
                                ->inline(false),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('company_name')
            ->columns([
                TextColumn::make('company_name')
                    ->label('Tvrtka')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('oib')
                    ->label('OIB')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('nkd_code')
                    ->label('NKD')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('contact_person')
                    ->label('Kontakt osoba')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('registered_office')
                    ->label('Sjedište')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('locations_count')
                    ->label('Broj lokacija')
                    ->counts('locations')
                    ->badge()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktivna')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kreirano')
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
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktivne',
                        '0' => 'Deaktivirane',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query) => $query->where('is_active', (bool) $data['value'])
                        );
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaz'),

                    EditAction::make()
                        ->label('Uredi'),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->modalHeading('Deaktiviraj organizaciju')
                        ->modalDescription('Jesi li siguran/a da želiš deaktivirati ovu organizaciju?')
                        ->successNotificationTitle('Organizacija je deaktivirana.'),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->successNotificationTitle('Organizacija je vraćena.'),

                    ForceDeleteAction::make()
                        ->label('Trajno izbriši')
                        ->modalHeading('Trajno izbriši organizaciju')
                        ->modalDescription('Jesi li siguran/a? Ova radnja je nepovratna.')
                        ->successNotificationTitle('Organizacija je trajno izbrisana.'),
                ]),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->modalHeading('Deaktiviraj odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->successNotificationTitle('Odabrane organizacije su deaktivirane.'),

                RestoreBulkAction::make()
                    ->label('Vrati označeno'),

                ForceDeleteBulkAction::make()
                    ->label('Trajno izbriši označeno')
                    ->modalHeading('Trajno izbriši odabrano')
                    ->modalDescription('Jesi li siguran/a? Ova radnja je nepovratna.'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
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
            'index' => ListWasteOrganizations::route('/'),
            'create' => CreateWasteOrganization::route('/create'),
            'view' => ViewWasteOrganization::route('/{record}'),
            'edit' => EditWasteOrganization::route('/{record}/edit'),
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
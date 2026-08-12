<?php

namespace App\Filament\Resources\WasteOrganizations;

use App\Filament\Resources\BaseResource;
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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WasteOrganizationResource extends BaseResource
{
    protected static ?string $model = WasteOrganization::class;

    protected static bool $usesSoftDeletes = true;

    protected static bool $hasOwnership = true;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-building-office-2';

    protected static ?string $navigationLabel =
        'Organizacije otpada';

    protected static ?string $modelLabel =
        'Organizacija otpada';

    protected static ?string $pluralModelLabel =
        'Organizacije otpada';

    protected static string|\UnitEnum|null $navigationGroup =
        'Zaštita okoliša';

    protected static ?int $navigationSort = 1;

    protected static function getModuleKey(): ?string
    {
        return 'waste_organizations';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                /*
                 * Ownership se ne bira ručno.
                 *
                 * Glavni korisnik i podkorisnici koriste
                 * isti ownerId() organizacije.
                 */
                Hidden::make('user_id')
                    ->default(
                        fn () => static::ownerId()
                    )
                    ->dehydrated(),

                FormSection::make('Podaci o organizaciji')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Tvrtka')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('oib')
                            ->label('OIB')
                            ->minLength(11)
                            ->maxLength(11)
                            ->numeric(),

                        TextInput::make('nkd_code')
                            ->label('NKD razred')
                            ->maxLength(50),

                        TextInput::make('contact_person')
                            ->label('Kontakt osoba')
                            ->maxLength(255),

                        TextInput::make('contact_details')
                            ->label('Kontakt podaci')
                            ->maxLength(255),

                        TextInput::make('registered_office')
                            ->label('Sjedište')
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->label('Aktivna')
                            ->default(true)
                            ->inline(false),
                    ]),

                FormSection::make(
                    'Lokacije / organizacijske jedinice'
                )
                    ->columnSpanFull()
                    ->description(
                        'Jedna organizacija može imati više lokacija. '
                        . 'Za svaku lokaciju kasnije će se voditi zaseban ONTO.'
                    )
                    ->schema([
                        Repeater::make('locations')
                            ->label('Lokacije')
                            ->relationship()
                            ->defaultItems(0)
                            ->addActionLabel('Dodaj lokaciju')
                            ->reorderable(true)
                            ->collapsible()
                            ->cloneable()
                            ->grid(2)
                            ->itemLabel(
                                function (array $state): ?string {
                                    $name =
                                        $state['name']
                                        ?? null;

                                    $internal =
                                        $state['internal_code']
                                        ?? null;

                                    if ($name && $internal) {
                                        return "{$name} ({$internal})";
                                    }

                                    return $name
                                        ?: 'Nova lokacija';
                                }
                            )
                            ->schema([
                                TextInput::make('name')
                                    ->label(
                                        'Naziv lokacije'
                                    )
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('unit_code')
                                    ->label(
                                        'Oznaka organizacijske jedinice'
                                    )
                                    ->helperText(
                                        'Ako nije određena, kasnije možeš koristiti 000.'
                                    )
                                    ->maxLength(20),

                                TextInput::make(
                                    'internal_code'
                                )
                                    ->label('Interni broj')
                                    ->placeholder(
                                        'npr. 001'
                                    )
                                    ->maxLength(20),

                                TextInput::make('address')
                                    ->label(
                                        'Adresa / polazište'
                                    )
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label(
                                        'Aktivna lokacija'
                                    )
                                    ->default(true)
                                    ->inline(false)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ])
            ->columns(1);
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
                    ->weight('bold')
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),

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
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktivna')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Kreirano')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make('deleted_at')
                    ->label('Deaktivirano')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])

            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktivne',
                        '0' => 'Deaktivirane',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return $query->when(
                                filled(
                                    $data['value']
                                    ?? null
                                ),
                                fn (Builder $query) =>
                                    $query->where(
                                        'is_active',
                                        (bool) $data['value']
                                    )
                            );
                        }
                    ),

                SelectFilter::make('record_status')
                    ->label('Status zapisa')
                    ->placeholder('Odaberi status')
                    ->options([
                        'active' =>
                            'Aktivni zapisi',

                        'trashed' =>
                            'Deaktivirani zapisi',

                        'all' =>
                            'Svi zapisi',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            $value =
                                $data['value']
                                ?? null;

                            return match ($value) {
                                'trashed' =>
                                    $query->onlyTrashed(),

                                'all' =>
                                    $query->withTrashed(),

                                default =>
                                    $query->withoutTrashed(),
                            };
                        }
                    ),
            ])

            ->recordActions([
                ActionGroup::make([
                    /*
                     * Pregled je dozvoljen i superadminu.
                     */
                    ViewAction::make()
                        ->label('Prikaz'),

                    /*
                     * Poslovne podatke organizacije
                     * ne uređuje superadmin.
                     */
                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            fn (
                                WasteOrganization $record
                            ): bool =>
                                ! $record->trashed()
                                && static::canEdit(
                                    $record
                                )
                        ),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WasteOrganization $record
                            ): bool =>
                                ! $record->trashed()
                                && static::canDelete(
                                    $record
                                )
                        )
                        ->modalHeading(
                            'Deaktiviraj organizaciju'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a da želiš deaktivirati ovu organizaciju?'
                        )
                        ->successNotificationTitle(
                            'Organizacija je deaktivirana.'
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WasteOrganization $record
                            ): bool =>
                                $record->trashed()
                                && static::canRestore(
                                    $record
                                )
                        )
                        ->successNotificationTitle(
                            'Organizacija je vraćena.'
                        ),

                    ForceDeleteAction::make()
                        ->label('Trajno izbriši')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WasteOrganization $record
                            ): bool =>
                                $record->trashed()
                                && static::canForceDelete(
                                    $record
                                )
                        )
                        ->modalHeading(
                            'Trajno izbriši organizaciju'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a? Ova radnja je nepovratna.'
                        )
                        ->successNotificationTitle(
                            'Organizacija je trajno izbrisana.'
                        ),
                ]),
            ])

            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->requiresConfirmation()
                    ->visible(
                    fn (HasTable $livewire): bool =>
                        ! static::isOnlyTrashed(
                            $livewire
                            )
                    )
                    ->modalHeading(
                        'Deaktiviraj odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->successNotificationTitle(
                        'Odabrane organizacije su deaktivirane.'
                    ),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->visible(
                    fn (HasTable $livewire): bool =>
                        static::isOnlyTrashed(
                            $livewire
                            )
                    ),

                ForceDeleteBulkAction::make()
                    ->label(
                        'Trajno izbriši označeno'
                    )
                    ->requiresConfirmation()
                    ->visible(
                    fn (HasTable $livewire): bool =>
                        static::isOnlyTrashed(
                            $livewire
                            )
                    )
                    ->modalHeading(
                        'Trajno izbriši odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a? Ova radnja je nepovratna.'
                    ),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Kreiranje
    |--------------------------------------------------------------------------
    |
    | Organizacijski korisnici smiju kreirati zapis
    | svoje organizacije.
    |
    | Superadmin ne kreira poslovne zapise organizacije.
    |
    */

    public static function canCreate(): bool
    {
        return parent::canCreate();
    }

    /*
    |--------------------------------------------------------------------------
    | Uređivanje
    |--------------------------------------------------------------------------
    |
    /*
    * Superadmin može administrirati postojeći zapis.
    * Organizacijski korisnik samo zapis svoje organizacije.
    */

    public static function canEdit(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = static::user();

        if (! $user) {
            return false;
        }

        return (int) $record->user_id
                === (int) $user->ownerId()
            && parent::canEdit($record);
    }

    /*
    |--------------------------------------------------------------------------
    | Deaktiviranje
    |--------------------------------------------------------------------------
    */

    public static function canDelete(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = static::user();

        if (! $user) {
            return false;
        }

        return (int) $record->user_id
                === (int) $user->ownerId()
            && parent::canDelete($record);
    }

    /*
    |--------------------------------------------------------------------------
    | Vraćanje deaktiviranog zapisa
    |--------------------------------------------------------------------------
    */

    public static function canRestore(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = static::user();

        if (! $user) {
            return false;
        }

        return (int) $record->user_id
                === (int) $user->ownerId()
            && parent::canRestore($record);
    }
    /*
    |--------------------------------------------------------------------------
    | Trajno brisanje
    |--------------------------------------------------------------------------
    */

    public static function canForceDelete(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        $user = static::user();

        if (! $user) {
            return false;
        }

        return (int) $record->user_id
                === (int) $user->ownerId()
            && parent::canForceDelete($record);
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk dozvole
    |--------------------------------------------------------------------------
    |
    |/*
    * Superadmin može administrirati postojeći zapis.
    * Organizacijski korisnik samo zapis svoje organizacije.
    */

    public static function canDeleteAny(): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canDeleteAny();
    }

    public static function canRestoreAny(): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canRestoreAny();
    }

    public static function canForceDeleteAny(): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canForceDeleteAny();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper za prikaz bulk akcija
    |--------------------------------------------------------------------------
    */

    private static function isOnlyTrashed(
        HasTable $livewire
    ): bool {
        $state =
            $livewire->getTableFilterState(
                'record_status'
            );

        $value = data_get(
            $state,
            'value'
        );

        return $value === 'trashed';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                ListWasteOrganizations::route('/'),

            'create' =>
                CreateWasteOrganization::route(
                    '/create'
                ),

            'view' =>
                ViewWasteOrganization::route(
                    '/{record}'
                ),

            'edit' =>
                EditWasteOrganization::route(
                    '/{record}/edit'
                ),
        ];
    }
}
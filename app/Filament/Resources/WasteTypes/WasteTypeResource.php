<?php

namespace App\Filament\Resources\WasteTypes;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\WasteTypes\Pages\CreateWasteType;
use App\Filament\Resources\WasteTypes\Pages\EditWasteType;
use App\Filament\Resources\WasteTypes\Pages\ListWasteTypes;
use App\Filament\Resources\WasteTypes\Pages\ViewWasteType;
use App\Models\WasteCatalogItem;
use App\Models\WasteType;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
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
use Illuminate\Validation\Rule;

class WasteTypeResource extends BaseResource
{
    protected static ?string $model = WasteType::class;

    protected static bool $usesSoftDeletes = true;

    protected static bool $hasOwnership = true;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-trash';

    protected static ?string $navigationLabel =
        'Vrste otpada';

    protected static ?string $modelLabel =
        'Vrsta otpada';

    protected static ?string $pluralModelLabel =
        'Vrste otpada';

    protected static string|\UnitEnum|null $navigationGroup =
        'Zaštita okoliša';

    protected static ?int $navigationSort = 2;

    protected static function getModuleKey(): ?string
    {
        return 'waste_types';
    }

    /*
    |--------------------------------------------------------------------------
    | OWNER
    |--------------------------------------------------------------------------
    */

    public static function resolveOwnerId(): ?int
    {
        $user = static::user();

        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->ownerId();
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    |
    | Vrste otpada su organizacijski zapisi.
    |
    | Superadmin ih može pregledavati radi administracije sustava,
    | ali ih ne kreira u ime organizacije.
    |
    */

    public static function canCreate(): bool
    {
        $user = static::user();

        return $user !== null
            && ! $user->isSuperAdmin()
            && parent::canCreate();
    }

    /*
    |--------------------------------------------------------------------------
    | RECORD-LEVEL WRITE ZAŠTITA
    |--------------------------------------------------------------------------
    |
    | Organizacijski korisnik smije mijenjati samo zapis čiji
    | user_id odgovara ownerId() njegove organizacije.
    |
    | Superadmin može administrirati postojeće zapise,
    | ali ne kreira nove vrste otpada u ime organizacije.
    |
    */

    protected static function canManageRecord(
        Model $record
    ): bool {
        $user = static::user();

        if (! $user) {
            return false;
        }

        /*
        * Superadmin može administrirati
        * svaki postojeći zapis.
        */
        if ($user->isSuperAdmin()) {
            return true;
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            return false;
        }

        /*
        * Organizacijski korisnik smije
        * upravljati samo zapisom svoje organizacije.
        */
        return (int) $record->user_id
            === (int) $ownerId;
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record)
            && static::canManageRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record)
            && static::canManageRecord($record);
    }

    public static function canRestore(Model $record): bool
    {
        return parent::canRestore($record)
            && static::canManageRecord($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        return parent::canForceDelete($record)
            && static::canManageRecord($record);
    }

    /*
    |--------------------------------------------------------------------------
    | FORMA
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                FormSection::make('Podaci o vrsti otpada')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('catalog_select')
                            ->label('Katalog otpada')
                            ->placeholder(
                                'Počni upisivati ključni broj ili naziv...'
                            )
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->getSearchResultsUsing(
                                function (string $search): array {
                                    return WasteCatalogItem::query()
                                        ->where(
                                            'waste_code',
                                            'like',
                                            '%'
                                            . str_replace(
                                                ' ',
                                                '',
                                                $search
                                            )
                                            . '%'
                                        )
                                        ->orWhere(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(
                                            fn ($item): array => [
                                                $item->id =>
                                                    $item->display_name,
                                            ]
                                        )
                                        ->toArray();
                                }
                            )
                            ->getOptionLabelUsing(
                                fn ($value) =>
                                    WasteCatalogItem::find(
                                        $value
                                    )?->display_name
                            )
                            ->afterStateUpdated(
                                function (
                                    $state,
                                    callable $set
                                ): void {
                                    if (! $state) {
                                        return;
                                    }

                                    $item =
                                        WasteCatalogItem::find(
                                            $state
                                        );

                                    if (! $item) {
                                        return;
                                    }

                                    $set(
                                        'waste_code',
                                        $item->waste_code
                                    );

                                    $set(
                                        'name',
                                        $item->name
                                    );

                                    $set(
                                        'is_hazardous',
                                        $item->is_hazardous
                                    );
                                }
                            )
                            ->helperText(
                                'Pretražuje cijeli katalog otpada, '
                                . 'a sprema samo vrste otpada koje '
                                . 'koristi vaša organizacija.'
                            )
                            ->columnSpanFull(),

                        TextInput::make('waste_code')
                            ->label(
                                'Ključni broj otpada'
                            )
                            ->required()
                            ->maxLength(20)
                            ->placeholder(
                                'npr. 15 01 10*'
                            )
                            ->formatStateUsing(
                                function (
                                    ?string $state
                                ): ?string {
                                    if (! $state) {
                                        return null;
                                    }

                                    $raw =
                                        trim($state);

                                    $hasStar =
                                        str_ends_with(
                                            $raw,
                                            '*'
                                        );

                                    $code =
                                        rtrim(
                                            $raw,
                                            '*'
                                        );

                                    $digits =
                                        preg_replace(
                                            '/\D+/',
                                            '',
                                            $code
                                        );

                                    if (
                                        strlen(
                                            $digits
                                        ) === 6
                                    ) {
                                        $code =
                                            substr(
                                                $digits,
                                                0,
                                                2
                                            )
                                            . ' '
                                            . substr(
                                                $digits,
                                                2,
                                                2
                                            )
                                            . ' '
                                            . substr(
                                                $digits,
                                                4,
                                                2
                                            );
                                    }

                                    return $hasStar
                                        ? $code . '*'
                                        : $code;
                                }
                            )
                            ->dehydrateStateUsing(
                                function (
                                    ?string $state
                                ): ?string {
                                    if (! $state) {
                                        return null;
                                    }

                                    return str_replace(
                                        ' ',
                                        '',
                                        trim($state)
                                    );
                                }
                            )
                            ->rules([
                                fn ($record) =>
                                    Rule::unique(
                                        'waste_types',
                                        'waste_code'
                                    )
                                        ->where(
                                            function (
                                                $query
                                            ) {
                                                $ownerId =
                                                    static::resolveOwnerId();

                                                if (
                                                    $ownerId
                                                ) {
                                                    $query
                                                        ->where(
                                                            'user_id',
                                                            $ownerId
                                                        );
                                                } else {
                                                    /*
                                                     * Kod superadmina
                                                     * forma nije dostupna
                                                     * za create/edit
                                                     * organizacijskog zapisa.
                                                     */
                                                    $query
                                                        ->whereNull(
                                                            'user_id'
                                                        );
                                                }

                                                $query
                                                    ->whereNull(
                                                        'deleted_at'
                                                    );
                                            }
                                        )
                                        ->ignore(
                                            $record?->id
                                        ),
                            ]),

                        TextInput::make('name')
                            ->label('Naziv')
                            ->required()
                            ->maxLength(255),

                        Toggle::make(
                            'is_hazardous'
                        )
                            ->label(
                                'Opasan otpad'
                            )
                            ->default(false)
                            ->inline(false)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLICA
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn (
                    WasteType $record
                ): string =>
                    static::canEdit($record)
                        ? static::getUrl(
                            'edit',
                            [
                                'record' =>
                                    $record,
                            ]
                        )
                        : static::getUrl(
                            'view',
                            [
                                'record' =>
                                    $record,
                            ]
                        )
            )
            ->paginated([
                10,
                25,
                50,
                'all',
            ])
            ->defaultSort('waste_code')
            ->columns([
                TextColumn::make(
                    'waste_code'
                )
                    ->label(
                        'Ključni broj otpada'
                    )
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->formatStateUsing(
                        function (
                            string $state
                        ): string {
                            $hasStar =
                                str_ends_with(
                                    $state,
                                    '*'
                                );

                            $code =
                                rtrim(
                                    $state,
                                    '*'
                                );

                            if (
                                strlen($code)
                                === 6
                            ) {
                                $code =
                                    substr(
                                        $code,
                                        0,
                                        2
                                    )
                                    . ' '
                                    . substr(
                                        $code,
                                        2,
                                        2
                                    )
                                    . ' '
                                    . substr(
                                        $code,
                                        4,
                                        2
                                    );
                            }

                            return $hasStar
                                ? $code
                                    . '<sup style="font-size:0.75em">*</sup>'
                                : $code;
                        }
                    )
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),

                IconColumn::make(
                    'is_hazardous'
                )
                    ->label('Opasan')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'created_at'
                )
                    ->label('Kreirano')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),

                TextColumn::make(
                    'deleted_at'
                )
                    ->label(
                        'Deaktivirano'
                    )
                    ->dateTime(
                        'd.m.Y. H:i'
                    )
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),
            ])
            ->filters([
                SelectFilter::make(
                    'is_hazardous'
                )
                    ->label('Vrsta')
                    ->options([
                        '1' =>
                            'Opasan otpad',

                        '0' =>
                            'Neopasan otpad',
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
                                fn (
                                    Builder $query
                                ) =>
                                    $query->where(
                                        'is_hazardous',
                                        (bool)
                                        $data['value']
                                    )
                            );
                        }
                    ),

                SelectFilter::make('status')
                    ->label(
                        'Status zapisa'
                    )
                    ->placeholder(
                        'Odaberi status'
                    )
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
                        ) {
                            $value =
                                $data['value']
                                ?? null;

                            return match (
                                $value
                            ) {
                                'trashed' =>
                                    $query
                                        ->onlyTrashed(),

                                'all' =>
                                    $query
                                        ->withTrashed(),

                                default =>
                                    $query
                                        ->withoutTrashed(),
                            };
                        }
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaz'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            fn (
                                WasteType $record
                            ): bool =>
                                ! $record
                                    ->trashed()
                                && static::canEdit(
                                    $record
                                )
                        ),

                    DeleteAction::make()
                        ->label(
                            'Deaktiviraj'
                        )
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WasteType $record
                            ): bool =>
                                ! $record
                                    ->trashed()
                                && static::canDelete(
                                    $record
                                )
                        )
                        ->modalHeading(
                            'Deaktiviraj vrstu otpada'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a da želiš '
                            . 'deaktivirati ovu vrstu otpada?'
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WasteType $record
                            ): bool =>
                                $record
                                    ->trashed()
                                && static::canRestore(
                                    $record
                                )
                        ),

                    ForceDeleteAction::make()
                        ->label(
                            'Trajno izbriši'
                        )
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WasteType $record
                            ): bool =>
                                $record
                                    ->trashed()
                                && static::canForceDelete(
                                    $record
                                )
                        )
                        ->modalHeading(
                            'Trajno izbriši vrstu otpada'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a? '
                            . 'Ova radnja je nepovratna.'
                        ),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label(
                        'Deaktiviraj označeno'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Deaktiviraj odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->visible(
                        fn (
                            HasTable $livewire
                        ): bool =>
                            static::isOnlyTrashed(
                                $livewire
                            )
                    ),

                RestoreBulkAction::make()
                    ->label(
                        'Vrati označeno'
                    )
                    ->requiresConfirmation()
                    ->visible(
                        fn (
                            HasTable $livewire
                        ): bool =>
                            static::isOnlyTrashed(
                                $livewire
                            )
                    ),

                ForceDeleteBulkAction::make()
                    ->label(
                        'Trajno izbriši označeno'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno izbriši odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a? '
                        . 'Ova radnja je nepovratna.'
                    )
                    ->visible(
                        fn (
                            HasTable $livewire
                        ): bool =>
                            static::isOnlyTrashed(
                                $livewire
                            )
                    ),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY / MULTI-TENANCY
    |--------------------------------------------------------------------------
    |
    | Superadmin:
    | - vidi sve zapise radi pregleda sustava
    |
    | Organizacija:
    | - vidi isključivo user_id = ownerId()
    |
    */

    public static function getEloquentQuery(): Builder
    {
        $query =
            parent::getEloquentQuery();

        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId =
            static::resolveOwnerId();

        if (! $ownerId) {
            return $query
                ->whereRaw('1 = 0');
        }

        return $query->where(
            'user_id',
            $ownerId
        );
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    /*
    |--------------------------------------------------------------------------
    | NAVIGATION BADGE
    |--------------------------------------------------------------------------
    */

    public static function getNavigationBadge(): ?string
    {
        $query =
            static::getModel()::query();

        if (! static::isSuperAdmin()) {
            $ownerId =
                static::resolveOwnerId();

            if (! $ownerId) {
                return '0';
            }

            $query->where(
                'user_id',
                $ownerId
            );
        }

        return (string)
            $query->count();
    }

    /*
    |--------------------------------------------------------------------------
    | SOFT DELETE FILTER
    |--------------------------------------------------------------------------
    */

    private static function isOnlyTrashed(
        HasTable $livewire
    ): bool {
        $state =
            $livewire
                ->getTableFilterState(
                    'status'
                );

        $value =
            data_get(
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
                ListWasteTypes::route('/'),

            'create' =>
                CreateWasteType::route(
                    '/create'
                ),

            'view' =>
                ViewWasteType::route(
                    '/{record}'
                ),

            'edit' =>
                EditWasteType::route(
                    '/{record}/edit'
                ),
        ];
    }
}
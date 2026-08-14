<?php

namespace App\Filament\Resources\LearningCategories;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\LearningCategories\Pages;
use App\Models\LearningCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LearningCategoryResource extends BaseResource
{
    protected static ?string $model = LearningCategory::class;

    /**
     * Posebna global/org logika.
     *
     * Globalna kategorija:
     * user_id = NULL
     * is_global = true
     *
     * Organizacijska kategorija:
     * user_id = ownerId()
     * is_global = false
     */
    protected static bool $hasOwnership = false;

    /**
     * Superadmin smije kreirati globalne kategorije.
     */
    protected static bool $superAdminCanCreate = true;

    protected static \BackedEnum|string|null $navigationIcon =
        'heroicon-o-folder-open';

    protected static \UnitEnum|string|null $navigationGroup =
        'Edukacija';

    protected static ?string $navigationLabel =
        'Kategorije edukacije';

    protected static ?string $modelLabel =
        'Kategorija edukacije';

    protected static ?string $pluralModelLabel =
        'Kategorije edukacije';

    protected static ?int $navigationSort = 1;

    /**
     * Ovaj modul nema granularne dozvole.
     */
    protected static function getModuleKey(): ?string
    {
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(
                    fn () =>
                        static::isSuperAdmin()
                            ? null
                            : static::ownerId()
                )
                ->dehydrated(),

            TextInput::make('name')
                ->label('Naziv kategorije')
                ->required()
                ->maxLength(255),

            TextInput::make('color')
                ->label('Boja / oznaka')
                ->placeholder(
                    'npr. blue, green, orange'
                )
                ->maxLength(50),

            TextInput::make('sort_order')
                ->label('Redoslijed')
                ->numeric()
                ->default(0),

            /**
             * Globalni status se ne mijenja kroz formu.
             *
             * CreateLearningCategory serverski određuje:
             * - superadmin = globalna kategorija
             * - organizacija = organizacijska kategorija
             *
             * EditLearningCategory čuva postojeći status.
             */
            Toggle::make('is_global')
                ->label('Globalna kategorija')
                ->helperText(
                    'Globalne kategorije vide sve organizacije.'
                )
                ->default(
                    fn (): bool =>
                        static::isSuperAdmin()
                )
                ->disabled()
                ->dehydrated(),

            Toggle::make('is_active')
                ->label('Aktivno')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Kategorija')
                    ->searchable()
                    ->sortable(),

                    static::userTableColumn()
                    ->toggleable(),
                TextColumn::make('materials_count')
                    ->label('Materijala')
                    ->counts('materials')
                    ->alignCenter(),

                IconColumn::make('is_global')
                    ->label('Globalno')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Aktivno')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('Redoslijed')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->label('Uredi'),

                DeleteAction::make()
                    ->label('Obriši')
                    ->requiresConfirmation(),
            ])

            /**
             * Bulk delete namjerno nije omogućen.
             */
            ->bulkActions([]);
    }

    /**
     * Vidljivost kategorija.
     *
     * Superadmin:
     * - vidi sve globalne i organizacijske kategorije.
     *
     * Organizacija:
     * - vidi ispravne globalne kategorije
     *   (is_global = true + user_id = NULL)
     * - vidi kategorije svoje organizacije.
     *
     * Time eventualni pogrešni zapis:
     * is_global = true + user_id = druga organizacija
     * neće postati globalno vidljiv.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query();

        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            function (Builder $query) use ($ownerId): void {
                $query
                    ->where(
                        function (Builder $global): void {
                            $global
                                ->where('is_global', true)
                                ->whereNull('user_id');
                        }
                    )
                    ->orWhere(
                        function (Builder $organization) use ($ownerId): void {
                            $organization
                                ->where('is_global', false)
                                ->where(
                                    'user_id',
                                    $ownerId
                                );
                        }
                    );
            }
        );
    }

    /**
     * Organizacija ne smije uređivati
     * globalnu kategoriju niti kategoriju
     * druge organizacije.
     */
    public static function canEdit(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        if ((bool) $record->is_global) {
            return false;
        }

        return (int) $record->user_id
            === (int) static::ownerId();
    }

    /**
     * Organizacija ne smije brisati
     * globalnu kategoriju niti kategoriju
     * druge organizacije.
     */
    public static function canDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        if ((bool) $record->is_global) {
            return false;
        }

        return (int) $record->user_id
            === (int) static::ownerId();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()
            ->count();
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListLearningCategories::route('/'),

            'create' =>
                Pages\CreateLearningCategory::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditLearningCategory::route(
                    '/{record}/edit'
                ),
        ];
    }
}
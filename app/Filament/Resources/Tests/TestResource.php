<?php

namespace App\Filament\Resources\Tests;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Tests\Pages;
use App\Filament\Resources\Tests\Schemas\TestForm;
use App\Models\Test;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TestResource extends BaseResource
{
    protected static ?string $model = Test::class;

    protected static bool $hasOwnership = false;

    protected static bool $superAdminCanCreate = true;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';

    protected static ?string $navigationLabel = 'Testovi';

    protected static ?string $modelLabel = 'Test';

    protected static ?string $pluralModelLabel = 'Testovi';

    protected static ?int $navigationSort = 97;

    protected static function getModuleKey(): ?string
    {
        return 'tests';
    }

    public static function form(Schema $schema): Schema
    {
        return TestForm::configure($schema);
    }

    /**
     * ID-evi glavnog korisnika i njegovih podkorisnika koristimo
     * i zbog kompatibilnosti sa starim zapisima koji su možda
     * spremani na Auth::id() prije prelaska na ownerId().
     */
    public static function organizationUserIds(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $ownerId = $user->ownerId();

        return User::query()
            ->where('id', $ownerId)
            ->orWhere('parent_user_id', $ownerId)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Testovi koje trenutni korisnik smije MIJENJATI.
     *
     * Superadmin:
     * - samo globalne testove
     *
     * Organizacija:
     * - samo vlastite organizacijske testove
     */
    public static function getManageableQuery(): Builder
    {
        $query = Test::query();

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereIn(
            'user_id',
            static::organizationUserIds()
        );
    }

    /**
     * Provjera može li trenutni korisnik uređivati/brisati test.
     */
    public static function canManageTest(Test $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Superadmin upravlja samo globalnim testovima.
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Organizacija ne smije mijenjati globalne testove.
        if ($record->user_id === null) {
            return false;
        }

        return in_array(
            (int) $record->user_id,
            static::organizationUserIds(),
            true
        );
    }

    /**
     * Zaštita direktnog /edit URL-a.
     */
    public static function canEdit($record): bool
    {
        return $record instanceof Test
            && parent::canEdit($record)
            && static::canManageTest($record);
    }

    /**
     * Zaštita brisanja.
     */
    public static function canDelete($record): bool
    {
        return $record instanceof Test
            && parent::canDelete($record)
            && static::canManageTest($record);
    }

    /**
     * Vidljivost zapisa.
     *
     * Superadmin vidi sve radi administracije.
     *
     * Organizacija vidi:
     * - globalne testove
     * - vlastite testove
     * - stare testove eventualno spremljene na ID podkorisnika
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $userIds = static::organizationUserIds();

        return $query->where(function (Builder $query) use ($userIds): void {
            $query
                ->whereNull('user_id')
                ->orWhereIn('user_id', $userIds);
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('naziv')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable(),

                static::userTableColumn(),

                TextColumn::make('sifra')
                    ->label('Šifra')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('minimalni_prolaz')
                    ->label('Prolaz (%)')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dodano')
                    ->date('d.m.Y.')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->label('Uredi')
                    ->visible(
                        fn (Test $record): bool =>
                            static::canManageTest($record)
                    ),

                DeleteAction::make()
                    ->label('Obriši')
                    ->requiresConfirmation()
                    ->visible(
                        fn (Test $record): bool =>
                            static::canManageTest($record)
                    ),
            ])
            ->bulkActions([]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTests::route('/'),
            'create' => Pages\CreateTest::route('/create'),
            'edit' => Pages\EditTest::route('/{record}/edit'),
        ];
    }
}

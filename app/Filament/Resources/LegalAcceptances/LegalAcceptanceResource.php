<?php

namespace App\Filament\Resources\LegalAcceptances;

use App\Filament\Resources\LegalAcceptances\Pages\ListLegalAcceptances;
use App\Filament\Resources\LegalAcceptances\Pages\ViewLegalAcceptance;
use App\Filament\Resources\LegalAcceptances\Schemas\LegalAcceptanceForm;
use App\Filament\Resources\LegalAcceptances\Schemas\LegalAcceptanceInfolist;
use App\Filament\Resources\LegalAcceptances\Tables\LegalAcceptancesTable;
use App\Models\LegalAcceptance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LegalAcceptanceResource extends Resource
{
    protected static ?string $model = LegalAcceptance::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup =
        'Administracija';

    protected static ?string $navigationLabel =
        'GDPR evidencija';

    protected static ?string $modelLabel =
        'GDPR prihvaćanje';

    protected static ?string $pluralModelLabel =
        'GDPR evidencija';

    protected static ?int $navigationSort = 99;

    protected static ?string $recordTitleAttribute =
        'user_name';

    /**
     * GDPR evidencija je centralna administrativna
     * evidencija dostupna samo superadminu.
     */
    protected static function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::isSuperAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::isSuperAdmin();
    }

    /**
     * Zaštita direktnog pristupa pojedinačnom zapisu.
     */
    public static function canView(Model $record): bool
    {
        return static::isSuperAdmin();
    }

    /**
     * GDPR prihvaćanja nastaju automatski kroz
     * proces prihvaćanja pravnih dokumenata.
     *
     * Ne stvaraju se ručno kroz Filament.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Evidencija prihvaćanja je audit zapis
     * i ne smije se uređivati.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Evidencija prihvaćanja je audit zapis
     * i ne smije se brisati kroz Filament.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! static::isSuperAdmin()) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->with('user')
            ->latest('accepted_at');
    }

    public static function form(Schema $schema): Schema
    {
        return LegalAcceptanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LegalAcceptanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalAcceptancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                ListLegalAcceptances::route('/'),

            'view' =>
                ViewLegalAcceptance::route('/{record}'),
        ];
    }
}

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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LegalAcceptanceResource extends Resource
{
    protected static ?string $model = LegalAcceptance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Administracija';

    protected static ?string $navigationLabel = 'GDPR evidencija';

    protected static ?string $modelLabel = 'GDPR prihvaćanje';

    protected static ?string $pluralModelLabel = 'GDPR evidencija';

    protected static ?int $navigationSort = 99;

    protected static ?string $recordTitleAttribute = 'user_name';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->isSuperAdmin() === true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        if (! Auth::user()?->isSuperAdmin()) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
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
            'index' => ListLegalAcceptances::route('/'),
            'view' => ViewLegalAcceptance::route('/{record}'),
        ];
    }
}

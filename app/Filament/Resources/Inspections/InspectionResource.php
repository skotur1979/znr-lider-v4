<?php

namespace App\Filament\Resources\Inspections;

use App\Filament\Resources\Inspections\Pages;
use App\Filament\Resources\Inspections\RelationManagers\FindingsRelationManager;
use App\Filament\Resources\Inspections\RelationManagers\ZonesRelationManager;
use App\Models\Inspection;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class InspectionResource extends Resource
{
    protected static ?string $model = Inspection::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|UnitEnum|null $navigationGroup = 'Upravljanje';

    protected static ?string $navigationLabel = 'Nadzori';
    protected static ?string $modelLabel = 'Nadzor';
    protected static ?string $pluralModelLabel = 'Nadzori';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->dehydrated(true),

            Hidden::make('inspection_type')
                ->default(fn () => request()->query('inspection_type', 'general'))
                ->dehydrated(true),

            Section::make('Osnovni podaci nadzora')
                ->schema([
                    TextInput::make('number')
                        ->label('Broj nadzora')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('title')
                        ->label('Naziv nadzora')
                        ->required(),

                    TextInput::make('location')
                        ->label('Lokacija')
                        ->required(),

                    DatePicker::make('performed_at')
                        ->label('Datum nadzora')
                        ->displayFormat('d.m.Y.')
                        ->required(),

                    TextInput::make('performed_by')
                        ->label('Proveo nadzor'),

                    Textarea::make('present_persons')
                        ->label('Prisutne osobe')
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('conclusion')
                        ->label('Zaključak')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['findings', 'zones']))
            ->columns([
                TextColumn::make('number')
                    ->label('Broj')
                    ->weight('bold'),

                TextColumn::make('inspection_type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'five_s' => '5S nadzor',
                        default => 'Nadzor',
                    })
                    ->alignment(Alignment::Center),

                TextColumn::make('performed_at')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->alignment(Alignment::Center),

                TextColumn::make('title')
                    ->label('Naziv')
                    ->wrap(),

                TextColumn::make('location')
                    ->label('Lokacija')
                    ->wrap(),

                TextColumn::make('five_s_score')
    ->label('5S rezultat')
    ->state(fn (Inspection $record) => $record->calculateFiveSScore()) // Prikaz rezultata
    ->formatStateUsing(fn ($state) => filled($state) ? $state . '%' : '-')  // Prikaz procenta
    ->badge() // Omogućuje boje za badge
    ->color(fn ($state) => match (true) { // Ovdje dodajemo boje
        blank($state) => 'gray',  // Siva boja ako nema rezultata
        $state < 40 => 'danger',  // Crvena boja za ispod 40%
        $state < 60 => 'warning', // Žuta boja za između 40% i 60%
        default => 'success',    // Zelena boja za iznad 60%
    })
    ->alignment(Alignment::Center), // Centriranje

                TextColumn::make('findings_count')
                    ->label('Nalaza')
                    ->alignment(Alignment::Center),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaz'),
                    EditAction::make()->label('Uredi'),
                    DeleteAction::make()->label('Obriši'),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FindingsRelationManager::class,
            ZonesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return Auth::user()?->isAdmin()
            ? parent::getEloquentQuery()
            : parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }
    public static function shouldRegisterNavigation(): bool
{
    $user = Auth::user();

    return $user?->isSuperAdmin() || $user?->canAccessModule('inspections');
}

public static function canViewAny(): bool
{
    return static::shouldRegisterNavigation();
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInspections::route('/'),
            'create' => Pages\CreateInspection::route('/create'),
            'view' => Pages\ViewInspection::route('/{record}'),
            'edit' => Pages\EditInspection::route('/{record}/edit'),
        ];
    }
}
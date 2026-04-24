<?php

namespace App\Filament\Resources\Budgets;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Budgets\Pages;
use App\Models\Budget;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BudgetResource extends BaseResource
{
    protected static ?string $model = Budget::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCreditCard;
    protected static ?string $navigationLabel = 'Budžet';
    protected static ?string $modelLabel = 'Budžet';
    protected static ?string $pluralModelLabel = 'Budžet';
    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 7;
    protected static ?string $recordTitleAttribute = 'godina';

    protected static function getModuleKey(): ?string
    {
        return 'budgets';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
    ->label('Korisnik')
    ->relationship('user', 'name')
    ->searchable()
    ->preload()
    ->required()
    ->visible(fn () => static::isSuperAdmin())
    ->dehydrated(fn () => static::isSuperAdmin())
    ->hiddenOn(['edit', 'view']),
    
            Hidden::make('user_id')
                ->default(fn () => Auth::user()?->ownerId())
                ->visible(fn () => ! Auth::user()?->isSuperAdmin())
                ->dehydrated(fn () => ! Auth::user()?->isSuperAdmin()),

            Section::make('Unos budžeta')
                ->columns(2)
                ->schema([
                    TextInput::make('godina')
                        ->label('Godina')
                        ->numeric()
                        ->required(),

                    TextInput::make('ukupni_budget')
                        ->label('Ukupni budžet (€)')
                        ->numeric()
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('godina', 'desc')
            ->columns([
                TextColumn::make('godina')
                    ->label('Godina')
                    ->sortable(),
static::userTableColumn(),
                TextColumn::make('ukupni_budget')
                    ->label('Ukupni budžet (€)')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.') . ' €')
                    ->sortable(),

                ViewColumn::make('stanje_budgeta')
                    ->label('Stanje budžeta')
                    ->view('filament.tables.columns.budget-status'),
            ])
            ->actions([
                EditAction::make()->label('Uredi'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Obriši označeno'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Novi budžet')
                    ->modalHeading('Novi budžet')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (! Auth::user()?->isSuperAdmin()) {
                            $data['user_id'] = Auth::user()?->ownerId();
                        }

                        return $data;
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::user()?->ownerId());
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where('user_id', Auth::user()?->ownerId());
        }

        return (string) $query->count();
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::user()?->ownerId();
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::user()?->ownerId();
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
            'view' => Pages\ViewBudget::route('/{record}'),
        ];
    }
}

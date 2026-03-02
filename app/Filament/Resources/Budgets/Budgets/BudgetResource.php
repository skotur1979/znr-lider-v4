<?php

namespace App\Filament\Resources\Budgets\Budgets;

use App\Filament\Resources\Budgets\Budgets\Pages;
use App\Models\Budget;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

use Filament\Support\Icons\Heroicon;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCreditCard;
    protected static ?string $navigationLabel = 'Budžet';
    protected static ?string $modelLabel = 'Budžet';
    protected static ?string $pluralModelLabel = 'Budžet';

    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'godina';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->label('Korisnik')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn () => Auth::user()?->isAdmin())
                ->dehydrated(fn () => Auth::user()?->isAdmin()),

            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->visible(fn () => ! Auth::user()?->isAdmin())
                ->dehydrated(fn () => ! Auth::user()?->isAdmin()),

            Section::make('Unos budžeta')
                ->columns(2)
                ->schema([
                    TextInput::make('godina')->label('Godina')->numeric()->required(),
                    TextInput::make('ukupni_budget')->label('Ukupni budžet (€)')->numeric()->required(),
                ]),
        ]);
    }

   public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('godina')
                ->label('Godina')
                ->sortable(),

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
            \Filament\Actions\CreateAction::make()
                ->label('Novi budžet')
                ->modalHeading('Novi budžet')
                ->mutateFormDataUsing(function (array $data): array {
                    if (! auth()->user()?->isAdmin()) {
                        $data['user_id'] = auth()->id();
                    }
                    return $data;
                }),
        ]);
}

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();

        if (Auth::user()?->isAdmin()) {
            return $q;
        }

        return $q->where('user_id', Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit'   => Pages\EditBudget::route('/{record}/edit'),
            'view'   => Pages\ViewBudget::route('/{record}'), // ako ga imaš
        ];
    }
}
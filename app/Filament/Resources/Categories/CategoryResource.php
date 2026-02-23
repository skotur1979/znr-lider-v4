<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\CategoryResource\Pages;
use App\Models\Category;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Kategorije ispitivanja';
    protected static ?string $modelLabel = 'Kategorija';
    protected static ?string $pluralModelLabel = 'Kategorije ispitivanja';
    protected static ?int $navigationSort = 5;
    protected static \UnitEnum|string|null $navigationGroup = 'Ispitivanja';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Podaci')
                ->schema([
                    TextInput::make('name')
                        ->label('Naziv')
                        ->required()
                        ->maxLength(255),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Vlasnik')
                    ->visible(fn () => Auth::user()?->isAdmin()),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                    ForceDeleteBulkAction::make()->label('Trajno obriši označeno'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view'   => Pages\ViewCategory::route('/{record}'),
            'edit'   => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return Auth::user()?->isAdmin()
            ? $query
            : $query->where('user_id', Auth::id());
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
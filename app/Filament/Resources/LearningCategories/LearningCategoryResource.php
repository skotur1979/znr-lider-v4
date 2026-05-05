<?php

namespace App\Filament\Resources\LearningCategories;

use App\Filament\Resources\LearningCategories\Pages;
use App\Models\LearningCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LearningCategoryResource extends Resource
{
    protected static ?string $model = LearningCategory::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-folder-open';
    protected static \UnitEnum|string|null $navigationGroup = 'Edukacija';
    protected static ?string $navigationLabel = 'Kategorije edukacije';
    protected static ?string $modelLabel = 'Kategorija edukacije';
    protected static ?string $pluralModelLabel = 'Kategorije edukacije';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => self::ownerId()),

            TextInput::make('name')
                ->label('Naziv kategorije')
                ->required()
                ->maxLength(255),

            TextInput::make('color')
                ->label('Boja / oznaka')
                ->placeholder('npr. blue, green, orange')
                ->maxLength(50),

            TextInput::make('sort_order')
                ->label('Redoslijed')
                ->numeric()
                ->default(0),

            Toggle::make('is_global')
                ->label('Globalna kategorija')
                ->helperText('Globalne kategorije vide svi korisnici.')
                ->visible(fn () => self::isSuperAdmin()),

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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (self::isSuperAdmin()) {
            return $query;
        }

        $ownerId = self::ownerId();

        return $query->where(function (Builder $q) use ($ownerId) {
            $q->where('is_global', true)
                ->orWhere('user_id', $ownerId);
        });
    }

    protected static function isSuperAdmin(): bool
    {
        $user = Auth::user();

        return (bool) (
            $user?->isSuperAdmin()
            || $user?->is_admin
            || $user?->role === 'admin'
        );
    }

    protected static function ownerId(): ?int
    {
        $user = Auth::user();

        return $user?->parent_user_id ?: $user?->id;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLearningCategories::route('/'),
            'create' => Pages\CreateLearningCategory::route('/create'),
            'edit' => Pages\EditLearningCategory::route('/{record}/edit'),
        ];
    }
    protected static function getModuleKey(): ?string
    {
        return 'learning_categories';
    }
}
<?php

namespace App\Filament\Resources\LearningCategories;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\LearningCategories\Pages;
use App\Models\LearningCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LearningCategoryResource extends BaseResource
{
    protected static ?string $model = LearningCategory::class;

    protected static bool $hasOwnership = false;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-folder-open';
    protected static \UnitEnum|string|null $navigationGroup = 'Edukacija';
    protected static ?string $navigationLabel = 'Kategorije edukacije';
    protected static ?string $modelLabel = 'Kategorija edukacije';
    protected static ?string $pluralModelLabel = 'Kategorije edukacije';

    protected static ?int $navigationSort = 1;

    protected static function getModuleKey(): ?string
    {
        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => static::ownerId()),

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
                ->visible(fn () => static::isSuperAdmin()),

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
        $query = static::getModel()::query();

        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId = static::ownerId();

        return $query->where(function (Builder $q) use ($ownerId) {
            $q->where('is_global', true)
                ->orWhere('user_id', $ownerId);
        });
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! static::isSuperAdmin()) {
            $data['user_id'] = static::ownerId();
            $data['is_global'] = false;
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (! static::isSuperAdmin()) {
            $data['user_id'] = static::ownerId();
            $data['is_global'] = false;
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLearningCategories::route('/'),
            'create' => Pages\CreateLearningCategory::route('/create'),
            'edit' => Pages\EditLearningCategory::route('/{record}/edit'),
        ];
    }
}
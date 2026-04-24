<?php

namespace App\Filament\Resources\Tests;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Tests\Pages;
use App\Filament\Resources\Tests\Schemas\TestForm;
use App\Models\Test;
use BackedEnum;
use Filament\Actions\DeleteBulkAction;
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
                EditAction::make()->label('Uredi'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Obriši označeno'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) {
            $q->whereNull('user_id')
                ->orWhere('user_id', Auth::id());
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where(function (Builder $q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', Auth::id());
            });
        }

        return (string) $query->count();
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

<?php

namespace App\Filament\Resources\Tests;

use App\Filament\Resources\Tests\Pages;
use App\Filament\Resources\Tests\Schemas\TestForm;
use App\Models\Test;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;

use Filament\Actions\DeleteBulkAction;

class TestResource extends Resource
{
    protected static ?string $model = Test::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';
    protected static ?string $navigationLabel = 'Testovi';
    protected static ?string $modelLabel = 'Test';
     protected static ?string $pluralModelLabel = 'Testovi';
    protected static ?int $navigationSort = 97;

    public static function form(Schema $schema): Schema
    {
        return TestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('naziv')->label('Naziv')->searchable(),
                TextColumn::make('sifra')->label('Šifra'),
                TextColumn::make('minimalni_prolaz')->label('Prolaz (%)'),
                TextColumn::make('created_at')->label('Dodano')->date('d.m.Y.'),
            ])
            ->actions([
    EditAction::make(),

            ])
            ->bulkActions([
    DeleteBulkAction::make(),

            ]);
    }


    public static function getNavigationBadge(): ?string
{
    $q = static::getModel()::query();

    if (Auth::user()?->isAdmin()) {
        return (string) $q->count();
    }

    $q->where(function (Builder $qq) {
        $qq->whereNull('user_id')
           ->orWhere('user_id', Auth::id());
    });

    return (string) $q->count();
}
    public static function getEloquentQuery(): Builder
{
    $q = parent::getEloquentQuery();

    if (Auth::user()?->isAdmin()) {
        return $q;
    }

    return $q->where(function (Builder $qq) {
        $qq->whereNull('user_id')
           ->orWhere('user_id', Auth::id());
    });
}

    public static function getPages(): array
{
    return [
        'index'  => Pages\ListTests::route('/'),
        'create' => Pages\CreateTest::route('/create'),
        'edit'   => Pages\EditTest::route('/{record}/edit'),
    ];
}
}
<?php

namespace App\Filament\Resources\Answers;

use App\Filament\Resources\Answers\Pages;
use App\Filament\Resources\Answers\Schemas\AnswerForm;
use App\Models\Answer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

class AnswerResource extends Resource
{
    protected static ?string $model = Answer::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';

    protected static ?string $navigationLabel = 'Odgovori';
    protected static ?string $modelLabel = 'Odgovor';
    protected static ?string $pluralModelLabel = 'Odgovori';
    protected static ?int $navigationSort = 99;

    public static function form(Schema $schema): Schema
    {
        return AnswerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question.tekst')->label('Pitanje')->limit(60),
                TextColumn::make('tekst')->label('Odgovor')->wrap(),
                IconColumn::make('is_correct')->label('Točno?')->boolean(),
            ])
            ->actions([
    EditAction::make(),
    DeleteAction::make(),
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
            'index'  => Pages\ListAnswers::route('/'),
            'create' => Pages\CreateAnswer::route('/create'),
            'edit'   => Pages\EditAnswer::route('/{record}/edit'),
        ];
    }
}
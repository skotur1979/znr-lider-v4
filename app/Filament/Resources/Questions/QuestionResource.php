<?php

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\Questions\Pages;
use App\Filament\Resources\Questions\Schemas\QuestionForm;
use App\Models\Question;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn as BadgeLike;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';
    protected static ?string $navigationLabel = 'Pitanja';
    protected static ?string $modelLabel = 'Pitanje';
    protected static ?string $pluralModelLabel = 'Pitanja';
    protected static ?int $navigationSort = 98;

    public static function form(Schema $schema): Schema
    {
        return QuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('test.naziv')
                    ->label('Test')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tekst')
                    ->label('Pitanje')
                    ->limit(80)
                    ->searchable(),

                TextColumn::make('answers_count')
                    ->label('Broj odgovora')
                    ->counts('answers')
                    ->sortable(),

                IconColumn::make('visestruki_odgovori')
                    ->label('Više odgovora')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('test_id')
                    ->label('Test')
                    ->relationship('test', 'naziv', function (Builder $q) {
                        if (! Auth::user()?->isAdmin()) {
                            $q->where('user_id', Auth::id());
                        }
                    })
                    ->searchable(),
            ])
            ->defaultSort('test_id')
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
            'index'  => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit'   => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
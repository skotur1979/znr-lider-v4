<?php

namespace App\Filament\Resources\Answers;

use App\Filament\Resources\Answers\Pages;
use App\Filament\Resources\Answers\Schemas\AnswerForm;
use App\Filament\Resources\BaseResource;
use App\Models\Answer;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;

class AnswerResource extends BaseResource
{
    protected static ?string $model = Answer::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';

    protected static ?string $navigationLabel = 'Odgovori';
    protected static ?string $modelLabel = 'Odgovor';
    protected static ?string $pluralModelLabel = 'Odgovori';
    protected static ?int $navigationSort = 99;

    protected static function getModuleKey(): ?string
    {
        return 'answers';
    }

    public static function form(Schema $schema): Schema
    {
        return AnswerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question.tekst')
                    ->label('Pitanje')
                    ->limit(60)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tekst')
                    ->label('Odgovor')
                    ->wrap()
                    ->searchable(),

                IconColumn::make('is_correct')
                    ->label('Točno?')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->actions([
                EditAction::make()->label('Uredi'),
                DeleteAction::make()->label('Obriši'),
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
            'index' => Pages\ListAnswers::route('/'),
            'create' => Pages\CreateAnswer::route('/create'),
            'edit' => Pages\EditAnswer::route('/{record}/edit'),
        ];
    }
}

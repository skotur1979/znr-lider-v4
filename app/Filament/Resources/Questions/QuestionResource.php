<?php

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Questions\Pages;
use App\Filament\Resources\Questions\Schemas\QuestionForm;
use App\Models\Question;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class QuestionResource extends BaseResource
{
    protected static ?string $model = Question::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static string|UnitEnum|null $navigationGroup = 'Testiranje';
    protected static ?string $navigationLabel = 'Pitanja';
    protected static ?string $modelLabel = 'Pitanje';
    protected static ?string $pluralModelLabel = 'Pitanja';
    protected static ?int $navigationSort = 98;

    protected static function getModuleKey(): ?string
    {
        return 'questions';
    }

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
static::userTableColumn(),
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
                    ->relationship('test', 'naziv', function (Builder $query) {
                        if (! Auth::user()?->isSuperAdmin()) {
                            $query->where(function (Builder $q) {
                                $q->whereNull('user_id')
                                    ->orWhere('user_id', Auth::id());
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('test_id')
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
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}

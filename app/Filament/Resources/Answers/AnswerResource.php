<?php

namespace App\Filament\Resources\Answers;

use App\Filament\Resources\Answers\Pages;
use App\Filament\Resources\Answers\Schemas\AnswerForm;
use App\Filament\Resources\BaseResource;
use App\Models\Answer;
use App\Models\Question;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
        ->paginated([10, 25, 50, 100, 200, 'all'])
            ->columns([
    TextColumn::make('question.test.naziv')
        ->label('Test')
        ->limit(35)
        ->searchable()
        ->sortable()
        ->toggleable(),

    TextColumn::make('question_number')
        ->label('Br. pitanja')
        ->alignCenter()
        ->badge()
        ->color('warning')
        ->state(function (Answer $record): string {
            if (! $record->question) {
                return '—';
            }

            $number = Question::query()
                ->where('test_id', $record->question->test_id)
                ->where('id', '<=', $record->question_id)
                ->orderBy('id')
                ->count();

            return (string) $number;
        })
        ->toggleable(),

    TextColumn::make('question.tekst')
        ->label('Pitanje')
        ->limit(60)
        ->searchable()
        ->sortable()
        ->toggleable(),

    static::userTableColumn()
        ->toggleable(),

    TextColumn::make('tekst')
        ->label('Odgovor')
        ->searchable()
        ->limit(120)
        ->tooltip(fn ($record) => $record->tekst)
        ->extraAttributes([
            'style' => '
                min-width: 420px;
                max-width: 520px;
                white-space: normal;
                line-height: 1.3;
            ',
        ])
        ->toggleable(),

    IconColumn::make('is_correct')
        ->label('Točno?')
        ->boolean()
        ->alignCenter()
        ->toggleable(),
])
            ->defaultSort('question_id')
            ->actions([
                EditAction::make()->label('Uredi'),
                DeleteAction::make()->label('Obriši'),
            ])
            ->filters([
    \Filament\Tables\Filters\SelectFilter::make('test_id')
        ->label('Test')
        ->relationship('question.test', 'naziv')
        ->searchable()
        ->preload(),
])
            ->bulkActions([
                DeleteBulkAction::make()->label('Obriši označeno'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['question.test']);

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

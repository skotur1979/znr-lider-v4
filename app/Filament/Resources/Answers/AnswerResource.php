<?php

namespace App\Filament\Resources\Answers;

use App\Filament\Resources\Answers\Pages;
use App\Filament\Resources\Answers\Schemas\AnswerForm;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Tests\TestResource;
use App\Models\Answer;
use App\Models\Question;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AnswerResource extends BaseResource
{
    protected static ?string $model = Answer::class;

    protected static bool $hasOwnership = false;

    protected static bool $superAdminCanCreate = true;

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

    /**
     * Odgovori koje korisnik smije uređivati.
     */
    public static function getManageableQuery(): Builder
    {
        return Answer::query()
            ->with(['question.test'])
            ->whereIn(
                'question_id',
                QuestionResource::getManageableQuery()->select('id')
            );
    }

    public static function canManageAnswer(Answer $record): bool
    {
        $record->loadMissing('question.test');

        if (! $record->question) {
            return false;
        }

        return QuestionResource::canManageQuestion(
            $record->question
        );
    }

    public static function canEdit($record): bool
    {
        return $record instanceof Answer
            && parent::canEdit($record)
            && static::canManageAnswer($record);
    }

    public static function canDelete($record): bool
    {
        return $record instanceof Answer
            && parent::canDelete($record)
            && static::canManageAnswer($record);
    }

    /**
     * Superadmin vidi sve.
     *
     * Organizacija vidi odgovore:
     * - globalnih testova
     * - testova svoje organizacije
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['question.test']);

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $userIds = TestResource::organizationUserIds();

        return $query->whereHas(
            'question.test',
            function (Builder $testQuery) use ($userIds): void {
                $testQuery->where(
                    function (Builder $query) use ($userIds): void {
                        $query
                            ->whereNull('user_id')
                            ->orWhereIn('user_id', $userIds);
                    }
                );
            }
        );
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
                            ->where(
                                'test_id',
                                $record->question->test_id
                            )
                            ->where(
                                'id',
                                '<=',
                                $record->question_id
                            )
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
                    ->tooltip(
                        fn (Answer $record): ?string =>
                            $record->tekst
                    )
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
            ->filters([
                SelectFilter::make('test_id')
                    ->label('Test')
                    ->relationship(
                        'question.test',
                        'naziv',
                        function (Builder $query): void {
                            $user = Auth::user();

                            if (! $user || $user->isSuperAdmin()) {
                                return;
                            }

                            $userIds = TestResource::organizationUserIds();

                            $query->where(
                                function (Builder $query) use ($userIds): void {
                                    $query
                                        ->whereNull('user_id')
                                        ->orWhereIn('user_id', $userIds);
                                }
                            );
                        }
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                EditAction::make()
                    ->label('Uredi')
                    ->visible(
                        fn (Answer $record): bool =>
                            static::canManageAnswer($record)
                    ),

                DeleteAction::make()
                    ->label('Obriši')
                    ->requiresConfirmation()
                    ->visible(
                        fn (Answer $record): bool =>
                            static::canManageAnswer($record)
                    ),
            ])
            ->bulkActions([]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
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

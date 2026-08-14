<?php

namespace App\Filament\Resources\Questions;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Questions\Pages;
use App\Filament\Resources\Questions\Schemas\QuestionForm;
use App\Filament\Resources\Tests\TestResource;
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

class QuestionResource extends BaseResource
{
    protected static ?string $model = Question::class;

    protected static bool $hasOwnership = false;

    protected static bool $superAdminCanCreate = true;

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

    /**
     * Pitanja koja trenutni korisnik smije mijenjati.
     *
     * Koristimo TestResource::getManageableQuery() tako da
     * ownership pravilo postoji na jednom mjestu.
     */
    public static function getManageableQuery(): Builder
    {
        return Question::query()
            ->with('test')
            ->whereIn(
                'test_id',
                TestResource::getManageableQuery()->select('id')
            );
    }

    public static function canManageQuestion(Question $record): bool
    {
        $record->loadMissing('test');

        if (! $record->test) {
            return false;
        }

        return TestResource::canManageTest($record->test);
    }

    public static function canEdit($record): bool
    {
        return $record instanceof Question
            && parent::canEdit($record)
            && static::canManageQuestion($record);
    }

    public static function canDelete($record): bool
    {
        return $record instanceof Question
            && parent::canDelete($record)
            && static::canManageQuestion($record);
    }

    /**
     * Superadmin vidi sva pitanja.
     *
     * Organizacija vidi pitanja:
     * - globalnih testova
     * - vlastitih organizacijskih testova
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with('test')
            ->withCount('answers')
            ->orderBy('test_id')
            ->orderBy('id');

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $userIds = TestResource::organizationUserIds();

        return $query->whereHas(
            'test',
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
            ->columns([
                TextColumn::make('question_number')
                    ->label('Br. pitanja')
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->state(function (Question $record): int {
                        return Question::query()
                            ->where('test_id', $record->test_id)
                            ->where('id', '<=', $record->id)
                            ->count();
                    })
                    ->sortable(false)
                    ->toggleable(),

                TextColumn::make('test.naziv')
                    ->label('Test')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                static::userTableColumn(),

                TextColumn::make('tekst')
                    ->label('Pitanje')
                    ->limit(80)
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('answers_count')
                    ->label('Broj odgovora')
                    ->counts('answers')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                IconColumn::make('visestruki_odgovori')
                    ->label('Više odgovora')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('test_id')
                    ->label('Test')
                    ->relationship(
                        'test',
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
            ->defaultSort('test_id')
            ->actions([
                EditAction::make()
                    ->label('Uredi')
                    ->visible(
                        fn (Question $record): bool =>
                            static::canManageQuestion($record)
                    ),

                DeleteAction::make()
                    ->label('Obriši')
                    ->requiresConfirmation()
                    ->visible(
                        fn (Question $record): bool =>
                            static::canManageQuestion($record)
                    ),
            ])
            ->bulkActions([])
            ->paginated([5, 10, 25, 50, 'all'])
            ->defaultPaginationPageOption(10);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()
            ->reorder()
            ->count();
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

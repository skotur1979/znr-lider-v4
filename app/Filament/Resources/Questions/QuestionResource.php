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
    protected static bool $hasOwnership = false;

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
    TextColumn::make('question_number')
        ->label('Br. pitanja')
        ->alignCenter()
        ->badge()
        ->color('warning')
        ->state(function (Question $record): int {
            return Question::query()
                ->where('test_id', $record->test_id)
                ->where('id', '<=', $record->id)
                ->orderBy('id')
                ->count();
        })
        ->sortable(false)
        ->toggleable(),

    TextColumn::make('test.naziv')
        ->label('Test')
        ->sortable()
        ->searchable()
        ->toggleable(),

    static::userTableColumn()
        ->toggleable(),

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
                EditAction::make()
            ->label('Uredi')
            ->visible(fn (Question $record): bool =>
                Auth::user()?->isSuperAdmin()
                || ($record->test?->user_id !== null)
            ),

            DeleteAction::make()
            ->label('Obriši')
            ->visible(fn (Question $record): bool =>
                Auth::user()?->isSuperAdmin()
                || ($record->test?->user_id !== null)
            ),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Obriši označeno'),
            ]);
    }

    protected static function organizationUserIds(): array
{
    $user = Auth::user();

    if (! $user) {
        return [];
    }

    $ownerId = method_exists($user, 'ownerId')
        ? $user->ownerId()
        : ($user->parent_user_id ?: $user->id);

    return \App\Models\User::query()
        ->where('id', $ownerId)
        ->orWhere('parent_user_id', $ownerId)
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
}

public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()
        ->with('test')
        ->withCount('answers')
        ->orderBy('test_id')
        ->orderBy('id');

    if (Auth::user()?->isSuperAdmin()) {
        return $query;
    }

    $userIds = static::organizationUserIds();

    return $query->where(function (Builder $q) use ($userIds): void {
        $q->whereNull('user_id')
            ->orWhereIn('user_id', $userIds)
            ->orWhereHas('test', function (Builder $testQuery) use ($userIds): void {
                $testQuery->whereNull('user_id')
                    ->orWhereIn('user_id', $userIds);
            });
    });
}

public static function getNavigationBadge(): ?string
{
    $query = static::getModel()::query();

    if (! Auth::user()?->isSuperAdmin()) {
        $userIds = static::organizationUserIds();

        $query->where(function (Builder $q) use ($userIds): void {
            $q->whereNull('user_id')
                ->orWhereIn('user_id', $userIds)
                ->orWhereHas('test', function (Builder $testQuery) use ($userIds): void {
                    $testQuery->whereNull('user_id')
                        ->orWhereIn('user_id', $userIds);
                });
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

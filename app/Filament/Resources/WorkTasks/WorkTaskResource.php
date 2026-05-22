<?php

namespace App\Filament\Resources\WorkTasks;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\WorkTasks\Pages\CreateWorkTask;
use App\Filament\Resources\WorkTasks\Pages\EditWorkTask;
use App\Filament\Resources\WorkTasks\Pages\ListWorkTasks;
use App\Models\WorkTask;
use App\Services\ActivityLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;

class WorkTaskResource extends BaseResource
{
    protected static ?string $model = WorkTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected static ?string $slug = 'radni-zadaci';
    protected static ?string $modelLabel = 'radni zadatak';
    protected static ?string $pluralModelLabel = 'radni zadaci';
    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 50;

    protected static function getModuleKey(): ?string
    {
        return 'work_tasks';
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Hidden::make('user_id')
                    ->default(fn () => Auth::user()?->ownerId())
                    ->dehydrated(),

                Section::make('Radni zadatak')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Naziv zadatka')
                            ->required()
                            ->maxLength(120),

                        DatePicker::make('due_date')
                            ->label('Datum')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y.'),

                        Textarea::make('description')
                            ->label('Opis')
                            ->rows(5)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 'all'])
            ->modifyQueryUsing(fn (Builder $query) => $query->latest('due_date')->latest('id'))
            ->columns([
    TextColumn::make('title')
        ->label('Zadatak')
        ->searchable()
        ->wrap()
        ->weight('bold')
        ->toggleable(),

    static::userTableColumn()
        ->toggleable(),

    TextColumn::make('description')
        ->label('Opis')
        ->limit(80)
        ->wrap()
        ->toggleable(),

    TextColumn::make('due_date')
        ->label('Datum')
        ->date('d.m.Y.')
        ->badge()
        ->color(function (WorkTask $record): string {
            if ($record->is_done) {
                return 'success';
            }

            if ($record->due_date?->isPast()) {
                return 'danger';
            }

            if ($record->due_date?->isToday()) {
                return 'warning';
            }

            return 'info';
        })
        ->toggleable(),

    IconColumn::make('is_done')
        ->label('Riješeno')
        ->boolean()
        ->toggleable(),

    TextColumn::make('completed_at')
        ->label('Zatvoreno')
        ->dateTime('d.m.Y. H:i')
        ->placeholder('-')
        ->toggleable(isToggledHiddenByDefault: true),
])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Otvoreni',
                        'closed' => 'Zatvoreni',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'closed' => $query->where('is_done', true),
                            'open' => $query->where('is_done', false),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('close')
                    ->label('Zatvori')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (WorkTask $record): bool => ! $record->is_done)
                    ->requiresConfirmation()
                    ->action(function (WorkTask $record): void {
                        $record->update([
                            'is_done' => true,
                            'completed_at' => now(),
                        ]);

                        ActivityLogger::status(
                            module: 'Radni zadaci',
                            title: 'Radni zadatak zatvoren',
                            description: 'Zatvoren je radni zadatak: ' . $record->title,
                            record: $record,
                        );

                        Notification::make()
                            ->title('Radni zadatak je zatvoren.')
                            ->success()
                            ->send();
                    }),

                Action::make('reopen')
                    ->label('Vrati u otvorene')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (WorkTask $record): bool => (bool) $record->is_done)
                    ->requiresConfirmation()
                    ->action(function (WorkTask $record): void {
                        $record->update([
                            'is_done' => false,
                            'completed_at' => null,
                        ]);

                        ActivityLogger::status(
                            module: 'Radni zadaci',
                            title: 'Radni zadatak vraćen u otvorene',
                            description: 'Radni zadatak je vraćen u otvorene: ' . $record->title,
                            record: $record,
                        );

                        Notification::make()
                            ->title('Radni zadatak je vraćen u otvorene.')
                            ->success()
                            ->send();
                    }),

                EditAction::make()->label('Uredi'),

                DeleteAction::make()->label('Obriši'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('bulk_close')
                    ->label('Zatvori označeno')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (EloquentCollection $records): bool => $records->contains(
                        fn (WorkTask $record): bool => ! $record->is_done
                    ))
                    ->action(function (EloquentCollection $records): void {
                        $count = 0;

                        $records->each(function (WorkTask $record) use (&$count): void {
                            if (! $record->is_done) {
                                $record->update([
                                    'is_done' => true,
                                    'completed_at' => now(),
                                ]);

                                $count++;
                            }
                        });

                        if ($count > 0) {
                            ActivityLogger::status(
                                module: 'Radni zadaci',
                                title: 'Zatvoreni označeni radni zadaci',
                                description: "Zatvoreno zadataka: {$count}.",
                            );
                        }

                        Notification::make()
                            ->title("Zatvoreno zadataka: {$count}")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('bulk_reopen')
                    ->label('Vrati označeno u otvorene')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (EloquentCollection $records): bool => $records->contains(
                        fn (WorkTask $record): bool => (bool) $record->is_done
                    ))
                    ->action(function (EloquentCollection $records): void {
                        $count = 0;

                        $records->each(function (WorkTask $record) use (&$count): void {
                            if ($record->is_done) {
                                $record->update([
                                    'is_done' => false,
                                    'completed_at' => null,
                                ]);

                                $count++;
                            }
                        });

                        if ($count > 0) {
                            ActivityLogger::status(
                                module: 'Radni zadaci',
                                title: 'Radni zadaci vraćeni u otvorene',
                                description: "Vraćeno u otvorene: {$count}.",
                            );
                        }

                        Notification::make()
                            ->title("Vraćeno u otvorene: {$count}")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
        ->with('user');

        if (Auth::user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::user()?->ownerId());
    }

    public static function getNavigationBadge(): ?string
{
    $user = Auth::user();

    if (! $user) {
        return null;
    }

    $cacheKey = 'work_tasks_badge_'
        . $user->id
        . '_'
        . now()->format('Y-m-d-H');

    return cache()->remember($cacheKey, now()->addMinutes(5), function () use ($user) {

        $query = static::getModel()::query();

        if (! $user->isSuperAdmin()) {
            $query->where('user_id', $user->ownerId());
        }

        return (string) $query->count();
    });
}

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::user()?->ownerId();
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::user()?->ownerId();
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkTasks::route('/'),
            'create' => CreateWorkTask::route('/create'),
            'edit' => EditWorkTask::route('/{record}/edit'),
        ];
    }
}
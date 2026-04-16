<?php

namespace App\Filament\Resources\WorkTasks;

use App\Filament\Resources\WorkTasks\Pages\CreateWorkTask;
use App\Filament\Resources\WorkTasks\Pages\EditWorkTask;
use App\Filament\Resources\WorkTasks\Pages\ListWorkTasks;
use App\Models\WorkTask;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;

class WorkTaskResource extends Resource
{
    protected static ?string $model = WorkTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $slug = 'radni-zadaci';

    protected static ?string $modelLabel = 'radni zadatak';
    protected static ?string $pluralModelLabel = 'radni zadaci';
    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 50;

    /*protected static bool $shouldRegisterNavigation = false;*/

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Radni zadatak')
                ->schema([
                    TextInput::make('title')
                        ->label('Naziv zadatka')
                        ->required()
                        ->maxLength(120)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Opis')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    DatePicker::make('due_date')
                        ->label('Datum')
                        ->required()
                        ->native(false)
                        ->displayFormat('d.m.Y.'),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if (! Auth::user()?->isAdmin()) {
                    $query->where('user_id', Auth::id());
                }

                return $query->latest('due_date')->latest('id');
            })
            ->columns([
                TextColumn::make('title')
                    ->label('Zadatak')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

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
                    }),

                IconColumn::make('is_done')
                    ->label('Riješeno')
                    ->boolean(),

                TextColumn::make('completed_at')
                    ->label('Zatvoreno')
                    ->dateTime('d.m.Y. H:i')
                    ->placeholder('-')
                    ->toggleable(),
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

                        Notification::make()
                            ->title('Radni zadatak je zatvoren.')
                            ->success()
                            ->send();
                    }),

                Action::make('reopen')
                    ->label('Vrati u otvorene')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (WorkTask $record): bool => $record->is_done)
                    ->requiresConfirmation()
                    ->action(function (WorkTask $record): void {
                        $record->update([
                            'is_done' => false,
                            'completed_at' => null,
                        ]);

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

                        Notification::make()
                            ->title("Vraćeno u otvorene: {$count}")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('due_date', 'asc');
    }
public static function shouldRegisterNavigation(): bool
{
    $user = Auth::user();

    return $user?->isSuperAdmin() || $user?->canAccessModule('work_tasks');
}

public static function canViewAny(): bool
{
    return static::shouldRegisterNavigation();
}
public static function getNavigationBadge(): ?string
    {
        $q = static::getModel()::query();

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return (string) $q->count();
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
<?php

namespace App\Filament\Resources\OperationalLogs;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\OperationalLogs\Pages;
use App\Models\OperationalLog;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OperationalLogResource extends BaseResource
{
    protected static ?string $model = OperationalLog::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static ?string $slug = 'operativni-dnevnik';
    protected static ?string $navigationLabel = 'Operativni dnevnik';
    protected static ?string $modelLabel = 'zapis dnevnika';
    protected static ?string $pluralModelLabel = 'operativni dnevnik';
    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => Auth::user()?->ownerId())
                ->dehydrated(),

            Section::make('Operativni dnevnik')
                ->description('Brzi zapis iz pogona. Ako odabereš radni zadatak, zapis će se automatski dodati i u Radne zadatke.')
                ->columns(1)
                ->schema([
                    DatePicker::make('log_date')
                        ->label('Datum')
                        ->default(now())
                        ->required()
                        ->native(false)
                        ->displayFormat('d.m.Y.'),

                    Textarea::make('note')
                        ->label('Bilješka')
                        ->rows(6)
                        ->required()
                        ->columnSpanFull(),

                    Select::make('type')
                        ->label('Vrsta zapisa')
                        ->options([
                            'note' => 'Samo zapis',
                            'task' => 'Radni zadatak',
                        ])
                        ->default('note')
                        ->native(false)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 'all'])
            ->defaultSort('log_date', 'desc')
            ->groups([
                Group::make('log_date')
                    ->label('Dan')
                    ->date(),
            ])
            ->columns([
                TextColumn::make('log_date')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable(),

                static::userTableColumn(),

                TextColumn::make('note')
                    ->label('Bilješka')
                    ->limit(140)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Vrsta')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'task' => 'Radni zadatak',
                        default => 'Samo zapis',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'task' => 'warning',
                        default => 'info',
                    }),

                TextColumn::make('converted_type')
                    ->label('Pretvoreno')
                    ->badge()
                    ->formatStateUsing(fn ($state) => filled($state) ? 'Da' : 'Ne')
                    ->color(fn ($state) => filled($state) ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Uneseno')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Vrsta')
                    ->options([
                        'note' => 'Samo zapis',
                        'task' => 'Radni zadatak',
                    ]),

                Filter::make('log_date')
                    ->label('Datum')
                    ->form([
                        DatePicker::make('from')
                            ->label('Od')
                            ->native(false)
                            ->displayFormat('d.m.Y.'),

                        DatePicker::make('until')
                            ->label('Do')
                            ->native(false)
                            ->displayFormat('d.m.Y.'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('log_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('log_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Obriši'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::user()?->ownerId());
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::user()?->ownerId();
        }

        $data['log_date'] = $data['log_date'] ?? now()->toDateString();
        $data['status'] = $data['type'] === 'task' ? 'converted' : 'recorded';

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::user()?->ownerId();
        }

        $data['status'] = $data['type'] === 'task' ? 'converted' : 'recorded';

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOperationalLogs::route('/'),
            'create' => Pages\CreateOperationalLog::route('/create'),
            'edit' => Pages\EditOperationalLog::route('/{record}/edit'),
        ];
    }
}
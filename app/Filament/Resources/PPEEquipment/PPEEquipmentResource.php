<?php

namespace App\Filament\Resources\PPEEquipment;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\PPEEquipment\Pages;
use App\Models\PPEEquipment;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PPEEquipmentResource extends BaseResource
{
    protected static ?string $model = PPEEquipment::class;

    protected static bool $hasOwnership = false;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';
    protected static string|UnitEnum|null $navigationGroup = 'Zaposlenici';
    protected static ?string $navigationLabel = 'Registar OZO';
    protected static ?string $modelLabel = 'OZO oprema';
    protected static ?string $pluralModelLabel = 'Registar OZO';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'registar-ozo';

    protected static function getModuleKey(): ?string
    {
        return 'ppe_logs';
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    protected static function isCurrentUserSuperAdmin(): bool
    {
        $user = auth()->user();

        return $user?->isSuperAdmin() === true;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (static::isCurrentUserSuperAdmin()) {
            return $query;
        }

        $ownerId = $user->ownerId();

        return $query->where(function (Builder $query) use ($ownerId) {
            $query->whereNull('user_id')
                ->orWhere('user_id', $ownerId);
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Podaci o OZO opremi')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Hidden::make('user_id')
                        ->default(fn () => static::isCurrentUserSuperAdmin() ? null : static::ownerId())
                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),

                    TextInput::make('name')
                        ->label('Naziv OZO')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('standard')
                        ->label('HRN EN / Norma')
                        ->maxLength(255),

                    TextInput::make('duration_months')
                        ->label('Rok uporabe (mjeseci)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(240),

                    Toggle::make('is_active')
                        ->label('Aktivno')
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 'all'])
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv OZO')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('standard')
                    ->label('HRN EN / Norma')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('duration_months')
                    ->label('Rok uporabe (mj.)')
                    ->alignCenter()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktivno')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('scope_label')
                    ->label('Vrsta zapisa')
                    ->badge()
                    ->alignCenter()
                    ->state(fn (PPEEquipment $record): string => $record->user_id === null ? 'Globalno' : 'Organizacija')
                    ->color(fn (PPEEquipment $record): string => $record->user_id === null ? 'success' : 'info'),

                static::userTableColumn(),
            ])
            ->filters([
        SelectFilter::make('scope')
            ->label('Vrsta zapisa')
            ->options([
                'global' => 'Globalno',
                'organization' => 'Organizacija',
            ])
            ->query(function (Builder $query, array $data): Builder {
                return match ($data['value'] ?? null) {
                    'global' => $query->whereNull('user_id'),
                    'organization' => $query->whereNotNull('user_id'),
                    default => $query,
                };
            }),

        SelectFilter::make('user_id')
            ->label('Korisnik')
            ->relationship(
                'user',
                'name',
                fn (Builder $query) => $query->orderBy('name')
            )
            ->searchable()
            ->preload()
            ->visible(fn () => static::isCurrentUserSuperAdmin()),
    ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (PPEEquipment $record): bool => static::canModifyRecord($record)),

                    DeleteAction::make()
                        ->label('Izbriši')
                        ->requiresConfirmation()
                        ->visible(fn (PPEEquipment $record): bool => static::canModifyRecord($record)),
                ]),
            ])
            ->bulkActions([
    DeleteBulkAction::make()
        ->label('Izbriši označeno')
        ->requiresConfirmation()
        ->action(function ($records) {
            $blocked = $records->filter(fn (PPEEquipment $record) => ! static::canModifyRecord($record));
            $allowed = $records->filter(fn (PPEEquipment $record) => static::canModifyRecord($record));

            if ($allowed->isNotEmpty()) {
                $allowed->each->delete();
            }

            if ($blocked->isNotEmpty()) {
                Notification::make()
                    ->title('Neki zapisi nisu obrisani')
                    ->body('Globalne OZO zapise može brisati samo superadmin.')
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Označeni zapisi su obrisani')
                    ->success()
                    ->send();
            }
        }),
]);
    }

    public static function canModifyRecord(PPEEquipment $record): bool
    {
        if (static::isCurrentUserSuperAdmin()) {
            return true;
        }

        return $record->user_id !== null
            && (int) $record->user_id === (int) static::ownerId();
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof PPEEquipment
            && static::canModifyRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof PPEEquipment
            && static::canModifyRecord($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPPEEquipment::route('/'),
            'create' => Pages\CreatePPEEquipment::route('/create'),
            'edit' => Pages\EditPPEEquipment::route('/{record}/edit'),
        ];
    }
}
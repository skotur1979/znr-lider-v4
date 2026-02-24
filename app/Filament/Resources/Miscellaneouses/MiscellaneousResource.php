<?php

namespace App\Filament\Resources\Miscellaneouses;

use App\Filament\Resources\Miscellaneouses\Pages;
use App\Models\Category;
use App\Models\Miscellaneous;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MiscellaneousResource extends Resource
{
    protected static ?string $model = Miscellaneous::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationLabel = 'Ostala ispitivanja';
    protected static ?string $modelLabel = 'Ispitivanje';
    protected static ?string $pluralModelLabel = 'Ispitivanja';
    protected static ?int $navigationSort = 4;
    protected static \UnitEnum|string|null $navigationGroup = 'Ispitivanja';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Podatci o predmetu')
                ->schema([
                    TextInput::make('name')
                        ->label('Naziv (obavezno)')
                        ->required()
                        ->maxLength(255),

                    Select::make('category_id')
                        ->label('Kategorija')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $q = Category::query();

                            if (! Auth::user()?->isAdmin()) {
                                $q->where('user_id', Auth::id());
                            }

                            return $q->orderBy('name')->pluck('name', 'id')->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search) {
                            $q = Category::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orderBy('name')
                                ->limit(50);

                            if (! Auth::user()?->isAdmin()) {
                                $q->where('user_id', Auth::id());
                            }

                            return $q->pluck('name', 'id')->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value) => Category::find($value)?->name)

                        // ✅ + dodavanje nove kategorije direktno iz selecta
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Naziv kategorije')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $category = Category::create([
                                'name' => $data['name'],
                                'user_id' => Auth::id(),
                            ]);

                            return $category->id;
                        }),

                    TextInput::make('examiner')
                        ->label('Ispitao')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('report_number')
                        ->label('Broj izvještaja')
                        ->maxLength(255)
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Ispitivanje')
                ->schema([
                    DatePicker::make('examination_valid_from')
                        ->label('Vrijedi od (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('examination_valid_until')
                        ->label('Vrijedi do (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),
                ])
                ->columns(2),

            Section::make('Napomena')
                ->schema([
                    Textarea::make('remark')
                        ->label('Napomena')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Prilozi')
                ->schema([
                    FileUpload::make('pdf')
                        ->label('Dodaj priloge (max. 5)')
                        ->disk('public')
                        ->directory('pdfs')
                        ->multiple()
                        ->maxFiles(5)
                        ->maxSize(30720) // 30 MB (KB)
                        ->preserveFilenames()
                        ->enableOpen()
                        ->enableDownload()
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'application/zip',
                            'application/x-rar-compressed',
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('examination_valid_until', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Kategorija')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->alignCenter(),

                TextColumn::make('examiner')
                    ->label('Ispitao')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('examination_valid_from')
                    ->label('Datum ispitivanja')
                    ->date('d.m.Y')
                    ->sortable()
                    ->alignCenter(),

                // ✅ expiry badge kao na Fires
                TextColumn::make('examination_valid_until')
                    ->label('Ispitivanje vrijedi do')
                    ->date('d.m.Y')
                    ->badge()
                    ->icon(fn ($state) => self::expiryIcon($state))
                    ->color(fn ($state) => self::expiryColor($state))
                    ->tooltip(fn ($state) => self::expiryTooltip($state))
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('remark')
                    ->label('Napomena')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('pdf')
                    ->label('Prilozi')
                    ->alignCenter()
                    ->badge()
                    ->icon(function (Miscellaneous $record) {
                        $count = is_array($record->pdf) ? count($record->pdf) : 0;
                        return $count > 0 ? 'heroicon-o-paper-clip' : null;
                    })
                    ->color(function (Miscellaneous $record) {
                        $count = is_array($record->pdf) ? count($record->pdf) : 0;
                        return $count > 0 ? 'info' : 'gray';
                    })
                    ->state(fn (Miscellaneous $record) => is_array($record->pdf) ? count($record->pdf) : 0)
                    ->tooltip(function (Miscellaneous $record) {
                        if (! is_array($record->pdf) || count($record->pdf) === 0) {
                            return 'Nema priloga';
                        }

                        return implode("\n", $record->pdf);
                    }),
            ])
            ->filters([
                // ✅ status filter (active/trashed/all)
                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder('Odaberi status')
                    ->options([
                        'active'  => 'Aktivni zapisi',
                        'trashed' => 'Deaktivirani zapisi',
                        'all'     => 'Svi zapisi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'trashed' => $query->onlyTrashed(),
                            'all'     => $query->withTrashed(),
                            default   => $query->withoutTrashed(),
                        };
                    }),

                SelectFilter::make('category_id')
                    ->label('Kategorije')
                    ->options(function () {
                        $q = Category::query();

                        if (! Auth::user()?->isAdmin()) {
                            $q->where('user_id', Auth::id());
                        }

                        return $q->orderBy('name')->pluck('name', 'id')->toArray();
                    })
                    ->searchable(),

                Filter::make('examination_validity_expired')
                    ->label('Ispitivanje (isteklo)')
                    ->query(fn (Builder $query) => $query->whereDate('examination_valid_until', '<', Carbon::today())),

                Filter::make('examination_validity_expiring')
                    ->label('Ispitivanje (uskoro ističe)')
                    ->query(fn (Builder $query) => $query
                        ->whereDate('examination_valid_until', '>=', Carbon::today())
                        ->whereDate('examination_valid_until', '<=', Carbon::today()->addDays(30))),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    DeleteAction::make()
                        ->requiresConfirmation(),

                    RestoreAction::make()
                        ->visible(fn (Miscellaneous $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->visible(fn (Miscellaneous $record) => method_exists($record, 'trashed') && $record->trashed())
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
            ->requiresConfirmation()
            ->modalHeading('Deaktiviraj odabrano')
            ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
            ->modalSubmitActionLabel('Deaktiviraj')
            ->modalCancelActionLabel('Odustani')
                    ->visible(fn (HasTable $livewire) => ! self::isOnlyTrashed($livewire)),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
            ->requiresConfirmation()
            ->modalHeading('Vrati odabrano')
            ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
            ->modalSubmitActionLabel('Vrati')
            ->modalCancelActionLabel('Odustani')
                    ->visible(fn (HasTable $livewire) => self::isOnlyTrashed($livewire)),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
            ->requiresConfirmation()
            ->modalHeading('Trajno obriši odabrano')
            ->modalDescription('Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.')
            ->modalSubmitActionLabel('Trajno obriši')
            ->modalCancelActionLabel('Odustani')
            
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMiscellaneouses::route('/'),
            'create' => Pages\CreateMiscellaneous::route('/create'),
            'view'   => Pages\ViewMiscellaneous::route('/{record}'),
            'edit'   => Pages\EditMiscellaneous::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        return Auth::user()?->isAdmin()
            ? $query
            : $query->where('user_id', Auth::id());
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $q = static::getModel()::query();

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return (string) $q->count();
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');
        $value = data_get($state, 'value');

        return $value === 'trashed';
    }

    private static function expiryColor($state): string
    {
        if (! $state) return 'gray';

        $d = Carbon::parse($state);

        if ($d->lt(Carbon::today())) return 'danger';

        $diff = Carbon::today()->diffInDays($d, false);
        return $diff <= 30 ? 'warning' : 'success';
    }

    private static function expiryIcon($state): ?string
    {
        if (! $state) return 'heroicon-o-minus-circle';

        $d = Carbon::parse($state);

        if ($d->lt(Carbon::today())) return 'heroicon-o-x-circle';

        $diff = Carbon::today()->diffInDays($d, false);
        return $diff <= 30 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle';
    }

    private static function expiryTooltip($state): string
    {
        if (! $state) return 'Nema roka';

        $d = Carbon::parse($state);

        if ($d->lt(Carbon::today())) return 'Rok je istekao';

        $diff = Carbon::today()->diffInDays($d, false);
        return $diff <= 30 ? 'Rok uskoro ističe' : 'Rok je važeći';
    }
}
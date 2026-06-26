<?php

namespace App\Filament\Resources\Miscellaneouses;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Miscellaneouses\Pages;
use App\Models\Category;
use App\Models\Miscellaneous;
use App\Services\StorageQuotaService;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Filament\Forms\Components\Hidden;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Schemas\Components\Grid;

class MiscellaneousResource extends BaseResource
{
    protected static ?string $model = Miscellaneous::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationLabel = 'Ostala ispitivanja';
    protected static ?string $modelLabel = 'Ispitivanje';
    protected static ?string $pluralModelLabel = 'Ispitivanja';
    protected static ?int $navigationSort = 4;
    protected static \UnitEnum|string|null $navigationGroup = 'Ispitivanja';

    protected static function getModuleKey(): ?string
    {
        return 'miscellaneous';
    }
    public static function getMaxContentWidth(): MaxWidth|string|null
{
    return MaxWidth::Full;
}

    public static function form(Schema $schema): Schema
{
    return $schema
        ->schema([
            Select::make('user_id')
                ->label('Korisnik')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn (string $operation): bool => static::isSuperAdmin() && $operation === 'create')
                ->dehydrated(fn (string $operation): bool => static::isSuperAdmin() && $operation === 'create'),

            Hidden::make('user_id')
                ->default(fn () => static::ownerId())
                ->visible(fn (string $operation): bool => ! static::isSuperAdmin() && $operation === 'create')
                ->dehydrated(fn (string $operation): bool => ! static::isSuperAdmin() && $operation === 'create'),

            Section::make('Podaci o ispitivanju')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Section::make('Podaci o predmetu')
                        ->columns(2)
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
                                ->options(fn () => static::getCategoryOptions())
                                ->getSearchResultsUsing(fn (string $search) => static::getCategorySearchResults($search))
                                ->getOptionLabelUsing(fn ($value) => Category::find($value)?->name)
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->label('Naziv kategorije')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    $category = Category::create([
                                        'name' => $data['name'],
                                        'user_id' => Auth::user()?->ownerId(),
                                    ]);

                                    return $category->id;
                                }),

                            TextInput::make('examiner')
                                ->label('Ispitao')
                                ->maxLength(255),

                            TextInput::make('report_number')
                                ->label('Broj izvještaja')
                                ->maxLength(255)
                                ->nullable()
                                ->rule(function ($record) {
                                    return Rule::unique('miscellaneouses', 'report_number')
                                        ->where(function ($query) {
                                            $query->where('user_id', Auth::user()?->ownerId())
                                                ->whereNull('deleted_at');
                                        })
                                        ->ignore($record?->id);
                                })
                                ->validationMessages([
                                    'unique' => 'Već postoji zapis s istim brojem izvještaja.',
                                ]),
                        ]),

                    Section::make('Ispitivanje')
                        ->columns(2)
                        ->schema([
                            DatePicker::make('examination_valid_from')
                                ->label('Vrijedi od (obavezno)')
                                ->required()
                                ->displayFormat('d.m.Y.')
                                ->weekStartsOnMonday()
                                ->timezone('Europe/Zagreb')
                                ->native(false),

                            DatePicker::make('examination_valid_until')
                                ->label('Vrijedi do (obavezno)')
                                ->required()
                                ->displayFormat('d.m.Y.')
                                ->weekStartsOnMonday()
                                ->timezone('Europe/Zagreb')
                                ->native(false),
                        ]),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            Section::make('Napomena')
                                ->extraAttributes([
                                    'style' => 'height:100%;',
                                ])
                                ->schema([
                                    Textarea::make('remark')
                                        ->label('Napomena')
                                        ->rows(7)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Prilozi')
                                ->extraAttributes([
                                    'style' => 'height:100%;',
                                ])
                                ->schema([
                            FileUpload::make('pdf')
                                ->label('Dodaj priloge (max. 5, do 30 MB po datoteci)')
                                ->disk('public')
                                ->directory('pdfs')
                                ->multiple()
                                ->maxFiles(5)
                                ->maxSize(30720)
                                ->preserveFilenames()
                                ->openable()
                                ->downloadable()

                                ->helperText(function () {
                                    $ownerId = auth()->user()?->ownerId();

                                    if (! $ownerId) {
                                        return null;
                                    }

                                    return 'Iskorištenost prostora organizacije: '
                                        . app(StorageQuotaService::class)->usageText($ownerId);
                                })

                                ->rules([
                                    function () {
                                        return function (
                                            string $attribute,
                                            mixed $value,
                                            \Closure $fail
                                        ) {
                                            $ownerId = auth()->user()?->ownerId();

                                            if (! $ownerId) {
                                                return;
                                            }

                                            if (! app(StorageQuotaService::class)
                                                ->canUpload($value, $ownerId)) {

                                                $fail(
                                                    'Dosegnut je maksimalni prostor za pohranu dokumenata organizacije. '
                                                    .'Obrišite nepotrebne priloge ili kontaktirajte administratora.'
                                                );
                                            }
                                        };
                                    },
                                ])

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
                                        ])
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ])
        ->columns(1);
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
        ->wrap()
        ->toggleable(),

    static::userTableColumn()
        ->toggleable(),

    TextColumn::make('category.name')
        ->label('Kategorija')
        ->sortable()
        ->searchable()
        ->wrap()
        ->alignCenter()
        ->toggleable(),

    TextColumn::make('examiner')
        ->label('Ispitao')
        ->sortable()
        ->alignCenter()
        ->toggleable(),

    TextColumn::make('examination_valid_from')
        ->label('Datum ispitivanja')
        ->date('d.m.Y')
        ->sortable()
        ->alignCenter()
        ->toggleable(),

    TextColumn::make('examination_valid_until')
        ->label('Ispitivanje vrijedi do')
        ->date('d.m.Y')
        ->badge()
        ->icon(fn ($state) => static::expiryIcon($state))
        ->color(fn ($state) => static::expiryColor($state))
        ->tooltip(fn ($state) => static::expiryTooltip($state))
        ->sortable()
        ->alignCenter()
        ->toggleable(),

    TextColumn::make('remark')
        ->label('Napomena')
        ->searchable()
        ->limit(60)
        ->toggleable(isToggledHiddenByDefault: true),

    TextColumn::make('pdf')
        ->label('Prilozi')
        ->alignment(Alignment::Center)
        ->html()
        ->state(function (Miscellaneous $record): string {
            if (! is_array($record->pdf) || count($record->pdf) === 0) {
                return '<span style="color:#6b7280;">0</span>';
            }

            return collect($record->pdf)
                ->map(function ($file, $index) {
                    $url = route('file.preview', [
                        'file' => ltrim($file, '/'),
                    ]);

                    $name = e(basename($file));
                    $number = $index + 1;

                    return '<a href="' . e($url) . '"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="' . $name . '"
                        onclick="event.preventDefault(); event.stopPropagation(); event.stopImmediatePropagation(); window.open(this.href, \'_blank\'); return false;"
                        style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            min-width:28px;
                            height:24px;
                            padding:0 8px;
                            margin:1px 2px;
                            border-radius:7px;
                            background:rgba(59,130,246,.15);
                            border:1px solid rgba(59,130,246,.35);
                            color:#93c5fd;
                            font-size:12px;
                            font-weight:700;
                            text-decoration:none;
                            cursor:pointer;
                        "
                    >📎 ' . $number . '</a>';
                })
                ->implode('');
        })
        ->tooltip(function (Miscellaneous $record): string {
            if (! is_array($record->pdf) || count($record->pdf) === 0) {
                return 'Nema priloga';
            }

            return collect($record->pdf)
                ->map(fn ($file, $index) => ($index + 1) . '. ' . basename($file))
                ->implode("\n");
        })
        ->toggleable(),
])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder('Odaberi status')
                    ->options([
                        'active' => 'Aktivni zapisi',
                        'trashed' => 'Deaktivirani zapisi',
                        'all' => 'Svi zapisi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            'trashed' => $query->onlyTrashed(),
                            'all' => $query->withTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    }),

                SelectFilter::make('category_id')
                    ->label('Kategorije')
                    ->options(fn () => static::getCategoryOptions())
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
            ->paginated([10, 25, 50, 100, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (Miscellaneous $record) => ! $record->trashed()),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (Miscellaneous $record) => ! $record->trashed()),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (Miscellaneous $record) => $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (Miscellaneous $record) => $record->trashed()),
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
                    ->visible(fn (HasTable $livewire) => ! static::isOnlyTrashed($livewire)),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Vrati odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->modalSubmitActionLabel('Vrati')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(fn (HasTable $livewire) => static::isOnlyTrashed($livewire)),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Trajno obriši odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.')
                    ->modalSubmitActionLabel('Trajno obriši')
                    ->modalCancelActionLabel('Odustani'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMiscellaneouses::route('/'),
            'create' => Pages\CreateMiscellaneous::route('/create'),
            'view' => Pages\ViewMiscellaneous::route('/{record}'),
            'edit' => Pages\EditMiscellaneous::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        if (Auth::user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::user()?->ownerId());
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where('user_id', Auth::user()?->ownerId());
        }

        return (string) $query->count();
    }

    private static function getCategoryOptions(): array
    {
        $query = Category::query();

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where('user_id', Auth::user()?->ownerId());
        }

        return $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    private static function getCategorySearchResults(string $search): array
    {
        $query = Category::query()
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(50);

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where('user_id', Auth::user()?->ownerId());
        }

        return $query->pluck('name', 'id')->toArray();
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');

        return data_get($state, 'value') === 'trashed';
    }

    private static function expiryColor($state): string
    {
        if (! $state) {
            return 'gray';
        }

        $date = Carbon::parse($state);

        if ($date->lt(Carbon::today())) {
            return 'danger';
        }

        $diff = Carbon::today()->diffInDays($date, false);

        return $diff <= 30 ? 'warning' : 'success';
    }

    private static function expiryIcon($state): ?string
    {
        if (! $state) {
            return 'heroicon-o-minus-circle';
        }

        $date = Carbon::parse($state);

        if ($date->lt(Carbon::today())) {
            return 'heroicon-o-x-circle';
        }

        $diff = Carbon::today()->diffInDays($date, false);

        return $diff <= 30 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle';
    }

    private static function expiryTooltip($state): string
    {
        if (! $state) {
            return 'Nema roka';
        }

        $date = Carbon::parse($state);

        if ($date->lt(Carbon::today())) {
            return 'Rok je istekao';
        }

        $diff = Carbon::today()->diffInDays($date, false);

        return $diff <= 30 ? 'Rok uskoro ističe' : 'Rok je važeći';
    }
}

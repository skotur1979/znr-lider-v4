<?php

namespace App\Filament\Resources\Miscellaneouses;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Miscellaneouses\Pages;
use App\Models\Category;
use App\Models\Miscellaneous;
use App\Support\SecureFilePreview;
use App\Services\StorageQuotaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MiscellaneousResource extends BaseResource
{
    protected static ?string $model = Miscellaneous::class;

    protected static \BackedEnum|string|null $navigationIcon =
        'heroicon-o-light-bulb';

    protected static ?string $navigationLabel =
        'Ostala ispitivanja';

    protected static ?string $modelLabel =
        'Ispitivanje';

    protected static ?string $pluralModelLabel =
        'Ispitivanja';

    protected static ?int $navigationSort = 4;

    protected static \UnitEnum|string|null $navigationGroup =
        'Ispitivanja';

    /**
     * Modul koristi SoftDeletes.
     */
    protected static bool $usesSoftDeletes = true;

    /**
     * Zapisi pripadaju cijeloj organizaciji.
     *
     * user_id = ownerId()
     */
    protected static bool $hasOwnership = true;

    protected static function getModuleKey(): ?string
    {
        return 'miscellaneous';
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    protected static function formOwnerId(
        ?Miscellaneous $record = null
    ): ?int {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        /*
         * Kod superadmin uređivanja koristimo ownera
         * postojećeg poslovnog zapisa.
         */
        if ($user->isSuperAdmin()) {
            return filled($record?->user_id)
                ? (int) $record->user_id
                : null;
        }

        return (int) $user->ownerId();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                /*
                 * user_id se više ne prikazuje niti šalje iz forme.
                 *
                 * CreateMiscellaneous koristi:
                 *
                 * MiscellaneousResource::fillOwnershipData($data)
                 *
                 * i BaseResource automatski postavlja:
                 *
                 * user_id = ownerId()
                 */

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
                                    ->options(
                                        fn ($record): array =>
                                            static::getCategoryOptions(
                                                $record
                                            )
                                    )
                                    ->getSearchResultsUsing(
                                        fn (
                                            string $search,
                                            $record
                                        ): array =>
                                            static::getCategorySearchResults(
                                                $search,
                                                $record
                                            )
                                    )
                                    ->getOptionLabelUsing(
                                        function (
                                            $value,
                                            $record
                                        ): ?string {
                                            $ownerId =
                                                static::formOwnerId(
                                                    $record
                                                );

                                            if (
                                                ! $value
                                                || ! $ownerId
                                            ) {
                                                return null;
                                            }

                                            return Category::query()
                                                ->whereKey($value)
                                                ->where(
                                                    'user_id',
                                                    $ownerId
                                                )
                                                ->value('name');
                                        }
                                    )
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Naziv kategorije')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(
                                        function (array $data): int {
                                            $user = Auth::user();
                                            if (
                                                ! $user
                                                || $user->isSuperAdmin()
                                            ) {
                                                throw ValidationException::withMessages([
                                                    'category_id' =>
                                                        'Super administrator ne kreira kategorije u ime organizacije.',
                                                ]);
                                            }
                                            /*
                                             * Kreiranje kategorije kroz
                                             * Ostala ispitivanja također
                                             * mora poštovati pravo CREATE
                                             * za modul Kategorije ispitivanja.
                                             */
                                            if (
                                                ! CategoryResource::allowsModulePermission(
                                                    'create'
                                                )
                                            ) {
                                                throw ValidationException::withMessages([
                                                    'category_id' =>
                                                        'Nemate ovlasti za dodavanje kategorije ispitivanja.',
                                                ]);
                                            }

                                            $ownerId = $user->ownerId();

                                            if (! $ownerId) {
                                                throw ValidationException::withMessages([
                                                    'category_id' =>
                                                        'Nije moguće odrediti organizaciju korisnika.',
                                                ]);
                                            }

                                            /*
                                             * Kategorija pripada cijeloj
                                             * organizaciji.
                                             */
                                            $category =
                                                Category::firstOrCreate([
                                                    'user_id' =>
                                                        $ownerId,
                                                    'name' =>
                                                        trim(
                                                            (string) $data['name']
                                                        ),
                                                ]);

                                            return (int) $category->id;
                                        }
                                    ),

                                TextInput::make('examiner')
                                    ->label('Ispitao')
                                    ->maxLength(255),

                                TextInput::make('report_number')
                                    ->label('Broj izvještaja')
                                    ->maxLength(255)
                                    ->nullable()
                                    ->rule(
                                    function ($record) {
                                        $ownerId =
                                            static::formOwnerId($record);

                                        return Rule::unique(
                                            'miscellaneouses',
                                            'report_number'
                                        )
                                            ->where(
                                                function ($query) use ($ownerId) {
                                                    $query
                                                        ->where(
                                                            'user_id',
                                                            $ownerId
                                                        )
                                                        ->whereNull(
                                                            'deleted_at'
                                                        );
                                                }
                                            )
                                                ->ignore(
                                                    $record?->id
                                                );
                                        }
                                    )
                                    ->validationMessages([
                                        'unique' =>
                                            'Već postoji zapis s istim brojem izvještaja.',
                                    ]),
                            ]),

                        Section::make('Ispitivanje')
                            ->columns(2)
                            ->schema([
                                DatePicker::make(
                                    'examination_valid_from'
                                )
                                    ->label(
                                        'Vrijedi od (obavezno)'
                                    )
                                    ->required()
                                    ->displayFormat('d.m.Y.')
                                    ->weekStartsOnMonday()
                                    ->timezone(
                                        'Europe/Zagreb'
                                    )
                                    ->native(false),

                                DatePicker::make(
                                    'examination_valid_until'
                                )
                                    ->label(
                                        'Vrijedi do (obavezno)'
                                    )
                                    ->required()
                                    ->displayFormat('d.m.Y.')
                                    ->weekStartsOnMonday()
                                    ->timezone(
                                        'Europe/Zagreb'
                                    )
                                    ->native(false),
                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                Section::make('Napomena')
                                    ->extraAttributes([
                                        'style' =>
                                            'height:100%;',
                                    ])
                                    ->schema([
                                        Textarea::make(
                                            'remark'
                                        )
                                            ->label(
                                                'Napomena'
                                            )
                                            ->rows(7)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Prilozi')
                                    ->extraAttributes([
                                        'style' =>
                                            'height:100%;',
                                    ])
                                    ->schema([
                                        FileUpload::make('pdf')
                                            ->label(
                                                'Dodaj priloge (max. 5, do 30 MB po datoteci)'
                                            )
                                            ->disk('public')
                                            ->directory('pdfs')
                                            ->multiple()
                                            ->maxFiles(5)
                                            ->maxSize(30720)
                                            ->preserveFilenames()
                                            ->openable()
                                            ->downloadable()

                                            ->helperText(
                                            function ($record) {
                                                $ownerId =
                                                    static::formOwnerId($record);

                                                if (! $ownerId) {
                                                    return null;
                                                }

                                                return
                                                    'Iskorištenost prostora organizacije: '
                                                    . app(
                                                        StorageQuotaService::class
                                                    )->usageText(
                                                        $ownerId
                                                        );
                                                }
                                            )

                                           ->rules([
                                            function ($record) {
                                                return function (
                                                    string $attribute,
                                                    mixed $value,
                                                    \Closure $fail
                                                ) use ($record) {
                                                    $ownerId =
                                                        static::formOwnerId($record);

                                                    if (! $ownerId) {
                                                        return;
                                                    }

                                                    if (
                                                        ! app(
                                                            StorageQuotaService::class
                                                        )->canUpload(
                                                            $value,
                                                            $ownerId
                                                        )
                                                    ) {
                                                        $fail(
                                                            'Dosegnut je maksimalni prostor za pohranu dokumenata organizacije. '
                                                            . 'Obrišite nepotrebne priloge ili kontaktirajte administratora.'
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
            ->defaultSort(
                'examination_valid_until',
                'desc'
            )

            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->toggleable(),

                /*
                 * Standardni prikaz korisnika iz
                 * BaseResource / HasUserTableColumn.
                 */
                static::userTableColumn(),

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

                TextColumn::make(
                    'examination_valid_from'
                )
                    ->label('Datum ispitivanja')
                    ->date('d.m.Y')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make(
                    'examination_valid_until'
                )
                    ->label(
                        'Ispitivanje vrijedi do'
                    )
                    ->date('d.m.Y')
                    ->badge()
                    ->icon(
                        fn ($state) =>
                            static::expiryIcon(
                                $state
                            )
                    )
                    ->color(
                        fn ($state) =>
                            static::expiryColor(
                                $state
                            )
                    )
                    ->tooltip(
                        fn ($state) =>
                            static::expiryTooltip(
                                $state
                            )
                    )
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('remark')
                    ->label('Napomena')
                    ->searchable()
                    ->limit(60)
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),

                TextColumn::make('pdf')
                    ->label('Prilozi')
                    ->alignment(
                        Alignment::Center
                    )
                    ->html()
                    ->state(
                        function (
                            Miscellaneous $record
                        ): string {
                            if (
                                ! is_array(
                                    $record->pdf
                                )
                                || count(
                                    $record->pdf
                                ) === 0
                            ) {
                                return
                                    '<span style="color:#6b7280;">0</span>';
                            }

                            return collect(
                                $record->pdf
                            )
                                ->map(
                                    function (
                                        $file,
                                        $index
                                    ) {
                                        $url = SecureFilePreview::url(
                                        $file
                                    );

                                        $name =
                                            e(
                                                basename(
                                                    $file
                                                )
                                            );

                                        $number =
                                            $index + 1;

                                        return
                                            '<a href="'
                                            . e($url)
                                            . '"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="'
                                            . $name
                                            . '"
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
                                            >📎 '
                                            . $number
                                            . '</a>';
                                    }
                                )
                                ->implode('');
                        }
                    )
                    ->tooltip(
                        function (
                            Miscellaneous $record
                        ): string {
                            if (
                                ! is_array(
                                    $record->pdf
                                )
                                || count(
                                    $record->pdf
                                ) === 0
                            ) {
                                return 'Nema priloga';
                            }

                            return collect(
                                $record->pdf
                            )
                                ->map(
                                    fn (
                                        $file,
                                        $index
                                    ) =>
                                        ($index + 1)
                                        . '. '
                                        . basename(
                                            $file
                                        )
                                )
                                ->implode("\n");
                        }
                    )
                    ->toggleable(),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder(
                        'Odaberi status'
                    )
                    ->options([
                        'active' =>
                            'Aktivni zapisi',

                        'trashed' =>
                            'Deaktivirani zapisi',

                        'all' =>
                            'Svi zapisi',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ) {
                            return match (
                                $data['value']
                                ?? null
                            ) {
                                'trashed' =>
                                    $query
                                        ->onlyTrashed(),

                                'all' =>
                                    $query
                                        ->withTrashed(),

                                default =>
                                    $query
                                        ->withoutTrashed(),
                            };
                        }
                    ),

                SelectFilter::make(
                    'category_id'
                )
                    ->label('Kategorije')
                    ->options(
                        fn (): array =>
                            static::getCategoryFilterOptions()
                    )
                    ->searchable(),

                Filter::make(
                    'examination_validity_expired'
                )
                    ->label(
                        'Ispitivanje (isteklo)'
                    )
                    ->query(
                        fn (
                            Builder $query
                        ) =>
                            $query->whereDate(
                                'examination_valid_until',
                                '<',
                                Carbon::today()
                            )
                    ),

                Filter::make(
                    'examination_validity_expiring'
                )
                    ->label(
                        'Ispitivanje (uskoro ističe)'
                    )
                    ->query(
                        fn (
                            Builder $query
                        ) =>
                            $query
                                ->whereDate(
                                    'examination_valid_until',
                                    '>=',
                                    Carbon::today()
                                )
                                ->whereDate(
                                    'examination_valid_until',
                                    '<=',
                                    Carbon::today()
                                        ->addDays(
                                            30
                                        )
                                )
                    ),
            ])

            ->paginated([
                10,
                25,
                50,
                100,
                'all',
            ])

            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaži')
                        ->color('gray'),

                    Action::make('qrCode')
                        ->label('QR kod')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->visible(
                            fn (
                                Miscellaneous $record
                            ): bool =>
                                ! $record->trashed()
                        )
                        ->url(
                            fn (
                                Miscellaneous $record
                            ): string =>
                                route(
                                    'miscellaneous.qr.admin',
                                    [
                                        'miscellaneous' =>
                                            $record,
                                    ]
                                )
                        ),

                    /*
                     * Gumb ostaje vidljiv korisniku.
                     * Ako nema UPDATE dozvolu,
                     * klik prikazuje obavijest.
                     */
                    Action::make(
                        'editMiscellaneous'
                    )
                        ->label('Uredi')
                        ->icon(
                            'heroicon-o-pencil-square'
                        )
                        ->color('warning')
                        ->visible(
                            fn (
                                Miscellaneous $record
                            ): bool =>
                                ! $record->trashed()
                        )
                        ->action(
                            function (
                                Miscellaneous $record
                            ) {
                                if (
                                    ! static::allowsModulePermission(
                                        'update'
                                    )
                                ) {
                                    return;
                                }

                                return redirect(
                                    static::getUrl(
                                        'edit',
                                        [
                                            'record' =>
                                                $record,
                                        ]
                                    )
                                );
                            }
                        ),

                    DeleteAction::make()
                    ->label('Deaktiviraj')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Deaktiviraj ispitivanje'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš deaktivirati ovo ispitivanje? Zapis ćeš kasnije moći vratiti.'
                    )
                    ->modalSubmitActionLabel(
                        'Deaktiviraj'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->before(
                        static::beforeModulePermission(
                            'delete'
                        )
                    )
                    ->visible(
                        fn (Miscellaneous $record): bool =>
                            ! $record->trashed()
                    ),

                RestoreAction::make()
                    ->label('Vrati')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Vrati ispitivanje'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš vratiti ovo ispitivanje?'
                    )
                    ->modalSubmitActionLabel(
                        'Vrati'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->before(
                        static::beforeModulePermission(
                            'delete'
                        )
                    )
                    ->visible(
                        fn (Miscellaneous $record): bool =>
                            $record->trashed()
                    ),

                ForceDeleteAction::make()
                    ->label('Trajno obriši')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno obriši ispitivanje'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš trajno obrisati ovo ispitivanje? Ova radnja se ne može poništiti.'
                    )
                    ->modalSubmitActionLabel(
                        'Trajno obriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->before(
                        static::beforeModulePermission(
                            'delete'
                        )
                    )
                    ->visible(
                        fn (Miscellaneous $record): bool =>
                            $record->trashed()
                    ),
                ]),
            ])

            ->bulkActions([
                DeleteBulkAction::make()
                ->label('Deaktiviraj označeno')
                ->requiresConfirmation()
                ->before(
                    static::beforeModulePermission(
                        'delete'
                    )
                )
                ->modalHeading(
                    'Deaktiviraj odabrana ispitivanja'
                )
                ->modalDescription(
                    'Jesi li siguran/a da želiš deaktivirati odabrana ispitivanja? Zapise ćeš kasnije moći vratiti.'
                )
                ->modalSubmitActionLabel(
                    'Deaktiviraj'
                )
                ->modalCancelActionLabel(
                    'Odustani'
                )
                ->visible(
                    fn (HasTable $livewire): bool =>
                        ! static::isOnlyTrashed(
                            $livewire
                        )
                )
                ->deselectRecordsAfterCompletion(),

            RestoreBulkAction::make()
                ->label('Vrati označeno')
                ->requiresConfirmation()
                ->before(
                    static::beforeModulePermission(
                        'delete'
                    )
                )
                ->modalHeading(
                    'Vrati odabrana ispitivanja'
                )
                ->modalDescription(
                    'Jesi li siguran/a da želiš vratiti odabrana ispitivanja?'
                )
                ->modalSubmitActionLabel(
                    'Vrati'
                )
                ->modalCancelActionLabel(
                    'Odustani'
                )
                ->visible(
                    fn (HasTable $livewire): bool =>
                        static::isOnlyTrashed(
                            $livewire
                        )
                )
                ->deselectRecordsAfterCompletion(),

            ForceDeleteBulkAction::make()
                ->label('Trajno obriši označeno')
                ->requiresConfirmation()
                ->before(
                    static::beforeModulePermission(
                        'delete'
                    )
                )
                ->modalHeading(
                    'Trajno obriši odabrana ispitivanja'
                )
                ->modalDescription(
                    'Jesi li siguran/a da želiš trajno obrisati odabrana ispitivanja? Ova radnja se ne može poništiti.'
                )
                ->modalSubmitActionLabel(
                    'Trajno obriši'
                )
                ->modalCancelActionLabel(
                    'Odustani'
                )
                ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListMiscellaneouses::route(
                    '/'
                ),

            'create' =>
                Pages\CreateMiscellaneous::route(
                    '/create'
                ),

            'view' =>
                Pages\ViewMiscellaneous::route(
                    '/{record}'
                ),

            'edit' =>
                Pages\EditMiscellaneous::route(
                    '/{record}/edit'
                ),
        ];
    }

    /**
     * Kategorije dostupne u formi.
     *
     * Organizacijski korisnik vidi kategorije svoje organizacije.
     * Superadmin kod uređivanja vidi samo kategorije ownera
     * postojećeg zapisa.
     */
    private static function getCategoryOptions(
        ?Miscellaneous $record = null
    ): array {
        $ownerId = static::formOwnerId($record);

        if (! $ownerId) {
            return [];
        }

        return Category::query()
            ->where('user_id', $ownerId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Pretraga kategorija u formi ograničena je
     * na ownera organizacije zapisa.
     */
    private static function getCategorySearchResults(
        string $search,
        ?Miscellaneous $record = null
    ): array {
        $ownerId = static::formOwnerId($record);

        if (! $ownerId) {
            return [];
        }

        return Category::query()
            ->where('user_id', $ownerId)
            ->where(
                'name',
                'like',
                "%{$search}%"
            )
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Opcije filtera tablice.
     *
     * Superadmin na listi smije vidjeti sve kategorije jer
     * istodobno vidi zapise svih organizacija.
     * Organizacijski korisnik vidi samo svoje kategorije.
     */
    private static function getCategoryFilterOptions(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $query = Category::query();

        if (! $user->isSuperAdmin()) {
            $ownerId = (int) $user->ownerId();

            if ($ownerId <= 0) {
                return [];
            }

            $query->where('user_id', $ownerId);
        }

        return $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Provjerava prikazuje li tablica
     * samo deaktivirane zapise.
     */
    private static function isOnlyTrashed(
        HasTable $livewire
    ): bool {
        $state =
            $livewire->getTableFilterState(
                'status'
            );

        return data_get(
            $state,
            'value'
        ) === 'trashed';
    }

    /**
     * Boja roka ispitivanja.
     */
    private static function expiryColor(
        $state
    ): string {
        if (! $state) {
            return 'gray';
        }

        $date = Carbon::parse($state);

        if (
            $date->lt(
                Carbon::today()
            )
        ) {
            return 'danger';
        }

        $diff =
            Carbon::today()
                ->diffInDays(
                    $date,
                    false
                );

        return $diff <= 30
            ? 'warning'
            : 'success';
    }

    /**
     * Ikona roka ispitivanja.
     */
    private static function expiryIcon(
        $state
    ): ?string {
        if (! $state) {
            return 'heroicon-o-minus-circle';
        }

        $date = Carbon::parse($state);

        if (
            $date->lt(
                Carbon::today()
            )
        ) {
            return 'heroicon-o-x-circle';
        }

        $diff =
            Carbon::today()
                ->diffInDays(
                    $date,
                    false
                );

        return $diff <= 30
            ? 'heroicon-o-exclamation-triangle'
            : 'heroicon-o-check-circle';
    }

    /**
     * Tooltip roka ispitivanja.
     */
    private static function expiryTooltip(
        $state
    ): string {
        if (! $state) {
            return 'Nema roka';
        }

        $date = Carbon::parse($state);

        if (
            $date->lt(
                Carbon::today()
            )
        ) {
            return 'Rok je istekao';
        }

        $diff =
            Carbon::today()
                ->diffInDays(
                    $date,
                    false
                );

        return $diff <= 30
            ? 'Rok uskoro ističe'
            : 'Rok je važeći';
    }
}
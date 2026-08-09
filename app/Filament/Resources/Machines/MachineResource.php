<?php

namespace App\Filament\Resources\Machines;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Machines\Pages;
use App\Models\Machine;
use App\Support\ExpiryBadge;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload as FormFileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MachineResource extends BaseResource
{
    protected static ?string $model = Machine::class;

    protected static bool $usesSoftDeletes = true;

    protected static bool $hasOwnership = true;

    protected static \BackedEnum|string|null $navigationIcon =
        Heroicon::OutlinedCog;

    protected static ?string $navigationLabel = 'Radna Oprema';

    protected static ?string $modelLabel = 'Radna Oprema';

    protected static ?string $pluralModelLabel = 'Radna Oprema';

    protected static \UnitEnum|string|null $navigationGroup =
        'Ispitivanja';

    protected static ?int $navigationSort = 1;

    protected static function getModuleKey(): ?string
    {
        return 'machines';
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                /*
                |--------------------------------------------------------------------------
                | Više nema user_id polja u formi
                |--------------------------------------------------------------------------
                |
                | Ownership se postavlja centralno u CreateMachine:
                |
                | MachineResource::fillOwnershipData($data)
                |
                | Glavni korisnik:
                | user_id = ownerId()
                |
                | Podkorisnik:
                | user_id = ownerId() glavnog korisnika
                |
                | Superadmin standardno ne kreira poslovne zapise.
                |
                */

                Section::make('Podaci o radnoj opremi')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        /*
                        |--------------------------------------------------------------------------
                        | OCR
                        |--------------------------------------------------------------------------
                        */

                        Section::make(
                            'OCR / Auto popunjavanje iz zapisnika'
                        )
                            ->description(
                                'Učitaj PDF ili sliku zapisnika pa zatim klikni OCR gumb gore desno.'
                            )
                            ->columns(2)
                            ->schema([
                                FormFileUpload::make('ocr_source')
                                    ->label('Zapisnik za OCR')
                                    ->disk('local')
                                    ->directory('tmp/machine-ocr')
                                    ->visibility('private')
                                    ->multiple(false)
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/rtf',
                                        'text/plain',
                                        'application/vnd.oasis.opendocument.text',
                                    ])
                                    ->preserveFilenames()
                                    ->openable()
                                    ->downloadable()
                                    ->live(),

                                Placeholder::make('ocr_help')
                                    ->label('Kako radi')
                                    ->content(
                                        '1. Učitaj PDF ili sliku. '
                                        . '2. Klikni gore desno "OCR analiza". '
                                        . '3. Sustav će pokušati popuniti prazna polja.'
                                    ),
                            ]),

                        /*
                        |--------------------------------------------------------------------------
                        | Osnovni podaci
                        |--------------------------------------------------------------------------
                        */

                        Section::make('Osnovni podaci')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Naziv (obavezno)')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('manufacturer')
                                    ->label('Proizvođač')
                                    ->maxLength(255),

                                TextInput::make('factory_number')
                                    ->label('Tvornički broj')
                                    ->maxLength(255),

                                TextInput::make('inventory_number')
                                    ->label('Inventarni broj')
                                    ->maxLength(255),
                            ]),

                        /*
                        |--------------------------------------------------------------------------
                        | Ispitivanje
                        |--------------------------------------------------------------------------
                        */

                        Section::make('Ispitivanje')
                            ->columns(2)
                            ->schema([
                                DatePicker::make(
                                    'examination_valid_from'
                                )
                                    ->label('Vrijedi od (obavezno)')
                                    ->required()
                                    ->displayFormat('d.m.Y.')
                                    ->weekStartsOnMonday()
                                    ->timezone('Europe/Zagreb'),

                                DatePicker::make(
                                    'examination_valid_until'
                                )
                                    ->label('Vrijedi do (obavezno)')
                                    ->required()
                                    ->displayFormat('d.m.Y.')
                                    ->weekStartsOnMonday()
                                    ->timezone('Europe/Zagreb'),

                                TextInput::make('examined_by')
                                    ->label('Ispitao')
                                    ->maxLength(255),

                                TextInput::make('report_number')
                                    ->label('Broj izvještaja')
                                    ->maxLength(255)
                                    ->rule(
                                        function ($record) {
                                            /*
                                             * Organizacijski korisnik:
                                             * provjera unutar njegove organizacije.
                                             *
                                             * Superadmin kod uređivanja:
                                             * provjera unutar organizacije kojoj
                                             * postojeći zapis pripada.
                                             */
                                            $ownerId =
                                                static::isSuperAdmin()
                                                    ? $record?->user_id
                                                    : static::ownerId();

                                            return Rule::unique(
                                                'machines',
                                                'report_number'
                                            )
                                                ->where(
                                                    function (
                                                        $query
                                                    ) use (
                                                        $ownerId
                                                    ): void {
                                                        if ($ownerId) {
                                                            $query->where(
                                                                'user_id',
                                                                $ownerId
                                                            );
                                                        }

                                                        $query->whereNull(
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

                        /*
                        |--------------------------------------------------------------------------
                        | Ostalo
                        |--------------------------------------------------------------------------
                        */

                        Section::make('Ostalo')
                            ->columns(2)
                            ->schema([
                                TextInput::make('location')
                                    ->label(
                                        'Lokacija (obavezno)'
                                    )
                                    ->required()
                                    ->maxLength(255),

                                Textarea::make('remark')
                                    ->label('Napomena')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        /*
                        |--------------------------------------------------------------------------
                        | Prilozi
                        |--------------------------------------------------------------------------
                        */

                        Section::make('Prilozi')
                            ->columnSpanFull()
                            ->schema([
                                FormFileUpload::make('pdf')
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
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
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

                TextColumn::make('manufacturer')
                    ->label('Proizvođač')
                    ->searchable()
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make('factory_number')
                    ->label('Tvor.broj')
                    ->searchable()
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make(
                    'examination_valid_from'
                )
                    ->label('Datum ispitivanja')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make(
                    'examination_valid_until'
                )
                    ->label('Ispitivanje vrijedi do')
                    ->date('d.m.Y.')
                    ->badge()
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->color(
                        fn ($state) =>
                            ExpiryBadge::color($state)
                    )
                    ->icon(
                        fn ($state) =>
                            ExpiryBadge::icon($state)
                    )
                    ->iconPosition('before')
                    ->tooltip(
                        fn ($state) =>
                            ExpiryBadge::tooltip($state)
                    )
                    ->toggleable(),

                TextColumn::make('location')
                    ->label('Lokacija')
                    ->sortable()
                    ->wrap()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make('pdf')
                    ->label('Prilozi')
                    ->alignment(
                        Alignment::Center
                    )
                    ->html()
                    ->state(
                        function (
                            Machine $record
                        ): string {
                            if (
                                ! is_array(
                                    $record->pdf
                                )
                                || count(
                                    $record->pdf
                                ) === 0
                            ) {
                                return '<span style="color:#6b7280;">0</span>';
                            }

                            return collect(
                                $record->pdf
                            )
                                ->map(
                                    function (
                                        $file,
                                        $index
                                    ) {
                                        $url = route(
                                            'file.preview',
                                            [
                                                'file' =>
                                                    ltrim(
                                                        $file,
                                                        '/'
                                                    ),
                                            ]
                                        );

                                        $name = e(
                                            basename(
                                                $file
                                            )
                                        );

                                        $number =
                                            $index + 1;

                                        return '<a href="'
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
                            Machine $record
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
                            $value =
                                $data['value']
                                ?? null;

                            return match ($value) {
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

                SelectFilter::make('location')
                    ->label('Lokacije')
                    ->placeholder('Sve')
                    ->options(
                        fn () =>
                            static::getLocationOptions()
                    )
                    ->searchable(),

                Filter::make('isteklo')
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

                Filter::make('uskoro')
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
                                        ->addDays(30)
                                )
                    ),
            ])

            ->paginated([
                10,
                25,
                50,
                'all',
            ])

            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            fn (
                                Machine $record
                            ) =>
                                ! $record->trashed()
                        ),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                Machine $record
                            ) =>
                                ! $record->trashed()
                        )
                        ->before(
                            static::beforeModulePermission(
                                'delete'
                            )
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                Machine $record
                            ) =>
                                $record->trashed()
                        )
                        ->before(
                            static::beforeModulePermission(
                                'delete'
                            )
                        ),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                Machine $record
                            ) =>
                                $record->trashed()
                        )
                        ->before(
                            static::beforeModulePermission(
                                'delete'
                            )
                        ),
                ])
                    ->icon(
                        Heroicon::EllipsisVertical
                    )
                    ->label(''),
            ])

            ->bulkActions([
                DeleteBulkAction::make()
                    ->label(
                        'Deaktiviraj označeno'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Deaktiviraj odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->modalSubmitActionLabel(
                        'Deaktiviraj'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (
                            HasTable $livewire
                        ) =>
                            ! self::isOnlyTrashed(
                                $livewire
                            )
                    )
                    ->before(
                        static::beforeModulePermission(
                            'delete'
                        )
                    ),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Vrati odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->modalSubmitActionLabel(
                        'Vrati'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (
                            HasTable $livewire
                        ) =>
                            self::isOnlyTrashed(
                                $livewire
                            )
                    )
                    ->before(
                        static::beforeModulePermission(
                            'delete'
                        )
                    ),

                BulkAction::make(
                    'copyAndCreateNew'
                )
                    ->label(
                        'Kopiraj i napravi novi'
                    )
                    ->icon(
                        Heroicon::DocumentDuplicate
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Kopiraj radnu opremu'
                    )
                    ->modalDescription(
                        'Kopirat će se odabrana radna oprema i otvoriti novi zapis za uređivanje.'
                    )
                    ->modalSubmitActionLabel(
                        'Kopiraj i otvori'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->before(
                        static::beforeModulePermission(
                            'create'
                        )
                    )
                    ->action(
                        function (
                            EloquentCollection $records
                        ) {
                            if (
                                $records->count()
                                !== 1
                            ) {
                                Notification::make()
                                    ->title(
                                        'Odaberi samo jednu radnu opremu'
                                    )
                                    ->body(
                                        'Za kopiranje može biti označena samo jedna radna oprema.'
                                    )
                                    ->danger()
                                    ->send();

                                return;
                            }

                            /** @var Machine $record */
                            $record =
                                $records->first();

                            $newRecord =
                                $record->replicate([
                                    'created_at',
                                    'updated_at',
                                    'deleted_at',
                                ]);

                            /*
                             * Prilozi se NE kopiraju.
                             */
                            $newRecord->pdf = [];

                            /*
                             * Standardni ownership.
                             *
                             * Organizacijski korisnici novi
                             * zapis spremaju na ownerId().
                             *
                             * Superadmin standardno ne može
                             * koristiti create dozvolu za
                             * poslovni modul.
                             */
                            $newRecord->user_id =
                                static::ownerId();

                            $newRecord->save();

                            Notification::make()
                                ->title(
                                    'Radna oprema je kopirana'
                                )
                                ->body(
                                    'Otvara se novi kopirani zapis za uređivanje.'
                                )
                                ->success()
                                ->send();

                            return redirect(
                                static::getUrl(
                                    'edit',
                                    [
                                        'record' =>
                                            $newRecord,
                                    ]
                                )
                            );
                        }
                    ),

                ForceDeleteBulkAction::make()
                    ->label(
                        'Trajno obriši označeno'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno obriši odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.'
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
                    ),
            ]);
    }

    private static function isOnlyTrashed(
        HasTable $livewire
    ): bool {
        $state =
            $livewire->getTableFilterState(
                'status'
            );

        $value = data_get(
            $state,
            'value'
        );

        return $value === 'trashed';
    }

    protected static function getLocationOptions(): array
    {
        return static::getEloquentQuery()
            ->whereNotNull('location')
            ->where(
                'location',
                '<>',
                ''
            )
            ->distinct()
            ->orderBy('location')
            ->pluck(
                'location',
                'location'
            )
            ->toArray();
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListMachines::route('/'),

            'create' =>
                Pages\CreateMachine::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditMachine::route(
                    '/{record}/edit'
                ),

            'view' =>
                Pages\ViewMachine::route(
                    '/{record}'
                ),
        ];
    }
}
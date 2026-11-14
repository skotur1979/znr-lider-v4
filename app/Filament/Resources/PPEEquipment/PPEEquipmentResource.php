<?php

namespace App\Filament\Resources\PPEEquipment;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\PPEEquipment\Pages;
use App\Models\PPEEquipment;
use App\Services\StorageQuotaService;
use App\Support\SecureFilePreview;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload as FormFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class PPEEquipmentResource extends BaseResource
{
    protected static ?string $model =
        PPEEquipment::class;

    protected static bool $hasOwnership =
        true;

    protected static BackedEnum|string|null $navigationIcon =
        'heroicon-o-hand-raised';

    protected static string|UnitEnum|null $navigationGroup =
        'Zaposlenici';

    protected static ?string $navigationLabel =
        'Registar OZO';

    protected static ?string $modelLabel =
        'OZO oprema';

    protected static ?string $pluralModelLabel =
        'Registar OZO';

    protected static ?int $navigationSort =
        4;

    protected static ?string $slug =
        'registar-ozo';

    protected static function getModuleKey(): ?string
    {
        return 'ppe_logs';
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    /*
    |--------------------------------------------------------------------------
    | CENTRALNA PRIPREMA PODATAKA
    |--------------------------------------------------------------------------
    |
    | Registar OZO je organizacijski modul.
    |
    | Identitet OZO zapisa:
    |
    | user_id + name
    |
    | To odgovara DB UNIQUE constraintu:
    |
    | ppe_equipments_user_id_name_unique
    |
    */

    public static function prepareData(
        array $data,
        ?PPEEquipment $record = null
    ): array {
        $user =
            Auth::user();

        if (! $user) {
            abort(403);
        }

        /*
         * EDIT
         */
        if ($record) {
            abort_unless(
                static::canEdit(
                    $record
                ),
                403
            );

            $ownerId =
                (int) $record->user_id;
        }

        /*
         * CREATE
         */
        else {
            abort_unless(
                static::canCreate(),
                403
            );

            /*
             * Superadmin ne kreira organizacijski
             * Registar OZO u ime organizacije.
             */
            if ($user->isSuperAdmin()) {
                abort(403);
            }

            $ownerId =
                (int) $user->ownerId();
        }

        if ($ownerId <= 0) {
            abort(403);
        }

        /*
         * Organizacijski korisnik smije raditi
         * samo unutar svoje organizacije.
         */
        if (
            ! $user->isSuperAdmin()
            && $ownerId
                !== (int) $user->ownerId()
        ) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | NAZIV
        |--------------------------------------------------------------------------
        */

        $name =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) (
                        $data['name']
                        ?? ''
                    )
                )
            );

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' =>
                    'Naziv OZO je obavezan.',
            ]);
        }

        if (
            mb_strlen(
                $name
            ) > 191
        ) {
            throw ValidationException::withMessages([
                'name' =>
                    'Naziv OZO može imati najviše 191 znak.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | NORMA
        |--------------------------------------------------------------------------
        */

        $standardRaw =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string) (
                        $data['standard']
                        ?? ''
                    )
                )
            );

        $standard =
            $standardRaw !== ''
                ? $standardRaw
                : null;

        if (
            $standard !== null
            && mb_strlen(
                $standard
            ) > 191
        ) {
            throw ValidationException::withMessages([
                'standard' =>
                    'HRN EN / Norma može imati najviše 191 znak.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ROK UPORABE
        |--------------------------------------------------------------------------
        */

        $durationRaw =
            $data['duration_months']
            ?? null;

        if (
            $durationRaw === ''
            || $durationRaw === null
        ) {
            $duration = null;
        } else {
            if (
                ! is_numeric(
                    $durationRaw
                )
            ) {
                throw ValidationException::withMessages([
                    'duration_months' =>
                        'Rok uporabe mora biti broj.',
                ]);
            }

            $duration =
                (int) $durationRaw;

            if (
                $duration < 0
                || $duration > 120
            ) {
                throw ValidationException::withMessages([
                    'duration_months' =>
                        'Rok uporabe mora biti između 0 i 120 mjeseci.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DUPLIKAT
        |--------------------------------------------------------------------------
        |
        | Jedinstvenost je:
        |
        | user_id + name
        |
        | Norma nije dio identiteta zapisa.
        |
        */

        $duplicateQuery =
            PPEEquipment::query()
                ->where(
                    'user_id',
                    $ownerId
                )
                ->where(
                    'name',
                    $name
                );

        if ($record) {
            $duplicateQuery
                ->where(
                    $record->getKeyName(),
                    '!=',
                    $record->getKey()
                );
        }

        if (
            $duplicateQuery->exists()
        ) {
            throw ValidationException::withMessages([
                'name' =>
                    'OZO s ovim nazivom već postoji u Registru OZO ove organizacije.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FINALNI PODACI
        |--------------------------------------------------------------------------
        */

        $data['user_id'] =
            $ownerId;

        $data['name'] =
            $name;

        $data['standard'] =
            $standard;

        $data['duration_months'] =
            $duration;

        $data['is_active'] =
            (bool) (
                $data['is_active']
                ?? true
            );

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | FORMA
    |--------------------------------------------------------------------------
    */

    public static function form(
        Schema $schema
    ): Schema {
        return $schema
            ->schema([
                Section::make(
                    'Podaci o OZO opremi'
                )
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make(
                            'name'
                        )
                            ->label(
                                'Naziv OZO'
                            )
                            ->required()
                            ->maxLength(
                                191
                            ),

                        TextInput::make(
                            'standard'
                        )
                            ->label(
                                'HRN EN / Norma'
                            )
                            ->maxLength(
                                191
                            ),

                        TextInput::make(
                            'duration_months'
                        )
                            ->label(
                                'Rok uporabe (mjeseci)'
                            )
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(
                                120
                            ),

                        Toggle::make(
                            'is_active'
                        )
                            ->label(
                                'Aktivno'
                            )
                            ->default(
                                true
                            )
                            ->inline(
                                false
                            ),

                        FormFileUpload::make(
                            'attachments'
                        )
                            ->label(
                                'Certifikati i upute za korištenje OZO (max. 5, do 30 MB po datoteci)'
                            )
                            ->disk(
                                'public'
                            )
                            ->directory(
                                function (
                                    ?PPEEquipment $record
                                ): string {
                                    $ownerId =
                                        (int) (
                                            $record?->user_id
                                            ?? auth()
                                                ->user()
                                                ?->ownerId()
                                            ?? 0
                                        );

                                    return
                                        'ppe-equipment/'
                                        . $ownerId
                                        . '/attachments';
                                }
                            )
                            ->multiple()
                            ->maxFiles(
                                5
                            )
                            ->maxSize(
                                30720
                            )
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull()

                            ->helperText(
                                function (
                                    ?PPEEquipment $record
                                ): ?string {
                                    $ownerId =
                                        (int) (
                                            $record?->user_id
                                            ?? auth()
                                                ->user()
                                                ?->ownerId()
                                            ?? 0
                                        );

                                    if (
                                        $ownerId <= 0
                                    ) {
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
                                function (
                                    ?PPEEquipment $record
                                ) {
                                    return function (
                                        string $attribute,
                                        mixed $value,
                                        \Closure $fail
                                    ) use (
                                        $record
                                    ): void {
                                        $ownerId =
                                            (int) (
                                                $record?->user_id
                                                ?? auth()
                                                    ->user()
                                                    ?->ownerId()
                                                ?? 0
                                            );

                                        if (
                                            $ownerId <= 0
                                        ) {
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
                            ]),
                    ]),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLICA
    |--------------------------------------------------------------------------
    */

    public static function table(
        Table $table
    ): Table {
        return $table

            ->paginated([
                10,
                25,
                50,
                'all',
            ])

            ->defaultSort(
                'name'
            )

            ->columns([
                TextColumn::make(
                    'name'
                )
                    ->label(
                        'Naziv OZO'
                    )
                    ->searchable()
                    ->sortable()
                    ->weight(
                        'semibold'
                    )
                    ->wrap()
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),

                TextColumn::make(
                    'standard'
                )
                    ->label(
                        'HRN EN / Norma'
                    )
                    ->searchable()
                    ->sortable()
                    ->html()
                    ->formatStateUsing(
                        function (
                            $state
                        ): string {
                            if (
                                blank(
                                    $state
                                )
                            ) {
                                return '—';
                            }

                            $text =
                                preg_replace(
                                    '/\s+/',
                                    ' ',
                                    trim(
                                        (string)
                                        $state
                                    )
                                );

                            return
                                '<div style="
                                    width:210px;
                                    max-width:210px;
                                    white-space:normal;
                                    word-break:break-word;
                                    line-height:1.25em;
                                    max-height:2.5em;
                                    overflow:hidden;
                                    display:-webkit-box;
                                    -webkit-box-orient:vertical;
                                    -webkit-line-clamp:2;
                                ">'
                                . e(
                                    $text
                                )
                                . '</div>';
                        }
                    )
                    ->tooltip(
                        function (
                            PPEEquipment $record
                        ): ?string {
                            if (
                                blank(
                                    $record->standard
                                )
                            ) {
                                return null;
                            }

                            return preg_replace(
                                '/\s+/',
                                ' ',
                                trim(
                                    (string)
                                    $record->standard
                                )
                            );
                        }
                    )
                    ->toggleable(),

                TextColumn::make(
                    'duration_months'
                )
                    ->label(
                        'Rok uporabe (mj.)'
                    )
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make(
                    'is_active'
                )
                    ->label(
                        'Aktivno'
                    )
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make(
                    'attachments'
                )
                    ->label(
                        'Prilozi'
                    )
                    ->alignCenter()
                    ->html()
                    ->state(
                        function (
                            PPEEquipment $record
                        ): string {
                            if (
                                ! is_array(
                                    $record->attachments
                                )
                                || count(
                                    $record->attachments
                                ) === 0
                            ) {
                                return
                                    '<span style="color:#6b7280;">0</span>';
                            }

                            return collect(
                                $record->attachments
                            )
                                ->map(
                                    function (
                                        $file,
                                        $index
                                    ) {
                                        $url =
                                            SecureFilePreview::url(
                                                $file
                                            );

                                        if (
                                            blank(
                                                $url
                                            )
                                        ) {
                                            return '';
                                        }

                                        $name =
                                            e(
                                                basename(
                                                    (string)
                                                    $file
                                                )
                                            );

                                        $number =
                                            $index + 1;

                                        return
                                            '<a href="'
                                            . e(
                                                $url
                                            )
                                            . '"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                title="'
                                            . $name
                                            . '"
                                                onclick="
                                                    event.preventDefault();
                                                    event.stopPropagation();
                                                    event.stopImmediatePropagation();
                                                    window.open(
                                                        this.href,
                                                        \'_blank\'
                                                    );
                                                    return false;
                                                "
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
                                ->filter()
                                ->implode('');
                        }
                    )
                    ->tooltip(
                        function (
                            PPEEquipment $record
                        ): string {
                            if (
                                ! is_array(
                                    $record->attachments
                                )
                                || count(
                                    $record->attachments
                                ) === 0
                            ) {
                                return
                                    'Nema priloga';
                            }

                            return collect(
                                $record->attachments
                            )
                                ->map(
                                    fn (
                                        $file,
                                        $index
                                    ) =>
                                        ($index + 1)
                                        . '. '
                                        . basename(
                                            (string)
                                            $file
                                        )
                                )
                                ->implode(
                                    "\n"
                                );
                        }
                    )
                    ->toggleable(),
            ])

            ->filters([])

            /*
            |--------------------------------------------------------------------------
            | AKCIJE
            |--------------------------------------------------------------------------
            */

            ->actions([
                ActionGroup::make([
                    Action::make(
                        'qrCode'
                    )
                        ->label(
                            'QR kod'
                        )
                        ->icon(
                            'heroicon-o-qr-code'
                        )
                        ->color(
                            'success'
                        )
                        ->visible(
                            fn (
                                PPEEquipment $record
                            ): bool =>
                                static::canView(
                                    $record
                                )
                        )
                        ->url(
                            fn (
                                PPEEquipment $record
                            ): string =>
                                route(
                                    'ppe-equipment.qr.admin',
                                    [
                                        'ppeEquipment' =>
                                            $record,
                                    ]
                                )
                        )
                        ->openUrlInNewTab(),

                    EditAction::make()
                        ->label(
                            'Uredi'
                        )
                        ->icon(
                            'heroicon-o-pencil-square'
                        )
                        ->color(
                            'warning'
                        )
                        ->visible(
                            fn (
                                PPEEquipment $record
                            ): bool =>
                                static::canEdit(
                                    $record
                                )
                        ),

                    DeleteAction::make()
                        ->label(
                            'Trajno izbriši'
                        )
                        ->color(
                            'danger'
                        )
                        ->requiresConfirmation()
                        ->modalHeading(
                            'Trajno izbriši OZO opremu'
                        )
                        ->modalDescription(
                            'OZO se može trajno izbrisati samo ako nikada nije korišten u Upisniku OZO. Ako se više ne koristi, postavi polje Aktivno na Ne.'
                        )
                        ->modalSubmitActionLabel(
                            'Trajno izbriši'
                        )
                        ->modalCancelActionLabel(
                            'Odustani'
                        )
                        ->visible(
                            fn (
                                PPEEquipment $record
                            ): bool =>
                                static::canDelete(
                                    $record
                                )
                        )
                        ->before(
                            function (
                                PPEEquipment $record
                            ): void {
                                abort_unless(
                                    static::canDelete(
                                        $record
                                    ),
                                    403
                                );
                            }
                        ),
                ])
                    ->label(''),
            ])

            /*
            |--------------------------------------------------------------------------
            | BULK AKCIJE
            |--------------------------------------------------------------------------
            */

            ->bulkActions([
                BulkAction::make(
                    'deleteSelected'
                )
                    ->label(
                        'Trajno izbriši označeno'
                    )
                    ->icon(
                        'heroicon-o-trash'
                    )
                    ->color(
                        'danger'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno izbriši odabranu OZO opremu'
                    )
                    ->modalDescription(
                        'Izbrisat će se samo nekorišteni OZO zapisi. OZO koji je korišten u Upisniku OZO neće biti izbrisan.'
                    )
                    ->modalSubmitActionLabel(
                        'Trajno izbriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (): bool =>
                            static::canDeleteAny()
                    )
                    ->action(
                        function (
                            EloquentCollection $records
                        ): void {
                            $deleted = 0;
                            $skipped = 0;

                            foreach (
                                $records
                                as $record
                            ) {
                                if (
                                    ! $record
                                        instanceof
                                        PPEEquipment
                                    || ! static::canDelete(
                                        $record
                                    )
                                ) {
                                    $skipped++;

                                    continue;
                                }

                                $record->delete();

                                $deleted++;
                            }

                            Notification::make()
                                ->title(
                                    'Brisanje Registra OZO završeno'
                                )
                                ->body(
                                    "Trajno izbrisano: {$deleted}\n"
                                    . "Preskočeno: {$skipped}"
                                )
                                ->success()
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | BRISANJE
    |--------------------------------------------------------------------------
    */

    public static function canDelete(
        Model $record
    ): bool {
        if (
            ! parent::canDelete(
                $record
            )
        ) {
            return false;
        }

        if (
            ! $record
                instanceof
                PPEEquipment
        ) {
            return false;
        }

        return
            ! $record
                ->isUsedInPPELogs();
    }

    /*
    |--------------------------------------------------------------------------
    | STRANICE
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListPPEEquipment::route(
                    '/'
                ),

            'create' =>
                Pages\CreatePPEEquipment::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditPPEEquipment::route(
                    '/{record}/edit'
                ),
        ];
    }
}
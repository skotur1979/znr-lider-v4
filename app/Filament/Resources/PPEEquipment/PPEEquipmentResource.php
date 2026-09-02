<?php

namespace App\Filament\Resources\PPEEquipment;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\PPEEquipment\Pages;
use App\Models\PPEEquipment;
use App\Services\StorageQuotaService;
use App\Support\SecureFilePreview;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload as FormFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PPEEquipmentResource extends BaseResource
{
    protected static ?string $model =
        PPEEquipment::class;

    /*
    |--------------------------------------------------------------------------
    | OWNERSHIP
    |--------------------------------------------------------------------------
    |
    | Registar OZO više nema globalne zapise.
    |
    | Svaki OZO zapis pripada jednoj organizaciji:
    |
    | user_id = ownerId()
    |
    | Glavni korisnik i njegovi podkorisnici
    | koriste isti organizacijski registar OZO.
    |
    */

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
                                255
                            ),

                        TextInput::make(
                            'standard'
                        )
                            ->label(
                                'HRN EN / Norma'
                            )
                            ->maxLength(
                                255
                            ),

                        TextInput::make(
                            'duration_months'
                        )
                            ->label(
                                'Rok uporabe (mjeseci)'
                            )
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(240),

                        Toggle::make(
                            'is_active'
                        )
                            ->label(
                                'Aktivno'
                            )
                            ->default(true)
                            ->inline(false),

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
                                'ppe-equipment/attachments'
                            )
                            ->multiple()
                            ->maxFiles(5)
                            ->maxSize(
                                30720
                            )
                            ->preserveFilenames()
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull()

                            /*
                             * Prikaz zauzeća storagea
                             * organizacije.
                             */
                            ->helperText(
                                function () {
                                    $ownerId =
                                        auth()
                                            ->user()
                                            ?->ownerId();

                                    if (
                                        ! $ownerId
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

                            /*
                             * Provjera organizacijskog
                             * storage limita.
                             */
                            ->rules([
                                function () {
                                    return function (
                                        string $attribute,
                                        mixed $value,
                                        \Closure $fail
                                    ): void {
                                        $ownerId =
                                            auth()
                                                ->user()
                                                ?->ownerId();

                                        if (
                                            ! $ownerId
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

                /*
                 * Korisnik / organizacija.
                 *
                 * Standardna kolona iz BaseResourcea.
                 */
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
                    ->wrap()
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

                /*
                |--------------------------------------------------------------------------
                | PRILOZI
                |--------------------------------------------------------------------------
                */

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
                                    $record
                                        ->attachments
                                )
                                || count(
                                    $record
                                        ->attachments
                                ) === 0
                            ) {
                                return
                                    '<span style="color:#6b7280;">0</span>';
                            }

                            return collect(
                                $record
                                    ->attachments
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

                                        $name =
                                            e(
                                                basename(
                                                    $file
                                                )
                                            );

                                        $number =
                                            $index
                                            + 1;

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
                                ->implode('');
                        }
                    )
                    ->tooltip(
                        function (
                            PPEEquipment $record
                        ): string {
                            if (
                                ! is_array(
                                    $record
                                        ->attachments
                                )
                                || count(
                                    $record
                                        ->attachments
                                ) === 0
                            ) {
                                return
                                    'Nema priloga';
                            }

                            return collect(
                                $record
                                    ->attachments
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
                                ->implode(
                                    "\n"
                                );
                        }
                    )
                    ->toggleable(),
            ])

            /*
             * Više nema filtera:
             *
             * Globalno
             * Organizacija
             *
             * jer svaki zapis pripada organizaciji.
             */

            ->filters([])

            /*
            |--------------------------------------------------------------------------
            | AKCIJE
            |--------------------------------------------------------------------------
            */

            ->actions([
                ActionGroup::make([
                    Action::make('qrCode')
                        ->label('QR kod')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
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
                        ),

                    DeleteAction::make()
                        ->label(
                            'Izbriši'
                        )
                        ->color(
                            'danger'
                        )
                        ->requiresConfirmation()
                        ->modalHeading(
                            'Izbriši OZO opremu'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a da želiš izbrisati ovu OZO opremu?'
                        )
                        ->modalSubmitActionLabel(
                            'Izbriši'
                        )
                        ->modalCancelActionLabel(
                            'Odustani'
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
                DeleteBulkAction::make()
                    ->label(
                        'Izbriši označeno'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Izbriši odabranu OZO opremu'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš izbrisati odabrane OZO zapise?'
                    )
                    ->modalSubmitActionLabel(
                        'Izbriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
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
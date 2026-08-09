<?php

namespace App\Filament\Resources\PPEEquipment;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\PPEEquipment\Pages;
use App\Models\PPEEquipment;
use App\Support\SecureFilePreview;
use App\Services\StorageQuotaService;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload as FormFileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PPEEquipmentResource extends BaseResource
{
    protected static ?string $model = PPEEquipment::class;

    /*
     * Registar OZO ima posebnu logiku:
     *
     * user_id = NULL
     *     globalni zapis koji održava superadmin
     *
     * user_id = ownerId()
     *     zapis konkretne organizacije
     */
    protected static bool $hasOwnership = false;

    protected static BackedEnum|string|null $navigationIcon =
        'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup =
        'Zaposlenici';

    protected static ?string $navigationLabel =
        'Registar OZO';

    protected static ?string $modelLabel =
        'OZO oprema';

    protected static ?string $pluralModelLabel =
        'Registar OZO';

    protected static ?int $navigationSort = 4;

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

    protected static function isCurrentUserSuperAdmin(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    /**
     * Superadmin vidi sve zapise.
     *
     * Organizacija vidi:
     * - globalne zapise
     * - vlastite organizacijske zapise
     */
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

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            function (Builder $query) use ($ownerId): void {
                $query
                    ->whereNull('user_id')
                    ->orWhere(
                        'user_id',
                        $ownerId
                    );
            }
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Podaci o OZO opremi')
                ->columnSpanFull()
                ->columns(3)
                ->schema([
                    Hidden::make('user_id')
                        ->default(
                            fn () =>
                                static::isCurrentUserSuperAdmin()
                                    ? null
                                    : static::ownerId()
                        )
                        ->dehydrated(
                            fn (string $operation): bool =>
                                $operation === 'create'
                        ),

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

                    FormFileUpload::make('attachments')
                        ->label(
                            'Certifikati i upute za korištenje OZO (max. 5, do 30 MB po datoteci)'
                        )
                        ->disk('public')
                        ->directory(
                            'ppe-equipment/attachments'
                        )
                        ->multiple()
                        ->maxFiles(5)
                        ->maxSize(30720)
                        ->preserveFilenames()
                        ->openable()
                        ->downloadable()
                        ->columnSpanFull()
                        ->helperText(function () {
                            $ownerId =
                                auth()->user()?->ownerId();

                            if (! $ownerId) {
                                return null;
                            }

                            return
                                'Iskorištenost prostora organizacije: '
                                . app(
                                    StorageQuotaService::class
                                )->usageText($ownerId);
                        })
                        ->rules([
                            function () {
                                return function (
                                    string $attribute,
                                    mixed $value,
                                    \Closure $fail
                                ): void {
                                    $ownerId =
                                        auth()->user()?->ownerId();

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
                                            'Dosegnut je maksimalni prostor za pohranu dokumenata organizacije. Obrišite nepotrebne priloge ili kontaktirajte administratora.'
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

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([
                10,
                25,
                50,
                'all',
            ])
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv OZO')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(),

                TextColumn::make('standard')
                    ->label('HRN EN / Norma')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('duration_months')
                    ->label('Rok uporabe (mj.)')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktivno')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('attachments')
                    ->label('Prilozi')
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
                                return '<span style="color:#6b7280;">0</span>';
                            }

                            return collect(
                                $record->attachments
                            )
                                ->map(
                                    function (
                                        $file,
                                        $index
                                    ) {
                                        $url = SecureFilePreview::url(
                                        $file
                                    );

                                        $name = e(
                                            basename($file)
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
                                return 'Nema priloga';
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
                                        . basename($file)
                                )
                                ->implode("\n");
                        }
                    )
                    ->toggleable(),

                TextColumn::make('scope_label')
                    ->label('Vrsta zapisa')
                    ->badge()
                    ->alignCenter()
                    ->state(
                        fn (
                            PPEEquipment $record
                        ): string =>
                            $record->user_id === null
                                ? 'Globalno'
                                : 'Organizacija'
                    )
                    ->color(
                        fn (
                            PPEEquipment $record
                        ): string =>
                            $record->user_id === null
                                ? 'success'
                                : 'info'
                    )
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->label('Vrsta zapisa')
                    ->options([
                        'global' =>
                            'Globalno',

                        'organization' =>
                            'Organizacija',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return match (
                                $data['value'] ?? null
                            ) {
                                'global' =>
                                    $query->whereNull(
                                        'user_id'
                                    ),

                                'organization' =>
                                    $query->whereNotNull(
                                        'user_id'
                                    ),

                                default =>
                                    $query,
                            };
                        }
                    ),

                SelectFilter::make('user_id')
                    ->label('Korisnik')
                    ->relationship(
                        'user',
                        'name',
                        fn (Builder $query) =>
                            $query->orderBy('name')
                    )
                    ->searchable()
                    ->preload()
                    ->visible(
                        fn (): bool =>
                            static::isCurrentUserSuperAdmin()
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            fn (
                                PPEEquipment $record
                            ): bool =>
                                static::canModifyRecord(
                                    $record
                                )
                        ),

                    DeleteAction::make()
                        ->label('Izbriši')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                PPEEquipment $record
                            ): bool =>
                                static::canModifyRecord(
                                    $record
                                )
                        ),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Izbriši označeno')
                    ->requiresConfirmation()
                    ->action(function ($records): void {
                        $blocked = $records->filter(
                            fn (
                                PPEEquipment $record
                            ): bool =>
                                ! static::canModifyRecord(
                                    $record
                                )
                        );

                        $allowed = $records->filter(
                            fn (
                                PPEEquipment $record
                            ): bool =>
                                static::canModifyRecord(
                                    $record
                                )
                        );

                        if ($allowed->isNotEmpty()) {
                            $allowed->each->delete();
                        }

                        if ($blocked->isNotEmpty()) {
                            Notification::make()
                                ->title(
                                    'Neki zapisi nisu obrisani'
                                )
                                ->body(
                                    static::isCurrentUserSuperAdmin()
                                        ? 'Superadmin može brisati samo globalne zapise Registra OZO.'
                                        : 'Globalne OZO zapise može brisati samo superadmin.'
                                )
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(
                                'Označeni zapisi su obrisani'
                            )
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Pravilo uređivanja Registra OZO:
     *
     * SUPERADMIN:
     * - može uređivati samo globalne zapise
     *   user_id = NULL
     * - ne može uređivati zapise organizacija
     *
     * ORGANIZACIJA:
     * - može uređivati samo svoje zapise
     * - ne može uređivati globalne zapise
     */
    public static function canModifyRecord(
        PPEEquipment $record
    ): bool {
        if (static::isCurrentUserSuperAdmin()) {
            return $record->user_id === null;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return false;
        }

        return
            $record->user_id !== null
            && (int) $record->user_id
                === (int) $ownerId;
    }

    public static function canEdit(
        Model $record
    ): bool {
        return
            $record instanceof PPEEquipment
            && parent::canEdit($record)
            && static::canModifyRecord($record);
    }

    public static function canDelete(
        Model $record
    ): bool {
        return
            $record instanceof PPEEquipment
            && parent::canDelete($record)
            && static::canModifyRecord($record);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListPPEEquipment::route('/'),

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
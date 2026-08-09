<?php

namespace App\Filament\Resources\DocumentationItems;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\DocumentationItems\Pages;
use App\Models\DocumentationItem;
use App\Services\StorageQuotaService;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class DocumentationItemResource extends BaseResource
{
    protected static ?string $model =
        DocumentationItem::class;

    /*
     * Standardni poslovni multi-tenant modul.
     *
     * user_id = ownerId()
     *
     * Superadmin vidi sve zapise,
     * ali ih standardno ne kreira.
     */
    protected static bool $hasOwnership = true;

    protected static \BackedEnum|string|null $navigationIcon =
        'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel =
        'Dokumentacija';

    protected static ?string $modelLabel =
        'Dokumentacija';

    protected static ?string $pluralModelLabel =
        'Dokumentacija';

    protected static \UnitEnum|string|null $navigationGroup =
        'Upravljanje';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute =
        'naziv';

    protected static function getModuleKey(): ?string
    {
        return 'documentation';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            /*
             * user_id se namjerno NE nalazi u formi.
             *
             * Ownership novog zapisa postavlja
             * CreateDocumentationItem preko:
             *
             * DocumentationItemResource::fillOwnershipData()
             */

            Section::make('Dokument')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    TextInput::make('naziv')
                        ->label('Naziv dokumenta')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('tvrtka')
                        ->label('Tvrtka')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    DatePicker::make('datum_izrade')
                        ->label('Datum izrade')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb')
                        ->columnSpanFull(),

                    TextInput::make('status_napomena')
                        ->label('Status / napomena')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->extraAttributes([
                    'style' =>
                        'max-width: 720px;',
                ]),

            Section::make('Prilozi')
                ->columnSpanFull()
                ->columns(1)
                ->extraAttributes([
                    'style' =>
                        'max-width: 720px; margin-top: 16px;',
                ])
                ->description(
                    'Dodaj nove dokumente ili upravljaj postojećim prilozima.'
                )
                ->schema([
                    FileUpload::make('prilozi')
                        ->label(
                            'Dodaj priloge (max. 5 kom do 30 MB po datoteci)'
                        )
                        ->disk('public')
                        ->directory('pdfs')
                        ->multiple()
                        ->maxFiles(5)
                        ->maxSize(30720)
                        ->preserveFilenames()
                        ->openable()
                        ->downloadable()
                        ->deletable()

                        /*
                         * Prikaz iskorištenosti prostora.
                         *
                         * Organizacijski korisnik:
                         * ownerId()
                         *
                         * Superadmin kod edita:
                         * owner zapisa.
                         */
                        ->helperText(
                            function (
                                $get,
                                $record
                            ) {
                                $user =
                                    auth()->user();

                                if (! $user) {
                                    return null;
                                }

                                $ownerId =
                                    $user->isSuperAdmin()
                                        ? $record?->user_id
                                        : $user->ownerId();

                                if (! $ownerId) {
                                    return null;
                                }

                                return
                                    'Iskorištenost prostora organizacije: '
                                    . app(
                                        StorageQuotaService::class
                                    )->usageText(
                                        (int) $ownerId
                                    );
                            }
                        )

                        /*
                         * Provjera ukupne kvote organizacije.
                         */
                        ->rules([
                            function (
                                $get,
                                $record
                            ) {
                                return function (
                                    string $attribute,
                                    mixed $value,
                                    \Closure $fail
                                ) use (
                                    $record
                                ) {
                                    $user =
                                        auth()->user();

                                    if (! $user) {
                                        return;
                                    }

                                    $ownerId =
                                        $user->isSuperAdmin()
                                            ? $record?->user_id
                                            : $user->ownerId();

                                    if (! $ownerId) {
                                        return;
                                    }

                                    if (
                                        ! app(
                                            StorageQuotaService::class
                                        )->canUpload(
                                            $value,
                                            (int) $ownerId
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(
                'datum_izrade',
                'desc'
            )
            ->columns([
                TextColumn::make('naziv')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),

                TextColumn::make('tvrtka')
                    ->label('Tvrtka')
                    ->searchable()
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make('datum_izrade')
                    ->label('Datum izrade')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make('status_napomena')
                    ->label('Status / napomena')
                    ->wrap()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make('prilozi')
                    ->label('Prilozi')
                    ->alignment(
                        Alignment::Center
                    )
                    ->html()
                    ->state(
                        function (
                            DocumentationItem $record
                        ): string {
                            if (
                                ! is_array(
                                    $record->prilozi
                                )
                                || count(
                                    $record->prilozi
                                ) === 0
                            ) {
                                return
                                    '<span style="color:#6b7280;">0</span>';
                            }

                            return collect(
                                $record->prilozi
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
                            DocumentationItem $record
                        ): string {
                            if (
                                ! is_array(
                                    $record->prilozi
                                )
                                || count(
                                    $record->prilozi
                                ) === 0
                            ) {
                                return 'Nema priloga';
                            }

                            return collect(
                                $record->prilozi
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
                        ->label('Uredi'),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->requiresConfirmation()
                        ->modalHeading(
                            'Obriši dokumentaciju'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a da želiš to učiniti?'
                        )
                        ->modalSubmitActionLabel(
                            'Obriši'
                        )
                        ->modalCancelActionLabel(
                            'Odustani'
                        ),
                ])
                    ->icon(
                        Heroicon::EllipsisVertical
                    )
                    ->label(''),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Obriši odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->modalSubmitActionLabel(
                        'Obriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    ),
            ]);
    }

    public static function infolist(
        Schema $schema
    ): Schema {
        return $schema->components([
            Tabs::make('Dokumentacija')
                ->tabs([
                    Tab::make('Osnovno')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Section::make(
                                        'Podaci o dokumentu'
                                    )
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make(
                                                'naziv'
                                            )
                                                ->label(
                                                    'Naziv dokumenta'
                                                )
                                                ->weight(
                                                    'bold'
                                                )
                                                ->columnSpanFull(),

                                            TextEntry::make(
                                                'tvrtka'
                                            )
                                                ->label(
                                                    'Tvrtka'
                                                )
                                                ->placeholder(
                                                    '—'
                                                ),

                                            TextEntry::make(
                                                'datum_izrade'
                                            )
                                                ->label(
                                                    'Datum izrade'
                                                )
                                                ->date(
                                                    'd.m.Y.'
                                                )
                                                ->placeholder(
                                                    '—'
                                                ),

                                            TextEntry::make(
                                                'status_napomena'
                                            )
                                                ->label(
                                                    'Status / napomena'
                                                )
                                                ->badge()
                                                ->color(
                                                    'warning'
                                                )
                                                ->placeholder(
                                                    '—'
                                                )
                                                ->columnSpanFull(),
                                        ]),

                                    Section::make(
                                        'Sažetak'
                                    )
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make(
                                                'broj_priloga'
                                            )
                                                ->label(
                                                    'Broj priloga'
                                                )
                                                ->state(
                                                    fn (
                                                        $record
                                                    ) =>
                                                        is_array(
                                                            $record->prilozi
                                                        )
                                                            ? count(
                                                                $record->prilozi
                                                            )
                                                            : 0
                                                )
                                                ->badge()
                                                ->color(
                                                    'info'
                                                ),

                                            TextEntry::make(
                                                'status_prikaza'
                                            )
                                                ->label(
                                                    'Status'
                                                )
                                                ->state(
                                                    'Aktivno'
                                                )
                                                ->badge()
                                                ->color(
                                                    'success'
                                                ),
                                        ]),
                                ]),
                        ]),

                    Tab::make('Prilozi')
                        ->schema([
                            Section::make(
                                'Prilozi dokumenta'
                            )
                                ->schema([
                                    TextEntry::make(
                                        'prilozi'
                                    )
                                        ->label('')
                                        ->html()
                                        ->state(
                                            function (
                                                $record
                                            ) {
                                                if (
                                                    ! is_array(
                                                        $record->prilozi
                                                    )
                                                    || count(
                                                        $record->prilozi
                                                    ) === 0
                                                ) {
                                                    return new HtmlString(
                                                        '<span style="color: #9ca3af;">Nema dodanih priloga.</span>'
                                                    );
                                                }

                                                $items =
                                                    collect(
                                                        $record->prilozi
                                                    )
                                                        ->map(
                                                            function (
                                                                $file
                                                            ) {
                                                                $url =
                                                                    route(
                                                                        'file.preview',
                                                                        [
                                                                            'file' =>
                                                                                ltrim(
                                                                                    $file,
                                                                                    '/'
                                                                                ),
                                                                        ]
                                                                    );

                                                                $name =
                                                                    e(
                                                                        basename(
                                                                            $file
                                                                        )
                                                                    );

                                                                return
                                                                    '<a href="'
                                                                    . e($url)
                                                                    . '" target="_blank" rel="noopener noreferrer" style="
                                                                        display: inline-flex;
                                                                        align-items: center;
                                                                        padding: 8px 12px;
                                                                        margin: 4px 6px 4px 0;
                                                                        border-radius: 10px;
                                                                        background: rgba(245, 158, 11, 0.12);
                                                                        color: #f59e0b;
                                                                        font-weight: 700;
                                                                        text-decoration: none;
                                                                    ">📎 '
                                                                    . $name
                                                                    . '</a>';
                                                            }
                                                        )
                                                        ->implode('');

                                                return new HtmlString(
                                                    $items
                                                );
                                            }
                                        ),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    /*
     * getEloquentQuery() se NAMJERNO ne definira ovdje.
     *
     * BaseResource već centralno provodi:
     *
     * - superadmin vidi sve
     * - glavni korisnik vidi ownerId()
     * - podkorisnik vidi ownerId() glavnog korisnika
     * - direktni record URL koristi isti tenant scope
     * - global search koristi isti tenant scope
     */

    /*
     * getNavigationBadge() se također ne definira ovdje.
     *
     * BaseResource već računa badge unutar odgovarajućeg
     * tenant scopea.
     */

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListDocumentationItems::route(
                    '/'
                ),

            'create' =>
                Pages\CreateDocumentationItem::route(
                    '/create'
                ),

            'view' =>
                Pages\ViewDocumentationItem::route(
                    '/{record}'
                ),

            'edit' =>
                Pages\EditDocumentationItem::route(
                    '/{record}/edit'
                ),
        ];
    }
}
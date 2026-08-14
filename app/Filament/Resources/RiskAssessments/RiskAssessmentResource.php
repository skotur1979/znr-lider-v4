<?php

namespace App\Filament\Resources\RiskAssessments;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\RiskAssessments\Pages;
use App\Filament\Resources\RiskAssessments\Schemas\RiskAssessmentInfolist;
use App\Models\RiskAssessment;
use App\Support\SecureFilePreview;
use App\Services\StorageQuotaService;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RiskAssessmentResource extends BaseResource
{
    protected static ?string $model = RiskAssessment::class;

    protected static bool $hasOwnership = true;

    protected static \BackedEnum|string|null $navigationIcon =
        Heroicon::OutlinedClipboard;

    protected static ?string $navigationLabel =
        'Procjene rizika';

    protected static ?string $modelLabel =
        'Procjena rizika';

    protected static ?string $pluralModelLabel =
        'Procjene rizika';

    protected static \UnitEnum|string|null $navigationGroup =
        'Upravljanje';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute =
        'tvrtka';

    protected static function getModuleKey(): ?string
    {
        return 'risk_assessments';
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    |
    | Procjena rizika je poslovni zapis organizacije.
    |
    | Superadmin može pregledavati postojeće zapise,
    | ali ne kreira procjene u ime organizacije.
    |
    */

    public static function canCreate(): bool
    {
        if (static::isSuperAdmin()) {
            return false;
        }

        return parent::canCreate();
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    |
    | Superadmin ne uređuje poslovne zapise organizacija.
    |
    | Glavni korisnik i podkorisnici mogu uređivati samo
    | zapise vlastite organizacije.
    |
    */

    public static function canEdit(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return false;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return false;
        }

        return (int) $record->user_id === (int) $ownerId;
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    |
    | Superadmin ne briše poslovne zapise organizacija.
    |
    */

    public static function canDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return false;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return false;
        }

        return (int) $record->user_id === (int) $ownerId;
    }

    /*
    |--------------------------------------------------------------------------
    | BULK DELETE
    |--------------------------------------------------------------------------
    |
    | Dodatna zaštita bulk brisanja.
    |
    */

    public static function canDeleteAny(): bool
    {
        if (static::isSuperAdmin()) {
            return false;
        }

        return static::ownerId() !== null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Hidden::make('user_id')
                ->default(fn () => static::ownerId())
                ->dehydrated()
                ->visible(
                    fn (string $operation): bool =>
                        $operation === 'create'
                ),

            Section::make('Podaci o procjeni rizika')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextInput::make('tvrtka')
                        ->label('Tvrtka')
                        ->required(),

                    TextInput::make('oib_tvrtke')
                        ->label('OIB tvrtke')
                        ->required()
                        ->maxLength(11)
                        ->rule('digits:11')
                        ->validationMessages([
                            'required' =>
                                'OIB tvrtke je obavezan.',

                            'digits' =>
                                'OIB mora imati točno 11 znamenki.',
                        ]),

                    TextInput::make('adresa_tvrtke')
                        ->label('Adresa tvrtke'),

                    TextInput::make('broj_procjene')
                        ->label('Broj procjene')
                        ->required(),

                    DatePicker::make('datum_izrade')
                        ->label('Datum izrade')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    TextInput::make('vrsta_procjene')
                        ->label('Vrsta procjene rizika')
                        ->required(),
                ]),

            Section::make('Sudionici izrade')
                ->collapsible()
                ->schema([
                    Repeater::make('participants')
                        ->relationship('participants')
                        ->label('Sudionici izrade')
                        ->columns(3)
                        ->schema([
                            TextInput::make('ime_prezime')
                                ->label('Ime i prezime'),

                            TextInput::make('uloga')
                                ->label('Uloga'),

                            Textarea::make('napomena')
                                ->label('Napomena')
                                ->rows(1),
                        ])
                        ->collapsible(),
                ]),

            Section::make('Revizije procjene rizika')
                ->collapsible()
                ->schema([
                    Repeater::make('revisions')
                        ->relationship('revisions')
                        ->label('Revizije')
                        ->columns(2)
                        ->schema([
                            TextInput::make('revizija_broj')
                                ->label('Revizija broj'),

                            DatePicker::make('datum_izrade')
                                ->label('Datum izrade')
                                ->displayFormat('d.m.Y.')
                                ->weekStartsOnMonday()
                                ->timezone('Europe/Zagreb'),
                        ])
                        ->collapsible(),
                ]),

            Section::make('Prilozi')
                ->collapsible()
                ->schema([
                    Repeater::make('attachments')
                        ->relationship('attachments')
                        ->label('Prilozi')
                        ->columns(2)
                        ->schema([
                            TextInput::make('naziv')
                                ->label('Naziv dokumenta')
                                ->required(),

                            FileUpload::make('file_path')
                                ->label('Dokument')
                                ->disk('public')
                                ->directory('risk-assessments/attachments')
                                ->visibility('public')
                                ->preserveFilenames()
                                ->openable()
                                ->downloadable()
                                ->maxSize(30720)
                                ->required()
                                ->helperText(function () {
                                    $user = auth()->user();

                                    if (! $user) {
                                        return null;
                                    }

                                    $ownerId = $user->ownerId();

                                    if (! $ownerId) {
                                        return null;
                                    }

                                    return
                                        'Iskorištenost prostora organizacije: '
                                        . app(StorageQuotaService::class)->usageText(
                                            (int) $ownerId
                                        );
                                })
                                ->rules([
                                    function () {
                                        return function (
                                            string $attribute,
                                            mixed $value,
                                            \Closure $fail
                                        ): void {
                                            $user = auth()->user();

                                            if (! $user) {
                                                return;
                                            }

                                            $ownerId = $user->ownerId();

                                            if (! $ownerId) {
                                                return;
                                            }

                                            if (
                                                ! app(StorageQuotaService::class)->canUpload(
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
                                ]),
                        ])
                        ->collapsible(),
                ]),
        ]);
    }

    public static function infolist(
        Schema $schema
    ): Schema {
        return RiskAssessmentInfolist::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {
        return $table
            ->modifyQueryUsing(
                fn ($query) =>
                    $query->with('attachments')
            )
            ->defaultSort(
                'datum_izrade',
                'desc'
            )
            ->columns([
                TextColumn::make('tvrtka')
                    ->label('Tvrtka')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap()
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),

                TextColumn::make('broj_procjene')
                    ->label('Broj procjene')
                    ->alignment(
                        Alignment::Center
                    )
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('datum_izrade')
                    ->label('Datum izrade')
                    ->date('d.m.Y.')
                    ->alignment(
                        Alignment::Center
                    )
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('vrsta_procjene')
                    ->label('Vrsta procjene')
                    ->alignment(
                        Alignment::Center
                    )
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('attachments')
                    ->label('Prilozi')
                    ->alignment(
                        Alignment::Center
                    )
                    ->html()
                    ->state(
                        function ($record): string {
                            $attachments =
                                $record->attachments;

                            if (
                                $attachments->isEmpty()
                            ) {
                                return
                                    '<span style="color:#6b7280;">0</span>';
                            }

                            return $attachments
                                ->map(
                                    function (
                                        $attachment,
                                        $index
                                    ) {
                                        if (
                                            blank(
                                                $attachment
                                                    ->file_path
                                            )
                                        ) {
                                            return null;
                                        }

                                        $url = SecureFilePreview::url(
                                            $attachment->file_path
                                        );

                                        $name = e(
                                            basename(
                                                $attachment
                                                    ->file_path
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
                                            onclick="
                                                event.preventDefault();
                                                event.stopPropagation();
                                                event.stopImmediatePropagation();
                                                window.open(this.href, \'_blank\');
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
                        function ($record): string {
                            $attachments =
                                $record->attachments;

                            if (
                                $attachments->isEmpty()
                            ) {
                                return 'Nema priloga';
                            }

                            return $attachments
                                ->map(
                                    fn (
                                        $attachment,
                                        $index
                                    ) =>
                                        ($index + 1)
                                        . '. '
                                        . basename(
                                            $attachment
                                                ->file_path
                                        )
                                )
                                ->implode("\n");
                        }
                    )
                    ->toggleable(),

                TextColumn::make(
                    'revisions_count'
                )
                    ->label('Broj revizija')
                    ->alignment(
                        Alignment::Center
                    )
                    ->counts('revisions')
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
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
                        ->label('Uredi'),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->requiresConfirmation()
                        ->modalHeading(
                            'Obriši procjenu rizika'
                        )
                        ->modalDescription(
                            'Jeste li sigurni da želite obrisati ovu procjenu rizika?'
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
                        'Jeste li sigurni da želite obrisati odabrane procjene rizika?'
                    )
                    ->modalSubmitActionLabel(
                        'Obriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListRiskAssessments::route(
                    '/'
                ),

            'create' =>
                Pages\CreateRiskAssessment::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditRiskAssessment::route(
                    '/{record}/edit'
                ),

            'view' =>
                Pages\ViewRiskAssessment::route(
                    '/{record}'
                ),
        ];
    }
}
<?php

namespace App\Filament\Resources\WorkPermits;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\WorkPermits\Pages;
use App\Models\WorkPermit;
use App\Services\FormVersionService;
use BackedEnum;
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
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class WorkPermitResource extends BaseResource
{
    protected static ?string $model = WorkPermit::class;

    protected static bool $usesSoftDeletes = true;

    protected static bool $hasOwnership = true;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-document-check';

    protected static string|UnitEnum|null $navigationGroup =
        'Upravljanje';

    protected static ?string $navigationLabel =
        'Dozvole za rad';

    protected static ?string $pluralModelLabel =
        'Dozvole za rad';

    protected static ?string $modelLabel =
        'Dozvola za rad';

    protected static ?int $navigationSort = 8;

    protected static function getModuleKey(): ?string
    {
        return 'work_permits';
    }

    /*
    |--------------------------------------------------------------------------
    | AUTORIZACIJA POSLOVNIH ZAPISA
    |--------------------------------------------------------------------------
    |
    | Dozvole za rad pripadaju organizaciji.
    |
    | Superadmin:
    | - može pregledavati sve postojeće zapise
    | - može uređivati postojeće zapise
    | - može deaktivirati, vratiti i trajno brisati
    | - ne može kreirati novu dozvolu u ime organizacije
    |
    | Organizacijski korisnici:
    | - rade samo nad zapisima svoje organizacije
    |
    */

    public static function canCreate(): bool
    {
        /*
        * Centralna logika BaseResourcea:
        *
        * - superadmin standardno ne može kreirati
        *   poslovne zapise
        *
        * - organizacijskim korisnicima ostaju
        *   postojeće module permissions provjere
        */
        return parent::canCreate();
    }


    public static function canEdit(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return static::canManageOrganizationRecord($record)
            && parent::canEdit($record);
    }


    public static function canDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return static::canManageOrganizationRecord($record)
            && parent::canDelete($record);
    }


    public static function canRestore(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return static::canManageOrganizationRecord($record)
            && parent::canRestore($record);
    }


    public static function canForceDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return static::canManageOrganizationRecord($record)
            && parent::canForceDelete($record);
    }


    /**
     * Dodatna record-level tenant zaštita
     * za organizacijske korisnike.
     */
    protected static function canManageOrganizationRecord(
        Model $record
    ): bool {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            return false;
        }

        return (int) $record->user_id === (int) $ownerId;
    }

    /*
     |--------------------------------------------------------------------------
     | BROJ DOZVOLE
     |--------------------------------------------------------------------------
     */

    public static function generateNextPermitNumber(): string
    {
        $year = now()->year;

        $query = WorkPermit::query()
            ->withTrashed();

        if (! static::isSuperAdmin()) {
            $ownerId = static::ownerId();

            if (! $ownerId) {
                return '01/' . $year;
            }

            $query->where(
                'user_id',
                $ownerId
            );
        }

        $last = $query
            ->whereYear(
                'created_at',
                $year
            )
            ->count();

        $next = $last + 1;

        return str_pad(
            (string) $next,
            2,
            '0',
            STR_PAD_LEFT
        ) . '/' . $year;
    }

    /*
     |--------------------------------------------------------------------------
     | FORMA
     |--------------------------------------------------------------------------
     */

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(
                    fn () => static::defaultUserId()
                )
                ->dehydrated(),

            Select::make('form_version')
                ->label(
                    'Verzija obrasca dozvole za rad'
                )
                ->options(
                    WorkPermit::formVersions()
                )
                ->default(
                    FormVersionService::currentWorkPermit()
                )
                ->required()
                ->helperText(
                    'Verzija se sprema uz dozvolu. Stare dozvole ostaju na staroj verziji obrasca.'
                ),

            Section::make('Osnovni podaci')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('permit_number')
                        ->label('Broj')
                        ->default(
                            fn () =>
                                static::generateNextPermitNumber()
                        )
                        ->required(),

                    DatePicker::make('issue_date')
                        ->label('Datum')
                        ->displayFormat('d.m.Y.')
                        ->default(now())
                        ->required(),

                    DateTimePicker::make('valid_from')
                        ->label('Vrijedi od')
                        ->seconds(false)
                        ->displayFormat(
                            'd.m.Y. H:i'
                        )
                        ->native(false),

                    DateTimePicker::make('valid_until')
                        ->label('Vrijedi do')
                        ->seconds(false)
                        ->displayFormat(
                            'd.m.Y. H:i'
                        )
                        ->native(false),
                ]),

            Section::make('Za poslove')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('work_types')
                        ->label('Vrsta poslova')
                        ->options(
                            WorkPermit::workTypeOptions()
                        )
                        ->columns(5),

                    TextInput::make('other_work_type')
                        ->label('Ostalo')
                        ->maxLength(50)
                        ->rule('max:50')
                        ->extraAttributes([
                            'maxlength' => 50,
                        ])
                        ->live(onBlur: true)
                        ->helperText(
                            fn ($state) =>
                                mb_strlen(
                                    (string) $state
                                ) . '/50'
                        ),

                    Textarea::make(
                        'request_or_regulation'
                    )
                        ->label('Zahtjev / propis')
                        ->rows(2)
                        ->maxLength(150)
                        ->rule('max:150')
                        ->extraAttributes([
                            'maxlength' => 150,
                        ])
                        ->live(onBlur: true)
                        ->helperText(
                            fn ($state) =>
                                mb_strlen(
                                    (string) $state
                                ) . '/150'
                        ),
                ]),

            Section::make('Radove izvode')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make(
                        'executor_types'
                    )
                        ->label('Izvođači')
                        ->options(
                            WorkPermit::executorTypeOptions()
                        )
                        ->columns(2),

                    Grid::make(3)
                        ->schema([
                            TextInput::make(
                                'worker_1'
                            )->label('Radnik 1'),

                            TextInput::make(
                                'worker_2'
                            )->label('Radnik 2'),

                            TextInput::make(
                                'worker_3'
                            )->label('Radnik 3'),

                            TextInput::make(
                                'worker_4'
                            )->label('Radnik 4'),

                            TextInput::make(
                                'worker_5'
                            )->label('Radnik 5'),

                            TextInput::make(
                                'worker_6'
                            )->label('Radnik 6'),

                            TextInput::make(
                                'worker_7'
                            )->label('Radnik 7'),

                            TextInput::make(
                                'worker_8'
                            )->label('Radnik 8'),

                            TextInput::make(
                                'worker_9'
                            )->label('Radnik 9'),
                        ]),

                    Textarea::make(
                        'work_description'
                    )
                        ->label(
                            'Opis poslova - radova'
                        )
                        ->rows(3)
                        ->maxLength(300)
                        ->rule('max:300')
                        ->extraAttributes([
                            'maxlength' => 300,
                        ])
                        ->live(onBlur: true)
                        ->helperText(
                            fn ($state) =>
                                mb_strlen(
                                    (string) $state
                                ) . '/300'
                        ),

                    Grid::make(2)
                        ->schema([
                            TextInput::make(
                                'contact_person'
                            )
                                ->label(
                                    'Kontakt osoba'
                                )
                                ->maxLength(50)
                                ->rule('max:50')
                                ->extraAttributes([
                                    'maxlength' => 50,
                                ])
                                ->live(
                                    onBlur: true
                                )
                                ->helperText(
                                    fn ($state) =>
                                        mb_strlen(
                                            (string) $state
                                        ) . '/50'
                                ),

                            TextInput::make('phone')
                                ->label(
                                    'Telefonski broj'
                                ),
                        ]),
                ]),

            Section::make('Mjere')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make(
                        'required_measures'
                    )
                        ->label(
                            'Mjere koje je potrebno poduzeti'
                        )
                        ->options(
                            WorkPermit::requiredMeasuresOptions()
                        )
                        ->columns(2),

                    Textarea::make(
                        'additional_measures'
                    )
                        ->label('Dodatne mjere')
                        ->rows(2)
                        ->maxLength(200)
                        ->rule('max:200')
                        ->extraAttributes([
                            'maxlength' => 200,
                        ])
                        ->live(onBlur: true)
                        ->helperText(
                            fn ($state) =>
                                mb_strlen(
                                    (string) $state
                                ) . '/200'
                        ),

                    Textarea::make(
                        'required_equipment'
                    )
                        ->label(
                            'Potrebna oprema'
                        )
                        ->rows(2),
                ]),

            Section::make('Opasnosti rada')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make(
                        'work_hazards'
                    )
                        ->label('Opasnosti')
                        ->options(
                            WorkPermit::hazardOptions()
                        )
                        ->columns(3),

                    TextInput::make(
                        'other_hazard'
                    )
                        ->label('Ostalo')
                        ->maxLength(30)
                        ->rule('max:30')
                        ->extraAttributes([
                            'maxlength' => 30,
                        ])
                        ->live(onBlur: true)
                        ->helperText(
                            fn ($state) =>
                                mb_strlen(
                                    (string) $state
                                ) . '/30'
                        ),
                ]),

            Section::make(
                'Osobna zaštitna oprema'
            )
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make(
                        'required_ppe'
                    )
                        ->label('OZO')
                        ->options(
                            WorkPermit::ppeOptions()
                        )
                        ->columns(4),
                ]),

            Section::make('Odobrenje')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make(
                        'requester_name'
                    )
                        ->label(
                            'Osoba koja zahtjeva dozvolu - ime i prezime'
                        ),

                    TextInput::make(
                        'requester_signature'
                    )
                        ->label(
                            'Osoba koja zahtjeva dozvolu - potpis'
                        ),

                    TextInput::make(
                        'approver_name'
                    )
                        ->label(
                            'Osoba koja odobrava dozvolu - ime i prezime'
                        ),

                    TextInput::make(
                        'approver_signature'
                    )
                        ->label(
                            'Osoba koja odobrava dozvolu - potpis'
                        ),
                ]),

            Section::make(
                'Produženje valjanosti'
            )
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    DateTimePicker::make(
                        'extension_valid_from'
                    )
                        ->label('Vrijedi od')
                        ->seconds(false)
                        ->displayFormat(
                            'd.m.Y. H:i'
                        )
                        ->native(false),

                    DateTimePicker::make(
                        'extension_valid_until'
                    )
                        ->label('Vrijedi do')
                        ->seconds(false)
                        ->displayFormat(
                            'd.m.Y. H:i'
                        )
                        ->native(false),

                    TextInput::make(
                        'extension_approver_name'
                    )
                        ->label(
                            'Osoba koja odobrava produženje - ime i prezime'
                        ),

                    TextInput::make(
                        'extension_approver_signature'
                    )
                        ->label(
                            'Osoba koja odobrava produženje - potpis'
                        ),
                ]),

            Section::make(
                'Provjera izvršenih radova'
            )
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Radio::make('works_finished')
                        ->label(
                            'Radovi su završeni'
                        )
                        ->boolean('DA', 'NE')
                        ->inline(),

                    Radio::make('checked_after')
                        ->label(
                            'Provjera provedena nakon'
                        )
                        ->options([
                            '1h' => '1 sata',
                            '3h' => '3 sata',
                        ])
                        ->inline(),

                    Textarea::make(
                        'unfinished_reason'
                    )
                        ->label(
                            'Ako nisu završeni navesti razlog'
                        )
                        ->rows(3)
                        ->maxLength(150)
                        ->rule('max:150')
                        ->extraAttributes([
                            'maxlength' => 150,
                        ])
                        ->live(onBlur: true)
                        ->helperText(
                            fn ($state) =>
                                mb_strlen(
                                    (string) $state
                                ) . '/150'
                        )
                        ->columnSpanFull(),

                    TextInput::make(
                        'verification_name'
                    )
                        ->label(
                            'Ime i prezime'
                        ),

                    TextInput::make(
                        'verification_signature'
                    )
                        ->label('Potpis'),

                    DatePicker::make(
                        'verification_date'
                    )
                        ->label('Datum')
                        ->displayFormat(
                            'd.m.Y.'
                        ),

                    TextInput::make(
                        'verification_time'
                    )
                        ->label('Vrijeme')
                        ->placeholder('14:30'),
                ]),
        ]);
    }

    public static function infolist(
        Schema $schema
    ): Schema {
        return $schema->components([]);
    }

    /*
     |--------------------------------------------------------------------------
     | TABLICA
     |--------------------------------------------------------------------------
     */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('permit_number')
                    ->label('Broj')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make(
                    'form_version_label'
                )
                    ->label(
                        'Verzija obrasca'
                    )
                    ->alignCenter()
                    ->badge()
                    ->wrap()
                    ->color('gray')
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),

                TextColumn::make('issue_date')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make('work_types')
                    ->label('Vrsta poslova')
                    ->searchable()
                    ->wrap()
                    ->formatStateUsing(
                        function ($state) {
                            $labels =
                                WorkPermit::workTypeOptions();

                            if (blank($state)) {
                                return '-';
                            }

                            $values =
                                is_array($state)
                                    ? $state
                                    : explode(
                                        ',',
                                        (string) $state
                                    );

                            return collect($values)
                                ->map(
                                    fn ($value) =>
                                        trim(
                                            $labels[
                                                trim($value)
                                            ]
                                            ?? trim($value)
                                        )
                                )
                                ->implode(', ');
                        }
                    )
                    ->toggleable(),

                TextColumn::make('valid_from')
                    ->label('Vrijedi od')
                    ->dateTime(
                        'd.m.Y. H:i'
                    )
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('valid_until')
                    ->label('Vrijedi do')
                    ->dateTime(
                        'd.m.Y. H:i'
                    )
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'works_finished'
                )
                    ->label('Završeno')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state) =>
                            match ($state) {
                                true => 'DA',
                                false => 'NE',
                                default => '-',
                            }
                    )
                    ->color(
                        fn ($state) =>
                            match ($state) {
                                true => 'success',
                                false => 'danger',
                                default => 'gray',
                            }
                    )
                    ->toggleable(),
            ])

            ->defaultSort(
                'issue_date',
                'desc'
            )

            ->recordUrl(
                fn (WorkPermit $record): string =>
                    static::getUrl(
                        'view',
                        [
                            'record' =>
                                $record,
                        ]
                    )
            )

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
                            $query,
                            array $data
                        ) {
                            $value =
                                $data['value']
                                ?? null;

                            return match ($value) {
                                'trashed' =>
                                    $query->onlyTrashed(),

                                'all' =>
                                    $query->withTrashed(),

                                default =>
                                    $query->withoutTrashed(),
                            };
                        }
                    ),
            ])

            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            fn (
                                WorkPermit $record
                            ): bool =>
                                ! $record->trashed()
                                && static::canEdit(
                                    $record
                                )
                        ),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WorkPermit $record
                            ): bool =>
                                ! $record->trashed()
                                && static::canDelete(
                                    $record
                                )
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WorkPermit $record
                            ): bool =>
                                $record->trashed()
                                && static::canRestore(
                                    $record
                                )
                        ),

                    ForceDeleteAction::make()
                        ->label(
                            'Trajno obriši'
                        )
                        ->requiresConfirmation()
                        ->visible(
                            fn (
                                WorkPermit $record
                            ): bool =>
                                $record->trashed()
                                && static::canForceDelete(
                                    $record
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
                    ->label('Deaktiviraj označeno')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Deaktiviraj odabrane dozvole za rad'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš deaktivirati odabrane dozvole za rad?'
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
                            && static::canDeleteAny()
                    )
                    ->deselectRecordsAfterCompletion(),

                RestoreBulkAction::make()
                    ->label(
                        'Vrati označeno'
                    )
                    ->requiresConfirmation()
                    ->visible(
                        fn (
                            HasTable $livewire
                        ): bool =>
                            static::isOnlyTrashed(
                                $livewire
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
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Kopiraj dozvolu za rad'
                    )
                    ->modalDescription(
                        'Kopirat će se odabrana dozvola za rad i otvoriti nova za uređivanje. Broj dozvole će se automatski postaviti na sljedeći broj.'
                    )
                    ->modalSubmitActionLabel(
                        'Kopiraj i otvori'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
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
                                        'Odaberi samo jednu dozvolu'
                                    )
                                    ->body(
                                        'Za kopiranje može biti označena samo jedna dozvola za rad.'
                                    )
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $user =
                                Auth::user();

                            if (
                                ! $user
                                || $user->isSuperAdmin()
                            ) {
                                abort(403);
                            }

                            $ownerId =
                                $user->ownerId();

                            if (! $ownerId) {
                                abort(403);
                            }

                            /** @var WorkPermit $record */
                            $record =
                                $records->first();

                            /*
                             * Serverska tenant provjera
                             * i kod bulk kopiranja.
                             */
                            if (
                                (int) $record->user_id
                                !== (int) $ownerId
                            ) {
                                abort(403);
                            }

                            $newRecord =
                                $record->replicate([
                                    'permit_number',
                                    'issue_date',
                                    'created_at',
                                    'updated_at',
                                    'deleted_at',
                                ]);

                            $newRecord
                                ->permit_number =
                                static::generateNextPermitNumber();

                            $newRecord
                                ->issue_date =
                                now()->toDateString();

                            /*
                             * Novi zapis ostaje
                             * u istoj organizaciji.
                             */
                            $newRecord->user_id =
                                $ownerId;

                            $newRecord
                                ->form_version =
                                $record->form_version
                                ?: FormVersionService::
                                    currentWorkPermit();

                            $newRecord->save();

                            Notification::make()
                                ->title(
                                    'Dozvola za rad je kopirana'
                                )
                                ->body(
                                    'Otvara se nova kopirana dozvola za rad za uređivanje.'
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
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno obriši odabrane dozvole za rad'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš trajno obrisati odabrane dozvole za rad? Ova radnja se ne može poništiti.'
                    )
                    ->modalSubmitActionLabel(
                        'Trajno obriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (HasTable $livewire): bool =>
                            static::canForceDeleteAny()
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    private static function isOnlyTrashed(
        HasTable $livewire
    ): bool {
        $state =
            $livewire->getTableFilterState(
                'status'
            );

        $value =
            data_get(
                $state,
                'value'
            );

        return $value === 'trashed';
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListWorkPermits::route(
                    '/'
                ),

            'create' =>
                Pages\CreateWorkPermit::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditWorkPermit::route(
                    '/{record}/edit'
                ),

            'view' =>
                Pages\ViewWorkPermit::route(
                    '/{record}'
                ),
        ];
    }
}
<?php

namespace App\Filament\Resources\WasteTrackingForms;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\WasteTrackingForms\Pages\CreateWasteTrackingForm;
use App\Filament\Resources\WasteTrackingForms\Pages\EditWasteTrackingForm;
use App\Filament\Resources\WasteTrackingForms\Pages\ListWasteTrackingForms;
use App\Filament\Resources\WasteTrackingForms\Pages\ViewWasteTrackingForm;
use App\Models\OntoRecord;
use App\Models\WasteTrackingForm;
use App\Services\ActivityLogger;
use App\Services\FormVersionService;
use App\Services\OntoService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class WasteTrackingFormResource extends BaseResource
{
    protected static ?string $model = WasteTrackingForm::class;

    protected static bool $usesSoftDeletes = true;

    protected static bool $hasOwnership = true;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel =
        'Prateći listovi';

    protected static ?string $modelLabel =
        'Prateći list';

    protected static ?string $pluralModelLabel =
        'Prateći listovi';

    protected static string|\UnitEnum|null $navigationGroup =
        'Zaštita okoliša';

    protected static ?int $navigationSort = 4;

    protected static function getModuleKey(): ?string
    {
        return 'waste_tracking_forms';
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    |
    | Prateći list je poslovni zapis organizacije.
    |
    | Superadmin:
    | - može pregledavati zapise
    | - NE kreira prateće listove u ime organizacija
    |
    | Organizacijski korisnik:
    | - mora imati pravo create
    | - zapis se sprema na ownerId()
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
    | RECORD OWNERSHIP
    |--------------------------------------------------------------------------
    |
    | Dodatna record-level obrana za write operacije.
    |
    | Organizacijski korisnik smije mijenjati samo zapis čiji
    | user_id odgovara ownerId() njegove organizacije.
    |
    | Superadmin nema write pristup poslovnim PL-O zapisima.
    |
    */

    protected static function canManageRecord(
        Model $record
    ): bool {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            return false;
        }

        return (int) $record->user_id ===
            (int) $ownerId;
    }

    public static function canEdit(
        Model $record
    ): bool {
        return parent::canEdit($record)
            && static::canManageRecord($record);
    }

    public static function canDelete(
        Model $record
    ): bool {
        return parent::canDelete($record)
            && static::canManageRecord($record);
    }

    public static function canRestore(
        Model $record
    ): bool {
        return parent::canRestore($record)
            && static::canManageRecord($record);
    }

    public static function canForceDelete(
        Model $record
    ): bool {
        if (
            $record instanceof WasteTrackingForm
            && $record->isLocked()
        ) {
            return false;
        }

        return parent::canForceDelete($record)
            && static::canManageRecord($record);
    }

    /*
    |--------------------------------------------------------------------------
    | OWNERSHIP PRI KREIRANJU
    |--------------------------------------------------------------------------
    |
    | CreateWasteTrackingForm već poziva:
    |
    | WasteTrackingFormResource::fillOwnershipData($data)
    |
    | Ovdje dodatno provjeravamo da odabrani ONTO obrazac
    | pripada istoj organizaciji.
    |
    */

    public static function fillOwnershipData(
        array $data
    ): array {
        $user = auth()->user();

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            abort(403);
        }

        $ownerId = (int) $user->ownerId();

        if ($ownerId <= 0) {
            abort(403);
        }

        $ontoRecordId =
            (int) ($data['onto_record_id'] ?? 0);

        if ($ontoRecordId <= 0) {
            abort(
                403,
                'ONTO obrazac nije ispravno odabran.'
            );
        }

        $validOntoRecord = OntoRecord::query()
            ->whereKey($ontoRecordId)
            ->where(
                'user_id',
                $ownerId
            )
            ->exists();

        abort_unless(
            $validOntoRecord,
            403,
            'Odabrani ONTO obrazac ne pripada vašoj organizaciji.'
        );

        $data['user_id'] = $ownerId;

        return $data;
    }

    public static function form(
        Schema $schema
    ): Schema {
        return $schema
            ->components([
                Select::make('form_version')
                    ->label('Verzija PL-O obrasca')
                    ->options(
                        WasteTrackingForm::formVersions()
                    )
                    ->default(
                        FormVersionService::currentPlo()
                    )
                    ->required()
                    ->helperText(
                        'Verzija se sprema uz prateći list. '
                        . 'Stari prateći listovi ostaju na '
                        . 'staroj verziji obrasca.'
                    ),

                FormSection::make(
                    'POŠILJKA OTPADA (A)'
                )
                    ->schema([
                        Select::make(
                            'onto_record_id'
                        )
                            ->label('ONTO obrazac')
                            ->options(
                                fn () =>
                                    static::getOntoRecordOptions()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(
                                function (
                                    $state,
                                    callable $set
                                ): void {
                                    /*
                                     * ONTO se dohvaća isključivo
                                     * unutar organizacije.
                                     */
                                    $user = auth()->user();

                                    if (
                                        ! $user
                                        || $user->isSuperAdmin()
                                    ) {
                                        return;
                                    }

                                    $ownerId =
                                        $user->ownerId();

                                    if (! $ownerId) {
                                        return;
                                    }

                                    $record =
                                        OntoRecord::query()
                                            ->with([
                                                'wasteType',
                                                'organizationLocation.organization',
                                            ])
                                            ->whereKey($state)
                                            ->where(
                                                'user_id',
                                                $ownerId
                                            )
                                            ->first();

                                    if (
                                        ! $record
                                        || ! $record->wasteType
                                    ) {
                                        return;
                                    }

                                    $rawWasteCode =
                                        (string)
                                        $record
                                            ->wasteType
                                            ->waste_code;

                                    $displayWasteCode =
                                        preg_replace(
                                            '/\D/',
                                            '',
                                            str_replace(
                                                '*',
                                                '',
                                                $rawWasteCode
                                            )
                                        );

                                    $displayWasteCode =
                                        trim(
                                            chunk_split(
                                                $displayWasteCode,
                                                2,
                                                ' '
                                            )
                                        );

                                    if (
                                        str_contains(
                                            $rawWasteCode,
                                            '*'
                                        )
                                    ) {
                                        $displayWasteCode .= '*';
                                    }

                                    $set(
                                        'waste_code_manual',
                                        $displayWasteCode
                                    );

                                    $set(
                                        'description',
                                        $record
                                            ->wasteType
                                            ->name
                                    );

                                    $set(
                                        'waste_description',
                                        $record
                                            ->wasteType
                                            ->name
                                    );

                                    $set(
                                        'waste_kind',
                                        str_ends_with(
                                            $rawWasteCode,
                                            '*'
                                        )
                                            ? 'opasni'
                                            : 'neopasni'
                                    );

                                    $set(
                                        'document_number',
                                        static::generateDocumentNumberFromOnto(
                                            $record
                                        )
                                    );
                                }
                            ),

                        TextInput::make(
                            'document_number'
                        )
                            ->label('Broj PL-O')
                            ->maxLength(255),

                        Grid::make(3)
                            ->schema([
                                TextInput::make(
                                    'waste_code_manual'
                                )
                                    ->label(
                                        'Ključni broj'
                                    )
                                    ->maxLength(50),

                                CheckboxList::make(
                                    'waste_source_types'
                                )
                                    ->label(
                                        'Izvor otpada'
                                    )
                                    ->options([
                                        'komunalni' =>
                                            'Komunalni',

                                        'proizvodni' =>
                                            'Proizvodni',
                                    ])
                                    ->columns(2),

                                Radio::make(
                                    'waste_kind'
                                )
                                    ->label(
                                        'Vrsta otpada'
                                    )
                                    ->options([
                                        'opasni' =>
                                            'Opasni',

                                        'neopasni' =>
                                            'Neopasni',
                                    ])
                                    ->inline()
                                    ->inlineLabel(
                                        false
                                    ),
                            ])
                            ->columnSpanFull(),

                        CheckboxList::make(
                            'hazard_properties'
                        )
                            ->label(
                                'Opasna svojstva'
                            )
                            ->options([
                                'HP1' => 'HP1',
                                'HP2' => 'HP2',
                                'HP3' => 'HP3',
                                'HP4' => 'HP4',
                                'HP5' => 'HP5',
                                'HP6' => 'HP6',
                                'HP7' => 'HP7',
                                'HP8' => 'HP8',
                                'HP9' => 'HP9',
                                'HP10' => 'HP10',
                                'HP11' => 'HP11',
                                'HP12' => 'HP12',
                                'HP13' => 'HP13',
                                'HP14' => 'HP14',
                                'HP15' => 'HP15',
                            ])
                            ->columns(15)
                            ->columnSpanFull(),

                        Grid::make(12)
                            ->schema([
                                CheckboxList::make(
                                    'physical_properties'
                                )
                                    ->label(
                                        'Fizikalna svojstva'
                                    )
                                    ->options([
                                        'prasina' =>
                                            'Prah',

                                        'kruto' =>
                                            'Krutina',

                                        'pastozno' =>
                                            'Pastozno',

                                        'muljevito' =>
                                            'Muljevito',

                                        'tekucina' =>
                                            'Tekuće',

                                        'plinovito' =>
                                            'Plinovito',

                                        'ostalo' =>
                                            'Ostalo',
                                    ])
                                    ->columns(7)
                                    ->live()
                                    ->columnSpan(8),

                                TextInput::make(
                                    'physical_properties_other'
                                )
                                    ->label('Ostalo')
                                    ->placeholder(
                                        'Upišite svojstvo'
                                    )
                                    ->visible(
                                        fn (
                                            callable $get
                                        ): bool =>
                                            in_array(
                                                'ostalo',
                                                $get(
                                                    'physical_properties'
                                                ) ?? [],
                                                true
                                            )
                                    )
                                    ->columnSpan(4),
                            ])
                            ->columnSpanFull(),

                        Grid::make(12)
                            ->schema([
                                CheckboxList::make(
                                    'packaging_types'
                                )
                                    ->label(
                                        'Pakiranje otpada'
                                    )
                                    ->options([
                                        'rasuto' =>
                                            'Rasuto',

                                        'posude' =>
                                            'Posuda',

                                        'kanta' =>
                                            'Kanta',

                                        'kanister' =>
                                            'Kanistar',

                                        'kontejner' =>
                                            'Kontejner',

                                        'bacva' =>
                                            'Bačva',

                                        'kutija' =>
                                            'Kutija',

                                        'vreca' =>
                                            'Vreća',

                                        'ostalo' =>
                                            'Ostalo',
                                    ])
                                    ->columns(9)
                                    ->columnSpan(10),

                                TextInput::make(
                                    'package_count'
                                )
                                    ->label(
                                        'Broj pakiranja'
                                    )
                                    ->numeric()
                                    ->columnSpan(2),
                            ])
                            ->columnSpanFull(),

                        Textarea::make(
                            'waste_description'
                        )
                            ->label('Opis otpada')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make(
                            'municipal_origin_note'
                        )
                            ->label(
                                'Porijeklo komunalnog otpada'
                            )
                            ->rows(2)
                            ->helperText(
                                'Ispunjava samo davatelj javne usluge'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                FormSection::make(
                    'POŠILJATELJ (B)'
                )
                    ->schema([
                        TextInput::make(
                            'sender_person_name'
                        )
                            ->label('Naziv'),

                        TextInput::make(
                            'sender_oib'
                        )
                            ->label('OIB / B.P.'),

                        TextInput::make(
                            'sender_nkd_code'
                        )
                            ->label(
                                'NKD razred (2007)'
                            ),

                        TextInput::make(
                            'sender_contact_person'
                        )
                            ->label(
                                'Kontakt osoba'
                            ),

                        TextInput::make(
                            'sender_contact_data'
                        )
                            ->label(
                                'Kontakt podaci'
                            ),
                    ])
                    ->columns(1)
                    ->columnSpan(1),

                FormSection::make(
                    'TOK OTPADA (F)'
                )
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Placeholder::make(
                                    'report_choice_label'
                                )
                                    ->hiddenLabel()
                                    ->content(
                                        'IZVJEŠĆE: O OBRADI OTPADA:'
                                    )
                                    ->columnSpan(6),

                                Radio::make(
                                    'report_choice'
                                )
                                    ->hiddenLabel()
                                    ->options([
                                        'da' => 'Da',
                                        'ne' => 'Ne',
                                    ])
                                    ->inline()
                                    ->columnSpan(6),

                                Placeholder::make(
                                    'purpose_choice_label'
                                )
                                    ->hiddenLabel()
                                    ->content(
                                        'NAMJENA:'
                                    )
                                    ->columnSpan(6),

                                Radio::make(
                                    'purpose_choice'
                                )
                                    ->hiddenLabel()
                                    ->options([
                                        'oporaba' =>
                                            'Oporaba',

                                        'zbrinjavanje' =>
                                            'Zbrinjavanje',
                                    ])
                                    ->inline()
                                    ->columnSpan(6),

                                Placeholder::make(
                                    'dispatch_point_label'
                                )
                                    ->hiddenLabel()
                                    ->content(
                                        'POLAZIŠTE:'
                                    )
                                    ->columnSpan(3),

                                TextInput::make(
                                    'dispatch_point'
                                )
                                    ->hiddenLabel()
                                    ->columnSpan(9),

                                Placeholder::make(
                                    'destination_point_label'
                                )
                                    ->hiddenLabel()
                                    ->content(
                                        'ODREDIŠTE:'
                                    )
                                    ->columnSpan(3),

                                TextInput::make(
                                    'destination_point'
                                )
                                    ->hiddenLabel()
                                    ->columnSpan(9),

                                Placeholder::make(
                                    'quantity_label'
                                )
                                    ->hiddenLabel()
                                    ->content(
                                        'KOLIČINA:'
                                    )
                                    ->columnSpan(2),

                                TextInput::make(
                                    'quantity_m3'
                                )
                                    ->label('m³')
                                    ->numeric()
                                    ->columnSpan(2),

                                TextInput::make(
                                    'quantity_kg'
                                )
                                    ->label('kg')
                                    ->required()
                                    ->numeric()
                                    ->columnSpan(2),

                                Radio::make(
                                    'quantity_determination_choice'
                                )
                                    ->hiddenLabel()
                                    ->options([
                                        'vaganje' =>
                                            'Vaganje',

                                        'procjena' =>
                                            'Procjena',
                                    ])
                                    ->inline()
                                    ->columnSpan(6),

                                Placeholder::make(
                                    'handover_date_label'
                                )
                                    ->hiddenLabel()
                                    ->content(
                                        'DATUM PREDAJE:'
                                    )
                                    ->columnSpan(3),

                                DatePicker::make(
                                    'handover_date'
                                )
                                    ->hiddenLabel()
                                    ->native(false)
                                    ->displayFormat(
                                        'd.m.Y.'
                                    )
                                    ->columnSpan(9),

                                Placeholder::make(
                                    'handed_over_by_label'
                                )
                                    ->hiddenLabel()
                                    ->content(
                                        'PREDAO:'
                                    )
                                    ->columnSpan(3),

                                TextInput::make(
                                    'handed_over_by'
                                )
                                    ->hiddenLabel()
                                    ->columnSpan(9),
                            ]),
                    ])
                    ->columns(1)
                    ->columnSpan(1),

                FormSection::make(
                    'PRIJEVOZNIK (C)'
                )
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FormSection::make('')
                                    ->schema([
                                        TextInput::make(
                                            'carrier_name'
                                        )
                                            ->label(
                                                'NAZIV'
                                            ),

                                        TextInput::make(
                                            'carrier_oib'
                                        )
                                            ->label(
                                                'OIB'
                                            ),

                                        TextInput::make(
                                            'carrier_authorization'
                                        )
                                            ->label(
                                                'OVLAST ZA PRIJEVOZ'
                                            ),

                                        TextInput::make(
                                            'carrier_contact_person'
                                        )
                                            ->label(
                                                'KONTAKT OSOBA'
                                            ),

                                        TextInput::make(
                                            'carrier_contact_data'
                                        )
                                            ->label(
                                                'KONTAKT PODACI'
                                            ),
                                    ])
                                    ->columns(1),

                                FormSection::make('')
                                    ->schema([
                                        CheckboxList::make(
                                            'transport_modes'
                                        )
                                            ->label(
                                                'NAČIN PRIJEVOZA'
                                            )
                                            ->options([
                                                'cestovni' =>
                                                    'cestovni',

                                                'zracni' =>
                                                    'zračni',

                                                'zeljeznicki' =>
                                                    'željeznički',

                                                'unutarnji_plovni_put' =>
                                                    'unutarnjim plovnim putem',

                                                'morski' =>
                                                    'morski',
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),

                                        TextInput::make(
                                            'carrier_vehicle_registration'
                                        )
                                            ->label(
                                                'REGISTARSKA OZNAKA'
                                            ),

                                        TextInput::make(
                                            'carrier_taken_over_by'
                                        )
                                            ->label(
                                                'PREUZEO'
                                            ),

                                        DatePicker::make(
                                            'carrier_taken_over_at'
                                        )
                                            ->label(
                                                'DATUM PREDAJE'
                                            )
                                            ->displayFormat(
                                                'd.m.Y.'
                                            )
                                            ->native(false),

                                        TextInput::make(
                                            'carrier_delivered_by'
                                        )
                                            ->label(
                                                'PREDAO'
                                            ),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                FormSection::make(
                    'PRIMATELJ (D)'
                )
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FormSection::make('')
                                    ->schema([
                                        TextInput::make(
                                            'receiver_name'
                                        )
                                            ->label(
                                                'NAZIV'
                                            ),

                                        TextInput::make(
                                            'receiver_oib'
                                        )
                                            ->label(
                                                'OIB'
                                            ),

                                        TextInput::make(
                                            'receiver_authorization'
                                        )
                                            ->label(
                                                'OVLAST ZA PREUZIMANJE'
                                            ),

                                        TextInput::make(
                                            'receiver_contact_person'
                                        )
                                            ->label(
                                                'KONTAKT OSOBA'
                                            ),

                                        TextInput::make(
                                            'receiver_contact_data'
                                        )
                                            ->label(
                                                'KONTAKT PODACI'
                                            ),
                                    ])
                                    ->columns(1),

                                FormSection::make('')
                                    ->schema([
                                        TextInput::make(
                                            'receiver_taken_over_by'
                                        )
                                            ->label(
                                                'PREUZEO'
                                            ),

                                        DatePicker::make(
                                            'receiver_weighing_time'
                                        )
                                            ->label(
                                                'DATUM VAGANJA'
                                            )
                                            ->displayFormat(
                                                'd.m.Y.'
                                            )
                                            ->native(false),

                                        TextInput::make(
                                            'receiver_measured_quantity_kg'
                                        )
                                            ->label(
                                                'PREUZETA KOLIČINA (kg)'
                                            )
                                            ->numeric(),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),

                FormSection::make(
                    'POSREDNIK ILI TRGOVAC (E)'
                )
                    ->schema([
                        TextInput::make(
                            'trader_name'
                        )
                            ->label('NAZIV'),

                        TextInput::make(
                            'trader_oib'
                        )
                            ->label('OIB'),

                        TextInput::make(
                            'trader_authorization'
                        )
                            ->label('OVLAST'),

                        TextInput::make(
                            'trader_contact_person'
                        )
                            ->label(
                                'KONTAKT OSOBA'
                            ),

                        TextInput::make(
                            'trader_contact_data'
                        )
                            ->label(
                                'KONTAKT PODACI'
                            ),
                    ])
                    ->columns(1)
                    ->columnSpan(1),

                FormSection::make(
                    'KONAČNI OBRAĐIVAČ (G)'
                )
                    ->schema([
                        TextInput::make(
                            'processor_name'
                        )
                            ->label('NAZIV'),

                        TextInput::make(
                            'processor_oib'
                        )
                            ->label('OIB'),

                        TextInput::make(
                            'processor_authorization'
                        )
                            ->label(
                                'OVLAST ZA OBRADU'
                            ),

                        DatePicker::make(
                            'processing_completed_at'
                        )
                            ->label(
                                'OBRADA ZAVRŠENA DANA'
                            )
                            ->displayFormat(
                                'd.m.Y.'
                            )
                            ->native(false),

                        TextInput::make(
                            'final_processing_method'
                        )
                            ->label(
                                'POSTUPAK OBRADE'
                            ),

                        TextInput::make(
                            'processor_confirmed_by'
                        )
                            ->label(
                                'POTVRDIO'
                            ),
                    ])
                    ->columns(1)
                    ->columnSpan(1),

                FormSection::make(
                    'NAPOMENE I PRILOZI (H)'
                )
                    ->schema([
                        Textarea::make('note')
                            ->label('NAPOMENE')
                            ->rows(6)
                            ->columnSpanFull(),

                        FileUpload::make(
                            'attachments'
                        )
                            ->label('PRILOZI')
                            ->multiple()
                            ->downloadable()
                            ->openable()
                            ->directory(
                                'waste-tracking-forms'
                            )
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

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
            ->modifyQueryUsing(
                fn (Builder $query) =>
                    $query
                        ->orderByDesc(
                            'handover_date'
                        )
                        ->orderByDesc(
                            'created_at'
                        )
                        ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make(
                    'document_number'
                )
                    ->label('Broj PL-O')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
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

                TextColumn::make(
                    'handover_date'
                )
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'ontoRecord.organizationLocation.name'
                )
                    ->label('Lokacija')
                    ->formatStateUsing(
                        fn (
                            $state,
                            WasteTrackingForm $record
                        ) =>
                            $record
                                ->ontoRecord
                                ?->organizationLocation
                                ?->display_name

                            ?? $record
                                ->ontoRecord
                                ?->organizationLocation
                                ?->name

                            ?? $record
                                ->ontoRecord
                                ?->organizationLocation
                                ?->location_name

                            ?? '-'
                    )
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'ontoRecord.wasteType.waste_code'
                )
                    ->label('K.B.')
                    ->html()
                    ->formatStateUsing(
                        function ($state) {
                            if (! $state) {
                                return '-';
                            }

                            $star =
                                str_contains(
                                    $state,
                                    '*'
                                )
                                    ? '<sup>*</sup>'
                                    : '';

                            $code =
                                str_replace(
                                    '*',
                                    '',
                                    $state
                                );

                            $code =
                                preg_replace(
                                    '/\D/',
                                    '',
                                    $code
                                );

                            $formatted =
                                trim(
                                    chunk_split(
                                        $code,
                                        2,
                                        ' '
                                    )
                                );

                            return $formatted
                                . $star;
                        }
                    )
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'ontoRecord.wasteType.name'
                )
                    ->label('Naziv otpada')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make(
                    'quantity_kg'
                )
                    ->label(
                        'Količina (kg)'
                    )
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(
                        fn ($state) =>
                            number_format(
                                (float) $state,
                                2,
                                ',',
                                '.'
                            )
                    )
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(
                        fn (
                            string $state
                        ) =>
                            $state === 'locked'
                                ? 'Zaključen'
                                : 'Nacrt'
                    )
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'locked',
                    ])
                    ->toggleable(),

                TextColumn::make(
                    'locked_at'
                )
                    ->label('Zaključan')
                    ->dateTime(
                        'd.m.Y. H:i'
                    )
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),

                TextColumn::make(
                    'deleted_at'
                )
                    ->label(
                        'Deaktivirano'
                    )
                    ->dateTime(
                        'd.m.Y. H:i'
                    )
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Nacrt',
                        'locked' =>
                            'Zaključen',
                    ]),

                SelectFilter::make(
                    'onto_record_id'
                )
                    ->label('ONTO obrazac')
                    ->relationship(
                        'ontoRecord',
                        'id'
                    )
                    ->searchable()
                    ->preload(),

                Filter::make('year')
                    ->form([
                        TextInput::make(
                            'year'
                        )
                            ->label('Godina')
                            ->numeric(),
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return $query->when(
                                filled(
                                    $data['year']
                                        ?? null
                                ),
                                fn (
                                    Builder $query
                                ) =>
                                    $query
                                        ->whereHas(
                                            'ontoRecord',
                                            fn (
                                                Builder $q
                                            ) =>
                                                $q->where(
                                                    'year',
                                                    $data[
                                                        'year'
                                                    ]
                                                )
                                        )
                            );
                        }
                    ),

                SelectFilter::make(
                    'record_status'
                )
                    ->label(
                        'Status zapisa'
                    )
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

                            return match (
                                $value
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
            ])
            ->recordActions([
                ActionGroup::make([
                    /*
                     * Pregled je dopušten i
                     * superadminu.
                     */
                    ViewAction::make()
                        ->label('Prikaz'),

                    /*
                     * Uređivanje:
                     * samo organizacijski korisnik
                     * + isti owner
                     * + nije zaključano
                     * + nije deaktivirano
                     * + pravo update
                     */
                    Action::make(
                        'editWasteTrackingForm'
                    )
                        ->label('Uredi')
                        ->icon(
                            'heroicon-o-pencil-square'
                        )
                        ->visible(
                            fn (
                                WasteTrackingForm $record
                            ): bool =>
                                static::canManageRecord(
                                    $record
                                )
                                && ! $record
                                    ->isLocked()
                                && ! $record
                                    ->trashed()
                        )
                        ->action(
                            function (
                                WasteTrackingForm $record
                            ) {
                                if (
                                    ! static::canManageRecord(
                                        $record
                                    )
                                ) {
                                    abort(403);
                                }

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

                    /*
                     * Zaključavanje je write
                     * operacija.
                     *
                     * Superadmin je nema.
                     */
                    Action::make('lock')
                        ->label('Zaključi')
                        ->icon(
                            'heroicon-o-lock-closed'
                        )
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(
                            'Zaključi prateći list'
                        )
                        ->modalDescription(
                            'Zaključavanjem će se automatski evidentirati izlaz u ONTO obrascu i skinuti količina sa stanja.'
                        )
                        ->visible(
                            fn (
                                WasteTrackingForm $record
                            ): bool =>
                                static::canManageRecord(
                                    $record
                                )
                                && ! $record
                                    ->isLocked()
                                && ! $record
                                    ->trashed()
                        )
                        ->action(
                            function (
                                WasteTrackingForm $record
                            ): void {
                                if (
                                    ! static::canManageRecord(
                                        $record
                                    )
                                ) {
                                    abort(403);
                                }

                                if (
                                    ! static::allowsModulePermission(
                                        'update'
                                    )
                                ) {
                                    return;
                                }

                                /*
                                 * Dodatna serverska
                                 * tenant provjera ONTO
                                 * zapisa.
                                 */
                                $ontoValid =
                                    OntoRecord::query()
                                        ->whereKey(
                                            $record
                                                ->onto_record_id
                                        )
                                        ->where(
                                            'user_id',
                                            static::ownerId()
                                        )
                                        ->exists();

                                abort_unless(
                                    $ontoValid,
                                    403
                                );

                                try {
                                    app(
                                        OntoService::class
                                    )
                                        ->lockTrackingForm(
                                            $record
                                        );

                                    ActivityLogger::status(
                                        module:
                                            'Prateći listovi otpada',

                                        title:
                                            'Prateći list zaključen',

                                        description:
                                            'Zaključen je prateći list: '
                                            . (
                                                $record
                                                    ->document_number
                                                ?: $record
                                                    ->display_name
                                            ),

                                        record:
                                            $record,
                                    );

                                    Notification::make()
                                        ->title(
                                            'Prateći list je zaključen.'
                                        )
                                        ->success()
                                        ->send();
                                } catch (
                                    RuntimeException $e
                                ) {
                                    Notification::make()
                                        ->title(
                                            $e
                                                ->getMessage()
                                        )
                                        ->danger()
                                        ->send();
                                }
                            }
                        ),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->before(
                            static::beforeModulePermission(
                                'delete'
                            )
                        )
                        ->visible(
                            fn (
                                WasteTrackingForm $record
                            ): bool =>
                                static::canManageRecord(
                                    $record
                                )
                                && ! $record
                                    ->trashed()
                        )
                        ->modalHeading(
                            'Deaktiviraj prateći list'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a da želiš deaktivirati ovaj prateći list?'
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->before(
                            static::beforeModulePermission(
                                'delete'
                            )
                        )
                        ->visible(
                            fn (
                                WasteTrackingForm $record
                            ): bool =>
                                static::canManageRecord(
                                    $record
                                )
                                && $record
                                    ->trashed()
                        ),

                    ForceDeleteAction::make()
                        ->label(
                            'Trajno izbriši'
                        )
                        ->requiresConfirmation()
                        ->before(
                            static::beforeModulePermission(
                                'delete'
                            )
                        )
                        ->visible(
                            fn (
                                WasteTrackingForm $record
                            ): bool =>
                                static::canManageRecord(
                                    $record
                                )
                                && $record
                                    ->trashed()
                                && ! $record
                                    ->isLocked()
                        )
                        ->modalHeading(
                            'Trajno izbriši prateći list'
                        )
                        ->modalDescription(
                            'Jesi li siguran/a? Ova radnja je nepovratna.'
                        ),
                ]),
            ])
            ->bulkActions([
                /*
                 * Superadmin nema bulk write
                 * operacije nad poslovnim
                 * zapisima organizacija.
                 */
                DeleteBulkAction::make()
                    ->label(
                        'Deaktiviraj označeno'
                    )
                    ->requiresConfirmation()
                    ->before(
                        static::beforeModulePermission(
                            'delete'
                        )
                    )
                    ->visible(
                        fn (
                            HasTable $livewire
                        ): bool =>
                            ! static::isSuperAdmin()
                            && ! static::isOnlyTrashed(
                                $livewire
                            )
                    )
                    ->modalHeading(
                        'Deaktiviraj odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    ),

                BulkAction::make(
                    'copyToNew'
                )
                    ->label(
                        'Kopiraj i napravi novi'
                    )
                    ->icon(
                        Heroicon::DocumentDuplicate
                    )
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    )
                    ->modalHeading(
                        'Kopiraj označeni prateći list'
                    )
                    ->modalDescription(
                        'Od označenog pratećeg lista napravit će se novi nacrt s kopiranim podacima.'
                    )
                    ->action(
                        function (
                            Collection $records,
                            $livewire
                        ): void {
                            if (
                                static::isSuperAdmin()
                            ) {
                                abort(403);
                            }

                            if (
                                ! static::allowsModulePermission(
                                    'create'
                                )
                            ) {
                                return;
                            }

                            if (
                                $records->count()
                                !== 1
                            ) {
                                Notification::make()
                                    ->title(
                                        'Označi točno jedan prateći list.'
                                    )
                                    ->danger()
                                    ->send();

                                return;
                            }

                            /** @var WasteTrackingForm $source */
                            $source =
                                $records
                                    ->first();

                            /*
                             * Dodatna record-level
                             * tenant zaštita.
                             */
                            if (
                                ! static::canManageRecord(
                                    $source
                                )
                            ) {
                                abort(403);
                            }

                            $source
                                ->loadMissing([
                                    'ontoRecord.wasteType',
                                    'ontoRecord.organizationLocation.organization',
                                ]);

                            /*
                             * ONTO izvornog PL-O mora
                             * pripadati istoj organizaciji.
                             */
                            if (
                                ! $source
                                    ->ontoRecord
                                || (int)
                                    $source
                                        ->ontoRecord
                                        ->user_id
                                    !==
                                    (int)
                                    static::ownerId()
                            ) {
                                abort(403);
                            }

                            $new =
                                $source
                                    ->replicate([
                                        'document_number',
                                        'status',
                                        'locked_at',
                                        'attachments',
                                        'deleted_at',
                                        'created_at',
                                        'updated_at',
                                    ]);

                            $new->user_id =
                                static::defaultUserId();

                            $new->document_number =
                                static::generateDocumentNumberFromOnto(
                                    $source
                                        ->ontoRecord
                                );

                            $new->status =
                                'draft';

                            $new->locked_at =
                                null;

                            $new->deleted_at =
                                null;

                            $new->attachments =
                                [];

                            $new->form_version =
                                $source
                                    ->form_version
                                ?: FormVersionService::currentPlo();

                            $new->save();

                            ActivityLogger::status(
                                module:
                                    'Prateći listovi otpada',

                                title:
                                    'Kopiran prateći list',

                                description:
                                    'Iz pratećeg lista '
                                    . (
                                        $source
                                            ->document_number
                                        ?: $source
                                            ->display_name
                                    )
                                    . ' napravljen je novi nacrt.',

                                record:
                                    $new,
                            );

                            Notification::make()
                                ->title(
                                    'Novi prateći list je napravljen iz kopije.'
                                )
                                ->success()
                                ->send();

                            $livewire
                                ->redirect(
                                    static::getUrl(
                                        'edit',
                                        [
                                            'record' =>
                                                $new,
                                        ]
                                    )
                                );
                        }
                    ),

                RestoreBulkAction::make()
                    ->label(
                        'Vrati označeno'
                    )
                    ->requiresConfirmation()
                    ->before(
                        static::beforeModulePermission(
                            'delete'
                        )
                    )
                    ->visible(
                        fn (
                            HasTable $livewire
                        ): bool =>
                            ! static::isSuperAdmin()
                            && static::isOnlyTrashed(
                                $livewire
                            )
                    ),

                ForceDeleteBulkAction::make()
                    ->label(
                        'Trajno izbriši označeno'
                    )
                    ->requiresConfirmation()
                    ->before(
                        static::beforeModulePermission(
                            'delete'
                        )
                    )
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    )
                    ->modalHeading(
                        'Trajno izbriši odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a? Ova radnja je nepovratna.'
                    ),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ONTO OPTIONS
    |--------------------------------------------------------------------------
    |
    | Organizacija vidi isključivo ONTO zapise svog ownerId().
    |
    | Superadmin ne izrađuje poslovne PL-O obrasce i zato mu
    | ovaj select ne vraća ONTO zapise drugih organizacija.
    |
    */

    protected static function getOntoRecordOptions(): array
    {
        $user = auth()->user();

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            return [];
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            return [];
        }

        return OntoRecord::query()
            ->with([
                'organizationLocation',
                'wasteType',
            ])
            ->where(
                'user_id',
                $ownerId
            )
            ->get()
            ->mapWithKeys(
                function (
                    OntoRecord $record
                ): array {
                    $code =
                        $record
                            ->wasteType
                            ?->waste_code
                        ?? '';

                    if ($code !== '') {
                        $hasStar =
                            str_contains(
                                $code,
                                '*'
                            );

                        $code =
                            str_replace(
                                '*',
                                '',
                                $code
                            );

                        $code =
                            preg_replace(
                                '/\D/',
                                '',
                                $code
                            );

                        $code =
                            trim(
                                chunk_split(
                                    $code,
                                    2,
                                    ' '
                                )
                            );

                        if ($hasStar) {
                            $code .= '*';
                        }
                    }

                    $location =
                        $record
                            ->organizationLocation
                            ?->display_name

                        ?? $record
                            ->organizationLocation
                            ?->name

                        ?? $record
                            ->organizationLocation
                            ?->location_name

                        ?? 'Lokacija';

                    return [
                        $record->id =>
                            $location
                            . ' / '
                            . $code
                            . ' / '
                            . $record->year,
                    ];
                }
            )
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | GENERIRANJE BROJA PL-O
    |--------------------------------------------------------------------------
    |
    | Numeriranje se računa isključivo unutar organizacije.
    |
    | Superadmin ne generira brojeve poslovnih PL-O zapisa.
    |
    */

    public static function generateDocumentNumberFromOnto(
        ?OntoRecord $ontoRecord
    ): string {
        $user = auth()->user();

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            return '';
        }

        $ownerId =
            (int) $user->ownerId();

        if (
            $ownerId <= 0
            || ! $ontoRecord
            || (int)
                $ontoRecord->user_id
                !== $ownerId
        ) {
            return '';
        }

        $ontoRecord->loadMissing([
            'wasteType',
            'organization',
            'organizationLocation.organization',
        ]);

        $wasteCode =
            preg_replace(
                '/\D/',
                '',
                (string) (
                    $ontoRecord
                        ->wasteType
                        ?->waste_code
                    ?? ''
                )
            );

        $oib =
            preg_replace(
                '/\D/',
                '',
                (string) (
                    $ontoRecord
                        ->organization
                        ?->oib

                    ?? $ontoRecord
                        ->organizationLocation
                        ?->organization
                        ?->oib

                    ?? ''
                )
            );

        /*
         * Prva tri znaka:
         * oznaka organizacijske jedinice.
         *
         * Ako oznaka nije unesena:
         * 000
         */
        $unitCodeRaw =
            preg_replace(
                '/\D/',
                '',
                (string) (
                    $ontoRecord
                        ->organizationLocation
                        ?->unit_code
                    ?? ''
                )
            );

        $unitCode =
            filled($unitCodeRaw)
                ? substr(
                    str_pad(
                        $unitCodeRaw,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),
                    -3
                )
                : '000';

        /*
         * Druga tri znaka:
         * interna oznaka lokacije.
         *
         * Ako nije unesena:
         * 000
         */
        $internalCodeRaw =
            preg_replace(
                '/\D/',
                '',
                (string) (
                    $ontoRecord
                        ->organizationLocation
                        ?->internal_code
                    ?? ''
                )
            );

        $internalCode =
            filled($internalCodeRaw)
                ? substr(
                    str_pad(
                        $internalCodeRaw,
                        3,
                        '0',
                        STR_PAD_LEFT
                    ),
                    -3
                )
                : '000';

        $prefix =
            $wasteCode
            . '-'
            . $oib
            . '-'
            . $unitCode
            . $internalCode;

        $year =
            (int) (
                $ontoRecord->year
                ?: now()->year
            );

        /*
         * Uzimamo i deaktivirane zapise kako se
         * već iskorišteni PL-O broj ne bi
         * dodijelio ponovno.
         *
         * Query je dodatno ograničen na
         * ownerId organizacije.
         */
        $existingNumbers =
            static::getModel()::query()
                ->withTrashed()
                ->where(
                    'user_id',
                    $ownerId
                )
                ->where(
                    'document_number',
                    'like',
                    $prefix . '-%'
                )
                ->whereHas(
                    'ontoRecord',
                    function (
                        Builder $query
                    ) use (
                        $year,
                        $ownerId
                    ): void {
                        $query
                            ->where(
                                'year',
                                $year
                            )
                            ->where(
                                'user_id',
                                $ownerId
                            );
                    }
                );

        $highestOrdinal =
            $existingNumbers
                ->pluck(
                    'document_number'
                )
                ->map(
                    function (
                        ?string $documentNumber
                    ) use (
                        $prefix
                    ): int {
                        if (
                            ! $documentNumber
                        ) {
                            return 0;
                        }

                        $suffix =
                            str_replace(
                                $prefix . '-',
                                '',
                                $documentNumber
                            );

                        return ctype_digit(
                            $suffix
                        )
                            ? (int) $suffix
                            : 0;
                    }
                )
                ->max()
            ?? 0;

        $nextOrdinal =
            $highestOrdinal + 1;

        return $prefix
            . '-'
            . str_pad(
                (string) $nextOrdinal,
                2,
                '0',
                STR_PAD_LEFT
            );
    }

    private static function isOnlyTrashed(
        HasTable $livewire
    ): bool {
        $state =
            $livewire
                ->getTableFilterState(
                    'record_status'
                );

        $value =
            data_get(
                $state,
                'value'
            );

        return $value === 'trashed';
    }

    public static function getRelations(): array
    {
        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | TENANT QUERY
    |--------------------------------------------------------------------------
    |
    | BaseResource radi osnovni ownership scope:
    |
    | - superadmin -> vidi sve
    | - organizacija -> user_id = ownerId()
    |
    | Ovdje samo eager-loadamo veze koje ovaj modul koristi.
    |
    */

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'ontoRecord.organizationLocation',
                'ontoRecord.wasteType',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                ListWasteTrackingForms::route(
                    '/'
                ),

            'create' =>
                CreateWasteTrackingForm::route(
                    '/create'
                ),

            'view' =>
                ViewWasteTrackingForm::route(
                    '/{record}'
                ),

            'edit' =>
                EditWasteTrackingForm::route(
                    '/{record}/edit'
                ),
        ];
    }
}
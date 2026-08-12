<?php

namespace App\Filament\Resources\OntoRecords;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\OntoRecords\Pages\CreateOntoRecord;
use App\Filament\Resources\OntoRecords\Pages\EditOntoRecord;
use App\Filament\Resources\OntoRecords\Pages\ListOntoRecords;
use App\Filament\Resources\OntoRecords\Pages\ViewOntoRecord;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Models\OntoRecord;
use App\Models\WasteOrganizationLocation;
use App\Models\WasteTrackingForm;
use App\Models\WasteType;
use App\Services\OntoService;
use Filament\Actions\Action;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class OntoRecordResource extends BaseResource
{
    protected static ?string $model = OntoRecord::class;

    /*
     * ONTO koristi SoftDeletes.
     * BaseResource će zato pravilno omogućiti
     * aktivne/deaktivirane zapise i tenant scope.
     */
    protected static bool $usesSoftDeletes = true;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel =
        'ONTO obrasci';

    protected static ?string $modelLabel =
        'ONTO obrazac';

    protected static ?string $pluralModelLabel =
        'ONTO obrasci';

    protected static string|\UnitEnum|null $navigationGroup =
        'Zaštita okoliša';

    protected static ?int $navigationSort = 3;

    protected static function getModuleKey(): ?string
    {
        return 'onto_records';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            /*
             * user_id se koristi samo kod CREATE operacije.
             *
             * Kod uređivanja se ownership ne šalje iz forme,
             * pa ga superadmin niti organizacijski korisnik
             * ne mogu slučajno promijeniti.
             */
            Hidden::make('user_id')
                ->default(fn () => static::ownerId())
                ->dehydrated(
                    fn (string $operation): bool =>
                        $operation === 'create'
                        && ! static::isSuperAdmin()
                ),

            FormSection::make('Podaci o ONTO obrascu')
                ->schema([
                    Select::make('waste_organization_id')
                        ->label('Organizacija')
                        ->relationship(
                            name: 'organization',
                            titleAttribute: 'company_name',
                            modifyQueryUsing:
                                fn (Builder $query): Builder =>
                                    static::applyOrganizationScope(
                                        $query
                                    ),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

                    Select::make(
                        'waste_organization_location_id'
                    )
                        ->label('Lokacija')
                        ->options(
                            function (callable $get): array {
                                $organizationId = $get(
                                    'waste_organization_id'
                                );

                                if (! $organizationId) {
                                    return [];
                                }

                                $query =
                                    WasteOrganizationLocation::
                                        query()
                                        ->where(
                                            'waste_organization_id',
                                            $organizationId
                                        )
                                        ->where(
                                            'is_active',
                                            true
                                        )
                                        ->orderBy('name');

                                /*
                                 * Dodatna tenant zaštita:
                                 * organizacijski korisnik može
                                 * odabrati samo lokaciju svoje
                                 * organizacije.
                                 */
                                if (
                                    ! static::isSuperAdmin()
                                ) {
                                    $query->whereHas(
                                        'organization',
                                        fn (
                                            Builder $q
                                        ): Builder =>
                                            $q->where(
                                                'user_id',
                                                static::ownerId()
                                            )
                                    );
                                }

                                return $query
                                    ->get()
                                    ->mapWithKeys(
                                        fn (
                                            WasteOrganizationLocation $location
                                        ): array => [
                                            $location->id =>
                                                $location->display_name,
                                        ]
                                    )
                                    ->toArray();
                            }
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('waste_type_id')
                        ->label('Vrsta otpada')
                        ->relationship(
                            name: 'wasteType',
                            titleAttribute: 'name',
                            modifyQueryUsing:
                                function (
                                    Builder $query
                                ): Builder {
                                    if (
                                        static::isSuperAdmin()
                                    ) {
                                        return $query;
                                    }

                                    return $query->where(
                                        'user_id',
                                        static::ownerId()
                                    );
                                },
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (
                                WasteType $record
                            ): string =>
                                $record->display_name
                        )
                        ->searchable([
                            'waste_code',
                            'name',
                        ])
                        ->preload()
                        ->required(),

                    TextInput::make('year')
                        ->label('Godina')
                        ->required()
                        ->numeric()
                        ->default(now()->year)
                        ->minValue(2020)
                        ->maxValue(2100),

                    TextInput::make(
                        'responsible_person'
                    )
                        ->label('Odgovorna osoba')
                        ->maxLength(255),

                    DatePicker::make('opening_date')
                        ->label('Datum otvaranja')
                        ->displayFormat('d.m.Y.')
                        ->format('Y-m-d')
                        ->native(false)
                        ->default(now()),

                    DatePicker::make('closing_date')
                        ->label('Datum zatvaranja')
                        ->displayFormat('d.m.Y.')
                        ->format('Y-m-d')
                        ->native(false),

                    TextInput::make(
                        'current_balance_kg'
                    )
                        ->label(
                            'Trenutno stanje (kg)'
                        )
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false),

                    Toggle::make('is_closed')
                        ->label('Zatvoren')
                        ->default(false)
                        ->inline(false),

                    Textarea::make('notes')
                        ->label('Napomena')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(3),
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
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make(
                    'organization.company_name'
                )
                    ->label('Organizacija')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                static::userTableColumn()
                    ->toggleable(),

                TextColumn::make(
                    'organizationLocation.name'
                )
                    ->label('Lokacija')
                    ->formatStateUsing(
                        fn (
                            $state,
                            OntoRecord $record
                        ) =>
                            $record
                                ->organizationLocation
                                ?->display_name
                            ?? $record
                                ->organizationLocation
                                ?->name
                            ?? $record
                                ->organizationLocation
                                ?->location_name
                            ?? '-'
                    )
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make(
                    'wasteType.waste_code'
                )
                    ->label('K.B.')
                    ->html()
                    ->formatStateUsing(
                        function (
                            ?string $state
                        ): string {
                            if (! $state) {
                                return '-';
                            }

                            $hasStar =
                                str_ends_with(
                                    $state,
                                    '*'
                                );

                            $code =
                                rtrim(
                                    $state,
                                    '*'
                                );

                            if (
                                strlen($code) === 6
                            ) {
                                $code =
                                    substr(
                                        $code,
                                        0,
                                        2
                                    )
                                    . ' '
                                    . substr(
                                        $code,
                                        2,
                                        2
                                    )
                                    . ' '
                                    . substr(
                                        $code,
                                        4,
                                        2
                                    );
                            }

                            return $hasStar
                                ? $code
                                    . '<sup style="font-size:0.75em">*</sup>'
                                : $code;
                        }
                    )
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make(
                    'wasteType.name'
                )
                    ->label('Naziv otpada')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('year')
                    ->label('Godina')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'current_balance_kg'
                )
                    ->label('Stanje (kg)')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format(
                                (float) $state,
                                2,
                                ',',
                                '.'
                            )
                    )
                    ->toggleable(),

                TextColumn::make(
                    'entries_count'
                )
                    ->label('Stavke')
                    ->counts('entries')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_closed')
                    ->label('Zatvoren')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'opening_date'
                )
                    ->label('Otvoren')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'closing_date'
                )
                    ->label('Zatvoren datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),

                TextColumn::make('deleted_at')
                    ->label('Deaktivirano')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    ),
            ])
            ->filters([
                SelectFilter::make(
                    'waste_organization_id'
                )
                    ->label('Organizacija')
                    ->relationship(
                        'organization',
                        'company_name',
                        fn (
                            Builder $query
                        ): Builder =>
                            static::applyOrganizationScope(
                                $query
                            ),
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make(
                    'waste_organization_location_id'
                )
                    ->label('Lokacija')
                    ->relationship(
                        'organizationLocation',
                        'name',
                        fn (
                            Builder $query
                        ): Builder =>
                            static::applyLocationScope(
                                $query
                            ),
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make(
                    'waste_type_id'
                )
                    ->label('Vrsta otpada')
                    ->relationship(
                        name: 'wasteType',
                        titleAttribute: 'name',
                        modifyQueryUsing:
                            function (
                                Builder $query
                            ): Builder {
                                if (
                                    static::isSuperAdmin()
                                ) {
                                    return $query;
                                }

                                return $query->where(
                                    'user_id',
                                    static::ownerId()
                                );
                            },
                    )
                    ->searchable()
                    ->preload(),

                Filter::make('year')
                    ->form([
                        TextInput::make('year')
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
                                ): Builder =>
                                    $query->where(
                                        'year',
                                        $data['year']
                                    ),
                            );
                        }
                    ),

                SelectFilter::make('is_closed')
                    ->label('Status')
                    ->options([
                        '0' => 'Otvoreni',
                        '1' => 'Zatvoreni',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return $query->when(
                                filled(
                                    $data['value']
                                    ?? null
                                ),
                                fn (
                                    Builder $query
                                ): Builder =>
                                    $query->where(
                                        'is_closed',
                                        (bool) (
                                            (int)
                                            $data[
                                                'value'
                                            ]
                                        )
                                    ),
                            );
                        }
                    ),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaz'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            fn (OntoRecord $record): bool =>
                                ! $record->is_closed
                                && ! $record->trashed()
                                && static::canEdit($record)
                        ),

                    Action::make('add_input')
                        ->label('Unesi ulaz')
                        ->icon(
                            'heroicon-o-plus-circle'
                        )
                        ->color('success')
                        ->visible(
                            fn (
                                OntoRecord $record
                            ): bool =>
                                ! static::isSuperAdmin()
                                && ! $record->is_closed
                                && ! $record->trashed()
                        )
                        ->form([
                            DatePicker::make(
                                'entry_date'
                            )
                                ->label('Datum')
                                ->displayFormat(
                                    'd.m.Y.'
                                )
                                ->format('Y-m-d')
                                ->native(false)
                                ->required()
                                ->default(now()),

                            TextInput::make(
                                'quantity_kg'
                            )
                                ->label(
                                    'Količina (kg)'
                                )
                                ->required()
                                ->numeric()
                                ->minValue(0.01),

                            TextInput::make(
                                'method'
                            )
                                ->label('Način')
                                ->default('UVL')
                                ->maxLength(100),

                            Textarea::make('note')
                                ->label('Napomena')
                                ->rows(3),
                        ])
                        ->action(
                            function (
                                OntoRecord $record,
                                array $data
                            ): void {
                                if (static::isSuperAdmin()) {
                                    abort(403);
                                }

                                try {
                                    app(
                                        OntoService::class
                                    )->addInput(
                                        $record,
                                        $data[
                                            'entry_date'
                                        ],
                                        (float)
                                            $data[
                                                'quantity_kg'
                                            ],
                                        $data[
                                            'method'
                                        ]
                                            ?? 'UVL',
                                        $data[
                                            'note'
                                        ]
                                            ?? null,
                                    );

                                    Notification::make()
                                        ->title(
                                            'Ulaz otpada je uspješno evidentiran.'
                                        )
                                        ->success()
                                        ->send();
                                } catch (
                                    RuntimeException $e
                                ) {
                                    Notification::make()
                                        ->title(
                                            $e->getMessage()
                                        )
                                        ->danger()
                                        ->send();
                                }
                            }
                        ),

                    Action::make('add_output')
                        ->label('Unesi izlaz')
                        ->icon(
                            'heroicon-o-minus-circle'
                        )
                        ->color('warning')
                        ->visible(
                            fn (
                                OntoRecord $record
                            ): bool =>
                                ! static::isSuperAdmin()
                                && ! $record->is_closed
                                && ! $record->trashed()
                        )
                        ->form([
                            DatePicker::make(
                                'entry_date'
                            )
                                ->label('Datum')
                                ->displayFormat(
                                    'd.m.Y.'
                                )
                                ->format('Y-m-d')
                                ->native(false)
                                ->required()
                                ->default(now()),

                            TextInput::make(
                                'quantity_kg'
                            )
                                ->label(
                                    'Količina (kg)'
                                )
                                ->required()
                                ->numeric()
                                ->minValue(0.01),

                            TextInput::make(
                                'method'
                            )
                                ->label('Način')
                                ->default('IP')
                                ->maxLength(100),

                            Textarea::make('note')
                                ->label('Napomena')
                                ->rows(3),
                        ])
                        ->action(
                            function (
                                OntoRecord $record,
                                array $data
                            ): void {
                                if (static::isSuperAdmin()) {
                                    abort(403);
                                }

                                try {
                                    app(
                                        OntoService::class
                                    )->addOutput(
                                        $record,
                                        $data[
                                            'entry_date'
                                        ],
                                        (float)
                                            $data[
                                                'quantity_kg'
                                            ],
                                        $data[
                                            'method'
                                        ]
                                            ?? 'IP',
                                        $data[
                                            'note'
                                        ]
                                            ?? null,
                                    );

                                    Notification::make()
                                        ->title(
                                            'Izlaz otpada je uspješno evidentiran.'
                                        )
                                        ->success()
                                        ->send();
                                } catch (
                                    RuntimeException $e
                                ) {
                                    Notification::make()
                                        ->title(
                                            $e->getMessage()
                                        )
                                        ->danger()
                                        ->send();
                                }
                            }
                        ),

                    Action::make(
                        'create_tracking_form'
                    )
                        ->label(
                            'Novi prateći list'
                        )
                        ->icon(
                            'heroicon-o-document-text'
                        )
                        ->color('info')
                        ->visible(
                            fn (
                                OntoRecord $record
                            ): bool =>
                                ! $record->is_closed
                                && ! $record->trashed()
                                && ! static::isSuperAdmin()
                        )
                        ->fillForm(
                            function (
                                OntoRecord $record
                            ): array {
                                $record->loadMissing([
                                    'organization',
                                    'organizationLocation',
                                    'wasteType',
                                ]);

                                return [
                                    'document_number' =>
                                        WasteTrackingFormResource::
                                            generateDocumentNumberFromOnto(
                                                $record
                                            ),

                                    'handover_date' =>
                                        now(),

                                    'quantity_kg' =>
                                        null,

                                    'description' =>
                                        $record
                                            ->wasteType
                                            ?->name
                                        ?? '',

                                    'note' => null,
                                ];
                            }
                        )
                        ->form([
                            TextInput::make(
                                'document_number'
                            )
                                ->label(
                                    'Broj PL-O'
                                )
                                ->required()
                                ->maxLength(255)
                                ->helperText(
                                    'Broj je automatski predložen, ali ga možete ručno promijeniti.'
                                ),

                            DatePicker::make(
                                'handover_date'
                            )
                                ->label(
                                    'Datum predaje'
                                )
                                ->native(false)
                                ->displayFormat(
                                    'd.m.Y.'
                                )
                                ->format('Y-m-d')
                                ->required(),

                            TextInput::make(
                                'quantity_kg'
                            )
                                ->label(
                                    'Količina (kg)'
                                )
                                ->required()
                                ->numeric()
                                ->minValue(0.01),

                            Textarea::make(
                                'description'
                            )
                                ->label(
                                    'Opis otpada'
                                )
                                ->rows(2)
                                ->required(),

                            Textarea::make('note')
                                ->label('Napomena')
                                ->rows(3),
                        ])
                        ->action(
                            function (
                                OntoRecord $record,
                                array $data
                            ): void {
                                if (
                                    static::isSuperAdmin()
                                ) {
                                    abort(403);
                                }

                                $record->loadMissing([
                                    'organization',
                                    'organizationLocation',
                                    'wasteType',
                                ]);

                                $rawWasteCode =
                                    (string) (
                                        $record
                                            ->wasteType
                                            ?->waste_code
                                        ?? ''
                                    );

                                $wasteCodeDigits =
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
                                    '';

                                if (
                                    $wasteCodeDigits
                                    !== ''
                                ) {
                                    $displayWasteCode =
                                        trim(
                                            chunk_split(
                                                $wasteCodeDigits,
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
                                        $displayWasteCode .=
                                            '*';
                                    }
                                }

                                $description =
                                    filled(
                                        $data[
                                            'description'
                                        ]
                                            ?? null
                                    )
                                        ? trim(
                                            (string)
                                            $data[
                                                'description'
                                            ]
                                        )
                                        : (string) (
                                            $record
                                                ->wasteType
                                                ?->name
                                            ?? ''
                                        );

                                $trackingForm =
                                    WasteTrackingForm::
                                        create([
                                            /*
                                             * Novi PL-O nasljeđuje
                                             * vlasnika ONTO obrasca.
                                             */
                                            'user_id' =>
                                                $record
                                                    ->user_id,

                                            'onto_record_id' =>
                                                $record
                                                    ->id,

                                            'document_number' =>
                                                filled(
                                                    $data[
                                                        'document_number'
                                                    ]
                                                        ?? null
                                                )
                                                    ? trim(
                                                        (string)
                                                        $data[
                                                            'document_number'
                                                        ]
                                                    )
                                                    : WasteTrackingFormResource::
                                                        generateDocumentNumberFromOnto(
                                                            $record
                                                        ),

                                            'handover_date' =>
                                                $data[
                                                    'handover_date'
                                                ]
                                                    ?? now(),

                                            'quantity_kg' =>
                                                $data[
                                                    'quantity_kg'
                                                ],

                                            'waste_code_manual' =>
                                                $displayWasteCode,

                                            'waste_description' =>
                                                $description,

                                            'description' =>
                                                $description,

                                            'waste_kind' =>
                                                str_contains(
                                                    $rawWasteCode,
                                                    '*'
                                                )
                                                    ? 'opasni'
                                                    : 'neopasni',

                                            'sender_name' =>
                                                $record
                                                    ->organization
                                                    ?->company_name
                                                ?? $record
                                                    ->organization
                                                    ?->name,

                                            'sender_person_name' =>
                                                $record
                                                    ->organization
                                                    ?->company_name
                                                ?? $record
                                                    ->organization
                                                    ?->name,

                                            'sender_oib' =>
                                                $record
                                                    ->organization
                                                    ?->oib,

                                            'sender_nkd_code' =>
                                                $record
                                                    ->organization
                                                    ?->nkd_code,

                                            'sender_contact_person' =>
                                                $record
                                                    ->organization
                                                    ?->contact_person,

                                            'sender_contact_data' =>
                                                $record
                                                    ->organization
                                                    ?->contact_data,

                                            'sender_address' =>
                                                $record
                                                    ->organizationLocation
                                                    ?->address
                                                ?? $record
                                                    ->organization
                                                    ?->address,

                                            'dispatch_point' =>
                                                $record
                                                    ->organizationLocation
                                                    ?->address
                                                ?? $record
                                                    ->organization
                                                    ?->address,

                                            'note' =>
                                                $data[
                                                    'note'
                                                ]
                                                    ?? null,

                                            'status' =>
                                                'draft',
                                        ]);

                                Notification::make()
                                    ->title(
                                        'Prateći list je kreiran.'
                                    )
                                    ->body(
                                        'Broj pratećeg lista, ključni broj i opis otpada automatski su popunjeni.'
                                    )
                                    ->success()
                                    ->send();

                                redirect(
                                    WasteTrackingFormResource::
                                        getUrl(
                                            'edit',
                                            [
                                                'record' =>
                                                    $trackingForm,
                                            ]
                                        )
                                );
                            }
                        ),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->visible(
                            fn (OntoRecord $record): bool =>
                                ! $record->trashed()
                                && $record->entries()->count() === 0
                                && static::canDelete($record)
                        )
                        ->requiresConfirmation(),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->visible(
                            fn (OntoRecord $record): bool =>
                                $record->trashed()
                                && static::canRestore($record)
                        )
                        ->requiresConfirmation(),

                    ForceDeleteAction::make()
                        ->label('Trajno izbriši')
                        ->visible(
                            fn (OntoRecord $record): bool =>
                                $record->trashed()
                                && $record->entries()->count() === 0
                                && static::canForceDelete($record)
                        )
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label(
                        'Deaktiviraj označeno'
                    )
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    )
                    ->modalHeading(
                        'Deaktiviraj odabrano'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->before(function (): void {
                        if (static::isSuperAdmin()) {
                            abort(403);
                        }
                    }),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->visible(
                        fn (): bool =>
                            ! static::isSuperAdmin()
                    )
                    ->before(function (): void {
                        if (static::isSuperAdmin()) {
                            abort(403);
                        }
                    }),

                ForceDeleteBulkAction::make()
                    ->label(
                        'Trajno izbriši označeno'
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
                    )
                    ->before(function (): void {
                        if (static::isSuperAdmin()) {
                            abort(403);
                        }
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * BaseResource već rješava tenant ownership:
     *
     * - superadmin vidi sve
     * - glavni korisnik vidi svoju organizaciju
     * - podkorisnik koristi isti ownerId()
     *
     * Ovdje samo dodajemo eager loading.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'organization',
                'organizationLocation',
                'wasteType',
            ]);
    }

    /**
     * Ne radimo novi ručni tenant scope.
     * BaseResource koristi isti siguran query.
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery();
    }

    /**
     * Organizacije koje se nude u ONTO formi/filterima.
     */
    protected static function applyOrganizationScope(
        Builder $query
    ): Builder {
        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'user_id',
            $ownerId
        );
    }

    /**
     * Lokacije koje pripadaju organizacijama
     * trenutačnog tenant-a.
     */
    protected static function applyLocationScope(
        Builder $query
    ): Builder {
        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId = static::ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'organization',
            fn (Builder $q): Builder =>
                $q->where(
                    'user_id',
                    $ownerId
                )
        );
    }

    /**
     * Administracija postojećih ONTO zapisa.
     *
     * Superadmin može administrirati postojeće zapise,
     * ali ne kreira nove ONTO obrasce niti nove
     * poslovne transakcije organizacije.
     */
    public static function canEdit(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canDelete($record);
    }

    public static function canRestore(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canRestore($record);
    }

    public static function canForceDelete(Model $record): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canForceDelete($record);
    }

    /**
     * Jednostavna logika:
     *
     * superadmin ONTO zapise samo pregledava,
     * ali ih ne kreira niti uređuje.
     *
     * Novi ONTO rade glavni korisnik i podkorisnici.
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        return parent::canCreate();
    }

    /**
     * Dodatna zaštita ownershipa kod create operacije.
     */
    public static function mutateFormDataBeforeCreate(
        array $data
    ): array {
        if (! static::isSuperAdmin()) {
            $data['user_id'] =
                static::ownerId();
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                ListOntoRecords::route('/'),

            'create' =>
                CreateOntoRecord::route('/create'),

            'view' =>
                ViewOntoRecord::route(
                    '/{record}'
                ),

            'edit' =>
                EditOntoRecord::route(
                    '/{record}/edit'
                ),
        ];
    }

    /**
     * BaseResource već:
     * - primjenjuje tenant scope
     * - kod SoftDeletes broji samo aktivne zapise
     *   za navigation badge.
     */
    public static function getNavigationBadge(): ?string
    {
        return parent::getNavigationBadge();
    }
}

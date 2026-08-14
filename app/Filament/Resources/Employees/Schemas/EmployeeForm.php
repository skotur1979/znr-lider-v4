<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Services\StorageQuotaService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EmployeeForm
{
    /**
     * Vlasnik organizacije za trenutačni Employee zapis.
     *
     * Organizacijski korisnik:
     * ownerId()
     *
     * Superadmin kod uređivanja:
     * user_id postojećeg zaposlenika.
     */
    private static function ownerIdForRecord(
        $record = null
    ): ?int {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return filled($record?->user_id)
                ? (int) $record->user_id
                : null;
        }

        return $user->ownerId();
    }

    /**
     * Ownership za child zapise alkotestiranja.
     */
    private static function alcoholTestOwnershipData(
        array $data,
        $record = null
    ): array {
        $ownerId = static::ownerIdForRecord(
            $record
        );

        if (! $ownerId) {
            abort(403);
        }

        $data['user_id'] = $ownerId;

        return $data;
    }

    public static function configure(Schema $schema): Schema
    {
        $date = fn (
            string $name,
            string $label,
            bool $required = false
        ) => DatePicker::make($name)
            ->label($label)
            ->required($required)
            ->displayFormat('d.m.Y.')
            ->weekStartsOnMonday()
            ->timezone('Europe/Zagreb');

        return $schema
            ->schema([
                Tabs::make('EmployeeTabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Osnovno')
                            ->schema([
                                Section::make(
                                    'Osobni podatci'
                                )
                                    ->columns(2)
                                    ->columnSpan(1)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(
                                                'Prezime i ime (obavezno)'
                                            )
                                            ->required()
                                            ->maxLength(255),

                                        Select::make('gender')
                                            ->label('Spol')
                                            ->options([
                                                'M' => 'M',
                                                'Ž' => 'Ž',
                                            ])
                                            ->native(false),

                                        TextInput::make('OIB')
                                            ->label('OIB')
                                            ->maxLength(32)
                                            ->rule(
                                                function (
                                                    $record
                                                ) {
                                                    return \Illuminate\Validation\Rule::unique(
                                                        'employees',
                                                        'OIB'
                                                    )
                                                        ->where(
                                                            function (
                                                                $query
                                                            ) use (
                                                                $record
                                                            ) {
                                                                $user =
                                                                    auth()
                                                                        ->user();

                                                                $ownerId =
                                                                    $user?->isSuperAdmin()
                                                                        ? $record?->user_id
                                                                        : $user?->ownerId();

                                                                if (
                                                                    $ownerId
                                                                ) {
                                                                    $query
                                                                        ->where(
                                                                            'user_id',
                                                                            $ownerId
                                                                        );
                                                                }

                                                                $query
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
                                                    'Već postoji zaposlenik s istim OIB-om.',
                                            ]),

                                        TextInput::make('phone')
                                            ->label(
                                                'Telefon/Mobitel'
                                            )
                                            ->maxLength(50),

                                        TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(255),

                                        TextInput::make('job_title')
                                            ->label('Zanimanje')
                                            ->maxLength(255),

                                        TextInput::make('education')
                                            ->label(
                                                'Školska sprema'
                                            )
                                            ->maxLength(255),

                                        TextInput::make(
                                            'place_of_birth'
                                        )
                                            ->label(
                                                'Datum i mjesto rođenja'
                                            )
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        TextInput::make(
                                            'name_of_parents'
                                        )
                                            ->label(
                                                'Ime oca – majke'
                                            )
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        TextInput::make('address')
                                            ->label('Adresa')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        TextInput::make('workplace')
                                            ->label('Radno mjesto')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        TextInput::make(
                                            'organization_unit'
                                        )
                                            ->label(
                                                'Organizacijska jedinica'
                                            )
                                            ->maxLength(255),

                                        TextInput::make(
                                            'contract_type'
                                        )
                                            ->label(
                                                'Vrsta ugovora'
                                            )
                                            ->maxLength(255),

                                        $date(
                                            'employeed_at',
                                            'Datum zaposlenja (obavezno)',
                                            true
                                        ),

                                        $date(
                                            'contract_ended_at',
                                            'Datum prekida ugovora'
                                        ),
                                    ]),

                                Section::make(
                                    'Liječnički pregled'
                                )
                                    ->columns(2)
                                    ->columnSpan(1)
                                    ->schema([
                                        $date(
                                            'medical_examination_valid_from',
                                            'Vrijedi od'
                                        ),

                                        $date(
                                            'medical_examination_valid_until',
                                            'Vrijedi do'
                                        ),

                                        Textarea::make('article')
                                            ->label(
                                                'Članak 3. točke'
                                            )
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Textarea::make('remark')
                                            ->label(
                                                'Napomena liječnika'
                                            )
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->columns(2),

                        Tab::make(
                            'Rokovi i osposobljavanja'
                        )
                            ->schema([
                                Section::make(
                                    'Zaštita na radu – Rad na siguran način'
                                )
                                    ->columns(2)
                                    ->columnSpan(1)
                                    ->schema([
                                        $date(
                                            'occupational_safety_valid_from',
                                            'Vrijedi od'
                                        ),
                                    ]),

                                Section::make(
                                    'Zaštita od požara – ZOP'
                                )
                                    ->columns(2)
                                    ->columnSpan(1)
                                    ->schema([
                                        $date(
                                            'fire_protection_valid_from',
                                            'ZOP – Vrijedi od'
                                        ),

                                        $date(
                                            'fire_protection_statement_at',
                                            'ZOP izjava od'
                                        ),

                                        $date(
                                            'evacuation_valid_from',
                                            'Voditelj evakuacije vrijedi od'
                                        )
                                            ->columnSpanFull(),
                                    ]),

                                /*
                                 * Prva pomoć nema rok važenja.
                                 * Evidentiramo samo datum
                                 * osposobljavanja.
                                 */
                                Section::make('Prva pomoć')
                                    ->columns(1)
                                    ->columnSpan(1)
                                    ->schema([
                                        $date(
                                            'first_aid_valid_from',
                                            'Vrijedi od'
                                        ),
                                    ]),

                                Section::make(
                                    'Toksikologija – Rad s opasnim kemikalijama'
                                )
                                    ->columns(2)
                                    ->columnSpan(1)
                                    ->schema([
                                        $date(
                                            'toxicology_valid_from',
                                            'Vrijedi od'
                                        ),

                                        $date(
                                            'toxicology_valid_until',
                                            'Vrijedi do'
                                        ),
                                    ]),

                                Section::make(
                                    'Rukovanje zapaljivim tvarima'
                                )
                                    ->columns(2)
                                    ->columnSpan(1)
                                    ->schema([
                                        $date(
                                            'handling_flammable_materials_valid_from',
                                            'Vrijedi od'
                                        ),

                                        $date(
                                            'handling_flammable_materials_valid_until',
                                            'Vrijedi do'
                                        ),
                                    ]),

                                Section::make(
                                    'Ovlaštenik poslodavca za ZNR'
                                )
                                    ->columns(2)
                                    ->columnSpan(1)
                                    ->schema([
                                        $date(
                                            'employers_authorization_valid_from',
                                            'Vrijedi od'
                                        ),

                                        $date(
                                            'employers_authorization_valid_until',
                                            'Vrijedi do'
                                        ),
                                    ]),
                            ])
                            ->columns(2),

                        Tab::make('Ostale edukacije')
                            ->schema([
                                Section::make(
                                    'Ostale edukacije i ovlaštenja'
                                )
                                    ->columnSpanFull()
                                    ->schema([
                                        Repeater::make(
                                            'certificates'
                                        )
                                            ->label(
                                                'Popis edukacija / ovlaštenja'
                                            )
                                            ->relationship()
                                            ->createItemButtonLabel(
                                                'Dodaj novi zapis'
                                            )
                                            ->defaultItems(0)
                                            ->minItems(0)
                                            ->columns(3)
                                            ->collapsible()
                                            ->itemLabel(
                                                fn (
                                                    $state
                                                ) =>
                                                    filled(
                                                        $state[
                                                            'title'
                                                        ]
                                                        ?? null
                                                    )
                                                        ? $state[
                                                            'title'
                                                        ]
                                                        : 'Nova stavka'
                                            )
                                            ->schema([
                                                TextInput::make(
                                                    'title'
                                                )
                                                    ->label(
                                                        'Naziv'
                                                    )
                                                    ->maxLength(
                                                        191
                                                    ),

                                                $date(
                                                    'valid_from',
                                                    'Vrijedi od'
                                                ),

                                                $date(
                                                    'valid_until',
                                                    'Vrijedi do'
                                                ),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Alkotestiranje')
                            ->schema([
                                Section::make(
                                    'Evidencija alkotestiranja'
                                )
                                    ->description(
                                        'Unos rezultata kontrole alkoholiziranosti radnika.'
                                    )
                                    ->columnSpanFull()
                                    ->schema([
                                        Repeater::make(
                                            'alcoholTests'
                                        )
                                            ->label(
                                                'Alkotestiranja'
                                            )
                                            ->relationship()
                                            ->createItemButtonLabel(
                                                'Dodaj alkotestiranje'
                                            )
                                            ->defaultItems(0)
                                            ->minItems(0)
                                            ->columns(4)
                                            ->collapsible()
                                            ->itemLabel(
                                                fn (
                                                    $state
                                                ) =>
                                                    filled(
                                                        $state[
                                                            'test_date'
                                                        ]
                                                        ?? null
                                                    )
                                                        ? 'Alkotestiranje - '
                                                            . \Illuminate\Support\Carbon::parse(
                                                                $state[
                                                                    'test_date'
                                                                ]
                                                            )
                                                                ->format(
                                                                    'd.m.Y.'
                                                                )
                                                        : 'Novo alkotestiranje'
                                            )

                                            /*
                                             * user_id nije polje forme.
                                             * Serverski ga postavljamo
                                             * na vlasnika Employee zapisa.
                                             */
                                            ->mutateRelationshipDataBeforeCreateUsing(
                                                function (
                                                    array $data,
                                                    $record
                                                ): array {
                                                    return static::alcoholTestOwnershipData(
                                                        $data,
                                                        $record
                                                    );
                                                }
                                            )
                                            ->mutateRelationshipDataBeforeSaveUsing(
                                                function (
                                                    array $data,
                                                    $record
                                                ): array {
                                                    return static::alcoholTestOwnershipData(
                                                        $data,
                                                        $record
                                                    );
                                                }
                                            )
                                            ->schema([
                                                $date(
                                                    'test_date',
                                                    'Datum kontrole',
                                                    true
                                                ),

                                                TextInput::make(
                                                    'result'
                                                )
                                                    ->label(
                                                        'Rezultat promila'
                                                    )
                                                    ->placeholder(
                                                        '0,50'
                                                    )
                                                    ->suffix('‰')
                                                    ->maxLength(
                                                        10
                                                    )
                                                    ->rule(
                                                        'regex:/^\d+,\d{2}$/'
                                                    )
                                                    ->validationMessages([
                                                        'regex' =>
                                                            'Rezultat upiši u formatu 0,00 npr. 0,50 ili 0,65.',
                                                    ])
                                                    ->helperText(
                                                        'Upisati rezultat u formatu 0,00 npr. 0,50 ili 0,65.'
                                                    ),

                                                TextInput::make(
                                                    'tested_by'
                                                )
                                                    ->label(
                                                        'Kontrolu proveo'
                                                    )
                                                    ->maxLength(
                                                        255
                                                    ),

                                                Textarea::make(
                                                    'note'
                                                )
                                                    ->label(
                                                        'Napomena'
                                                    )
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Prilozi')
                            ->schema([
                                FileUpload::make('pdf')
                                    ->label(
                                        'Dodaj priloge (max. 10, do 30 MB po datoteci)'
                                    )
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory(
                                        'employees/attachments'
                                    )
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->maxSize(30720)
                                    ->preserveFilenames()
                                    ->openable()
                                    ->downloadable()
                                    ->helperText(
                                        function (
                                            $record
                                        ) {
                                            $ownerId =
                                                static::ownerIdForRecord(
                                                    $record
                                                );

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
                                        function (
                                            $record
                                        ) {
                                            return function (
                                                string $attribute,
                                                mixed $value,
                                                \Closure $fail
                                            ) use (
                                                $record
                                            ): void {
                                                $ownerId =
                                                    static::ownerIdForRecord(
                                                        $record
                                                    );

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
                                                        'Dosegnut je maksimalni prostor za pohranu dokumenata organizacije. Obrišite nepotrebne priloge ili kontaktirajte administratora.'
                                                    );
                                                }
                                            };
                                        },
                                    ]),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
<?php

namespace App\Filament\Resources\Chemicals\Schemas;

use App\Enums\HazardStatement;
use App\Enums\PrecautionaryStatement;
use App\Services\StorageQuotaService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ChemicalForm
{
    public static function configure(Schema $schema): Schema
    {
        $date = fn (string $name, string $label) =>
            DatePicker::make($name)
                ->label($label)
                ->displayFormat('d.m.Y.')
                ->weekStartsOnMonday()
                ->timezone('Europe/Zagreb')
                ->nullable();

        return $schema
            ->schema([
                Tabs::make('ChemicalTabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Osnovno')
                            ->schema([
                                Section::make('Osnovni podatci')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('product_name')
                                            ->label('Ime proizvoda')
                                            ->required()
                                            ->maxLength(191),

                                        TextInput::make('cas_number')
                                            ->label('CAS broj')
                                            ->nullable()
                                            ->maxLength(191),

                                        TextInput::make('ufi_number')
                                            ->label('UFI broj')
                                            ->nullable()
                                            ->maxLength(191)
                                            ->dehydrateStateUsing(
                                                function ($state) {
                                                    $state = trim(
                                                        (string) $state
                                                    );

                                                    return in_array(
                                                        $state,
                                                        [
                                                            '',
                                                            '/',
                                                            '-',
                                                        ],
                                                        true
                                                    )
                                                        ? null
                                                        : $state;
                                                }
                                            ),

                                        TextInput::make('usage_location')
                                            ->label('Mjesto upotrebe')
                                            ->required()
                                            ->maxLength(191),
                                    ]),
                            ]),

                        Tab::make('Opasnosti')
                            ->schema([
                                Section::make(
                                    'Označavanje opasnosti'
                                )
                                    ->columns(2)
                                    ->schema([
                                        Select::make(
                                            'hazard_pictograms'
                                        )
                                            ->label(
                                                'Piktogrami opasnosti'
                                            )
                                            ->options([
                                                'GHS01' =>
                                                    'GHS01 – Eksplozivno',

                                                'GHS02' =>
                                                    'GHS02 – Zapaljivo',

                                                'GHS03' =>
                                                    'GHS03 – Oksidirajuće',

                                                'GHS04' =>
                                                    'GHS04 – Plin pod tlakom',

                                                'GHS05' =>
                                                    'GHS05 – Korozivno',

                                                'GHS06' =>
                                                    'GHS06 – Akutna toksičnost',

                                                'GHS07' =>
                                                    'GHS07 – Nadražujuće / štetno',

                                                'GHS08' =>
                                                    'GHS08 – Ozbiljna opasnost za zdravlje',

                                                'GHS09' =>
                                                    'GHS09 – Opasno za okoliš',
                                            ])
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->default([])
                                            ->nullable()
                                            ->placeholder(
                                                'Odaberi jedan ili više piktograma'
                                            )
                                            ->helperText(
                                                'Možete odabrati više piktograma opasnosti.'
                                            )
                                            ->afterStateHydrated(
                                                function (
                                                    Select $component,
                                                    mixed $state
                                                ): void {
                                                    if (
                                                        ! is_array(
                                                            $state
                                                        )
                                                    ) {
                                                        $state =
                                                            filled(
                                                                $state
                                                            )
                                                                ? [
                                                                    $state,
                                                                ]
                                                                : [];
                                                    }

                                                    $normalized =
                                                        collect(
                                                            $state
                                                        )
                                                            ->flatMap(
                                                                function (
                                                                    $value
                                                                ): array {
                                                                    return preg_split(
                                                                        '/\s*[,;]\s*/',
                                                                        (string) $value
                                                                    ) ?: [];
                                                                }
                                                            )
                                                            ->map(
                                                                fn (
                                                                    $value
                                                                ) =>
                                                                    strtoupper(
                                                                        trim(
                                                                            (string) $value
                                                                        )
                                                                    )
                                                            )
                                                            ->filter(
                                                                fn (
                                                                    $value
                                                                ) =>
                                                                    preg_match(
                                                                        '/^GHS0[1-9]$/',
                                                                        $value
                                                                    )
                                                            )
                                                            ->unique()
                                                            ->values()
                                                            ->all();

                                                    $component->state(
                                                        $normalized
                                                    );
                                                }
                                            )
                                            ->dehydrateStateUsing(
                                                function (
                                                    mixed $state
                                                ): array {
                                                    if (
                                                        ! is_array(
                                                            $state
                                                        )
                                                    ) {
                                                        $state =
                                                            filled(
                                                                $state
                                                            )
                                                                ? [
                                                                    $state,
                                                                ]
                                                                : [];
                                                    }

                                                    return collect(
                                                        $state
                                                    )
                                                        ->flatMap(
                                                            function (
                                                                $value
                                                            ): array {
                                                                return preg_split(
                                                                    '/\s*[,;]\s*/',
                                                                    (string) $value
                                                                ) ?: [];
                                                            }
                                                        )
                                                        ->map(
                                                            fn (
                                                                $value
                                                            ) =>
                                                                strtoupper(
                                                                    trim(
                                                                        (string) $value
                                                                    )
                                                                )
                                                        )
                                                        ->filter(
                                                            fn (
                                                                $value
                                                            ) =>
                                                                preg_match(
                                                                    '/^GHS0[1-9]$/',
                                                                    $value
                                                                )
                                                        )
                                                        ->unique()
                                                        ->values()
                                                        ->all();
                                                }
                                            )
                                            ->columnSpanFull(),

                                        Select::make(
                                            'h_statements'
                                        )
                                            ->label(
                                                'H oznake (opasnosti)'
                                            )
                                            ->options(
                                                HazardStatement::list()
                                            )
                                            ->searchable()
                                            ->multiple()
                                            ->nullable()
                                            ->default([])
                                            ->columnSpanFull(),

                                        Select::make(
                                            'p_statements'
                                        )
                                            ->label(
                                                'P oznake (mjere opreza)'
                                            )
                                            ->options(
                                                PrecautionaryStatement::list()
                                            )
                                            ->searchable()
                                            ->multiple()
                                            ->nullable()
                                            ->default([])
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make(
                            'Količine i izloženost'
                        )
                            ->schema([
                                Section::make(
                                    'Količine, granične vrijednosti i STL'
                                )
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make(
                                            'annual_quantity'
                                        )
                                            ->label(
                                                'Godišnje količine (kg/l)'
                                            )
                                            ->nullable()
                                            ->maxLength(191),

                                        TextInput::make(
                                            'gvi_kgvi'
                                        )
                                            ->label(
                                                'GVI / KGVI'
                                            )
                                            ->nullable()
                                            ->maxLength(191),

                                        TextInput::make('voc')
                                            ->label(
                                                'Hlapljivi organski spojevi (VOC)'
                                            )
                                            ->nullable()
                                            ->maxLength(191),

                                        $date(
                                            'stl_hzjz',
                                            'STL – HZJZ'
                                        ),
                                    ]),
                            ]),

                        Tab::make('Prilozi')
                            ->schema([
                                Section::make('Prilozi')
                                    ->schema([
                                        FileUpload::make(
                                            'attachments'
                                        )
                                            ->label(
                                                'Dodaj priloge (max. 10, do 30 MB po datoteci)'
                                            )
                                            ->directory(
                                                function (
                                                    $record
                                                ): string {
                                                    $user =
                                                        auth()
                                                            ->user();

                                                    $ownerId =
                                                        $user?->isSuperAdmin()
                                                            ? $record?->user_id
                                                            : $user?->ownerId();

                                                    /*
                                                     * Novi zapisi organizacijskog
                                                     * korisnika uvijek imaju ownerId.
                                                     *
                                                     * Legacy NULL-owner zapis
                                                     * superadmin može pregledavati,
                                                     * ali upload je na takvom
                                                     * zapisu onemogućen.
                                                     */
                                                    if (
                                                        filled(
                                                            $ownerId
                                                        )
                                                        && (int) $ownerId > 0
                                                    ) {
                                                        return
                                                            'chemicals/'
                                                            . (int) $ownerId;
                                                    }

                                                    return
                                                        'chemicals/legacy';
                                                }
                                            )
                                            ->disk('public')
                                            ->visibility(
                                                'public'
                                            )
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
                                                'application/vnd.rar',
                                            ])
                                            ->maxSize(30720)
                                            ->multiple()
                                            ->maxFiles(10)
                                            /*
                                             * Namjerno nema
                                             * preserveFilenames().
                                             *
                                             * Time izbjegavamo koliziju
                                             * jednakih naziva datoteka.
                                             */
                                            ->openable()
                                            ->downloadable()
                                            ->disabled(
                                                fn (
                                                    $record
                                                ): bool =>
                                                    $record !== null
                                                    && blank(
                                                        $record->user_id
                                                    )
                                            )
                                            ->helperText(
                                                function (
                                                    $get,
                                                    $record
                                                ) {
                                                    $user =
                                                        auth()
                                                            ->user();

                                                    if (! $user) {
                                                        return null;
                                                    }

                                                    $ownerId =
                                                        $user
                                                            ->isSuperAdmin()
                                                            ? $record?->user_id
                                                            : $user->ownerId();

                                                    if (
                                                        ! $ownerId
                                                    ) {
                                                        return
                                                            'Legacy zapis bez vlasnika organizacije – dodavanje novih priloga nije dopušteno.';
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
                                                    ): void {
                                                        $user =
                                                            auth()
                                                                ->user();

                                                        if (
                                                            ! $user
                                                        ) {
                                                            return;
                                                        }

                                                        $ownerId =
                                                            $user
                                                                ->isSuperAdmin()
                                                                ? $record?->user_id
                                                                : $user->ownerId();

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
                                    ]),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
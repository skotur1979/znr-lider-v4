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
use Illuminate\Validation\Rule;

class ChemicalForm
{
    public static function configure(Schema $schema): Schema
    {
        $date = fn (string $name, string $label) => DatePicker::make($name)
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
            ->maxLength(255),

        TextInput::make('cas_number')
            ->label('CAS broj')
            ->maxLength(50),

        TextInput::make('ufi_number')
        ->label('UFI broj')
        ->maxLength(255)
        ->nullable()
        ->dehydrateStateUsing(fn ($state) => trim((string) $state) === '/' ? null : $state),

        TextInput::make('usage_location')
            ->label('Mjesto upotrebe')
            ->required()
            ->maxLength(255),
    ]),
                            ]),

                        Tab::make('Opasnosti')
                            ->schema([
                                Section::make('Označavanje opasnosti')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('hazard_pictograms')
                                            ->label('Piktogrami opasnosti')
                                            ->options([
                                                'GHS01' => 'GHS01 – Eksplozivno',
                                                'GHS02' => 'GHS02 – Zapaljivo',
                                                'GHS03' => 'GHS03 – Oksidirajuće',
                                                'GHS04' => 'GHS04 – Plin pod tlakom',
                                                'GHS05' => 'GHS05 – Korozivno',
                                                'GHS06' => 'GHS06 – Akutna toksičnost',
                                                'GHS07' => 'GHS07 – Nadražujuće / štetno',
                                                'GHS08' => 'GHS08 – Ozbiljna opasnost za zdravlje',
                                                'GHS09' => 'GHS09 – Opasno za okoliš',
                                            ])
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->default([])
                                            ->nullable()
                                            ->placeholder('Odaberi jedan ili više piktograma')
                                            ->helperText('Možete odabrati više piktograma opasnosti.')
                                            ->afterStateHydrated(function (Select $component, mixed $state): void {
                                                if (! is_array($state)) {
                                                    $state = filled($state) ? [$state] : [];
                                                }
                                        
                                                $normalized = collect($state)
                                                    ->flatMap(function ($value): array {
                                                        return preg_split('/\s*[,;]\s*/', (string) $value) ?: [];
                                                    })
                                                    ->map(fn ($value) => strtoupper(trim($value)))
                                                    ->filter(fn ($value) => preg_match('/^GHS0[1-9]$/', $value))
                                                    ->unique()
                                                    ->values()
                                                    ->all();
                                        
                                                $component->state($normalized);
                                            })
                                            ->dehydrateStateUsing(function (mixed $state): array {
                                                if (! is_array($state)) {
                                                    $state = filled($state) ? [$state] : [];
                                                }
                                        
                                                return collect($state)
                                                    ->flatMap(function ($value): array {
                                                        return preg_split('/\s*[,;]\s*/', (string) $value) ?: [];
                                                    })
                                                    ->map(fn ($value) => strtoupper(trim($value)))
                                                    ->filter(fn ($value) => preg_match('/^GHS0[1-9]$/', $value))
                                                    ->unique()
                                                    ->values()
                                                    ->all();
                                            })
                                            ->columnSpanFull(),

                                        Select::make('h_statements')
                                            ->label('H oznake (opasnosti)')
                                            ->options(HazardStatement::list())
                                            ->searchable()
                                            ->multiple()
                                            ->nullable()
                                            ->default([])
                                            ->columnSpanFull(),

                                        Select::make('p_statements')
                                            ->label('P oznake (mjere opreza)')
                                            ->options(PrecautionaryStatement::list())
                                            ->searchable()
                                            ->multiple()
                                            ->nullable()
                                            ->default([])
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Količine i izloženost')
                            ->schema([
                                Section::make('Količine, granične vrijednosti i STL')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('annual_quantity')
                                            ->label('Godišnje količine (kg/l)')
                                            ->nullable()
                                            ->maxLength(50),

                                        TextInput::make('gvi_kgvi')
                                            ->label('GVI / KGVI')
                                            ->nullable()
                                            ->maxLength(50),

                                        TextInput::make('voc')
                                            ->label('Hlapljivi organski spojevi (VOC)')
                                            ->nullable()
                                            ->maxLength(50),

                                        $date('stl_hzjz', 'STL – HZJZ'),
                                    ]),
                            ]),

                        Tab::make('Prilozi')
                            ->schema([
                                Section::make('Prilozi')
                                    ->schema([
                    FileUpload::make('attachments')
                        ->label('Dodaj priloge (max. 10, do 30 MB po datoteci)')
                        ->directory('chemicals')
                        ->disk('public')
                        ->visibility('public')
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
                        ->maxSize(30720)
                        ->multiple()
                        ->maxFiles(10)
                        ->preserveFilenames()
                        ->openable()
                        ->downloadable()
                        ->helperText(function () {
                            $ownerId = auth()->user()?->ownerId();

                            if (! $ownerId) {
                                return null;
                            }

                            return 'Iskorištenost prostora organizacije: '
                                . app(StorageQuotaService::class)->usageText($ownerId);
                        })
                        ->rules([
                            function () {
                                return function (string $attribute, mixed $value, \Closure $fail) {
                                    $ownerId = auth()->user()?->ownerId();

                                    if (! $ownerId) {
                                        return;
                                    }

                                    if (! app(StorageQuotaService::class)->canUpload($value, $ownerId)) {
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
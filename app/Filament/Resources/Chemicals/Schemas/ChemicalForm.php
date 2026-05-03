<?php

namespace App\Filament\Resources\Chemicals\Schemas;

use App\Enums\HazardStatement;
use App\Enums\PrecautionaryStatement;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
            ->rule(function ($record) {
                return Rule::unique('chemicals', 'ufi_number')
                    ->where(function ($query) {
                        $query->where('user_id', auth()->user()?->ownerId() ?? auth()->id())
                            ->whereNull('deleted_at');
                    })
                    ->ignore($record?->id);
            }),

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
                                        TagsInput::make('hazard_pictograms')
                                            ->label('Piktogrami opasnosti')
                                            ->suggestions([
                                                'GHS01',
                                                'GHS02',
                                                'GHS03',
                                                'GHS04',
                                                'GHS05',
                                                'GHS06',
                                                'GHS07',
                                                'GHS08',
                                                'GHS09',
                                            ])
                                            ->placeholder('npr. GHS05, GHS07')
                                            ->nullable()
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
                                            ->downloadable(),
                                    ]),
                            ]),
                    ]),
            ])
            ->columns(1);
    }
}
<?php

namespace App\Filament\Resources\Chemicals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChemicalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)->schema([
                TextInput::make('product_name')
                    ->label('Ime proizvoda')
                    ->required()
                    ->maxLength(255),

                TextInput::make('cas_number')
                    ->label('CAS broj')
                    ->maxLength(50),

                TextInput::make('ufi_number')
                    ->label('UFI broj')
                    ->maxLength(50),

                TagsInput::make('hazard_pictograms')
                    ->label('Piktogrami opasnosti')
                    ->suggestions(['GHS01','GHS02','GHS03','GHS04','GHS05','GHS06','GHS07','GHS08','GHS09'])
                    ->placeholder('npr. GHS05, GHS07')
                    ->nullable(),

                Select::make('h_statements')
                    ->label('H oznake (opasnosti)')
                    ->options(\App\Enums\HazardStatement::list())
                    ->searchable()
                    ->multiple()
                    ->nullable()
                    ->default([]),

                Select::make('p_statements')
                    ->label('P oznake (mjere opreza)')
                    ->options(\App\Enums\PrecautionaryStatement::list())
                    ->searchable()
                    ->multiple()
                    ->nullable()
                    ->default([]),

                TextInput::make('usage_location')
                    ->label('Mjesto upotrebe')
                    ->required()
                    ->maxLength(255),

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

                DatePicker::make('stl_hzjz')
                    ->label('STL – HZJZ')
                    ->nullable(),

                FileUpload::make('attachments')
                    ->label('Prilozi')
                    ->directory('chemicals')
                    ->disk('public')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg','image/png','image/gif','image/webp',
                        'application/zip','application/x-rar-compressed',
                    ])
                    ->maxSize(30480)
                    ->multiple()
                    ->maxFiles(10)
                    ->preserveFilenames()
                    ->enableOpen()
                    ->enableDownload(),
            ]),
        ]);
    }
}
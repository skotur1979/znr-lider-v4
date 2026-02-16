<?php

namespace App\Filament\Resources\Machines\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MachineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('manufacturer')
                    ->default(null),
                TextInput::make('factory_number')
                    ->default(null),
                TextInput::make('inventory_number')
                    ->default(null),
                DatePicker::make('examination_valid_from')
                    ->required(),
                DatePicker::make('examination_valid_until')
                    ->required(),
                TextInput::make('location')
                    ->required(),
                TextInput::make('remark')
                    ->default(null),
                Textarea::make('pdf')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(null),
                TextInput::make('examined_by')
                    ->default(null),
                TextInput::make('report_number')
                    ->default(null),
            ]);
    }
}

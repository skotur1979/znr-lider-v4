<?php

namespace App\Filament\Resources\TestAttempts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('test_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('ime_prezime')
                    ->required(),
                TextInput::make('radno_mjesto')
                    ->default(null),
                DatePicker::make('datum_rodjenja'),
                TextInput::make('bodovi_osvojeni')
                    ->numeric()
                    ->default(null),
                TextInput::make('rezultat')
                    ->numeric()
                    ->default(null),
                Toggle::make('prolaz')
                    ->required(),
            ]);
    }
}

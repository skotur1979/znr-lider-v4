<?php

namespace App\Filament\Resources\WasteOrganizations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WasteOrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('company_name')
                    ->required(),
                TextInput::make('oib')
                    ->default(null),
                TextInput::make('nkd_code')
                    ->default(null),
                TextInput::make('contact_person')
                    ->default(null),
                TextInput::make('contact_details')
                    ->default(null),
                TextInput::make('registered_office')
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}

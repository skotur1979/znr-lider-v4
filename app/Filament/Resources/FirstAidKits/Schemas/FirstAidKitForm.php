<?php

namespace App\Filament\Resources\FirstAidKits\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class FirstAidKitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('location')
                    ->required(),
                DatePicker::make('inspected_at')
                    ->required(),
                Textarea::make('note')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}

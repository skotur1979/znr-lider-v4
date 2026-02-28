<?php

namespace App\Filament\Resources\Observations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ObservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                DatePicker::make('incident_date')
                    ->required(),
                TextInput::make('observation_type')
                    ->required()
                    ->default('Negative Observation'),
                TextInput::make('location')
                    ->required(),
                TextInput::make('item')
                    ->required(),
                TextInput::make('potential_incident_type')
                    ->required(),
                Textarea::make('action')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('responsible')
                    ->default(null),
                DatePicker::make('target_date'),
                Select::make('status')
                    ->options(['Not started' => 'Not started', 'In progress' => 'In progress', 'Complete' => 'Complete'])
                    ->default('Not started')
                    ->required(),
                Textarea::make('comments')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('picture_path')
                    ->default(null),
            ]);
    }
}

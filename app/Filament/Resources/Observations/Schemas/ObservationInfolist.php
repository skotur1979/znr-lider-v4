<?php

namespace App\Filament\Resources\Observations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ObservationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('incident_date')
                    ->date(),
                TextEntry::make('observation_type'),
                TextEntry::make('location'),
                TextEntry::make('item'),
                TextEntry::make('potential_incident_type'),
                TextEntry::make('responsible'),
                TextEntry::make('target_date')
                    ->date(),
                TextEntry::make('status'),
                TextEntry::make('picture_path'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }
}

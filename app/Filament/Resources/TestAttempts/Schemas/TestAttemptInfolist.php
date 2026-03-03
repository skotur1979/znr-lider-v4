<?php

namespace App\Filament\Resources\TestAttempts\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TestAttemptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('test_id')
                    ->numeric(),
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('ime_prezime'),
                TextEntry::make('radno_mjesto'),
                TextEntry::make('datum_rodjenja')
                    ->date(),
                TextEntry::make('bodovi_osvojeni')
                    ->numeric(),
                TextEntry::make('rezultat')
                    ->numeric(),
                IconEntry::make('prolaz')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}

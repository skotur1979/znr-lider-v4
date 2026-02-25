<?php

namespace App\Filament\Resources\FirstAidKits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class FirstAidKitInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user_id')
                    ->numeric(),
                TextEntry::make('location'),
                TextEntry::make('inspected_at')
                    ->date(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}

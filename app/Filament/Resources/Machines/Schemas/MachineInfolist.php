<?php

namespace App\Filament\Resources\Machines\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MachineInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('manufacturer'),
                TextEntry::make('factory_number'),
                TextEntry::make('inventory_number'),
                TextEntry::make('examination_valid_from')
                    ->date(),
                TextEntry::make('examination_valid_until')
                    ->date(),
                TextEntry::make('location'),
                TextEntry::make('remark'),
                TextEntry::make('deleted_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('user.name')
                    ->numeric(),
                TextEntry::make('examined_by'),
                TextEntry::make('report_number'),
            ]);
    }
}

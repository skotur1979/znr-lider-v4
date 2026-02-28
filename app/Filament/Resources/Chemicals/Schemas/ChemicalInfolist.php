<?php

namespace App\Filament\Resources\Chemicals\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ChemicalInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('product_name'),
                TextEntry::make('cas_number'),
                TextEntry::make('ufi_number'),
                TextEntry::make('usage_location'),
                TextEntry::make('annual_quantity'),
                TextEntry::make('gvi_kgvi'),
                TextEntry::make('voc'),
                TextEntry::make('stl_hzjz')
                    ->date(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
                TextEntry::make('user_id')
                    ->numeric(),
            ]);
    }
}

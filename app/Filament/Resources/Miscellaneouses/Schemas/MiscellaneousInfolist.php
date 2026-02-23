<?php

namespace App\Filament\Resources\Miscellaneouses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MiscellaneousInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('examiner'),
                TextEntry::make('category_id')
                    ->numeric(),
                TextEntry::make('report_number'),
                TextEntry::make('examination_valid_from')
                    ->date(),
                TextEntry::make('examination_valid_until')
                    ->date(),
                TextEntry::make('remark'),
                TextEntry::make('deleted_at')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('user_id')
                    ->numeric(),
            ]);
    }
}

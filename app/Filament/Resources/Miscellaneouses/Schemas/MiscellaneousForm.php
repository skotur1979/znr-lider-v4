<?php

namespace App\Filament\Resources\Miscellaneouses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MiscellaneousForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('examiner')
                    ->default(null),
                TextInput::make('category_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('report_number')
                    ->default(null),
                DatePicker::make('examination_valid_from')
                    ->required(),
                DatePicker::make('examination_valid_until')
                    ->required(),
                TextInput::make('remark')
                    ->default(null),
                Textarea::make('pdf')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Tests\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Osnovni podaci')
                ->columns(2)
                ->schema([
                    TextInput::make('naziv')
                        ->label('Naziv')
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('sifra')
                        ->label('Šifra')
                        ->required()
                        // ✅ Filament v4 način (automatski ignorira record na edit)
                        ->unique(table: 'tests', column: 'sifra', ignoreRecord: true),

                    TextInput::make('minimalni_prolaz')
                        ->label('Minimalni prolaz (%)')
                        ->numeric()
                        ->default(75)
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),

                    Textarea::make('opis')
                        ->label('Opis')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
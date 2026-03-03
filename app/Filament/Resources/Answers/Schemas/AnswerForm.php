<?php

namespace App\Filament\Resources\Answers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AnswerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Odgovor')
                ->columns(2)
                ->schema([
                    Select::make('question_id')
                        ->label('Pitanje')
                        ->relationship('question', 'tekst')
                        ->searchable()
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('tekst')
                        ->label('Odgovor')
                        ->required()
                        ->columnSpanFull(),

                    FileUpload::make('slika_path')
                        ->label('Slika uz odgovor')
                        ->image()
                        ->disk('public')
                        ->directory('answers')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->columnSpanFull(),

                    Toggle::make('is_correct')
                        ->label('Točan odgovor'),
                ]),
        ]);
    }
}
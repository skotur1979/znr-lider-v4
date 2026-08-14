<?php

namespace App\Filament\Resources\Answers\Schemas;

use App\Filament\Resources\Questions\QuestionResource;
use App\Models\Question;
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
                        ->options(function (): array {
                            return QuestionResource::getManageableQuery()
                                ->get()
                                ->mapWithKeys(
                                    function (Question $question): array {
                                        $testName = $question->test?->naziv;

                                        $label = $testName
                                            ? $testName . ' — ' . $question->tekst
                                            : $question->tekst;

                                        return [
                                            $question->id => $label,
                                        ];
                                    }
                                )
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->placeholder('Odaberite pitanje')
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
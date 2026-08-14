<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Filament\Resources\Tests\TestResource;
use App\Models\Test;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pitanje')
                ->columns(2)
                ->schema([
                    Select::make('test_id')
                        ->label('Test')
                        ->options(
                            fn (): array =>
                                TestResource::getManageableQuery()
                                    ->orderBy('naziv')
                                    ->pluck('naziv', 'id')
                                    ->toArray()
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->required()
                        ->placeholder('Odaberite test')
                        ->columnSpanFull(),

                    TextInput::make('tekst')
                        ->label('Tekst pitanja')
                        ->required()
                        ->columnSpanFull(),

                    FileUpload::make('slika_path')
                        ->label('Slika uz pitanje')
                        ->image()
                        ->disk('public')
                        ->directory('questions')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->columnSpanFull(),

                    Toggle::make('visestruki_odgovori')
                        ->label('Dozvoli više točnih odgovora')
                        ->helperText(
                            'Omogući ako pitanje ima više ispravnih odgovora.'
                        )
                        ->columnSpanFull(),
                ]),

            Section::make('Odgovori')
                ->schema([
                    Repeater::make('answers')
                        ->label('Odgovori')
                        ->relationship()
                        ->schema([
                            TextInput::make('tekst')
                                ->label('Tekst odgovora')
                                ->required(),

                            FileUpload::make('slika_path')
                                ->label('Slika uz odgovor')
                                ->image()
                                ->disk('public')
                                ->directory('answers')
                                ->visibility('public')
                                ->maxSize(2048),

                            Forms\Components\Toggle::make('is_correct')
                                ->label('Točan odgovor'),
                        ])
                        ->columns(2)
                        ->createItemButtonLabel('Dodaj odgovor')

                        /*
                         * Kada se odgovor kreira kroz Repeater,
                         * CreateAnswer stranica se NE izvršava.
                         *
                         * Zato ownership odgovora postavljamo
                         * prema testu kojem pripada pitanje.
                         */
                        ->mutateRelationshipDataBeforeCreateUsing(
                            function (array $data, $record): array {
                                if (! $record) {
                                    return $data;
                                }

                                $record->loadMissing('test');

                                $data['user_id'] =
                                    $record->test?->user_id;

                                return $data;
                            }
                        )

                        /*
                         * Isto pravilo vrijedi i kod uređivanja
                         * postojećeg odgovora kroz Repeater.
                         */
                        ->mutateRelationshipDataBeforeSaveUsing(
                            function (array $data, $record): array {
                                /*
                                 * $record je ovdje Answer zapis,
                                 * zato ownership ostavljamo
                                 * kakav već jest.
                                 */
                                $data['user_id'] =
                                    $record?->user_id;

                                return $data;
                            }
                        ),
                ]),
        ]);
    }
}
<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Models\Test;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

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
                        ->options(function (): array {
                            $user = Auth::user();
                    
                            if (! $user) {
                                return [];
                            }
                    
                            $query = Test::query()
                                ->orderBy('naziv');
                    
                            if (! $user->isSuperAdmin()) {
                                $ownerId = $user->ownerId();
                    
                                $organizationUserIds = \App\Models\User::query()
                                    ->where('id', $ownerId)
                                    ->orWhere('parent_user_id', $ownerId)
                                    ->pluck('id');
                    
                                $query->where(function (Builder $query) use ($organizationUserIds): void {
                                    $query
                                        ->whereNull('user_id')
                                        ->orWhereIn('user_id', $organizationUserIds);
                                });
                            }
                    
                            return $query
                                ->pluck('naziv', 'id')
                                ->toArray();
                        })
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
                        ->helperText('Omogući ako pitanje ima više ispravnih odgovora.')
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
                        ->createItemButtonLabel('Dodaj odgovor'),
                ]),
        ]);
    }
}
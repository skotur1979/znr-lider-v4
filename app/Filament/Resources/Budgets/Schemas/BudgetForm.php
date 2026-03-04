<?php

namespace App\Filament\Resources\Budgets\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;

class BudgetForm
{
    public static function schema(): array
    {
        return [
            Section::make('Unos budžeta')
                ->schema([
                    Hidden::make('user_id')
                        ->default(fn () => Auth::id())
                        ->dehydrated(true),

                    TextInput::make('godina')
                        ->label('Godina')
                        ->numeric()
                        ->required(),

                    TextInput::make('ukupni_budget')
                        ->label('Ukupni budžet (€)')
                        ->numeric()
                        ->required(),
                ])
                ->columns(2),
        ];
    }
}
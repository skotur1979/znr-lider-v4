<?php

namespace App\Filament\Resources\Expenses\Expenses\Schemas;

use App\Models\Budget;
use App\Models\Category;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;

class ExpenseForm
{
    public static function schema(): array
    {
        return [
            // ✅ Jedan user_id (admin bira, ostali hidden)
            Select::make('user_id')
                ->label('Korisnik')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn () => Auth::user()?->isAdmin())
                ->dehydrated(fn () => Auth::user()?->isAdmin()),

            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->visible(fn () => ! Auth::user()?->isAdmin())
                ->dehydrated(fn () => ! Auth::user()?->isAdmin()),

            Section::make('Unos troška')
                ->columns(2)
                ->schema([
                    // ✅ Kategorija (iz Categories) + kreiranje nove iz selecta
                    Select::make('category_id')
                        ->label('Kategorija')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $q = Category::query()->orderBy('name');

                            if (! Auth::user()?->isAdmin()) {
                                $q->where('user_id', Auth::id());
                            }

                            return $q->pluck('name', 'id')->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search) {
                            $q = Category::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orderBy('name')
                                ->limit(50);

                            if (! Auth::user()?->isAdmin()) {
                                $q->where('user_id', Auth::id());
                            }

                            return $q->pluck('name', 'id')->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value) => Category::find($value)?->name)
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Naziv kategorije')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $category = Category::create([
                                'name'    => $data['name'],
                                'user_id' => Auth::id(),
                            ]);

                            return $category->id;
                        }),

                    // ✅ Godina = budget_id (prikazuje godinu, sprema budget_id)
                    Select::make('budget_id')
                        ->label('Godina')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $qb = Budget::query()->orderByDesc('godina');

                            if (! Auth::user()?->isAdmin()) {
                                $qb->where('user_id', Auth::id());
                            }

                            return $qb->pluck('godina', 'id')->toArray();
                        }),

                    Select::make('mjesec')
                        ->label('Mjesec')
                        ->options(self::months())
                        ->required(),

                    TextInput::make('naziv_troska')
                        ->label('Naziv troška')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('iznos')
                        ->label('Iznos (€)')
                        ->numeric()
                        ->required(),

                    TextInput::make('dobavljac')
                        ->label('Dobavljač')
                        ->maxLength(255)
                        ->nullable(),

                    Toggle::make('realizirano')
                        ->label('Realizirano')
                        ->default(true),
                ]),
        ];
    }

    public static function months(): array
    {
        return [
            'Siječanj' => 'Siječanj',
            'Veljača'  => 'Veljača',
            'Ožujak'   => 'Ožujak',
            'Travanj'  => 'Travanj',
            'Svibanj'  => 'Svibanj',
            'Lipanj'   => 'Lipanj',
            'Srpanj'   => 'Srpanj',
            'Kolovoz'  => 'Kolovoz',
            'Rujan'    => 'Rujan',
            'Listopad' => 'Listopad',
            'Studeni'  => 'Studeni',
            'Prosinac' => 'Prosinac',
        ];
    }
}
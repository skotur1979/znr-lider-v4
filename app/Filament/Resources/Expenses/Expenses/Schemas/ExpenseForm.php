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
    protected static function ownerId(): ?int
    {
        return Auth::user()?->ownerId() ?? Auth::id();
    }

    protected static function isSuperAdmin(): bool
    {
        return Auth::user()?->isSuperAdmin() ?? false;
    }

    public static function schema(): array
    {
        return [
    Hidden::make('user_id')
        ->default(fn () => auth()->id())
        ->dehydrated(),

    Section::make('Unos troška')
        ->columnSpanFull()
        ->columns(2)
        ->schema([
                    Select::make('category_id')
                        ->label('Kategorija')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $query = Category::query()->orderBy('name');

                            if (! static::isSuperAdmin()) {
                                $query->where('user_id', static::ownerId());
                            }

                            return $query->pluck('name', 'id')->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search) {
                            $query = Category::query()
                                ->where('name', 'like', "%{$search}%")
                                ->orderBy('name')
                                ->limit(50);

                            if (! static::isSuperAdmin()) {
                                $query->where('user_id', static::ownerId());
                            }

                            return $query->pluck('name', 'id')->toArray();
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
                                'name' => $data['name'],
                                'user_id' => static::ownerId(),
                            ]);

                            return $category->id;
                        }),

                    Select::make('budget_id')
                        ->label('Godina')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            $query = Budget::query()->orderByDesc('godina');

                            if (! static::isSuperAdmin()) {
                                $query->where('user_id', static::ownerId());
                            }

                            return $query->pluck('godina', 'id')->toArray();
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
            'Veljača' => 'Veljača',
            'Ožujak' => 'Ožujak',
            'Travanj' => 'Travanj',
            'Svibanj' => 'Svibanj',
            'Lipanj' => 'Lipanj',
            'Srpanj' => 'Srpanj',
            'Kolovoz' => 'Kolovoz',
            'Rujan' => 'Rujan',
            'Listopad' => 'Listopad',
            'Studeni' => 'Studeni',
            'Prosinac' => 'Prosinac',
        ];
    }
}
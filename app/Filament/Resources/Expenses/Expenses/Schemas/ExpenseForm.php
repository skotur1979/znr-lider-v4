<?php

namespace App\Filament\Resources\Expenses\Expenses\Schemas;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Budget;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExpenseForm
{
    /**
     * Vlasnik organizacije čije podatke
     * forma smije koristiti.
     *
     * Organizacijski korisnik:
     * ownerId()
     *
     * Superadmin kod EDITA:
     * owner postojećeg Expense zapisa.
     *
     * Superadmin ne kreira nove troškove.
     */
    protected static function ownerId(
        $record = null
    ): ?int {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            return filled(
                $record?->user_id
            )
                ? (int) $record->user_id
                : null;
        }

        return $user->ownerId();
    }

    public static function schema(): array
    {
        return [
            Section::make('Unos troška')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    /*
                     * KATEGORIJA
                     */
                    Select::make('category_id')
                        ->label('Kategorija')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(
                            function (
                                $record
                            ): array {
                                $ownerId =
                                    static::ownerId(
                                        $record
                                    );

                                if (! $ownerId) {
                                    return [];
                                }

                                return Category::query()
                                    ->where(
                                        'user_id',
                                        $ownerId
                                    )
                                    ->orderBy(
                                        'name'
                                    )
                                    ->pluck(
                                        'name',
                                        'id'
                                    )
                                    ->toArray();
                            }
                        )
                        ->getSearchResultsUsing(
                            function (
                                string $search,
                                $record
                            ): array {
                                $ownerId =
                                    static::ownerId(
                                        $record
                                    );

                                if (! $ownerId) {
                                    return [];
                                }

                                return Category::query()
                                    ->where(
                                        'user_id',
                                        $ownerId
                                    )
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orderBy(
                                        'name'
                                    )
                                    ->limit(50)
                                    ->pluck(
                                        'name',
                                        'id'
                                    )
                                    ->toArray();
                            }
                        )
                        ->getOptionLabelUsing(
                            function (
                                $value,
                                $record
                            ): ?string {
                                $ownerId =
                                    static::ownerId(
                                        $record
                                    );

                                if (
                                    ! $ownerId
                                    || ! $value
                                ) {
                                    return null;
                                }

                                return Category::query()
                                    ->whereKey(
                                        $value
                                    )
                                    ->where(
                                        'user_id',
                                        $ownerId
                                    )
                                    ->value(
                                        'name'
                                    );
                            }
                        )
                        ->createOptionForm([
                            TextInput::make(
                                'name'
                            )
                                ->label(
                                    'Naziv kategorije'
                                )
                                ->required()
                                ->maxLength(256),
                        ])
                        ->createOptionUsing(
                            function (
                                array $data
                            ): int {
                                $user =
                                    Auth::user();

                                /*
                                 * Superadmin standardno
                                 * ne kreira poslovne
                                 * kategorije organizacije.
                                 */
                                if (
                                    ! $user
                                    || $user
                                        ->isSuperAdmin()
                                ) {
                                    abort(403);
                                }

                                /*
                                 * Podkorisnik mora imati
                                 * CREATE pravo za Categories.
                                 */
                                if (
                                    ! CategoryResource::
                                        ensureModulePermission(
                                            'create'
                                        )
                                ) {
                                    throw ValidationException::
                                        withMessages([
                                            'category_id' =>
                                                'Nemate ovlasti za akciju, kontaktirajte administratora.',
                                        ]);
                                }

                                $ownerId =
                                    (int)
                                    $user->ownerId();

                                if (
                                    $ownerId <= 0
                                ) {
                                    abort(403);
                                }

                                $name = trim(
                                    (string)
                                    $data['name']
                                );

                                /*
                                 * Ne dopuštamo duplikat
                                 * iste kategorije za
                                 * istog vlasnika.
                                 */
                                $existingCategory =
                                    Category::query()
                                        ->where(
                                            'user_id',
                                            $ownerId
                                        )
                                        ->whereRaw(
                                            'LOWER(TRIM(name)) = LOWER(?)',
                                            [$name]
                                        )
                                        ->first();

                                if (
                                    $existingCategory
                                ) {
                                    return (int)
                                        $existingCategory
                                            ->id;
                                }

                                $category =
                                    Category::create([
                                        'name' =>
                                            $name,

                                        'user_id' =>
                                            $ownerId,
                                    ]);

                                return (int)
                                    $category->id;
                            }
                        ),

                    /*
                     * BUDŽET / GODINA
                     */
                    Select::make('budget_id')
                        ->label('Godina')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(
                            function (
                                $record
                            ): array {
                                $ownerId =
                                    static::ownerId(
                                        $record
                                    );

                                if (! $ownerId) {
                                    return [];
                                }

                                return Budget::query()
                                    ->where(
                                        'user_id',
                                        $ownerId
                                    )
                                    ->orderByDesc(
                                        'godina'
                                    )
                                    ->pluck(
                                        'godina',
                                        'id'
                                    )
                                    ->toArray();
                            }
                        )
                        ->getOptionLabelUsing(
                            function (
                                $value,
                                $record
                            ): ?string {
                                $ownerId =
                                    static::ownerId(
                                        $record
                                    );

                                if (
                                    ! $ownerId
                                    || ! $value
                                ) {
                                    return null;
                                }

                                $year =
                                    Budget::query()
                                        ->whereKey(
                                            $value
                                        )
                                        ->where(
                                            'user_id',
                                            $ownerId
                                        )
                                        ->value(
                                            'godina'
                                        );

                                return filled(
                                    $year
                                )
                                    ? (string) $year
                                    : null;
                            }
                        ),

                    /*
                     * MJESEC
                     */
                    Select::make('mjesec')
                        ->label('Mjesec')
                        ->options(
                            static::months()
                        )
                        ->required(),

                    /*
                     * NAZIV TROŠKA
                     */
                    TextInput::make(
                        'naziv_troska'
                    )
                        ->label(
                            'Naziv troška'
                        )
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    /*
                     * IZNOS
                     */
                    TextInput::make('iznos')
                        ->label('Iznos (€)')
                        ->numeric()
                        ->required()
                        ->minValue(0),

                    /*
                     * DOBAVLJAČ
                     */
                    TextInput::make(
                        'dobavljac'
                    )
                        ->label('Dobavljač')
                        ->maxLength(191)
                        ->nullable(),

                    /*
                     * REALIZIRANO
                     */
                    Toggle::make(
                        'realizirano'
                    )
                        ->label(
                            'Realizirano'
                        )
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
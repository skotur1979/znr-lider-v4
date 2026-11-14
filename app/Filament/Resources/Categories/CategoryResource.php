<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Categories\Pages;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends BaseResource
{
    protected static ?string $model = Category::class;

    protected static \BackedEnum|string|null $navigationIcon =
        'heroicon-o-tag';

    protected static ?string $navigationLabel =
        'Kategorije ispitivanja';

    protected static ?string $modelLabel =
        'Kategorija';

    protected static ?string $pluralModelLabel =
        'Kategorije ispitivanja';

    protected static ?int $navigationSort = 5;

    protected static \UnitEnum|string|null $navigationGroup =
        'Ispitivanja';

    protected static function getModuleKey(): ?string
    {
        return 'categories';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Podaci')
                ->schema([
                    TextInput::make('name')
                        ->label('Naziv')
                        ->required()
                        ->maxLength(256)
                        ->rule(
                            function (?Category $record) {
                                return function (
                                    string $attribute,
                                    mixed $value,
                                    \Closure $fail
                                ) use ($record): void {
                                    $ownerId =
                                        $record?->user_id
                                        ?? static::defaultUserId();

                                    if (! $ownerId) {
                                        $fail(
                                            'Nije moguće odrediti organizaciju korisnika.'
                                        );

                                        return;
                                    }

                                    $name =
                                        trim(
                                            (string) $value
                                        );

                                    $query =
                                        Category::query()
                                            ->where(
                                                'user_id',
                                                $ownerId
                                            )
                                            ->whereRaw(
                                                'LOWER(TRIM(name)) = LOWER(?)',
                                                [$name]
                                            );

                                    if ($record) {
                                        $query->whereKeyNot(
                                            $record->getKey()
                                        );
                                    }

                                    if ($query->exists()) {
                                        $fail(
                                            'Kategorija s ovim nazivom već postoji.'
                                        );
                                    }
                                };
                            }
                        ),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable(),

                static::userTableColumn(),
            ])
            ->paginated([
                10,
                25,
                50,
                'all',
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaži')
                        ->color('gray'),

                    /*
                     * Obični Action ostaje vidljiv i kada korisnik
                     * nema pravo uređivanja. Klik tada prikazuje
                     * obavijest o nedostatku ovlasti.
                     */
                    Action::make('editCategory')
                        ->label('Uredi')
                        ->icon(
                            Heroicon::PencilSquare
                        )
                        ->color('warning')
                        ->action(
                            function (
                                Category $record
                            ) {
                                if (
                                    ! static::
                                        allowsModulePermission(
                                            'update'
                                        )
                                ) {
                                    return;
                                }

                                return redirect(
                                    static::getUrl(
                                        'edit',
                                        [
                                            'record' =>
                                                $record,
                                        ]
                                    )
                                );
                            }
                        ),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->disabled(
                            fn (
                                Category $record
                            ): bool =>
                                $record
                                    ->miscellaneouses()
                                    ->exists()
                                || $record
                                    ->expenses()
                                    ->exists()
                        )
                        ->tooltip(
                            fn (
                                Category $record
                            ): ?string =>
                                (
                                    $record
                                        ->miscellaneouses()
                                        ->exists()
                                    || $record
                                        ->expenses()
                                        ->exists()
                                )
                                    ? 'Kategoriju nije moguće obrisati jer se koristi u evidenciji.'
                                    : null
                        )
                        ->before(
                            static::
                                beforeModulePermission(
                                    'delete'
                                )
                        ),
                ])
                    ->icon(
                        Heroicon::EllipsisVertical
                    )
                    ->label(''),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListCategories::route(
                    '/'
                ),

            'create' =>
                Pages\CreateCategory::route(
                    '/create'
                ),

            'view' =>
                Pages\ViewCategory::route(
                    '/{record}'
                ),

            'edit' =>
                Pages\EditCategory::route(
                    '/{record}/edit'
                ),
        ];
    }
}

<?php

namespace App\Filament\Resources\TestAttempts;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\TestAttempts\Pages;
use App\Models\Test;
use App\Models\TestAttempt;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TestAttemptResource extends BaseResource
{
    protected static ?string $model = TestAttempt::class;

    /*
     * Riješeni test pripada organizaciji.
     *
     * user_id = ownerId()
     *
     * Glavni korisnik i svi podkorisnici iste organizacije
     * zato vide iste rezultate testiranja.
     */
    protected static bool $hasOwnership = true;

    protected static \BackedEnum|string|null $navigationIcon =
        'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup =
        'Testiranje';

    protected static ?string $navigationLabel =
        'Rješeni testovi';

    protected static ?string $modelLabel =
        'Rješeni test';

    protected static ?string $pluralModelLabel =
        'Rješeni testovi';

    protected static ?int $navigationSort = 96;

    protected static function getModuleKey(): ?string
    {
        return 'test_attempts';
    }

    /*
     * Pokušaji testa ne kreiraju se ručno iz administracije.
     * Nastaju kroz javno / korisničko rješavanje testa.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /*
     * Rezultat testa ne smije se naknadno ručno uređivati.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /*
     * Brisanje:
     *
     * - superadmin NE briše poslovne rezultate organizacija
     * - organizacijski korisnik može obrisati samo rezultat
     *   svoje organizacije
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        /*
         * Superadmin ima pregled svih rezultata,
         * ali ih ne mijenja niti briše.
         */
        if ($user->isSuperAdmin()) {
            return false;
        }

        return (int) $record->user_id ===
            (int) $user->ownerId();
    }

    /*
     * Masovno brisanje rezultata ne dopuštamo.
     *
     * Time izbjegavamo slučajno masovno uklanjanje
     * rezultata testiranja.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    /*
     * BaseResource već radi:
     *
     * superadmin -> svi zapisi
     * organizacija -> user_id = ownerId()
     *
     * Ovdje samo dodajemo eager loading.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user',
                'test',
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([
                10,
                25,
                50,
                'all',
            ])

            ->defaultSort(
                'created_at',
                'desc'
            )

            ->columns([
                static::userTableColumn()
                    ->toggleable(
                        isToggledHiddenByDefault:
                            ! Auth::user()?->isSuperAdmin()
                    ),

                TextColumn::make('test.naziv')
                    ->label('Naziv testa')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->wrap()
                    ->extraAttributes([
                        'style' => 'max-width:220px;',
                    ]),

                TextColumn::make('ime_prezime')
                    ->label('Ime i prezime')
                    ->searchable()
                    ->limit(22)
                    ->wrap()
                    ->extraAttributes([
                        'style' => 'max-width:145px;',
                    ]),

                TextColumn::make('oib')
                    ->label('OIB')
                    ->searchable()
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('radno_mjesto')
                    ->label('Radno mjesto')
                    ->searchable()
                    ->limit(18)
                    ->wrap()
                    ->toggleable()
                    ->extraAttributes([
                        'style' => 'max-width:120px;',
                    ]),

                TextColumn::make('datum_rodjenja')
                    ->label('Rođen')
                    ->date('d.m.Y.')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('bodovi_osvojeni')
                    ->label('Bod.')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('rezultat')
                    ->label('Rez. %')
                    ->formatStateUsing(
                        fn ($state): string =>
                            number_format(
                                (float) $state,
                                2,
                                ',',
                                '.'
                            ) . '%'
                    )
                    ->sortable()
                    ->alignCenter(),

                IconColumn::make('prolaz')
                    ->label('Prolaz')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Slanje')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignCenter(),
            ])

            ->filters([
                /*
                 * Prikazujemo samo testove za koje postoje
                 * pokušaji koje trenutni korisnik smije vidjeti.
                 *
                 * Tako organizaciji u filteru ne izlažemo
                 * nazive testova drugih organizacija.
                 */
                SelectFilter::make('test_id')
                    ->label('Vrsta testa')
                    ->options(function (): array {
                        $testIds = static::getEloquentQuery()
                            ->reorder()
                            ->select('test_attempts.test_id')
                            ->whereNotNull('test_attempts.test_id')
                            ->distinct()
                            ->pluck('test_attempts.test_id');

                        return Test::query()
                            ->whereIn('id', $testIds)
                            ->orderBy('naziv')
                            ->pluck('naziv', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),
            ])

            ->actions([
                Action::make('show')
                    ->label('Prikaži')
                    ->icon('heroicon-o-eye')
                    ->url(
                        fn (TestAttempt $record): string =>
                            route(
                                'test-attempts.show',
                                $record
                            )
                    )
                    ->openUrlInNewTab(),

                Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(
                        fn (TestAttempt $record): string =>
                            route(
                                'test-attempts.download',
                                $record
                            )
                    )
                    ->openUrlInNewTab(),

                /*
                 * Superadmin ne vidi akciju brisanja.
                 *
                 * Organizacijski korisnik može obrisati
                 * samo zapis svoje organizacije.
                 */
                DeleteAction::make()
                    ->label('Obriši')
                    ->visible(
                        fn (TestAttempt $record): bool =>
                            static::canDelete($record)
                    )
                    ->authorize(
                        fn (TestAttempt $record): bool =>
                            static::canDelete($record)
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Obriši pokušaj testa'
                    )
                    ->modalDescription(
                        'Jeste li sigurni? Ova akcija je trajna.'
                    )
                    ->successNotificationTitle(
                        'Pokušaj je obrisan.'
                    ),
            ])

            /*
             * Bulk brisanje namjerno nije omogućeno.
             */
            ->bulkActions([]);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()
            ->reorder()
            ->count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestAttempts::route('/'),
        ];
    }
}
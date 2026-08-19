<?php

namespace App\Filament\Resources\Expenses\Expenses;

use App\Exports\ExpensesExport;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Expenses\Expenses\Pages;
use App\Filament\Resources\Expenses\Expenses\Schemas\ExpenseForm;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseResource extends BaseResource
{
    protected static ?string $model = Expense::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCalculator;
    protected static ?string $navigationLabel = 'Troškovi';
    protected static ?string $modelLabel = 'Trošak';
    protected static ?string $pluralModelLabel = 'Troškovi';
    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 6;
    protected static ?string $recordTitleAttribute = 'naziv_troska';

    protected static function getModuleKey(): ?string
    {
        return 'expenses';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema(
            ExpenseForm::schema()
        );
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    /**
     * Centralna provjera ownershipa troška.
     *
     * Novi zapis:
     * - organizacija -> ownerId()
     * - superadmin -> owner od odabranog Budgeta
     *
     * Edit:
     * - postojeći owner se ne smije promijeniti.
     *
     * Budget i Category moraju pripadati istom owneru.
     */
    public static function prepareOwnershipData(
        array $data,
        ?Expense $record = null
    ): array {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

    $budget = Budget::query()
        ->findOrFail($data['budget_id']);

    $category = Category::query()
        ->findOrFail($data['category_id']);

    /*
     * CREATE:
     * superadmin ne kreira troškove.
     *
     * EDIT:
     * ownership postojećeg zapisa ostaje nepromijenjen.
     */
    if ($record) {
        $ownerId = (int) $record->user_id;
    } else {
        if ($user->isSuperAdmin()) {
            abort(403);
        }

        $ownerId = (int) $user->ownerId();
    }

    if ($ownerId <= 0) {
        abort(403);
    }

    /*
     * Organizacijski korisnik smije raditi
     * samo unutar svoje organizacije.
     */
    if (
        ! $user->isSuperAdmin()
        && $ownerId !== (int) $user->ownerId()
    ) {
        abort(403);
    }

    /*
     * Budžet mora pripadati istoj organizaciji.
     */
    abort_unless(
        (int) $budget->user_id === $ownerId,
        403
    );

    /*
     * Kategorija mora pripadati istoj organizaciji.
     */
    abort_unless(
        (int) $category->user_id === $ownerId,
        403
    );

    $data['user_id'] = $ownerId;

    return $data;
}

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 'all'])
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->orderByRaw("
                    FIELD(
                        mjesec,
                        'Siječanj','Veljača','Ožujak','Travanj','Svibanj','Lipanj',
                        'Srpanj','Kolovoz','Rujan','Listopad','Studeni','Prosinac'
                    )
                ");
            })
            ->columns([
                TextColumn::make('budget.godina')
                    ->label('Godina')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                static::userTableColumn(),

                TextColumn::make('category.name')
                    ->label('Kategorija')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('mjesec')
                    ->label('Mjesec')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('naziv_troska')
                    ->label('Naziv troška')
                    ->searchable()
                    ->wrap()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('iznos')
                    ->label('Iznos (€)')
                    ->formatStateUsing(
                        fn ($state) =>
                            number_format((float) $state, 2, ',', '.') . ' €'
                    )
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('dobavljac')
                    ->label('Dobavljač')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),

                IconColumn::make('realizirano')
                    ->label('Realizirano')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('mjesec')
                    ->label('Mjesec')
                    ->options(ExpenseForm::months())
                    ->placeholder('Sve'),

                SelectFilter::make('category_id')
                    ->label('Kategorija')
                    ->options(function (): array {
                        $query = Category::query()
                            ->orderBy('name');

                        $user = Auth::user();

                        if (! $user) {
                            return [];
                        }

                        if (! $user->isSuperAdmin()) {
                            $query->where(
                                'user_id',
                                $user->ownerId()
                            );
                        }

                        return $query
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('godina')
                    ->label('Godina')
                    ->options(function (): array {
                        $query = Budget::query()
                            ->orderByDesc('godina');

                        $user = Auth::user();

                        if (! $user) {
                            return [];
                        }

                        if (! $user->isSuperAdmin()) {
                            $query->where(
                                'user_id',
                                $user->ownerId()
                            );
                        }

                        return $query
                            ->pluck('godina', 'godina')
                            ->toArray();
                    })
                    ->placeholder('Sve')
                    ->query(function (Builder $query, array $data): Builder {
                        $year = $data['value'] ?? null;

                        if (! filled($year)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'budget',
                            fn (Builder $budgetQuery) =>
                                $budgetQuery->where('godina', $year)
                        );
                    }),

                SelectFilter::make('realizirano')
                    ->label('Realizirano')
                    ->options([
                        '1' => 'Da',
                        '0' => 'Ne',
                    ])
                    ->placeholder('Sve')
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return $query->where(
                            'realizirano',
                            (bool) (int) $value
                        );
                    }),
            ])
            ->actions([
                    ActionGroup::make([
                        EditAction::make()
                            ->label('Uredi'),

                        Action::make('deletePermanently')
                            ->label('Trajno obriši')
                            ->icon(Heroicon::Trash)
                            ->color('danger')
                            ->requiresConfirmation()
                            ->modalHeading(
                                'Trajno obriši trošak'
                            )
                            ->modalDescription(
                                'Jesi li siguran/a da želiš trajno obrisati ovaj trošak? Ova radnja se ne može poništiti.'
                            )
                            ->modalSubmitActionLabel(
                                'Trajno obriši'
                            )
                            ->modalCancelActionLabel(
                                'Odustani'
                            )
                            ->visible(
                                fn (Expense $record): bool =>
                                    static::canDelete($record)
                            )
                            ->action(
                                function (
                                    Expense $record
                                ): void {
                                    if (
                                        ! static::canDelete(
                                            $record
                                        )
                                    ) {
                                        abort(403);
                                    }

                                    $record->delete();
                                }
                            ),
                    ])
                        ->icon(
                            Heroicon::EllipsisVertical
                        )
                        ->label(''),
                ])
            ->headerActions([
                CreateAction::make()
                    ->label('Novi trošak')
                    ->modalHeading('Novi trošak')
                    ->visible(fn (): bool => static::canCreate())
                    ->form(ExpenseForm::schema())
                    ->mutateFormDataUsing(
                        fn (array $data): array =>
                            static::prepareOwnershipData($data)
                    ),

                Action::make('export_excel')
                    ->label('Izvoz u Excel')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(function ($livewire) {
                        $year = data_get(
                            $livewire->tableFilters,
                            'godina.value'
                        );

                        if (! filled($year)) {
                            $year = (string) Carbon::now(
                                'Europe/Zagreb'
                            )->year;
                        }

                        return Excel::download(
                            new ExpensesExport((string) $year),
                            'Troskovi_' . $year . '.xlsx'
                        );
                    }),
            ])
            ->bulkActions([
                BulkAction::make(
                    'deleteSelectedPermanently'
                )
                    ->label(
                        'Trajno obriši označeno'
                    )
                    ->icon(
                        Heroicon::Trash
                    )
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno obriši odabrane troškove'
                    )
                    ->modalDescription(
                        'Jesi li siguran/a da želiš trajno obrisati odabrane troškove? Ova radnja se ne može poništiti.'
                    )
                    ->modalSubmitActionLabel(
                        'Trajno obriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (): bool =>
                            static::canDeleteAny()
                    )
                    ->action(
                        function (
                            EloquentCollection $records
                        ): void {
                            $records->each(
                                function (
                                    Expense $record
                                ): void {
                                    if (
                                        ! static::canDelete(
                                            $record
                                        )
                                    ) {
                                        return;
                                    }

                                    $record->delete();
                                }
                            );
                        }
                    )
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}

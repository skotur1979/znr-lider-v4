<?php

namespace App\Filament\Resources\Expenses\Expenses;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Expenses\Expenses\Pages;
use App\Filament\Resources\Expenses\Expenses\Schemas\ExpenseForm;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use App\Exports\ExpensesExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class ExpenseResource extends BaseResource
{
    protected static ?string $model = Expense::class;

    protected static \BackedEnum|string|null $navigationIcon =
        Heroicon::OutlinedCalculator;

    protected static ?string $navigationLabel = 'Troškovi';

    protected static ?string $modelLabel = 'Trošak';

    protected static ?string $pluralModelLabel = 'Troškovi';

    protected static \UnitEnum|string|null $navigationGroup =
        'Upravljanje';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute =
        'naziv_troska';

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
     * CREATE:
     * - organizacijski korisnik -> ownerId()
     * - superadmin ne kreira nove troškove.
     *
     * EDIT:
     * - postojeći owner se ne smije promijeniti.
     *
     * Budget i Category moraju pripadati
     * istom owneru kao i Expense.
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
         * EDIT:
         * postojeći owner ostaje nepromijenjen.
         *
         * Stari povijesni zapisi bez ownera
         * ne mogu se mijenjati kroz standardnu formu.
         */
        if ($record) {
            if ($record->user_id === null) {
                abort(403);
            }

            $ownerId = (int) $record->user_id;
        } else {
            /*
             * CREATE:
             * superadmin standardno ne kreira
             * poslovne troškove organizacija.
             */
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
         * Budget mora pripadati istoj organizaciji.
         */
        abort_unless(
            $budget->user_id !== null
            && (int) $budget->user_id === $ownerId,
            403
        );

        /*
         * Category mora pripadati istoj organizaciji.
         */
        abort_unless(
            $category->user_id !== null
            && (int) $category->user_id === $ownerId,
            403
        );

        $data['user_id'] = $ownerId;

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([10, 25, 50, 'all'])
            ->modifyQueryUsing(
                function (Builder $query): Builder {
                    return $query->orderByRaw("
                        FIELD(
                            mjesec,
                            'Siječanj',
                            'Veljača',
                            'Ožujak',
                            'Travanj',
                            'Svibanj',
                            'Lipanj',
                            'Srpanj',
                            'Kolovoz',
                            'Rujan',
                            'Listopad',
                            'Studeni',
                            'Prosinac'
                        )
                    ");
                }
            )
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
                            number_format(
                                (float) $state,
                                2,
                                ',',
                                '.'
                            ) . ' €'
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
                    ->options(
                        ExpenseForm::months()
                    )
                    ->placeholder('Sve'),

                SelectFilter::make('category_id')
                    ->label('Kategorija')
                    ->options(
                        function (): array {
                            $user = Auth::user();

                            if (! $user) {
                                return [];
                            }

                            $query = Category::query()
                                ->orderBy('name');

                            if (! $user->isSuperAdmin()) {
                                $query->where(
                                    'user_id',
                                    $user->ownerId()
                                );
                            }

                            return $query
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('godina')
                    ->label('Godina')
                    ->options(function (): array {
                        $user = Auth::user();

                        if (! $user) {
                            return [];
                        }

                        $query = Budget::query()
                            ->orderByDesc('godina');

                        if (! $user->isSuperAdmin()) {
                            $query->where(
                                'user_id',
                                $user->ownerId()
                            );
                        }

                        return $query
                            ->pluck(
                                'godina',
                                'godina'
                            )
                            ->toArray();
                    })
                    ->default(
                        (string) Carbon::now(
                            'Europe/Zagreb'
                        )->year
                    )
                    ->placeholder('Sve godine')
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            $year =
                                $data['value']
                                ?? null;

                            if (! filled($year)) {
                                return $query;
                            }

                            return $query->whereHas(
                                'budget',
                                fn (
                                    Builder $budgetQuery
                                ) =>
                                    $budgetQuery->where(
                                        'godina',
                                        $year
                                    )
                            );
                        }
                    ),

                SelectFilter::make('realizirano')
                    ->label('Realizirano')
                    ->options([
                        '1' => 'Da',
                        '0' => 'Ne',
                    ])
                    ->placeholder('Sve')
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            $value =
                                $data['value']
                                ?? null;

                            if (
                                $value === null
                                || $value === ''
                            ) {
                                return $query;
                            }

                            return $query->where(
                                'realizirano',
                                (bool) (int) $value
                            );
                        }
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            fn (
                                Expense $record
                            ): bool =>
                                $record->user_id !== null
                                && $record->category_id !== null
                                && static::canEdit(
                                    $record
                                )
                        ),

                    Action::make(
                        'deletePermanently'
                    )
                        ->label('Trajno obriši')
                        ->icon(
                            Heroicon::Trash
                        )
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
                            fn (
                                Expense $record
                            ): bool =>
                                static::canDelete(
                                    $record
                                )
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
                    ->visible(
                        fn (): bool =>
                            static::canCreate()
                    )
                    ->form(
                        ExpenseForm::schema()
                    )
                    ->mutateFormDataUsing(
                        fn (
                            array $data
                        ): array =>
                            static::prepareOwnershipData(
                                $data
                            )
                    ),

                Action::make('reports')
                    ->label('Izvještaji')
                    ->icon(
                        'heroicon-o-chart-bar-square'
                    )
                    ->color('info')
                    ->visible(
                        fn (): bool =>
                            static::canViewModule()
                    )
                    ->action(
                        function () {
                            if (
                                ! static::
                                    allowsModulePermission(
                                        'view'
                                    )
                            ) {
                                return;
                            }

                            return redirect(
                                static::getUrl(
                                    'reports'
                                )
                            );
                        }
                    ),

                Action::make('export_excel')
                    ->label('Izvoz u Excel')
                    ->icon(
                        'heroicon-o-document-arrow-down'
                    )
                    ->color('success')
                    ->visible(
                        fn (): bool =>
                            static::canViewModule()
                    )
                    ->action(
                        function ($livewire) {
                            $year = data_get(
                                $livewire->tableFilters,
                                'godina.value'
                            );

                            $selectedYear =
                                filled($year)
                                    ? (string) $year
                                    : null;

                            $fileName =
                                $selectedYear
                                    ? 'Troskovi_'
                                        . $selectedYear
                                        . '.xlsx'
                                    : 'Troskovi_sve_godine.xlsx';

                            return Excel::download(
                                new ExpensesExport(
                                    $selectedYear
                                ),
                                $fileName
                            );
                        }
                    ),
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

    /**
     * Superadmin ne kreira poslovne troškove.
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (
            ! $user
            || $user->isSuperAdmin()
        ) {
            return false;
        }

        return parent::canCreate();
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListExpenses::route('/'),

            'create' =>
                Pages\CreateExpense::route(
                    '/create'
                ),

            'reports' =>
                Pages\ExpenseReports::route(
                    '/reports'
                ),

            'edit' =>
                Pages\EditExpense::route(
                    '/{record}/edit'
                ),
        ];
    }
}

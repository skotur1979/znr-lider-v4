<?php

namespace App\Filament\Resources\Expenses\Expenses;

use App\Exports\ExpensesExport;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Expenses\Expenses\Pages;
use App\Filament\Resources\Expenses\Expenses\Schemas\ExpenseForm;
use App\Models\Budget;
use App\Models\Expense;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
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
        return $schema->schema(ExpenseForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->searchable(),
static::userTableColumn(),
                TextColumn::make('category.name')
                    ->label('Kategorija')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('mjesec')
                    ->label('Mjesec')
                    ->sortable(),

                TextColumn::make('naziv_troska')
                    ->label('Naziv troška')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),

                TextColumn::make('iznos')
                    ->label('Iznos (€)')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, ',', '.') . ' €')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('dobavljac')
                    ->label('Dobavljač')
                    ->searchable()
                    ->wrap(),

                IconColumn::make('realizirano')
                    ->label('Realizirano')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                SelectFilter::make('mjesec')
                    ->label('Mjesec')
                    ->options(ExpenseForm::months())
                    ->placeholder('Sve'),

                SelectFilter::make('category_id')
                    ->label('Kategorija')
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),

                SelectFilter::make('godina')
                    ->label('Godina')
                    ->options(function (): array {
                        $query = Budget::query()->orderByDesc('godina');

                        if (! Auth::user()?->isSuperAdmin()) {
                            $query->where('user_id', Auth::user()?->ownerId());
                        }

                        return $query->pluck('godina', 'godina')->toArray();
                    })
                    ->placeholder('Sve')
                    ->query(function (Builder $query, array $data): Builder {
                        $year = $data['value'] ?? null;

                        if (! filled($year)) {
                            return $query;
                        }

                        return $query->whereHas(
                            'budget',
                            fn (Builder $budgetQuery) => $budgetQuery->where('godina', $year)
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

                        return $query->where('realizirano', (bool) (int) $value);
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->label('Uredi'),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->label(''),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Novi trošak')
                    ->modalHeading('Novi trošak')
                    ->form(ExpenseForm::schema())
                    ->mutateFormDataUsing(function (array $data): array {
                        if (! Auth::user()?->isSuperAdmin()) {
                            $data['user_id'] = Auth::user()?->ownerId();
                        }

                        return $data;
                    }),

                Action::make('export_excel')
                    ->label('Izvoz u Excel')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->action(function () {
                        $year = data_get(request()->input('tableFilters.godina'), 'value')
                            ?: (string) Carbon::now('Europe/Zagreb')->year;

                        return Excel::download(
                            new ExpensesExport($year),
                            'Troskovi_' . $year . '.xlsx'
                        );
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Obriši označeno'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::user()?->ownerId());
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where('user_id', Auth::user()?->ownerId());
        }

        return (string) $query->count();
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::user()?->ownerId();
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['user_id'] = Auth::user()?->ownerId();
        }

        return $data;
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

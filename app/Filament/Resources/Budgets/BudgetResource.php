<?php

namespace App\Filament\Resources\Budgets;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Budgets\Pages;
use App\Models\Budget;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BudgetResource extends BaseResource
{
    protected static ?string $model = Budget::class;

    protected static \BackedEnum|string|null $navigationIcon =
        Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Budžet';

    protected static ?string $modelLabel = 'Budžet';

    protected static ?string $pluralModelLabel = 'Budžet';

    protected static \UnitEnum|string|null $navigationGroup =
        'Upravljanje';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'godina';

    /*
     * Standardni poslovni modul.
     *
     * BaseResource koristi:
     * user_id = ownerId()
     */
    protected static bool $hasOwnership = true;

    protected static function getModuleKey(): ?string
    {
        return 'budgets';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Unos budžeta')
                ->columns(2)
                ->schema([
                    TextInput::make('godina')
                        ->label('Godina')
                        ->numeric()
                        ->required(),

                    TextInput::make('ukupni_budget')
                        ->label('Ukupni budžet (€)')
                        ->numeric()
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('godina', 'desc')
            ->columns([
                TextColumn::make('godina')
                    ->label('Godina')
                    ->sortable(),

                static::userTableColumn(),

                TextColumn::make('ukupni_budget')
                    ->label('Ukupni budžet (€)')
                    ->formatStateUsing(
                        fn ($state) =>
                            number_format(
                                (float) $state,
                                2,
                                ',',
                                '.'
                            ) . ' €'
                    )
                    ->sortable(),

                ViewColumn::make('stanje_budgeta')
                    ->label('Stanje budžeta')
                    ->view(
                        'filament.tables.columns.budget-status'
                    ),
            ])
            ->actions([
                EditAction::make()
                    ->label('Uredi'),

                DeleteAction::make()
                    ->label('Obriši')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Obriši budžet')
                    ->modalDescription(
                        'Jeste li sigurni da želite obrisati ovaj budžet?'
                    )
                    ->modalSubmitActionLabel('Obriši')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(
                        fn (Budget $record): bool =>
                            (int) $record->expenses_count === 0
                    ),

                Action::make('cannot_delete')
                    ->label(
                        'Budžet se ne može obrisati dok postoje troškovi'
                    )
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->visible(
                        fn (Budget $record): bool =>
                            (int) $record->expenses_count > 0
                    )
                    ->action(function (): void {
                        Notification::make()
                            ->title(
                                'Brisanje budžeta nije moguće'
                            )
                            ->body(
                                'Najprije obrišite sve troškove povezane s ovim budžetom.'
                            )
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([])
            ->headerActions([
                CreateAction::make()
                    ->label('Novi budžet')
                    ->modalHeading('Novi budžet')
                    ->visible(
                        fn (): bool => static::canCreate()
                    )
                    ->mutateFormDataUsing(
                        fn (array $data): array =>
                            static::fillOwnershipData($data)
                    ),
            ]);
    }

    /**
     * BaseResource već radi tenant scope.
     *
     * Ovdje samo dodajemo expenses_count potreban
     * tablici i zaštiti brisanja.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('expenses');
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListBudgets::route('/'),

            'create' =>
                Pages\CreateBudget::route('/create'),

            'edit' =>
                Pages\EditBudget::route('/{record}/edit'),

            'view' =>
                Pages\ViewBudget::route('/{record}'),
        ];
    }
}

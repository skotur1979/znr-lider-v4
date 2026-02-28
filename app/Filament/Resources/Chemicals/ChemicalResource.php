<?php

namespace App\Filament\Resources\Chemicals;

use App\Filament\Resources\Chemicals\Pages;
use App\Filament\Resources\Chemicals\Schemas\ChemicalForm;
use App\Models\Chemical;

use BackedEnum;
use UnitEnum;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;

use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ChemicalResource extends Resource
{
    protected static ?string $model = Chemical::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'Kemikalije';
    protected static ?string $modelLabel = 'Kemikalija';
    protected static ?string $pluralModelLabel = 'Kemikalije';

    protected static UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'product_name';

    public static function form(Schema $schema): Schema
    {
        return ChemicalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_name')
                    ->label('Ime proizvoda')
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn (Chemical $record) => (string) $record->product_name),

                TextColumn::make('cas_number')
                    ->label('CAS')
                    ->wrap(),

                TextColumn::make('ufi_number')
                    ->label('UFI')
                    ->wrap(),

                ViewColumn::make('hazard_pictograms')
                    ->label('Piktogrami')
                    ->alignCenter()
                    ->view('filament.tables.columns.hazard-pictograms'),

                TextColumn::make('h_statements')
                    ->label('H oznake')
                    ->alignCenter()
                    ->wrap()
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state),

                TextColumn::make('p_statements')
                    ->label('P oznake')
                    ->alignCenter()
                    ->wrap()
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state),

                TextColumn::make('usage_location')
                    ->label('Mjesto upotrebe')
                    ->alignCenter()
                    ->wrap(),

                TextColumn::make('annual_quantity')
                    ->label('Količina')
                    ->alignCenter()
                    ->wrap(),

                TextColumn::make('gvi_kgvi')
                    ->label('GVI / KGVI')
                    ->alignCenter()
                    ->wrap(),

                TextColumn::make('voc')
                    ->label('VOC')
                    ->alignCenter()
                    ->wrap(),

                TextColumn::make('stl_hzjz')
                    ->label('STL – HZJZ')
                    ->date('d.m.Y')
                    ->alignCenter(),

                TextColumn::make('attachments')
                    ->label('Prilozi')
                    ->alignCenter()
                    ->badge()
                    ->icon(fn (Chemical $record) => is_array($record->attachments) && count($record->attachments) > 0 ? 'heroicon-o-paper-clip' : null)
                    ->color(fn (Chemical $record) => is_array($record->attachments) && count($record->attachments) > 0 ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state, Chemical $record) => is_array($record->attachments) ? (string) count($record->attachments) : '0')
                    ->tooltip(function (Chemical $record) {
                        $files = $record->attachments;

                        if (! is_array($files) || empty($files)) {
                            return 'Nema priloga';
                        }

                        return collect($files)
                            ->map(fn ($path) => is_string($path) ? basename($path) : (string) $path)
                            ->implode("\n");
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder('Odaberi status')
                    ->options([
                        'active'  => 'Aktivni zapisi',
                        'trashed' => 'Deaktivirani zapisi',
                        'all'     => 'Svi zapisi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'trashed' => $query->onlyTrashed(),
                            'all'     => $query->withTrashed(),
                            default   => $query->withoutTrashed(),
                        };
                    }),

                SelectFilter::make('usage_location')
                    ->label('Mjesto upotrebe')
                    ->options(fn () => Chemical::query()
                        ->whereNotNull('usage_location')
                        ->distinct()
                        ->orderBy('usage_location')
                        ->pluck('usage_location', 'usage_location')
                        ->toArray()
                    ),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make()
                        ->visible(fn (Chemical $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Chemical $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    RestoreAction::make()
                        ->requiresConfirmation()
                        ->visible(fn (Chemical $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->requiresConfirmation()
                        ->visible(fn (Chemical $record) => method_exists($record, 'trashed') && $record->trashed()),
                ]),
            ])
            ->bulkActions([
    DeleteBulkAction::make()
        ->label('Deaktiviraj označeno')
        ->requiresConfirmation()
        ->modalHeading('Deaktiviraj odabrano')
        ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
        ->modalSubmitActionLabel('Deaktiviraj')
        ->modalCancelActionLabel('Odustani')
        ->visible(fn (HasTable $livewire) => ! self::isOnlyTrashed($livewire)),

    // OVO JE DRUGI CRVENI GUMB – kao na Machines: uvijek vidljiv
    ForceDeleteBulkAction::make()
        ->label('Trajno obriši označeno')
        ->requiresConfirmation()
        ->modalHeading('Trajno obriši odabrano')
        ->modalDescription('Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.')
        ->modalSubmitActionLabel('Trajno obriši')
        ->modalCancelActionLabel('Odustani'),
            ]);
    }

    /**
     * Radi s TrashedFilter::make() (key = 'trashed'):
     * value: 'only' | 'with' | 'without' | null
     */
    private static function isOnlyTrashed(HasTable $livewire): bool
{
    $state = $livewire->getTableFilterState('status'); // SelectFilter::make('status')
    $value = data_get($state, 'value');

    return $value === 'trashed';
}

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemicals::route('/'),
            'create' => Pages\CreateChemical::route('/create'),
            'edit'   => Pages\EditChemical::route('/{record}/edit'),
        ];
    }

    /** Admin sve, korisnik samo svoje */
    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        return Auth::user()?->isAdmin()
            ? $q
            : $q->where('user_id', Auth::id());
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $q = static::getModel()::query();

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return (string) $q->count();
    }
    
}
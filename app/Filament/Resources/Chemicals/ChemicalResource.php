<?php

namespace App\Filament\Resources\Chemicals;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Chemicals\Pages;
use App\Filament\Resources\Chemicals\Schemas\ChemicalForm;
use App\Models\Chemical;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Filament\Support\Enums\MaxWidth;

class ChemicalResource extends BaseResource
{
    protected static ?string $model = Chemical::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-beaker';
    protected static UnitEnum|string|null $navigationGroup = 'Upravljanje';

    protected static ?string $navigationLabel = 'Kemikalije';
    protected static ?string $modelLabel = 'Kemikalija';
    protected static ?string $pluralModelLabel = 'Kemikalije';
    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'product_name';

    protected static function getModuleKey(): ?string
    {
        return 'chemicals';
    }
    public static function getMaxContentWidth(): MaxWidth|string|null
{
    return MaxWidth::Full;
}

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
        ->searchable()
        ->sortable()
        ->wrap()
        ->tooltip(fn (Chemical $record) => (string) $record->product_name)
        ->toggleable(),

    static::userTableColumn()
        ->toggleable(),

    TextColumn::make('cas_number')
        ->label('CAS')
        ->searchable()
        ->sortable()
        ->wrap()
        ->toggleable(),

    TextColumn::make('ufi_number')
        ->label('UFI')
        ->searchable()
        ->sortable()
        ->wrap()
        ->toggleable(),

    ViewColumn::make('hazard_pictograms')
        ->label('Piktogrami')
        ->alignCenter()
        ->view('filament.tables.columns.hazard-pictograms')
        ->toggleable(),

    TextColumn::make('h_statements')
        ->label('H oznake')
        ->alignCenter()
        ->wrap()
        ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state)
        ->toggleable(),

    TextColumn::make('p_statements')
        ->label('P oznake')
        ->alignCenter()
        ->wrap()
        ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state)
        ->toggleable(),

    TextColumn::make('usage_location')
        ->label('Mjesto upotrebe')
        ->alignCenter()
        ->sortable()
        ->wrap()
        ->toggleable(),

    TextColumn::make('annual_quantity')
        ->label('Količina')
        ->alignCenter()
        ->sortable()
        ->wrap()
        ->toggleable(),

    TextColumn::make('gvi_kgvi')
        ->label('GVI / KGVI')
        ->alignCenter()
        ->wrap()
        ->toggleable(isToggledHiddenByDefault: true),

    TextColumn::make('voc')
        ->label('VOC')
        ->alignCenter()
        ->wrap()
        ->toggleable(isToggledHiddenByDefault: true),

    TextColumn::make('stl_hzjz')
        ->label('STL – HZJZ')
        ->date('d.m.Y.')
        ->sortable()
        ->alignCenter()
        ->toggleable(),

    TextColumn::make('attachments')
        ->label('Prilozi')
        ->alignment(\Filament\Support\Enums\Alignment::Center)
        ->html()
        ->state(function (Chemical $record): string {
            if (! is_array($record->attachments) || count($record->attachments) === 0) {
                return '<span style="color:#6b7280;">0</span>';
            }

            return collect($record->attachments)
                ->map(function ($file, $index) {
                    $url = route('file.preview', [
                        'file' => ltrim($file, '/'),
                    ]);

                    $name = e(basename($file));
                    $number = $index + 1;

                    return '<a href="' . e($url) . '"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="' . $name . '"
                        onclick="event.preventDefault(); event.stopPropagation(); event.stopImmediatePropagation(); window.open(this.href, \'_blank\'); return false;"
                        style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            min-width:28px;
                            height:24px;
                            padding:0 8px;
                            margin:1px 2px;
                            border-radius:7px;
                            background:rgba(59,130,246,.15);
                            border:1px solid rgba(59,130,246,.35);
                            color:#93c5fd;
                            font-size:12px;
                            font-weight:700;
                            text-decoration:none;
                            cursor:pointer;
                        "
                    >📎 ' . $number . '</a>';
                })
                ->implode('');
        })
        ->tooltip(function (Chemical $record): string {
            if (! is_array($record->attachments) || count($record->attachments) === 0) {
                return 'Nema priloga';
            }

            return collect($record->attachments)
                ->map(fn ($file, $index) => ($index + 1) . '. ' . basename($file))
                ->implode("\n");
        })
        ->toggleable(),
])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder('Odaberi status')
                    ->options([
                        'active' => 'Aktivni zapisi',
                        'trashed' => 'Deaktivirani zapisi',
                        'all' => 'Svi zapisi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            'trashed' => $query->onlyTrashed(),
                            'all' => $query->withTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    }),

                SelectFilter::make('usage_location')
                    ->label('Mjesto upotrebe')
                    ->options(fn () => static::getEloquentQuery()
                        ->whereNotNull('usage_location')
                        ->where('usage_location', '<>', '')
                        ->distinct()
                        ->orderBy('usage_location')
                        ->pluck('usage_location', 'usage_location')
                        ->toArray()
                    )
                    ->searchable(),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (Chemical $record) => ! $record->trashed()),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Chemical $record) => ! $record->trashed()),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (Chemical $record) => $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (Chemical $record) => $record->trashed()),
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
                    ->visible(fn (HasTable $livewire) => ! static::isOnlyTrashed($livewire)),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Trajno obriši odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.')
                    ->modalSubmitActionLabel('Trajno obriši')
                    ->modalCancelActionLabel('Odustani'),
            ]);
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');

        return data_get($state, 'value') === 'trashed';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChemicals::route('/'),
            'create' => Pages\CreateChemical::route('/create'),
            'edit' => Pages\EditChemical::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        if (Auth::user()?->isSuperAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::user()?->ownerId());
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function getNavigationBadge(): ?string
    {
        $query = static::getModel()::query();

        if (! Auth::user()?->isSuperAdmin()) {
            $query->where('user_id', Auth::user()?->ownerId());
        }

        return (string) $query->count();
    }
}

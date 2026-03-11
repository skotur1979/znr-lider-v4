<?php

namespace App\Filament\Resources\WasteTypes;

use App\Filament\Resources\WasteTypes\Pages\CreateWasteType;
use App\Filament\Resources\WasteTypes\Pages\EditWasteType;
use App\Filament\Resources\WasteTypes\Pages\ListWasteTypes;
use App\Filament\Resources\WasteTypes\Pages\ViewWasteType;
use App\Models\WasteType;
use App\Models\WasteCatalogItem;
use App\Support\WasteCodeFormatter;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;

use Filament\Schemas\Components\Section as FormSection;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;

use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WasteTypeResource extends Resource
{
    protected static ?string $model = WasteType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Vrste otpada';
    protected static ?string $modelLabel = 'Vrsta otpada';
    protected static ?string $pluralModelLabel = 'Vrste otpada';
    protected static string | \UnitEnum | null $navigationGroup = 'Zaštita okoliša';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
{
    return $schema->components([
        FormSection::make('Podaci o vrsti otpada')
            ->schema([
                Select::make('catalog_select')
                    ->label('Katalog otpada')
                    ->placeholder('Počni upisivati ključni broj ili naziv...')
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->getSearchResultsUsing(function (string $search) {
                        return WasteCatalogItem::query()
                            ->where('waste_code', 'like', '%' . str_replace(' ', '', $search) . '%')
                            ->orWhere('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn ($item) => [
                                $item->id => $item->display_name,
                            ])
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => WasteCatalogItem::find($value)?->display_name)
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if (! $state) {
                            return;
                        }

                        $item = WasteCatalogItem::find($state);

                        if (! $item) {
                            return;
                        }

                        $set('waste_code', $item->waste_code);
                        $set('name', $item->name);
                        $set('is_hazardous', $item->is_hazardous);
                    })
                    ->columnSpanFull(),

                TextInput::make('waste_code')
    ->label('Ključni broj otpada')
    ->required()
    ->maxLength(20)
    ->placeholder('npr. 150110*')
    ->unique(ignoreRecord: true)
    ->formatStateUsing(function (?string $state) {
        if (! $state) {
            return null;
        }

        $raw = trim($state);
        $hasStar = str_ends_with($raw, '*');
        $code = rtrim($raw, '*');
        $digits = preg_replace('/\D+/', '', $code);

        if (strlen($digits) === 6) {
            $code = substr($digits, 0, 2) . ' ' . substr($digits, 2, 2) . ' ' . substr($digits, 4, 2);
        }

        return $hasStar ? $code . '*' : $code;
    })
    ->dehydrateStateUsing(function (?string $state) {
        if (! $state) {
            return null;
        }

        return str_replace(' ', '', $state);
    }),

                TextInput::make('name')
                    ->label('Naziv')
                    ->required()
                    ->columnSpan(2),

                Toggle::make('is_hazardous')
                    ->label('Opasan otpad')
                    ->default(false)
                    ->columnSpanFull(),
            ])
            ->columns(3),
    ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('waste_code')

            ->columns([

                TextColumn::make('waste_code')
                    ->label('Ključni broj otpada')
                    ->searchable()
                    ->sortable()
                    ->html()

                    ->formatStateUsing(function (string $state): string {

                        $hasStar = str_ends_with($state, '*');
                        $code = rtrim($state, '*');

                        if (strlen($code) === 6) {
                            $code =
                                substr($code, 0, 2) . ' ' .
                                substr($code, 2, 2) . ' ' .
                                substr($code, 4, 2);
                        }

                        return $hasStar
                            ? $code . '<sup style="font-size:0.75em">*</sup>'
                            : $code;
                    }),

                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                IconColumn::make('is_hazardous')
                    ->label('Opasan')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Kreirano')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deaktivirano')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([

                SelectFilter::make('is_hazardous')
                    ->label('Vrsta')
                    ->options([
                        '1' => 'Opasan otpad',
                        '0' => 'Neopasan otpad',
                    ])

                    ->query(function (Builder $query, array $data): Builder {

                        return $query->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query) =>
                            $query->where('is_hazardous', (bool)$data['value'])
                        );
                    }),

                TrashedFilter::make(),

            ])

            ->recordActions([

                ActionGroup::make([

                    ViewAction::make()->label('Prikaz'),

                    EditAction::make()->label('Uredi'),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->modalHeading('Deaktiviraj vrstu otpada')
                        ->modalDescription('Jesi li siguran/a da želiš deaktivirati ovu vrstu otpada?'),

                    RestoreAction::make()->label('Vrati'),

                    ForceDeleteAction::make()
                        ->label('Trajno izbriši')
                        ->modalHeading('Trajno izbriši vrstu otpada')
                        ->modalDescription('Jesi li siguran/a? Ova radnja je nepovratna.'),

                ]),

            ])

            ->bulkActions([

                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->modalHeading('Deaktiviraj odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?'),

                RestoreBulkAction::make()->label('Vrati označeno'),

                ForceDeleteBulkAction::make()
                    ->label('Trajno izbriši označeno')
                    ->modalHeading('Trajno izbriši odabrano')
                    ->modalDescription('Jesi li siguran/a? Ova radnja je nepovratna.'),

            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWasteTypes::route('/'),
            'create' => CreateWasteType::route('/create'),
            'view' => ViewWasteType::route('/{record}'),
            'edit' => EditWasteType::route('/{record}/edit'),
        ];
    }
}
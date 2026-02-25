<?php

namespace App\Filament\Resources\FirstAidKits;

use App\Filament\Resources\FirstAidKits\Pages;
use App\Models\FirstAidKit;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use UnitEnum;
use BackedEnum;

class FirstAidKitResource extends Resource
{
    protected static ?string $model = FirstAidKit::class;

    // icon = BackedEnum|string|null
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-plus-circle';

    // group = UnitEnum|string|null
    protected static string|UnitEnum|null $navigationGroup = 'Ispitivanja';

    protected static ?string $pluralModelLabel = 'Prva pomoć';
    protected static ?string $navigationLabel  = 'Prva pomoć - ormarići';
    protected static ?int $navigationSort = 3;

     public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->dehydrated(true),

            Section::make('Sanitetski materijal za prvu pomoć')
                ->schema([
                    TextInput::make('location')
                        ->label('Lokacija ormarića PP')
                        ->required()
                        ->maxLength(255),

                    DatePicker::make('inspected_at')
                        ->label('Pregled obavljen dana')
                        ->required(),

                    Textarea::make('note')
                        ->label('Napomena')
                        ->rows(2),
                ])
                ->columns(1),

            Section::make('Sadržaj ormarića prve pomoći')
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label('Sanitetski materijal')
                        ->schema([
                            TextInput::make('material_type')
                                ->label('Vrsta sanitetskog materijala')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('purpose')
                                ->label('Namjena')
                                ->required()
                                ->maxLength(255),

                            DatePicker::make('valid_until')
                                ->label('Vrijedi do'),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->createItemButtonLabel('Dodaj stavku'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('items'))
            ->columns([
                TextColumn::make('location')
                    ->label('Lokacija ormarića')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('inspected_at')
                    ->label('Pregled obavljen')
                    ->alignCenter()
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Ukupan broj stavki')
                    ->alignCenter()
                    ->counts('items'),

                ViewColumn::make('items_summary')
                    ->label('Rok ističe/istekao')
                    ->alignCenter()
                    ->view('filament.resources.first-aid-kits.items-summary'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaz'),
                    EditAction::make()->label('Uredi'),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->modalHeading('Obriši Prvu pomoć')
                        ->modalSubheading('Jeste li sigurni da želite obrisati ovu Prvu pomoć?')
                        ->successNotificationTitle('Prva pomoć je obrisana.'),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->modalHeading('Obriši Prve pomoći')
                    ->modalSubheading('Jeste li sigurni da želite obrisati odabrane zapise?')
                    ->successNotificationTitle('Prve pomoći su obrisane.'),
            ])
            ->defaultSort('inspected_at', 'desc');
    }

    /** Admin vidi sve, user samo svoje */
    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();

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

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = $data['user_id'] ?? Auth::id();
        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFirstAidKits::route('/'),
            'create' => Pages\CreateFirstAidKit::route('/create'),
            'edit'   => Pages\EditFirstAidKit::route('/{record}/edit'),
            'view'   => Pages\ViewFirstAidKit::route('/{record}'),
        ];
    }
}
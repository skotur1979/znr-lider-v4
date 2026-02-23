<?php

namespace App\Filament\Resources\Machines;

use App\Filament\Resources\Machines\Pages;
use App\Models\Machine;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

use Filament\Resources\Resource;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Support\ExpiryBadge;

class MachineResource extends Resource
{
    
    // Filament v4: Resource očekuje ?string
    protected static ?string $model = Machine::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCog;

    protected static ?string $navigationLabel = 'Radna Oprema';
    protected static ?string $modelLabel = 'Radna Oprema';
    protected static ?string $pluralModelLabel = 'Radna Oprema';

    protected static \UnitEnum|string|null $navigationGroup = 'Ispitivanja';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->label('Korisnik')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn () => Auth::user()?->isAdmin())
                ->dehydrated(fn () => Auth::user()?->isAdmin()),

            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->visible(fn () => ! Auth::user()?->isAdmin())
                ->dehydrated(fn () => ! Auth::user()?->isAdmin()),

            Section::make('Podatci o radnoj opremi')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Naziv (obavezno)')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('manufacturer')
                        ->label('Proizvođač')
                        ->maxLength(255),

                    TextInput::make('factory_number')
                        ->label('Tvornički broj')
                        ->maxLength(255),

                    TextInput::make('inventory_number')
                        ->label('Inventarni broj')
                        ->maxLength(255),
                ]),

            Section::make('Ispitivanje')
                ->columns(2)
                ->schema([
                    DatePicker::make('examination_valid_from')
                        ->label('Vrijedi od (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('examination_valid_until')
                        ->label('Vrijedi do (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    TextInput::make('examined_by')
                        ->label('Ispitao')
                        ->maxLength(255),

                    TextInput::make('report_number')
                        ->label('Broj izvještaja')
                        ->maxLength(255),
                ]),

            Section::make('Ostalo')
                ->columns(2)
                ->schema([
                    TextInput::make('location')
                        ->label('Lokacija (obavezno)')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('remark')
                        ->label('Napomena')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            FileUpload::make('pdf')
                ->label('Dodaj priloge (max. 5, do 30 MB po datoteci)')
                ->disk('public')
                ->directory('pdfs')
                ->multiple()
                ->maxFiles(5)
                ->maxSize(30720)
                ->preserveFilenames()
                ->openable()
                ->downloadable()
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/zip',
                    'application/x-rar-compressed',
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('manufacturer')
                    ->label('Proizvođač')
                    ->searchable()
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('factory_number')
                    ->label('Tvor.broj')
                    ->searchable()
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('examination_valid_from')
                    ->label('Datum ispitivanja')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('examination_valid_until')
    ->label('Ispitivanje vrijedi do')
    ->date('d.m.Y.')
    ->badge()
    ->sortable()
    ->alignment(Alignment::Center)
    ->color(fn ($state) => ExpiryBadge::color($state))
    ->icon(fn ($state) => ExpiryBadge::icon($state))
    ->iconPosition('before')
    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state)),

                TextColumn::make('location')
                    ->label('Lokacija')
                    ->sortable()
                    ->wrap()
                    ->alignment(Alignment::Center),

                TextColumn::make('pdf')
                    ->label('Prilozi')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->icon(fn (Machine $record) => is_array($record->pdf) && count($record->pdf) ? Heroicon::PaperClip : null)
                    ->color(fn (Machine $record) => is_array($record->pdf) && count($record->pdf) ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state, Machine $record) => is_array($record->pdf) ? (string) count($record->pdf) : '0')
                    ->tooltip(fn (Machine $record) => is_array($record->pdf) && count($record->pdf)
                        ? implode("\n", $record->pdf)
                        : 'Nema priloga'),
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
            default   => $query->withoutTrashed(), // ✅ bez filtera = samo aktivni
        };
    }),

    SelectFilter::make('location')
        ->label('Lokacije')
        ->placeholder('Sve')
        ->options(fn () => static::getLocationOptions())
        ->searchable(),

    Filter::make('isteklo')
        ->label('Ispitivanje (isteklo)')
        ->query(fn (Builder $query) => $query->whereDate('examination_valid_until', '<', Carbon::today())),

    Filter::make('uskoro')
        ->label('Ispitivanje (uskoro ističe)')
        ->query(fn (Builder $query) => $query
            ->whereDate('examination_valid_until', '>=', Carbon::today())
            ->whereDate('examination_valid_until', '<=', Carbon::today()->addDays(30))),
])
        ->paginated([10, 25, 50, 'all']) // ✅ dodano "all"

            ->recordActions([
    ActionGroup::make([
        ViewAction::make()->label('Prikaži'),
        EditAction::make()->label('Uredi'),
        DeleteAction::make()->label('Deaktiviraj')->requiresConfirmation(),
        RestoreAction::make()->label('Vrati')->requiresConfirmation(),
        ForceDeleteAction::make()->label('Trajno obriši')->requiresConfirmation(),
    ])
        ->icon(Heroicon::EllipsisVertical)
        ->label(''),
])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Deaktiviraj označeno')->requiresConfirmation(),
                    RestoreBulkAction::make()->label('Vrati označeno')->requiresConfirmation(),
                    ForceDeleteBulkAction::make()->label('Trajno obriši označeno')->requiresConfirmation(),
                ]),
            ]);
    }

    protected static function getLocationOptions(): array
    {
        return static::getEloquentQuery()
            ->whereNotNull('location')
            ->where('location', '<>', '')
            ->distinct()
            ->orderBy('location')
            ->pluck('location', 'location')
            ->toArray();
    }

    public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()
        ->withoutGlobalScopes([SoftDeletingScope::class]);

    if (Auth::user()?->isAdmin()) {
        return $query;
    }

    return $query->where('user_id', Auth::id());
}

    public static function getNavigationBadge(): ?string
{
    $q = static::getModel()::query(); // default scope = samo aktivni

    if (! Auth::user()?->isAdmin()) {
        $q->where('user_id', Auth::id());
    }

    return (string) $q->count();
}

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMachines::route('/'),
            'create' => Pages\CreateMachine::route('/create'),
            'edit'   => Pages\EditMachine::route('/{record}/edit'),
            'view'   => Pages\ViewMachine::route('/{record}'),
        ];
    }
}


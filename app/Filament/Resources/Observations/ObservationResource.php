<?php

namespace App\Filament\Resources\Observations;

use App\Filament\Resources\Observations\Pages;
use App\Models\Employee;
use App\Models\Observation;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Contracts\HasTable;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ObservationResource extends Resource
{
    protected static ?string $model = Observation::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedExclamationCircle;

    protected static ?string $navigationLabel = 'Zapažanja';
    protected static ?string $modelLabel = 'Zapažanje';
    protected static ?string $pluralModelLabel = 'Zapažanja';

    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
{
    return $schema->schema([
        // Admin bira korisnika, useru se automatski setira.
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

        // ✅ SVE U JEDNOJ SEKCIJI
        Section::make('Zapažanje')
            ->columns(2)
            ->schema([
                DatePicker::make('incident_date')
                    ->label('Datum')
                    ->required()
                    ->displayFormat('d.m.Y.')
                    ->weekStartsOnMonday()
                    ->timezone('Europe/Zagreb'),

                Select::make('observation_type')
                    ->label('Vrsta zapažanja')
                    ->options([
                        'Near Miss' => 'Near Miss - Skoro nezgoda',
                        'Negative Observation' => 'Negativno zapažanje',
                        'Positive Observation' => 'Pozitivno zapažanje',
                    ])
                    ->required(),

                TextInput::make('location')
                    ->label('Lokacija')
                    ->required()
                    ->maxLength(255),

                TextInput::make('item')
                    ->label('Opis zapažanja')
                    ->required()
                    ->maxLength(255),

                TextInput::make('potential_incident_type')
                    ->label('Vrsta opasnosti')
                    ->datalist([
                        'Kontakt s pokretnim dijelovima strojeva',
                        'Utapanje ili gušenje',
                        'Izloženost struji',
                        'Izloženost ekstremnim temperaturama',
                        'Izloženost vatri',
                        'Pad s visine',
                        'Pad na istoj razini',
                        'Udarac pokretnim vozilom',
                        'Udarac pokretnim, letećim ili padajućim predmetom',
                        'Udarac u nešto nepomično',
                        'Ručno rukovanje, podizanje ili nošenje',
                        'Profesionalna bolest/bolest',
                        'Fizički napad',
                        'Padovi, spoticanje ili pokliznuće',
                        'Incident s trećom stranom',
                        'Zarobljenost nečim što se ruši',
                        'Ostalo',
                        'Porezotine, ogrebotine ili abrazije',
                        'Blokirana protupožarna oprema',
                        'Blokirani evakuacijski putevi',
                        'Nedostatak odgovarajuće rasvjete',
                        'Nedostatak čistoće',
                        'Nepravilno skladištenje',
                    ])
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(), // ✅ da bude široko jer je dugačko

                FileUpload::make('picture_path')
                    ->label('Slika')
                    ->image()
                    ->disk('public')
                    ->directory('observations')
                    ->visibility('public')
                    ->preserveFilenames()
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(), // ✅ slika preko cijele širine

                Textarea::make('action')
                    ->label('Potrebna radnja')
                    ->rows(3)
                    ->columnSpanFull(),

                TextInput::make('responsible')
                    ->label('Odgovorna osoba')
                    ->datalist(fn () => Employee::query()
                        ->orderBy('name')
                        ->pluck('name')
                        ->unique()
                        ->toArray()
                    )
                    ->placeholder('Upiši ime')
                    ->maxLength(255),

                DatePicker::make('target_date')
                    ->label('Rok za provedbu')
                    ->displayFormat('d.m.Y.')
                    ->weekStartsOnMonday()
                    ->timezone('Europe/Zagreb'),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'Not started' => 'Nije započeto',
                        'In progress' => 'U tijeku',
                        'Complete' => 'Završeno',
                    ])
                    ->required(),

                Textarea::make('comments')
                    ->label('Komentar')
                    ->rows(3)
                    ->columnSpanFull(),
            ]),
    ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('incident_date')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->wrap(),

                TextColumn::make('observation_type')
                    ->label('Vrsta zapažanja')
                    ->alignment(Alignment::Center)
                    ->wrap()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'Near Miss' => 'NM - Skoro nezgoda',
                        'Negative Observation' => 'Negativno zapažanje',
                        'Positive Observation' => 'Pozitivno zapažanje',
                        default => $state,
                    }),

                TextColumn::make('location')
                    ->label('Lokacija')
                    ->alignment(Alignment::Center)
                    ->wrap(),

                TextColumn::make('item')
                    ->label('Opis')
                    ->wrap()
                    ->limit(70),

                TextColumn::make('potential_incident_type')
                    ->label('Vrsta opasnosti')
                    ->alignment(Alignment::Center)
                    ->wrap(),

                ImageColumn::make('picture_path')
    ->label('Slika')
    ->disk('public')          // ✅ BITNO
    ->visibility('public')
    ->height(50)
    ->width(80)
    ->extraImgAttributes(['style' => 'object-fit: cover; border-radius: 6px;'])
    ->getStateUsing(fn ($record) => $record->picture_path ?: null)
    ->url(fn ($record) => $record->picture_path
        ? Storage::disk('public')->url($record->picture_path)
        : null
    )
    ->openUrlInNewTab(),

                TextColumn::make('action')
                    ->label('Potrebna radnja')
                    ->wrap()
                    ->limit(70),

                TextColumn::make('responsible')
                    ->label('Odgovorna osoba')
                    ->alignment(Alignment::Center)
                    ->wrap(),

                TextColumn::make('target_date')
                    ->label('Rok za provedbu')
                    ->alignment(Alignment::Center)
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d.m.Y.') : null)
                    ->color(function (Observation $record) {
                        if (! $record->target_date || $record->status === 'Complete') {
                            return null;
                        }

                        $datum = Carbon::parse($record->target_date);
                        $danas = Carbon::today();

                        if ($datum->isPast()) {
                            return 'danger';
                        }

                        if ($datum->diffInDays($danas) <= 30) {
                            return 'warning';
                        }

                        return null;
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->alignment(Alignment::Center)
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Not started' => 'danger',
                        'In progress' => 'warning',
                        'Complete' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'Not started' => 'Nije započeto',
                        'In progress' => 'U tijeku',
                        'Complete' => 'Završeno',
                        default => $state,
                    }),

                TextColumn::make('comments')
                    ->label('Komentar')
                    ->limit(20)
                    ->wrap(),
            ])
            ->filters([
                // 1) Aktivni / Deaktivirani / Svi (isti pattern kao Machines)
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

                // 2) Vrsta zapažanja (samo filter po tipu, ne miješamo trashed ovdje)
                SelectFilter::make('observation_type')
                    ->label('Vrsta zapažanja')
                    ->placeholder('Sve')
                    ->options([
                        'Near Miss' => 'Near Miss - Skoro nezgoda',
                        'Negative Observation' => 'Negativno zapažanje',
                        'Positive Observation' => 'Pozitivno zapažanje',
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        filled($data['value'] ?? null)
                            ? $query->where('observation_type', $data['value'])
                            : $query
                    ),

                // 3) Godina nastanka
                SelectFilter::make('year')
                    ->label('Godina nastanka')
                    ->placeholder('Sve')
                    ->options(fn () => static::getYearOptions())
                    ->query(function (Builder $query, array $data) {
                        $year = $data['value'] ?? null;

                        if (filled($year)) {
                            $query->whereYear('incident_date', (int) $year);
                        }

                        return $query;
                    }),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (Observation $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (Observation $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (Observation $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (Observation $record) => method_exists($record, 'trashed') && $record->trashed()),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->label(''),
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

    RestoreBulkAction::make()
        ->label('Vrati označeno')
        ->requiresConfirmation()
        ->modalHeading('Vrati odabrano')
        ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
        ->modalSubmitActionLabel('Vrati')
        ->modalCancelActionLabel('Odustani')
        ->visible(fn (HasTable $livewire) => self::isOnlyTrashed($livewire)),

    ForceDeleteBulkAction::make()
        ->label('Trajno obriši označeno')
        ->requiresConfirmation()
        ->modalHeading('Trajno obriši odabrano')
        ->modalDescription('Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.')
        ->modalSubmitActionLabel('Trajno obriši')
        ->modalCancelActionLabel('Odustani'),
            ])
            ->defaultSort('incident_date', 'desc');
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
{
    // Filter u tvom resource-u: SelectFilter::make('observation_filter') + opcija 'trashed'
    $state = $livewire->getTableFilterState('observation_filter');
    $value = data_get($state, 'value');

    return $value === 'trashed';
}

    protected static function getYearOptions(): array
    {
        // uzmi godine iz queryja koji poštuje admin/user scope i soft deletes scope (bez global scope)
        return static::getEloquentQuery()
            ->selectRaw('YEAR(incident_date) as year')
            ->whereNotNull('incident_date')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year', 'year')
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListObservations::route('/'),
            'create' => Pages\CreateObservation::route('/create'),
            'edit'   => Pages\EditObservation::route('/{record}/edit'),
            'view'   => Pages\ViewObservation::route('/{record}'),
        ];
    }
}
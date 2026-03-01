<?php

namespace App\Filament\Resources\Incidents;

use App\Filament\Resources\Incidents\Pages;
use App\Models\Incident;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

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

use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

use Filament\Tables\Filters\SelectFilter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedEye;

    protected static ?string $navigationLabel = 'Incidenti';
    protected static ?string $modelLabel = 'Incident';
    protected static ?string $pluralModelLabel = 'Incidenti';

    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 5;

    public static array $INCIDENT_TYPES = [
        'LTA' => 'LTA – Ozljeda na radu',
        'MTA' => 'MTA – Pružanje PP izvan tvrtke',
        'FAA' => 'FAA – Pružanje PP u tvrtki',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            // 1:1 kao Machines
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

            Section::make('Osnovno')
                ->columns(2)
                ->schema([
                    TextInput::make('location')
                        ->label('Lokacija (obavezno)')
                        ->required()
                        ->maxLength(255),

                    Select::make('type_of_incident')
                        ->label('Vrsta incidenta (obavezno)')
                        ->options(self::$INCIDENT_TYPES)
                        ->required(),

                    Select::make('permanent_or_temporary')
                        ->label('Vrsta zaposlenja (obavezno)')
                        ->options([
                            'Permanent' => 'Stalni',
                            'Temporary' => 'Privremeni',
                        ])
                        ->required(),

                    DatePicker::make('date_occurred')
                        ->label('Datum nastanka (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb')
                        ->reactive(),

                    DatePicker::make('date_of_return')
                        ->label('Datum povratka na posao')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb')
                        ->reactive()
                        ->after('date_occurred')
                        ->afterStateUpdated(function ($state, $context, $set, $get) {
                            $start = $get('date_occurred');
                            $end = $state;

                            if ($start && $end) {
                                $startDate = \Carbon\Carbon::parse($start);
                                $endDate = \Carbon\Carbon::parse($end);

                                // Isključujemo dan nezgode
                                $daysLost = $startDate->diffInWeekdays($endDate) - 1;
                                $set('working_days_lost', max($daysLost, 0));
                            }
                        }),

                    TextInput::make('working_days_lost')
                        ->label('Izgubljeni radni dani')
                        ->numeric(),
                ]),

            Section::make('Detalji')
                ->columns(2)
                ->schema([
                    Textarea::make('causes_of_injury')
                        ->label('Uzrok ozljede')
                        ->rows(2),

                    Textarea::make('accident_injury_type')
                        ->label('Tip ozljede')
                        ->rows(2),

                    TextInput::make('injured_body_part')
                        ->label('Ozlijeđeni dio tijela')
                        ->maxLength(255),

                    TextInput::make('other')
                        ->label('Napomena - Podaci o ozlijeđenom radniku')
                        ->columnSpanFull(),
                ]),

            Section::make('Prilozi')
                ->columns(2)
                ->schema([
                    FileUpload::make('image_path')
    ->label('Slika')
    ->image()
    ->disk('public')
    ->directory('incidents')
    ->visibility('public')
    ->preserveFilenames()
    ->openable()
    ->downloadable(),

                    FileUpload::make('investigation_report')
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
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $maxTotalMB = 150;
                            $totalBytes = 0;

                            if (is_array($state)) {
                                foreach ($state as $file) {
                                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                                        $totalBytes += $file->getSize();
                                    }
                                }
                            }

                            if ($totalBytes > $maxTotalMB * 1024 * 1024) {
                                $set('investigation_report', []);
                                \Filament\Notifications\Notification::make()
                                    ->title("Ukupna veličina svih datoteka ne smije biti veća od {$maxTotalMB} MB.")
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location')
                    ->label('Lokacija')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('type_of_incident')
                    ->label('Vrsta incidenta')
                    ->alignment(Alignment::Center)
                    ->weight('bold')
                    ->description(function (Incident $record) {
                        $map = [
                            'LTA' => 'Ozljeda na radu',
                            'MTA' => 'Pružanje PP izvan tvrtke',
                            'FAA' => 'Pružanje PP u tvrtki',
                        ];
                        return $map[$record->type_of_incident] ?? null;
                    }, position: 'below')
                    ->wrap(),

                TextColumn::make('date_occurred')
                    ->label('Datum nastanka')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('working_days_lost')
                    ->label('Izgubljeni radni dani')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('injured_body_part')
                    ->label('Ozlijeđeni dio tijela')
                    ->wrap()
                    ->alignment(Alignment::Center),

                ImageColumn::make('image_path')
    ->label('Slika')
    ->disk('public')
    ->circular()
    ->height(36)
    ->width(36),

                TextColumn::make('other')
                    ->label('Napomena')
                    ->wrap(),

                TextColumn::make('investigation_report')
                    ->label('Izvještaji')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->icon(fn (Incident $record) => is_array($record->investigation_report) && count($record->investigation_report) ? Heroicon::PaperClip : null)
                    ->color(fn (Incident $record) => is_array($record->investigation_report) && count($record->investigation_report) ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state, Incident $record) => is_array($record->investigation_report) ? (string) count($record->investigation_report) : '0')
                    ->tooltip(fn (Incident $record) => is_array($record->investigation_report) && count($record->investigation_report)
                        ? implode("\n", $record->investigation_report)
                        : 'Nema izvještaja'),
            ])
            ->filters([
                // 1:1 kao Machines: status filter
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

                SelectFilter::make('type_of_incident')
                    ->label('Vrsta incidenta')
                    ->placeholder('Sve')
                    ->options(self::$INCIDENT_TYPES),

                SelectFilter::make('godina_filter')
                    ->label('Godina nastanka')
                    ->options(fn () => static::getYearOptions())
                    ->placeholder('Sve')
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if ($value) {
                            $query->whereYear('date_occurred', $value);
                        }
                    }),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (Incident $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (Incident $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (Incident $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (Incident $record) => method_exists($record, 'trashed') && $record->trashed()),
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
            ]);
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');
        $value = data_get($state, 'value');

        return $value === 'trashed';
    }

    protected static function getYearOptions(): array
    {
        return static::getEloquentQuery()
            ->selectRaw('YEAR(date_occurred) as year')
            ->whereNotNull('date_occurred')
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
            'index'  => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'edit'   => Pages\EditIncident::route('/{record}/edit'),
            'view'   => Pages\ViewIncident::route('/{record}'),
        ];
    }
}
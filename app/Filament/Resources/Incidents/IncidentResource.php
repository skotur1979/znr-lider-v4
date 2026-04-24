<?php

namespace App\Filament\Resources\Incidents;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Incidents\Pages;
use App\Models\Incident;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class IncidentResource extends BaseResource
{
    protected static ?string $model = Incident::class;

    protected static bool $usesSoftDeletes = true;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedEye;

    protected static ?string $navigationLabel = 'Incidenti';
    protected static ?string $modelLabel = 'Incident';
    protected static ?string $pluralModelLabel = 'Incidenti';

    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 5;

    protected static function getModuleKey(): ?string
    {
        return 'incidents';
    }

    protected static function incidentTypes(): array
    {
        return [
            'LTA' => 'LTA – Ozljeda na radu',
            'MTA' => 'MTA – Pružanje PP izvan tvrtke',
            'FAA' => 'FAA – Pružanje PP u tvrtki',
        ];
    }

    protected static function incidentTypeDescription(?string $type): ?string
    {
        return match ($type) {
            'LTA' => 'Ozljeda na radu',
            'MTA' => 'Pružanje PP izvan tvrtke',
            'FAA' => 'Pružanje PP u tvrtki',
            default => null,
        };
    }

    protected static function calculateWorkingDaysLost(?string $start, ?string $end): int
    {
        if (! $start || ! $end) {
            return 0;
        }

        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        return max($startDate->diffInWeekdays($endDate) - 1, 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Hidden::make('user_id')
                ->default(fn () => static::defaultUserId())
                ->dehydrated(),

            Section::make('Osnovno')
                ->columns(2)
                ->schema([
                    TextInput::make('location')
                        ->label('Lokacija (obavezno)')
                        ->required()
                        ->maxLength(255),

                    Select::make('type_of_incident')
                        ->label('Vrsta incidenta (obavezno)')
                        ->options(static::incidentTypes())
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
                            $set(
                                'working_days_lost',
                                static::calculateWorkingDaysLost(
                                    $get('date_occurred'),
                                    $state,
                                )
                            );
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
                                    if ($file instanceof UploadedFile) {
                                        $totalBytes += $file->getSize();
                                    }
                                }
                            }

                            if ($totalBytes > $maxTotalMB * 1024 * 1024) {
                                $set('investigation_report', []);

                                Notification::make()
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
                    
                    static::userTableColumn(),

                TextColumn::make('type_of_incident')
                    ->label('Vrsta incidenta')
                    ->alignment(Alignment::Center)
                    ->weight('bold')
                    ->description(
                        fn (Incident $record) => static::incidentTypeDescription($record->type_of_incident),
                        position: 'below'
                    )
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
                    ->options(static::incidentTypes()),

                SelectFilter::make('godina_filter')
    ->label('Godina nastanka')
    ->options(fn () => static::getYearOptions())
    ->default((string) now()->year)
    ->selectablePlaceholder(true)
    ->placeholder('Sve')
    ->query(function (Builder $query, array $data) {
        $value = $data['value'] ?? null;

        if (filled($value)) {
            $query->whereYear('date_occurred', $value);
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
                        ->visible(fn (Incident $record) => ! $record->trashed()),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (Incident $record) => ! $record->trashed()),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (Incident $record) => $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (Incident $record) => $record->trashed()),
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
    $years = static::getEloquentQuery()
        ->selectRaw('YEAR(date_occurred) as year')
        ->whereNotNull('date_occurred')
        ->distinct()
        ->orderByDesc('year')
        ->pluck('year', 'year')
        ->toArray();

    $currentYear = (string) now()->year;

    $years[$currentYear] = $currentYear;

    krsort($years);

    return $years;
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

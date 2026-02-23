<?php

namespace App\Filament\Resources\Miscellaneouses;

use App\Filament\Resources\Miscellaneouses\MiscellaneousResource\Pages;
use App\Models\Category;
use App\Models\Miscellaneous;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

class MiscellaneousResource extends Resource
{
    protected static ?string $model = Miscellaneous::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationLabel = 'Ostala ispitivanja';
    protected static ?string $modelLabel = 'Ispitivanje';
    protected static ?string $pluralModelLabel = 'Ispitivanja';
    protected static ?int $navigationSort = 4;
    protected static \UnitEnum|string|null $navigationGroup = 'Ispitivanja';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Podatci o predmetu')
                ->schema([
                    TextInput::make('name')
                        ->label('Naziv (obavezno)')
                        ->required()
                        ->maxLength(255),

                    Select::make('category_id')
    ->label('Kategorija')
    ->searchable()
    ->preload()

    // opcije (filtrirano po useru)
    ->options(function () {
        $q = Category::query();

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return $q->orderBy('name')->pluck('name', 'id')->toArray();
    })

    // search rezultati (isto filtrirano)
    ->getSearchResultsUsing(function (string $search) {
        $q = Category::query()
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(50);

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return $q->pluck('name', 'id')->toArray();
    })

    // labela za spremljenu vrijednost
    ->getOptionLabelUsing(fn ($value) => Category::find($value)?->name)

    // ✅ PLUS: dodaj novu kategoriju direktno iz selecta
    ->createOptionForm([
        TextInput::make('name')
            ->label('Naziv kategorije')
            ->required()
            ->maxLength(255),
    ])
    ->createOptionUsing(function (array $data) {
        return Category::create([
            'name'    => $data['name'],
            'user_id' => Auth::id(), // user vlasništvo
        ])->id;
    })

    ->required(),

                    TextInput::make('examiner')
                        ->label('Ispitao')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make('report_number')
                        ->label('Broj izvještaja')
                        ->maxLength(255)
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Ispitivanje')
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
                ])
                ->columns(2),

            Section::make('Napomena')
                ->schema([
                    Textarea::make('remark')
                        ->label('Napomena')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ]),

            Section::make('Prilozi')
                ->schema([
                    FileUpload::make('pdf')
                        ->label('Dodaj priloge (max. 5)')
                        ->disk('public')
                        ->directory('pdfs')
                        ->multiple()
                        ->maxFiles(5)
                        ->maxSize(30720)
                        ->preserveFilenames()
                        ->enableOpen()
                        ->enableDownload()
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
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('examination_valid_until', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Kategorija')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->alignCenter(),

                TextColumn::make('examiner')
                    ->label('Ispitao')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('examination_valid_from')
                    ->label('Datum ispitivanja')
                    ->date('d.m.Y')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('examination_valid_until')
                    ->label('Ispitivanje vrijedi do')
                    ->date('d.m.Y')
                    ->badge()
                    ->color(function ($state) {
                        if (! $state) return 'gray';
                        $d = Carbon::parse($state);

                        if ($d->lt(Carbon::today())) return 'danger';

                        $diff = Carbon::today()->diffInDays($d, false);
                        return $diff <= 30 ? 'warning' : 'success';
                    })
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('remark')
                    ->label('Napomena')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                TextColumn::make('pdf')
                    ->label('Prilozi')
                    ->alignCenter()
                    ->badge()
                    ->icon(function (Miscellaneous $record) {
                        $count = is_array($record->pdf) ? count($record->pdf) : 0;
                        return $count > 0 ? 'heroicon-o-paper-clip' : null;
                    })
                    ->color(function (Miscellaneous $record) {
                        $count = is_array($record->pdf) ? count($record->pdf) : 0;
                        return $count > 0 ? 'info' : 'gray';
                    })
                    ->state(fn (Miscellaneous $record) => is_array($record->pdf) ? count($record->pdf) : 0)
                    ->tooltip(function (Miscellaneous $record) {
                        if (! is_array($record->pdf) || count($record->pdf) === 0) return 'Nema priloga';
                        return implode("\n", $record->pdf);
                    }),
            ])
            ->filters([
                TrashedFilter::make(),

                SelectFilter::make('category_id')
                    ->label('Kategorije')
                    ->options(function () {
                        $q = Category::query();

                        if (! Auth::user()?->isAdmin()) {
                            $q->where('user_id', Auth::id());
                        }

                        return $q->orderBy('name')->pluck('name', 'id')->toArray();
                    })
                    ->searchable(),

                Filter::make('examination_validity_expired')
                    ->label('Ispitivanje (isteklo)')
                    ->query(fn (Builder $query) => $query->whereDate('examination_valid_until', '<', Carbon::today())),

                Filter::make('examination_validity_expiring')
                    ->label('Ispitivanje (uskoro ističe)')
                    ->query(fn (Builder $query) => $query
                        ->whereDate('examination_valid_until', '>=', Carbon::today())
                        ->whereDate('examination_valid_until', '<=', Carbon::today()->addDays(30))),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()->requiresConfirmation(),

                    RestoreAction::make()
                        ->visible(fn (Miscellaneous $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->visible(fn (Miscellaneous $record) => method_exists($record, 'trashed') && $record->trashed())
                        ->requiresConfirmation(),
                ]),
            ])
           ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Deaktiviraj označeno'),
                    RestoreBulkAction::make()->label('Vrati označeno'),
                    ForceDeleteBulkAction::make()->label('Trajno obriši označeno'),
                ]),
            ])
    
            ->headerActions([
                Action::make('export_pdf')
                    ->label('Izvoz u PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->action(function (Tables\Contracts\HasTable $livewire) {
                        // TODO: PDF export
                    }),

                Action::make('export_excel')
                    ->label('Izvoz u Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Tables\Contracts\HasTable $livewire) {
                        // TODO: Excel export
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMiscellaneouses::route('/'),
            'create' => Pages\CreateMiscellaneous::route('/create'),
            'view'   => Pages\ViewMiscellaneous::route('/{record}'),
            'edit'   => Pages\EditMiscellaneous::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        return Auth::user()?->isAdmin()
            ? $query
            : $query->where('user_id', Auth::id());
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
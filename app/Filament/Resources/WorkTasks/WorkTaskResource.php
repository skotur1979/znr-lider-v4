<?php

namespace App\Filament\Resources\WorkTasks;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\WorkTasks\Pages\CreateWorkTask;
use App\Filament\Resources\WorkTasks\Pages\EditWorkTask;
use App\Filament\Resources\WorkTasks\Pages\ListWorkTasks;
use App\Models\WorkTask;
use App\Services\ActivityLogger;
use BackedEnum;
use Illuminate\Support\HtmlString;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Toggle;

class WorkTaskResource extends BaseResource
{
    protected static ?string $model = WorkTask::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $slug = 'radni-zadaci';

    protected static ?string $modelLabel = 'radni zadatak';

    protected static ?string $pluralModelLabel = 'radni zadaci';

    protected static \UnitEnum|string|null $navigationGroup =
        'Upravljanje';

    protected static ?int $navigationSort = 50;

    protected static function getModuleKey(): ?string
    {
        return 'work_tasks';
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    /**
     * Radni zadaci su poslovni zapisi organizacije.
     *
     * Superadmin ih smije pregledavati radi administracije,
     * ali ih ne smije kreirati niti mijenjati.
     *
     * Glavni korisnik i podkorisnici mogu mijenjati samo
     * zadatke svoje organizacije.
     */
    public static function canManageTask(
        WorkTask $record
    ): bool {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $ownerId =
            $user->ownerId();

        if (! $ownerId) {
            return false;
        }

        /*
        * Druga organizacija nikada.
        */
        if (
            (int) $record->user_id
            !==
            (int) $ownerId
        ) {
            return false;
        }

        /*
        * Organizacijski zadatak:
        * svi korisnici organizacije ga
        * mogu obrađivati.
        */
        if (
            (bool)
            $record
                ->is_shared_with_organization
        ) {
            return true;
        }

        /*
        * Privatni zadatak:
        * samo stvarni autor.
        */
        return
            (int) (
                $record
                    ->created_by_user_id
                ?? 0
            )
            ===
            (int) $user->id;
    }

    /**
     * Superadmin ne kreira organizacijske radne zadatke.
     */
    public static function canCreate(): bool
    {
        return parent::canCreate();
    }

    /**
     * Zaštita uređivanja, uključujući direktni /edit URL.
     */
    public static function canEdit(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        return $record instanceof WorkTask
            && static::canManageTask($record)
            && parent::canEdit($record);
    }

    /**
     * Zaštita pojedinačnog brisanja.
     */
    public static function canDelete(
        Model $record
    ): bool {
        if (static::isSuperAdmin()) {
            return true;
        }

        return $record instanceof WorkTask
            && static::canManageTask($record)
            && parent::canDelete($record);
    }

    /**
     * Bulk brisanje dostupno je samo
     * organizacijskim korisnicima.
     */
    public static function canDeleteAny(): bool
    {
        if (static::isSuperAdmin()) {
            return true;
        }

        return parent::canDeleteAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Hidden::make('user_id')
                    ->default(
                        fn () =>
                            Auth::user()?->ownerId()
                    )
                    ->dehydrated(),

                Section::make('Radni zadatak')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Naziv zadatka')
                            ->required()
                            ->maxLength(120),

                        DatePicker::make('due_date')
                            ->label('Datum')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y.'),

                        Textarea::make('description')
                            ->label('Opis')
                            ->rows(5)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Toggle::make(
                            'is_shared_with_organization'
                        )
                            ->label(
                                'Vidljivo cijeloj organizaciji'
                            )
                            ->helperText(
                                'Uključi ako ovaj radni zadatak trebaju vidjeti svi korisnici organizacije. '
                                . 'Ako je isključeno, zadatak vidi samo korisnik koji ga je kreirao.'
                            )
                            ->default(false)
                            ->inline(false)
                            ->columnSpanFull()
                            ->disabled(
                                function (
                                    ?WorkTask $record
                                ): bool {
                                    /*
                                    * Kod kreiranja toggle je aktivan.
                                    */
                                    if (! $record) {
                                        return false;
                                    }

                                    /*
                                    * Kod postojećeg zadatka
                                    * vidljivost može mijenjati
                                    * samo njegov autor.
                                    *
                                    * Legacy zadaci nemaju autora
                                    * i ostaju organizacijski.
                                    */
                                    return
                                        (int) (
                                            $record
                                                ->created_by_user_id
                                            ?? 0
                                        )
                                        !==
                                        (int) Auth::id();
                                }
                            )
                            ->dehydrated(true),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([
                10,
                25,
                50,
                'all',
            ])

            ->modifyQueryUsing(
                fn (Builder $query) =>
                    $query
                        ->latest('due_date')
                        ->latest('id')
            )

           ->columns([
                /*
                |--------------------------------------------------------------------------
                | MOBILNI PRIKAZ
                |--------------------------------------------------------------------------
                |
                | Na mobitelu prikazujemo jedan široki "card" stupac,
                | slično Operativnom dnevniku.
                |
                | Desktop stupci ispod sakriveni su na malim ekranima.
                |
                */

                TextColumn::make('mobile_card')
                    ->label('Radni zadatak')
                    ->hiddenFrom('md')
                    ->html()
                    ->grow()
                    ->state(
                        function (
                            WorkTask $record
                        ): HtmlString {
                            $title = e(
                                trim(
                                    (string) $record->title
                                )
                            );

                            $description = trim(
                                (string) $record->description
                            );

                            $date =
                                $record->due_date
                                    ?->format('d.m.Y.')
                                ?? '—';

                            /*
                            |--------------------------------------------------------------------------
                            | OPIS
                            |--------------------------------------------------------------------------
                            |
                            | Ako je opis jednak nazivu zadatka,
                            | nema potrebe isti tekst prikazivati dvaput.
                            |
                            */

                            $descriptionHtml = '';

                            if (
                                $description !== ''
                                && mb_strtolower($description)
                                    !== mb_strtolower(
                                        trim(
                                            (string) $record->title
                                        )
                                    )
                            ) {
                                $descriptionHtml = '
                                    <div style="
                                        margin-top:7px;
                                        font-size:13px;
                                        line-height:1.45;
                                        opacity:.78;
                                        white-space:normal;
                                        overflow-wrap:anywhere;
                                    ">
                                        '
                                        . nl2br(
                                            e($description)
                                        )
                                        . '
                                    </div>
                                ';
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | STATUS
                            |--------------------------------------------------------------------------
                            */

                            if ($record->is_done) {
                                $statusHtml = '
                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        min-height:28px;
                                        padding:0 9px;
                                        border-radius:8px;
                                        border:1px solid rgba(34,197,94,.28);
                                        background:rgba(34,197,94,.12);
                                        color:#22c55e;
                                        font-size:12px;
                                        font-weight:800;
                                        white-space:nowrap;
                                    ">
                                        ✓ Završeno
                                    </span>
                                ';
                            } elseif (
                                $record->due_date?->isPast()
                            ) {
                                $statusHtml = '
                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        min-height:28px;
                                        padding:0 9px;
                                        border-radius:8px;
                                        border:1px solid rgba(239,68,68,.30);
                                        background:rgba(239,68,68,.12);
                                        color:#ef4444;
                                        font-size:12px;
                                        font-weight:800;
                                        white-space:nowrap;
                                    ">
                                        Otvoreno
                                    </span>
                                ';
                            } elseif (
                                $record->due_date?->isToday()
                            ) {
                                $statusHtml = '
                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        min-height:28px;
                                        padding:0 9px;
                                        border-radius:8px;
                                        border:1px solid rgba(245,158,11,.30);
                                        background:rgba(245,158,11,.12);
                                        color:#f59e0b;
                                        font-size:12px;
                                        font-weight:800;
                                        white-space:nowrap;
                                    ">
                                        Danas
                                    </span>
                                ';
                            } else {
                                $statusHtml = '
                                    <span style="
                                        display:inline-flex;
                                        align-items:center;
                                        min-height:28px;
                                        padding:0 9px;
                                        border-radius:8px;
                                        border:1px solid rgba(59,130,246,.28);
                                        background:rgba(59,130,246,.10);
                                        color:#3b82f6;
                                        font-size:12px;
                                        font-weight:800;
                                        white-space:nowrap;
                                    ">
                                        Otvoreno
                                    </span>
                                ';
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | VIDLJIVOST
                            |--------------------------------------------------------------------------
                            */

                            $visibilityHtml =
                                $record->is_shared_with_organization
                                    ? '
                                        <span style="
                                            display:inline-flex;
                                            align-items:center;
                                            min-height:28px;
                                            padding:0 9px;
                                            border-radius:8px;
                                            border:1px solid rgba(59,130,246,.22);
                                            background:rgba(59,130,246,.08);
                                            color:#3b82f6;
                                            font-size:12px;
                                            font-weight:800;
                                            white-space:nowrap;
                                        ">
                                            👥 Organizacija
                                        </span>
                                    '
                                    : '
                                        <span style="
                                            display:inline-flex;
                                            align-items:center;
                                            min-height:28px;
                                            padding:0 9px;
                                            border-radius:8px;
                                            border:1px solid rgba(148,163,184,.20);
                                            background:rgba(148,163,184,.08);
                                            color:#94a3b8;
                                            font-size:12px;
                                            font-weight:800;
                                            white-space:nowrap;
                                        ">
                                            👤 Privatno
                                        </span>
                                    ';

                            /*
                            |--------------------------------------------------------------------------
                            | MOBILNI PRIKAZ
                            |--------------------------------------------------------------------------
                            |
                            | Stil namjerno prati Operativni dnevnik:
                            |
                            | - bez fiksne bijele/crne boje teksta
                            | - tekst nasljeđuje Filament light/dark boju
                            | - narančasta kartica označava radni zadatak
                            | - datum i status su kompaktni badgevi
                            |
                            */

                            return new HtmlString(
                                '
                                <div style="
                                    width:100%;
                                    min-width:0;
                                    padding:3px 0 6px;
                                    box-sizing:border-box;
                                ">

                                    <div style="
                                        display:flex;
                                        align-items:center;
                                        justify-content:space-between;
                                        gap:10px;
                                        width:100%;
                                        margin-bottom:8px;
                                    ">

                                        <div style="
                                            font-size:16px;
                                            line-height:1.2;
                                            font-weight:800;
                                            white-space:nowrap;
                                        ">
                                            '
                                            . e($date)
                                            . '
                                        </div>

                                        <div style="
                                            display:flex;
                                            align-items:center;
                                            gap:6px;
                                            flex-wrap:wrap;
                                            justify-content:flex-end;
                                        ">
                                            '
                                            . $statusHtml
                                            . '
                                        </div>

                                    </div>

                                    <div style="
                                        width:100%;
                                        min-width:0;
                                        padding:11px 12px;
                                        box-sizing:border-box;

                                        border-radius:12px;

                                        background:rgba(
                                            245,
                                            158,
                                            11,
                                            .10
                                        );

                                        border:1px solid rgba(
                                            245,
                                            158,
                                            11,
                                            .28
                                        );
                                    ">

                                        <div style="
                                            margin-bottom:5px;
                                            color:#d97706;
                                            font-size:11px;
                                            line-height:1.2;
                                            font-weight:800;
                                        ">
                                            ✓ RADNI ZADATAK
                                        </div>

                                        <div style="
                                            font-size:15px;
                                            line-height:1.5;
                                            font-weight:650;
                                            white-space:normal;
                                            overflow-wrap:anywhere;
                                        ">
                                            '
                                            . $title
                                            . '
                                        </div>

                                        '
                                        . $descriptionHtml
                                        . '

                                        <div style="
                                            margin-top:10px;
                                            display:flex;
                                            align-items:center;
                                            flex-wrap:wrap;
                                            gap:6px;
                                        ">
                                            '
                                            . $visibilityHtml
                                            . '
                                        </div>

                                    </div>

                                </div>
                                '
                            );
                        }
                    ),
                /*
                |--------------------------------------------------------------------------
                | DESKTOP / TABLET
                |--------------------------------------------------------------------------
                |
                | Od md širine naviše ostaje tvoj postojeći
                | tablični prikaz.
                |
                */

                TextColumn::make('title')
                    ->label('Zadatak')
                    ->searchable()
                    ->wrap()
                    ->weight('bold')
                    ->toggleable()
                    ->visibleFrom('md'),

                static::userTableColumn()
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('description')
                    ->label('Opis')
                    ->limit(80)
                    ->wrap()
                    ->toggleable()
                    ->visibleFrom('md'),

                TextColumn::make('due_date')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(
                        function (
                            WorkTask $record
                        ): string {
                            if ($record->is_done) {
                                return 'success';
                            }

                            if (
                                $record->due_date?->isPast()
                            ) {
                                return 'danger';
                            }

                            if (
                                $record->due_date?->isToday()
                            ) {
                                return 'warning';
                            }

                            return 'info';
                        }
                    )
                    ->toggleable()
                    ->visibleFrom('md'),

                IconColumn::make('is_done')
                    ->label('Riješeno')
                    ->boolean()
                    ->toggleable()
                    ->visibleFrom('md'),

                    IconColumn::make(
                        'is_shared_with_organization'
                    )
                        ->label('Organizacija')
                        ->boolean()
                        ->tooltip(
                            fn (WorkTask $record): string =>
                                $record
                                    ->is_shared_with_organization
                                        ? 'Vidljivo cijeloj organizaciji'
                                        : 'Privatni radni zadatak'
                        )
                        ->toggleable(
                            isToggledHiddenByDefault: true
                        )
                        ->visibleFrom('md'),

                TextColumn::make('completed_at')
                    ->label('Zatvoreno')
                    ->dateTime('d.m.Y. H:i')
                    ->placeholder('-')
                    ->toggleable(
                        isToggledHiddenByDefault: true
                    )
                    ->visibleFrom('md'),
            ])

            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Otvoreni',
                        'closed' => 'Zatvoreni',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            $value =
                                $data['value']
                                ?? null;

                            return match ($value) {
                                'closed' =>
                                    $query->where(
                                        'is_done',
                                        true
                                    ),

                                'open' =>
                                    $query->where(
                                        'is_done',
                                        false
                                    ),

                                default => $query,
                            };
                        }
                    ),
            ])

            ->recordActions([
                Action::make('close')
                    ->label('Zatvori')
                    ->icon(
                        'heroicon-o-check-circle'
                    )
                    ->color('success')
                    ->visible(
                        fn (
                            WorkTask $record
                        ): bool =>
                            ! $record->is_done
                            && static::canManageTask(
                                $record
                            )
                    )
                    ->requiresConfirmation()
                    ->action(
                        function (
                            WorkTask $record
                        ): void {
                            /*
                             * Serverska provjera.
                             *
                             * Nije dovoljno samo sakriti
                             * gumb u sučelju.
                             */
                            abort_unless(
                                static::canManageTask(
                                    $record
                                ),
                                403
                            );

                            $record->update([
                                'is_done' => true,
                                'completed_at' => now(),
                            ]);

                            ActivityLogger::status(
                                module:
                                    'Radni zadaci',

                                title:
                                    'Radni zadatak zatvoren',

                                description:
                                    'Zatvoren je radni zadatak: '
                                    . $record->title,

                                record: $record,
                            );

                            Notification::make()
                                ->title(
                                    'Radni zadatak je zatvoren.'
                                )
                                ->success()
                                ->send();
                        }
                    ),

                Action::make('reopen')
                    ->label(
                        'Vrati u otvorene'
                    )
                    ->icon(
                        'heroicon-o-arrow-path'
                    )
                    ->color('warning')
                    ->visible(
                        fn (
                            WorkTask $record
                        ): bool =>
                            (bool) $record->is_done
                            && static::canManageTask(
                                $record
                            )
                    )
                    ->requiresConfirmation()
                    ->action(
                        function (
                            WorkTask $record
                        ): void {
                            abort_unless(
                                static::canManageTask(
                                    $record
                                ),
                                403
                            );

                            $record->update([
                                'is_done' => false,
                                'completed_at' => null,
                            ]);

                            ActivityLogger::status(
                                module:
                                    'Radni zadaci',

                                title:
                                    'Radni zadatak vraćen u otvorene',

                                description:
                                    'Radni zadatak je vraćen u otvorene: '
                                    . $record->title,

                                record: $record,
                            );

                            Notification::make()
                                ->title(
                                    'Radni zadatak je vraćen u otvorene.'
                                )
                                ->success()
                                ->send();
                        }
                    ),

                EditAction::make()
                    ->label('Uredi')
                    ->visible(
                        fn (
                            WorkTask $record
                        ): bool =>
                            static::canManageTask(
                                $record
                            )
                    ),

                DeleteAction::make()
                    ->label('Obriši')
                    ->visible(
                        fn (
                            WorkTask $record
                        ): bool =>
                            static::canManageTask(
                                $record
                            )
                    )
                    ->requiresConfirmation(),
            ])

            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->visible(
                        fn (): bool =>
                            static::canDeleteAny()
                    )
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('bulk_close')
                    ->label('Zatvori označeno')
                    ->icon(
                        'heroicon-o-check-circle'
                    )
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(
                        function (
                            EloquentCollection $records
                        ): bool {
                            return $records->contains(
                                fn (
                                    WorkTask $record
                                ): bool =>
                                    ! $record->is_done
                                    && static::canManageTask(
                                        $record
                                    )
                            );
                        }
                    )
                    ->action(
                        function (
                            EloquentCollection $records
                        ): void {
                            /*
                             * Ako bi netko pokušao
                             * ručno pozvati bulk akciju,
                             * obrađujemo samo zapise koje
                             * trenutna organizacija smije
                             * mijenjati.
                             */
                            $allowedRecords =
                                $records->filter(
                                    fn (
                                        WorkTask $record
                                    ): bool =>
                                        static::canManageTask(
                                            $record
                                        )
                                );

                            $count = 0;

                            $allowedRecords->each(
                                function (
                                    WorkTask $record
                                ) use (
                                    &$count
                                ): void {
                                    if (
                                        ! $record->is_done
                                    ) {
                                        $record->update([
                                            'is_done' =>
                                                true,

                                            'completed_at' =>
                                                now(),
                                        ]);

                                        $count++;
                                    }
                                }
                            );

                            if ($count > 0) {
                                ActivityLogger::status(
                                    module:
                                        'Radni zadaci',

                                    title:
                                        'Zatvoreni označeni radni zadaci',

                                    description:
                                        "Zatvoreno zadataka: {$count}.",
                                );
                            }

                            Notification::make()
                                ->title(
                                    "Zatvoreno zadataka: {$count}"
                                )
                                ->success()
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('bulk_reopen')
                    ->label(
                        'Vrati označeno u otvorene'
                    )
                    ->icon(
                        'heroicon-o-arrow-path'
                    )
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(
                        function (
                            EloquentCollection $records
                        ): bool {

                            return $records->contains(
                                fn (
                                    WorkTask $record
                                ): bool =>
                                    (bool) $record->is_done
                                    && static::canManageTask(
                                        $record
                                    )
                            );
                        }
                    )
                    ->action(
                        function (
                            EloquentCollection $records
                        ): void {

                            $allowedRecords =
                                $records->filter(
                                    fn (
                                        WorkTask $record
                                    ): bool =>
                                        static::canManageTask(
                                            $record
                                        )
                                );

                            $count = 0;

                            $allowedRecords->each(
                                function (
                                    WorkTask $record
                                ) use (
                                    &$count
                                ): void {
                                    if (
                                        $record->is_done
                                    ) {
                                        $record->update([
                                            'is_done' =>
                                                false,

                                            'completed_at' =>
                                                null,
                                        ]);

                                        $count++;
                                    }
                                }
                            );

                            if ($count > 0) {
                                ActivityLogger::status(
                                    module:
                                        'Radni zadaci',

                                    title:
                                        'Radni zadaci vraćeni u otvorene',

                                    description:
                                        "Vraćeno u otvorene: {$count}.",
                                );
                            }

                            Notification::make()
                                ->title(
                                    "Vraćeno u otvorene: {$count}"
                                )
                                ->success()
                                ->send();
                        }
                    )
                    ->deselectRecordsAfterCompletion(),
            ])

            ->defaultSort(
                'due_date',
                'asc'
            );
    }

    /**
     * BaseResource daje:
     *
     * superadmin -> svi zapisi
     * organizacija -> samo user_id = ownerId()
     *
     * Time je multi-tenant izolacija riješena
     * i za listu i za route model binding.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user');
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $cacheKey =
            'work_tasks_badge_'
            . $user->id
            . '_'
            . now()->format('Y-m-d-H');

        return cache()->remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($user) {
                $query =
                    static::getModel()::query();

                if (! $user->isSuperAdmin()) {
                    $query->where(
                        'user_id',
                        $user->ownerId()
                    );
                }

                return (string) $query->count();
            }
        );
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                ListWorkTasks::route('/'),

            'create' =>
                CreateWorkTask::route('/create'),

            'edit' =>
                EditWorkTask::route(
                    '/{record}/edit'
                ),
        ];
    }
}
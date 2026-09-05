<?php

namespace App\Filament\Resources\PPELogs\RelationManagers;

use App\Models\PPEEquipment;
use App\Support\ExpiryBadge;
use App\Support\SecureFilePreview;
use App\Support\SignatureStorage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class ItemsRelationManager extends RelationManager
{
    protected static string $relationship =
        'items';


    protected static ?string $title =
        'Popis osobne zaštitne opreme';


    /**
     * RelationManager je read-only samo kada
     * trenutni korisnik nema pravo upravljanja
     * stavkama konkretnog Upisnika OZO.
     */
    public function isReadOnly(): bool
    {
        return ! $this->canManageItems();
    }


    /**
     * Stavke Upisnika OZO:
     *
     * SUPERADMIN:
     * - može administrirati stavke postojećeg
     *   organizacijskog Upisnika OZO
     *
     * ORGANIZACIJSKI KORISNIK:
     * - može administrirati samo stavke Upisnika
     *   svoje organizacije.
     *
     * Ownership samog Upisnika pritom se ne mijenja.
     */
    protected function canManageItems(): bool
    {
        $user = Auth::user();


        if (! $user) {
            return false;
        }


        $recordOwnerId =
            (int) $this->getOwnerRecord()->user_id;


        if ($recordOwnerId <= 0) {
            return false;
        }


        /*
         * Superadmin administrira postojeći
         * organizacijski Upisnik OZO.
         */
        if ($user->isSuperAdmin()) {
            return true;
        }


        $ownerId =
            (int) $user->ownerId();


        if ($ownerId <= 0) {
            return false;
        }


        return $recordOwnerId === $ownerId;
    }


    /**
     * Registar OZO dostupan prilikom rada
     * s konkretnim Upisnikom OZO.
     *
     * Organizacijski korisnik vidi:
     * - globalne OZO zapise
     * - OZO zapise svoje organizacije
     *
     * Superadmin kod uređivanja postojećeg
     * Upisnika vidi:
     * - globalne OZO zapise
     * - OZO zapise organizacije kojoj pripada
     *   taj konkretni Upisnik
     *
     * Time ne može slučajno koristiti privatni
     * OZO zapis neke druge organizacije.
     */
    protected function equipmentQuery(): Builder
    {
        $query = PPEEquipment::query()
            ->where(
                'is_active',
                true
            );


        $user = Auth::user();


        if (! $user) {
            return $query->whereRaw(
                '1 = 0'
            );
        }


        /*
         * Kod superadmina vlasnika određuje
         * postojeći Upisnik OZO.
         */
        if ($user->isSuperAdmin()) {
            $ownerId =
                (int) $this->getOwnerRecord()->user_id;
        } else {
            $ownerId =
                (int) $user->ownerId();
        }


        if ($ownerId <= 0) {
            return $query->whereRaw(
                '1 = 0'
            );
        }


        return $query->where(
            function (
                Builder $query
            ) use ($ownerId): void {
                $query
                    ->whereNull(
                        'user_id'
                    )
                    ->orWhere(
                        'user_id',
                        $ownerId
                    );
            }
        );
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make(
                    'equipment_lookup'
                )
                    ->label(
                        'Odaberi OZO iz registra'
                    )
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->placeholder(
                        'Odaberi OZO ili ručno upiši u polje ispod'
                    )
                    ->getSearchResultsUsing(
                        function (
                            string $search
                        ): array {
                            return $this
                                ->equipmentQuery()
                                ->where(
                                    function (
                                        Builder $query
                                    ) use (
                                        $search
                                    ): void {
                                        $query
                                            ->where(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'standard',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                )
                                ->orderBy(
                                    'name'
                                )
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(
                                    fn (
                                        PPEEquipment $equipment
                                    ) => [
                                        $equipment->id =>
                                            trim(
                                                $equipment->name
                                                . (
                                                    $equipment->standard
                                                        ? ' — '
                                                            . $equipment->standard
                                                        : ''
                                                )
                                            ),
                                    ]
                                )
                                ->toArray();
                        }
                    )
                    ->getOptionLabelUsing(
                        function (
                            $value
                        ): ?string {
                            if (! $value) {
                                return null;
                            }


                            $equipment =
                                $this
                                    ->equipmentQuery()
                                    ->whereKey(
                                        $value
                                    )
                                    ->first();


                            return $equipment
                                ? trim(
                                    $equipment->name
                                    . (
                                        $equipment->standard
                                            ? ' — '
                                                . $equipment->standard
                                            : ''
                                    )
                                )
                                : null;
                        }
                    )
                    ->afterStateUpdated(
                        function (
                            $state,
                            callable $set,
                            callable $get
                        ): void {
                            if (! $state) {
                                return;
                            }


                            $equipment =
                                $this
                                    ->equipmentQuery()
                                    ->whereKey(
                                        $state
                                    )
                                    ->first();


                            if (! $equipment) {
                                return;
                            }


                            $set(
                                'equipment_name',
                                $equipment->name
                            );


                            $set(
                                'standard',
                                $equipment->standard
                            );


                            $set(
                                'duration_months',
                                $equipment
                                    ->duration_months
                            );


                            static::recalcEndDate(
                                $set,
                                $get
                            );
                        }
                    ),


                TextInput::make(
                    'equipment_name'
                )
                    ->label('Naziv OZO')
                    ->required()
                    ->maxLength(255)
                    ->helperText(
                        'Možeš promijeniti naziv ili ručno upisati OZO ako nije u registru.'
                    ),


                TextInput::make('standard')
                    ->label('HRN EN')
                    ->maxLength(64)
                    ->helperText(
                        'Automatski se povlači iz registra, ali ga možeš ručno promijeniti.'
                    ),


                TextInput::make('size')
                    ->label('Veličina')
                    ->maxLength(20),


                TextInput::make(
                    'duration_months'
                )
                    ->label(
                        'Rok uporabe (mjeseci)'
                    )
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(120)
                    ->live()
                    ->helperText(
                        'Automatski se povlači iz registra, ali ga možeš ručno promijeniti.'
                    )
                    ->afterStateUpdated(
                        fn (
                            $state,
                            $set,
                            $get
                        ) =>
                            static::recalcEndDate(
                                $set,
                                $get
                            )
                    ),


                DatePicker::make(
                    'issue_date'
                )
                    ->label(
                        'Datum izdavanja'
                    )
                    ->required()
                    ->native(false)
                    ->displayFormat(
                        'd.m.Y.'
                    )
                    ->format('Y-m-d')
                    ->closeOnDateSelection()
                    ->live()
                    ->afterStateUpdated(
                        fn (
                            $state,
                            $set,
                            $get
                        ) =>
                            static::recalcEndDate(
                                $set,
                                $get
                            )
                    ),


                DatePicker::make(
                    'end_date'
                )
                    ->label('Datum isteka')
                    ->native(false)
                    ->displayFormat(
                        'd.m.Y.'
                    )
                    ->format('Y-m-d')
                    ->readOnly()
                    ->helperText(
                        'Automatski izračun iz “Izdano” + “Rok (mjeseci)”.'
                    ),


                ViewField::make('signature')
                    ->label(
                        'Potpis – preuzeo OZO'
                    )
                    ->view(
                        'filament.components.ozo-signature'
                    )
                    ->columnSpanFull(),


                DatePicker::make(
                    'return_date'
                )
                    ->label(
                        'Datum vraćanja'
                    )
                    ->native(false)
                    ->displayFormat(
                        'd.m.Y.'
                    )
                    ->format('Y-m-d')
                    ->closeOnDateSelection()
                    ->live()
                    ->afterStateUpdated(
                        fn (
                            $state,
                            $set,
                            $get
                        ) =>
                            static::recalcEndDate(
                                $set,
                                $get
                            )
                    ),
            ])
            ->columns(4);
    }


    protected static function recalcEndDate(
        callable $set,
        callable $get
    ): void {
        $returnDate =
            $get('return_date');


        if (! blank($returnDate)) {
            $set(
                'end_date',
                null
            );


            return;
        }


        $issue =
            $get('issue_date');


        $months =
            (int) (
                $get(
                    'duration_months'
                ) ?? 0
            );


        if (
            blank($issue)
            || $months <= 0
        ) {
            $set(
                'end_date',
                null
            );


            return;
        }


        try {
            $set(
                'end_date',
                Carbon::parse($issue)
                    ->addMonths($months)
                    ->format('Y-m-d')
            );
        } catch (\Throwable) {
            $set(
                'end_date',
                null
            );
        }
    }


    protected static function prepareFormData(
        array $data
    ): array {
        unset(
            $data['equipment_lookup']
        );


        if (
            ! empty(
                $data['signature']
            )
            && str_starts_with(
                $data['signature'],
                'data:image'
            )
        ) {
            $data['signature'] =
                SignatureStorage::storeDataUrl(
                    $data['signature']
                );
        }


        if (
            ! empty(
                $data['return_date']
            )
        ) {
            $data['end_date'] = null;


            return $data;
        }


        $issue =
            $data['issue_date']
            ?? null;


        $months =
            (int) (
                $data[
                    'duration_months'
                ] ?? 0
            );


        if (
            ! empty($issue)
            && $months > 0
        ) {
            try {
                $data['end_date'] =
                    Carbon::parse($issue)
                        ->addMonths(
                            $months
                        )
                        ->format(
                            'Y-m-d'
                        );
            } catch (\Throwable) {
                $data['end_date'] =
                    null;
            }
        } else {
            $data['end_date'] =
                null;
        }


        return $data;
    }


    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (
                    Builder $query
                ) =>
                    $query
                        ->orderByRaw(
                            'return_date IS NOT NULL'
                        )
                        ->orderBy(
                            'end_date'
                        )
            )
            ->emptyStateIcon(
                'heroicon-o-shield-check'
            )
            ->emptyStateHeading(
                'Nema osobne zaštitne opreme'
            )
            ->emptyStateDescription(
                'Stvori OZO kako bi započeo'
            )
            ->columns([
                TextColumn::make(
                    'equipment_name'
                )
                    ->label('Naziv OZO')
                    ->searchable()
                    ->weight(
                        'semibold'
                    ),


                TextColumn::make(
                    'standard'
                )
                    ->label('HRN EN')
                    ->toggleable()
                    ->wrap(),


                TextColumn::make('size')
                    ->label('Veličina')
                    ->alignCenter(),


                TextColumn::make(
                    'duration_months'
                )
                    ->label(
                        'Rok (mjeseci)'
                    )
                    ->alignCenter(),


                TextColumn::make(
                    'issue_date'
                )
                    ->label('Izdano')
                    ->date('d.m.Y.')
                    ->alignCenter(),


                TextColumn::make(
                    'end_date'
                )
                    ->label('Istek')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state) =>
                            blank($state)
                                ? '—'
                                : Carbon::parse(
                                    $state
                                )->format(
                                    'd.m.Y.'
                                )
                    )
                    ->color(
                        fn ($state) =>
                            blank($state)
                                ? 'gray'
                                : ExpiryBadge::color(
                                    $state,
                                    30
                                )
                    )
                    ->icon(
                        fn ($state) =>
                            blank($state)
                                ? null
                                : ExpiryBadge::icon(
                                    $state,
                                    30
                                )
                    )
                    ->tooltip(
                        fn ($state) =>
                            blank($state)
                                ? null
                                : ExpiryBadge::tooltip(
                                    $state,
                                    30
                                )
                    )
                    ->sortable(),


                TextColumn::make(
                    'return_date'
                )
                    ->label(
                        'Datum vraćanja'
                    )
                    ->date('d.m.Y.')
                    ->alignCenter()
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),


                TextColumn::make(
                    'signature'
                )
                    ->label('Potpis')
                    ->html()
                    ->state(
                        function (
                            $record
                        ): string {
                            if (
                                blank(
                                    $record
                                        ->signature
                                )
                            ) {
                                return '<span style="color:#6b7280;">—</span>';
                            }


                            $url =
                                str_starts_with(
                                    $record->signature,
                                    'data:image'
                                )
                                    ? $record->signature
                                    : SecureFilePreview::url(
                                        $record->signature
                                    );


                            return
                                '<img src="'
                                . e($url)
                                . '" style="
                                    width:120px;
                                    height:45px;
                                    object-fit:contain;
                                    background:white;
                                    border:1px solid #d1d5db;
                                    border-radius:6px;
                                    padding:2px;
                                " />';
                        }
                    )
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('isteklo')
                    ->label('Isteklo')
                    ->query(
                        fn (
                            Builder $query
                        ) =>
                            $query
                                ->whereNull(
                                    'return_date'
                                )
                                ->whereNotNull(
                                    'end_date'
                                )
                                ->whereDate(
                                    'end_date',
                                    '<',
                                    today()
                                )
                    ),


                Filter::make('uskoro')
                    ->label(
                        'Uskoro ističe (≤30d)'
                    )
                    ->query(
                        fn (
                            Builder $query
                        ) =>
                            $query
                                ->whereNull(
                                    'return_date'
                                )
                                ->whereNotNull(
                                    'end_date'
                                )
                                ->whereBetween(
                                    'end_date',
                                    [
                                        today(),

                                        today()
                                            ->copy()
                                            ->addDays(
                                                30
                                            ),
                                    ]
                                )
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj OZO')
                    ->modalHeading('Napravi OZO')
                    ->modalSubmitActionLabel('Spremi OZO')
                    ->visible(
                        fn (): bool =>
                            $this
                                ->canManageItems()
                    )
                    ->mutateFormDataUsing(
                        fn (
                            array $data
                        ) =>
                            static::prepareFormData(
                                $data
                            )
                    ),
            ])
            ->actions([
                EditAction::make()
                    ->label('Uredi')
                    ->modalHeading('Uredi OZO')
                    ->modalSubmitActionLabel('Spremi promjene')
                    ->visible(
                        fn (): bool =>
                            $this
                                ->canManageItems()
                    )
                    ->mutateFormDataUsing(
                        fn (
                            array $data
                        ) =>
                            static::prepareFormData(
                                $data
                            )
                    ),

                Action::make('extend3')
                    ->label(
                        'Produži +3 mj'
                    )
                    ->visible(
                        fn (
                            $record
                        ): bool =>
                            $this
                                ->canManageItems()
                            && blank(
                                $record
                                    ->return_date
                            )
                    )
                    ->requiresConfirmation()
                    ->action(
                        function (
                            $record
                        ): void {
                            if (
                                ! $this
                                    ->canManageItems()
                            ) {
                                abort(403);
                            }


                            $record
                                ->duration_months =
                                max(
                                    0,
                                    (int) $record
                                        ->duration_months
                                ) + 3;


                            if (
                                $record
                                    ->issue_date
                                && $record
                                    ->duration_months
                                    > 0
                            ) {
                                $record
                                    ->end_date =
                                    Carbon::parse(
                                        $record
                                            ->issue_date
                                    )->addMonths(
                                        $record
                                            ->duration_months
                                    );
                            } else {
                                $record
                                    ->end_date =
                                    null;
                            }


                            $record->save();
                        }
                    ),


                Action::make(
                    'returnedToday'
                )
                    ->label(
                        'Označi vraćeno'
                    )
                    ->color('success')
                    ->icon(
                        'heroicon-o-check-circle'
                    )
                    ->visible(
                        fn (): bool =>
                            $this
                                ->canManageItems()
                    )
                    ->modalHeading(
                        'Označi OZO kao vraćen'
                    )
                    ->modalSubmitActionLabel(
                        'Spremi datum vraćanja'
                    )
                    ->schema([
                        DatePicker::make(
                            'return_date'
                        )
                            ->label(
                                'Datum vraćanja'
                            )
                            ->default(
                                today()
                            )
                            ->required()
                            ->native(false)
                            ->displayFormat(
                                'd.m.Y.'
                            )
                            ->format(
                                'Y-m-d'
                            )
                            ->closeOnDateSelection(),
                    ])
                    ->action(
                        function (
                            $record,
                            array $data
                        ): void {
                            if (
                                ! $this
                                    ->canManageItems()
                            ) {
                                abort(403);
                            }


                            $record->update([
                                'return_date' =>
                                    $data[
                                        'return_date'
                                    ],

                                'end_date' =>
                                    null,
                            ]);
                        }
                    ),


                DeleteAction::make()
                    ->label('Deaktiviraj')
                    ->visible(
                        fn (): bool =>
                            $this
                                ->canManageItems()
                    ),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->visible(
                        fn (): bool =>
                            $this
                                ->canManageItems()
                    ),
            ]);
    }
}
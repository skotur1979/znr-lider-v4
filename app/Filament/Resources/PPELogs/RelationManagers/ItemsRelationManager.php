<?php

namespace App\Filament\Resources\PPELogs\RelationManagers;

use App\Filament\Resources\PPELogs\PPELogResource;
use App\Models\PPEEquipment;
use App\Support\ExpiryBadge;
use App\Support\SecureFilePreview;
use App\Support\SignatureStorage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title =
        'Popis osobne zaštitne opreme';

    public function isReadOnly(): bool
    {
        return ! (
            $this->canCreateItems()
            || $this->canUpdateItems()
            || $this->canDeleteItems()
        );
    }

    protected function canAccessOwnerRecord(): bool
    {
        $record =
            $this->getOwnerRecord();

        if (! $record) {
            return false;
        }

        if (
            method_exists(
                $record,
                'trashed'
            )
            && $record->trashed()
        ) {
            return false;
        }

        return PPELogResource::canView(
            $record
        );
    }

    protected function hasItemPermission(
        string $permission
    ): bool {
        $user =
            Auth::user();

        if (
            ! $user
            || ! $this
                ->canAccessOwnerRecord()
        ) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $ownerId =
            (int)
            $user->ownerId();

        $recordOwnerId =
            (int)
            $this
                ->getOwnerRecord()
                ->user_id;

        if (
            $ownerId <= 0
            || $recordOwnerId <= 0
            || $ownerId !== $recordOwnerId
        ) {
            return false;
        }

        return PPELogResource::allowsModulePermission(
            $permission
        );
    }

    protected function canCreateItems(): bool
    {
        return $this
            ->hasItemPermission(
                'create'
            );
    }

    protected function canUpdateItems(): bool
    {
        return $this
            ->hasItemPermission(
                'update'
            );
    }

    protected function canDeleteItems(): bool
    {
        return $this
            ->hasItemPermission(
                'delete'
            );
    }

    protected function equipmentOwnerQuery(): Builder
    {
        $query =
            PPEEquipment::query();

        $user =
            Auth::user();

        if (! $user) {
            return $query
                ->whereRaw(
                    '1 = 0'
                );
        }

        $ownerId =
            $user->isSuperAdmin()
                ? (int)
                    $this
                        ->getOwnerRecord()
                        ->user_id
                : (int)
                    $user
                        ->ownerId();

        if ($ownerId <= 0) {
            return $query
                ->whereRaw(
                    '1 = 0'
                );
        }

        return $query
            ->where(
                'user_id',
                $ownerId
            );
    }

    protected function equipmentQuery(): Builder
    {
        return $this
            ->equipmentOwnerQuery()
            ->where(
                'is_active',
                true
            );
    }

    public function form(
        Schema $schema
    ): Schema {
        return $schema
            ->schema([
                Select::make(
                    'ppe_equipment_id'
                )
                    ->label(
                        'Odaberi OZO iz registra'
                    )
                    ->searchable()
                    ->live()
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
                                    ->equipmentOwnerQuery()
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
                                $equipment
                                    ->standard
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
                    ->label(
                        'Naziv OZO'
                    )
                    ->required()
                    ->maxLength(191)
                    ->helperText(
                        'Možeš promijeniti naziv ili ručno upisati OZO ako nije u registru.'
                    ),

                TextInput::make(
                    'standard'
                )
                    ->label(
                        'HRN EN'
                    )
                    ->maxLength(191)
                    ->helperText(
                        'Automatski se povlači iz registra, ali ga možeš ručno promijeniti.'
                    ),

                TextInput::make(
                    'size'
                )
                    ->label(
                        'Veličina'
                    )
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
                    ->format(
                        'Y-m-d'
                    )
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
                    ->label(
                        'Datum isteka'
                    )
                    ->native(false)
                    ->displayFormat(
                        'd.m.Y.'
                    )
                    ->format(
                        'Y-m-d'
                    )
                    ->readOnly()
                    ->helperText(
                        'Automatski izračun iz “Izdano” + “Rok (mjeseci)”.'
                    ),

                ViewField::make(
                    'signature'
                )
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
                    ->format(
                        'Y-m-d'
                    )
                    ->minDate(
                        fn ($get) =>
                            $get(
                                'issue_date'
                            )
                    )
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
            $get(
                'return_date'
            );

        if (! blank($returnDate)) {
            $set(
                'end_date',
                null
            );

            return;
        }

        $issue =
            $get(
                'issue_date'
            );

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
                Carbon::parse(
                    $issue
                )
                    ->addMonths(
                        $months
                    )
                    ->format(
                        'Y-m-d'
                    )
            );
        } catch (\Throwable) {
            $set(
                'end_date',
                null
            );
        }
    }

    protected function prepareFormData(
        array $data
    ): array {
        $equipmentId =
            filled(
                $data[
                    'ppe_equipment_id'
                ] ?? null
            )
                ? (int)
                    $data[
                        'ppe_equipment_id'
                    ]
                : null;

        if (
            $equipmentId
            && ! $this
                ->equipmentOwnerQuery()
                ->whereKey(
                    $equipmentId
                )
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'ppe_equipment_id' =>
                    'Odabrani OZO nije dostupan ovoj organizaciji.',
            ]);
        }

        $data[
            'ppe_equipment_id'
        ] =
            $equipmentId
                ?: null;

        if (
            ! empty(
                $data[
                    'signature'
                ]
            )
            && is_string(
                $data[
                    'signature'
                ]
            )
            && str_starts_with(
                $data[
                    'signature'
                ],
                'data:image'
            )
        ) {
            $data[
                'signature'
            ] =
                SignatureStorage::storeDataUrl(
                    $data[
                        'signature'
                    ]
                );
        }

        $issue =
            $data[
                'issue_date'
            ] ?? null;

        $returnDate =
            $data[
                'return_date'
            ] ?? null;

        if (
            filled($issue)
            && filled(
                $returnDate
            )
            && Carbon::parse(
                $returnDate
            )->lt(
                Carbon::parse(
                    $issue
                )
            )
        ) {
            throw ValidationException::withMessages([
                'return_date' =>
                    'Datum vraćanja ne može biti prije datuma izdavanja.',
            ]);
        }

        if (! empty($returnDate)) {
            $data[
                'end_date'
            ] = null;

            return $data;
        }

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
                $data[
                    'end_date'
                ] =
                    Carbon::parse(
                        $issue
                    )
                        ->addMonths(
                            $months
                        )
                        ->format(
                            'Y-m-d'
                        );
            } catch (\Throwable) {
                $data[
                    'end_date'
                ] = null;
            }
        } else {
            $data[
                'end_date'
            ] = null;
        }

        return $data;
    }

    public function table(
        Table $table
    ): Table {
        return $table
            ->modifyQueryUsing(
                fn (
                    Builder $query
                ) =>
                    $query
                        ->withTrashed()
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
                    ->label(
                        'Naziv OZO'
                    )
                    ->searchable()
                    ->weight(
                        'semibold'
                    ),

                TextColumn::make(
                    'deleted_at'
                )
                    ->label(
                        'Status'
                    )
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                            blank($state)
                                ? 'Aktivno'
                                : 'Deaktivirano'
                    )
                    ->color(
                        fn ($state): string =>
                            blank($state)
                                ? 'success'
                                : 'danger'
                    ),

                TextColumn::make(
                    'standard'
                )
                    ->label(
                        'HRN EN'
                    )
                    ->toggleable()
                    ->html()
                    ->formatStateUsing(
                        function (
                            $state
                        ): string {
                            if (
                                blank($state)
                            ) {
                                return '—';
                            }

                            $text =
                                preg_replace(
                                    '/\s+/',
                                    ' ',
                                    trim(
                                        (string)
                                        $state
                                    )
                                );

                            return
                                '<div style="
                                    width:180px;
                                    max-width:180px;
                                    white-space:normal;
                                    word-break:break-word;
                                    line-height:1.25em;
                                    max-height:2.5em;
                                    overflow:hidden;
                                    display:-webkit-box;
                                    -webkit-box-orient:vertical;
                                    -webkit-line-clamp:2;
                                ">'
                                . e($text)
                                . '</div>';
                        }
                    )
                    ->tooltip(
                        function (
                            $record
                        ): ?string {
                            if (
                                blank(
                                    $record
                                        ->standard
                                )
                            ) {
                                return null;
                            }

                            return preg_replace(
                                '/\s+/',
                                ' ',
                                trim(
                                    (string)
                                    $record
                                        ->standard
                                )
                            );
                        }
                    ),

                TextColumn::make(
                    'size'
                )
                    ->label(
                        'Veličina'
                    )
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
                    ->label(
                        'Izdano'
                    )
                    ->date(
                        'd.m.Y.'
                    )
                    ->alignCenter(),

                TextColumn::make(
                    'end_date'
                )
                    ->label(
                        'Istek'
                    )
                    ->badge()
                    ->formatStateUsing(
                        fn ($state) =>
                            blank($state)
                                ? '—'
                                : Carbon::parse(
                                    $state
                                )
                                    ->format(
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
                    ->date(
                        'd.m.Y.'
                    )
                    ->alignCenter()
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),

                TextColumn::make(
                    'signature'
                )
                    ->label(
                        'Potpis'
                    )
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
                                return
                                    '<span style="color:#6b7280;">—</span>';
                            }

                            $url =
                                str_starts_with(
                                    $record
                                        ->signature,
                                    'data:image'
                                )
                                    ? $record
                                        ->signature
                                    : SecureFilePreview::url(
                                        $record
                                            ->signature
                                    );

                            if (blank($url)) {
                                return
                                    '<span style="color:#6b7280;">—</span>';
                            }

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
                SelectFilter::make(
                    'status_ozo'
                )
                    ->label(
                        'Status OZO'
                    )
                    ->options([
                        'aktivni' =>
                            'Aktivni',

                        'deaktivirani' =>
                            'Deaktivirani',

                        'svi' =>
                            'Svi',
                    ])
                    ->default(
                        'aktivni'
                    )
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            $status =
                                $data['value']
                                ?? 'aktivni';

                            return match ($status) {
                                'deaktivirani' =>
                                    $query
                                        ->withTrashed()
                                        ->whereNotNull(
                                            'deleted_at'
                                        ),

                                'svi' =>
                                    $query
                                        ->withTrashed(),

                                default =>
                                    $query
                                        ->withTrashed()
                                        ->whereNull(
                                            'deleted_at'
                                        ),
                            };
                        }
                    ),

                Filter::make(
                    'isteklo'
                )
                    ->label(
                        'Isteklo'
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
                                ->whereDate(
                                    'end_date',
                                    '<',
                                    today()
                                )
                    ),

                Filter::make(
                    'uskoro'
                )
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
                    ->label(
                        'Dodaj OZO'
                    )
                    ->modalHeading(
                        'Napravi OZO'
                    )
                    ->modalSubmitActionLabel(
                        'Spremi OZO'
                    )
                    ->visible(
                        fn (): bool =>
                            $this
                                ->canCreateItems()
                    )
                    ->before(
                        function (): void {
                            abort_unless(
                                $this
                                    ->canCreateItems(),
                                403
                            );
                        }
                    )
                    ->mutateFormDataUsing(
                        fn (
                            array $data
                        ) =>
                            $this
                                ->prepareFormData(
                                    $data
                                )
                    ),
            ])
            ->actions([
                EditAction::make()
                    ->label(
                        'Uredi'
                    )
                    ->modalHeading(
                        'Uredi OZO'
                    )
                    ->modalSubmitActionLabel(
                        'Spremi promjene'
                    )
                    ->visible(
                        fn (
                            $record
                        ): bool =>
                            $this
                                ->canUpdateItems()
                            && ! $record
                                ->trashed()
                    )
                    ->before(
                        function (
                            $record
                        ): void {
                            abort_unless(
                                $this
                                    ->canUpdateItems()
                                && ! $record
                                    ->trashed(),
                                403
                            );
                        }
                    )
                    ->mutateFormDataUsing(
                        fn (
                            array $data
                        ) =>
                            $this
                                ->prepareFormData(
                                    $data
                                )
                    ),

                Action::make(
                    'extend3'
                )
                    ->label(
                        'Produži +3 mj'
                    )
                    ->visible(
                        fn (
                            $record
                        ): bool =>
                            $this
                                ->canUpdateItems()
                            && ! $record
                                ->trashed()
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
                                    ->canUpdateItems()
                                || $record
                                    ->trashed()
                            ) {
                                abort(403);
                            }

                            $record
                                ->duration_months =
                                    min(
                                        120,
                                        max(
                                            0,
                                            (int)
                                            $record
                                                ->duration_months
                                        ) + 3
                                    );

                            $record
                                ->end_date =
                                    $record
                                        ->issue_date
                                    && $record
                                        ->duration_months
                                        > 0
                                        ? Carbon::parse(
                                            $record
                                                ->issue_date
                                        )
                                            ->addMonths(
                                                $record
                                                    ->duration_months
                                            )
                                        : null;

                            $record->save();
                        }
                    ),

                Action::make(
                    'returnedToday'
                )
                    ->label(
                        'Označi vraćeno'
                    )
                    ->color(
                        'success'
                    )
                    ->icon(
                        'heroicon-o-check-circle'
                    )
                    ->visible(
                        fn (
                            $record
                        ): bool =>
                            $this
                                ->canUpdateItems()
                            && ! $record
                                ->trashed()
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
                                    ->canUpdateItems()
                                || $record
                                    ->trashed()
                            ) {
                                abort(403);
                            }

                            $returnDate =
                                Carbon::parse(
                                    $data[
                                        'return_date'
                                    ]
                                );

                            if (
                                $record
                                    ->issue_date
                                && $returnDate
                                    ->lt(
                                        Carbon::parse(
                                            $record
                                                ->issue_date
                                        )
                                    )
                            ) {
                                throw ValidationException::withMessages([
                                    'return_date' =>
                                        'Datum vraćanja ne može biti prije datuma izdavanja.',
                                ]);
                            }

                            $record->update([
                                'return_date' =>
                                    $returnDate
                                        ->format(
                                            'Y-m-d'
                                        ),

                                'end_date' =>
                                    null,
                            ]);
                        }
                    ),

                DeleteAction::make()
                    ->label(
                        'Deaktiviraj'
                    )
                    ->icon(
                        'heroicon-o-trash'
                    )
                    ->color(
                        'danger'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Deaktiviraj osobnu zaštitnu opremu'
                    )
                    ->modalDescription(
                        'Jesi li siguran da želiš deaktivirati ovu osobnu zaštitnu opremu?'
                    )
                    ->modalSubmitActionLabel(
                        'Deaktiviraj'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (
                            $record
                        ): bool =>
                            $this
                                ->canDeleteItems()
                            && ! $record
                                ->trashed()
                    )
                    ->before(
                        function (
                            $record
                        ): void {
                            abort_unless(
                                $this
                                    ->canDeleteItems()
                                && ! $record
                                    ->trashed(),
                                403
                            );
                        }
                    ),

                RestoreAction::make()
                    ->label(
                        'Vrati'
                    )
                    ->icon(
                        'heroicon-o-arrow-path'
                    )
                    ->color(
                        'success'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Vrati osobnu zaštitnu opremu'
                    )
                    ->modalDescription(
                        'Deaktivirana osobna zaštitna oprema ponovno će biti aktivna.'
                    )
                    ->modalSubmitActionLabel(
                        'Vrati'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (
                            $record
                        ): bool =>
                            $this
                                ->canDeleteItems()
                            && $record
                                ->trashed()
                    )
                    ->before(
                        function (
                            $record
                        ): void {
                            abort_unless(
                                $this
                                    ->canDeleteItems()
                                && $record
                                    ->trashed(),
                                403
                            );
                        }
                    ),

                ForceDeleteAction::make()
                    ->label(
                        'Trajno izbriši'
                    )
                    ->icon(
                        'heroicon-o-trash'
                    )
                    ->color(
                        'danger'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno izbriši osobnu zaštitnu opremu'
                    )
                    ->modalDescription(
                        'Ova radnja trajno briše zapis i pripadajući potpis te se ne može poništiti.'
                    )
                    ->modalSubmitActionLabel(
                        'Trajno izbriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        fn (
                            $record
                        ): bool =>
                            $this
                                ->canDeleteItems()
                            && $record
                                ->trashed()
                    )
                    ->before(
                        function (
                            $record
                        ): void {
                            abort_unless(
                                $this
                                    ->canDeleteItems()
                                && $record
                                    ->trashed(),
                                403
                            );
                        }
                    ),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label(
                        'Deaktiviraj označeno'
                    )
                    ->icon(
                        'heroicon-o-trash'
                    )
                    ->color(
                        'danger'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Deaktiviraj označenu osobnu zaštitnu opremu'
                    )
                    ->modalDescription(
                        'Jesi li siguran da želiš deaktivirati označenu osobnu zaštitnu opremu?'
                    )
                    ->modalSubmitActionLabel(
                        'Deaktiviraj'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        function (
                            $livewire
                        ): bool {
                            $status =
                                $livewire
                                    ->tableFilters[
                                        'status_ozo'
                                    ][
                                        'value'
                                    ]
                                ?? 'aktivni';

                            return $this
                                ->canDeleteItems()
                                && $status
                                    === 'aktivni';
                        }
                    )
                    ->before(
                        function (): void {
                            abort_unless(
                                $this
                                    ->canDeleteItems(),
                                403
                            );
                        }
                    ),

                RestoreBulkAction::make()
                    ->label(
                        'Vrati označeno'
                    )
                    ->icon(
                        'heroicon-o-arrow-path'
                    )
                    ->color(
                        'success'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Vrati označenu osobnu zaštitnu opremu'
                    )
                    ->modalSubmitActionLabel(
                        'Vrati'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        function (
                            $livewire
                        ): bool {
                            $status =
                                $livewire
                                    ->tableFilters[
                                        'status_ozo'
                                    ][
                                        'value'
                                    ]
                                ?? null;

                            return $this
                                ->canDeleteItems()
                                && $status
                                    === 'deaktivirani';
                        }
                    )
                    ->before(
                        function (): void {
                            abort_unless(
                                $this
                                    ->canDeleteItems(),
                                403
                            );
                        }
                    ),

                ForceDeleteBulkAction::make()
                    ->label(
                        'Trajno izbriši označeno'
                    )
                    ->icon(
                        'heroicon-o-trash'
                    )
                    ->color(
                        'danger'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Trajno izbriši označenu osobnu zaštitnu opremu'
                    )
                    ->modalDescription(
                        'Ova radnja trajno briše označene zapise i njihove potpise te se ne može poništiti.'
                    )
                    ->modalSubmitActionLabel(
                        'Trajno izbriši'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->visible(
                        function (
                            $livewire
                        ): bool {
                            $status =
                                $livewire
                                    ->tableFilters[
                                        'status_ozo'
                                    ][
                                        'value'
                                    ]
                                ?? null;

                            return $this
                                ->canDeleteItems()
                                && $status
                                    === 'deaktivirani';
                        }
                    )
                    ->before(
                        function (): void {
                            abort_unless(
                                $this
                                    ->canDeleteItems(),
                                403
                            );
                        }
                    ),
            ]);
    }
}
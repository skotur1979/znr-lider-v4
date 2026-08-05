<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Filament\Resources\Concerns\HasUserTableColumn;
use App\Models\Employee;
use App\Models\EmployeeAlcoholTest;
use App\Models\EmployeeCertificate;
use App\Support\ExpiryBadge;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EmployeesTable
{
    use HasUserTableColumn;

    public static function configure(Table $table): Table
{
    return $table
        ->modifyQueryUsing(
            fn (Builder $query): Builder => $query
                ->with('latestAlcoholTest')
        )
        ->defaultSort('name', 'asc')

            /*
            |--------------------------------------------------------------------------
            | STUPCI TABLICE
            |--------------------------------------------------------------------------
            */

            ->columns([
                TextColumn::make('name')
                    ->label('Prezime i ime')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Korisnik')
                    ->badge()
                    ->visible(
                        fn (): bool => auth()->user()?->isSuperAdmin() === true
                    )
                    ->toggleable(),

                TextColumn::make('workplace')
                    ->label('Radno mjesto')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('organization_unit')
                    ->label('Organizacijska jedinica')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('medical_examination_valid_until')
                    ->label('Liječnički (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                TextColumn::make('article')
                    ->label('Članak 3. točke')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | ZAŠTITA NA RADU
                |--------------------------------------------------------------------------
                */

                TextColumn::make('occupational_safety_valid_from')
                    ->label('ZNR')
                    ->state(function (Employee $record): string {
                        if ($record->occupational_safety_valid_from) {
                            return Carbon::parse(
                                $record->occupational_safety_valid_from
                            )->format('d.m.Y.');
                        }

                        if ($record->znrTrainingDueDate()) {
                            return 'Rok: ' . $record->znrTrainingDueLabel();
                        }

                        return '—';
                    })
                    ->badge()
                    ->color(function (Employee $record): string {
                        if ($record->occupational_safety_valid_from) {
                            return 'success';
                        }

                        return ExpiryBadge::color(
                            $record->znrTrainingDueDate()
                        );
                    })
                    ->icon(function (Employee $record): string {
                        if ($record->occupational_safety_valid_from) {
                            return 'heroicon-m-check-circle';
                        }

                        return ExpiryBadge::icon(
                            $record->znrTrainingDueDate()
                        );
                    })
                    ->tooltip(function (Employee $record): string {
                        if ($record->occupational_safety_valid_from) {
                            return 'Osposobljavanje iz zaštite na radu je evidentirano';
                        }

                        return ExpiryBadge::tooltip(
                            $record->znrTrainingDueDate()
                        );
                    })
                    ->sortable(
                        query: fn (
                            Builder $query,
                            string $direction
                        ): Builder => $query->orderBy(
                            'occupational_safety_valid_from',
                            $direction
                        )
                    )
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | TOKSIKOLOGIJA
                |--------------------------------------------------------------------------
                */

                TextColumn::make('toxicology_valid_until')
                    ->label('Toksikologija (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | PRVA POMOĆ – NEMA ROKA VAŽENJA
                |--------------------------------------------------------------------------
                */

                TextColumn::make('first_aid_valid_from')
                    ->label('Prva pomoć (od)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(
                        fn ($state): string => filled($state)
                            ? 'success'
                            : 'gray'
                    )
                    ->icon(
                        fn ($state): string => filled($state)
                            ? 'heroicon-m-check-circle'
                            : 'heroicon-m-minus-circle'
                    )
                    ->tooltip(
                        fn ($state): string => filled($state)
                            ? 'Osposobljavanje za pružanje prve pomoći je evidentirano'
                            : 'Osposobljavanje za pružanje prve pomoći nije evidentirano'
                    )
                    ->placeholder('—')
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | ZAPALJIVE TVARI
                |--------------------------------------------------------------------------
                */

                TextColumn::make(
                    'handling_flammable_materials_valid_until'
                )
                    ->label('Zapaljive tvari (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | OVLAŠTENIK POSLODAVCA ZA ZNR
                |--------------------------------------------------------------------------
                */

                TextColumn::make(
                    'employers_authorization_valid_until'
                )
                    ->label('Ovlaštenik ZNR (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | ALKOTESTIRANJE
                |--------------------------------------------------------------------------
                */

                TextColumn::make('latestAlcoholTest.test_date')
                    ->label('Zadnje alkotestiranje')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->badge()
                    ->color(
                        fn ($state): string => filled($state)
                            ? 'info'
                            : 'gray'
                    )
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                TextColumn::make('latestAlcoholTest.result')
                    ->label('Rezultat promila')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string => filled($state)
                            ? $state . ' ‰'
                            : '—'
                    )
                    ->color(function ($state): string {
                        $value = (float) str_replace(
                            ',',
                            '.',
                            (string) $state
                        );

                        return filled($state) && $value > 0.5
                            ? 'danger'
                            : 'success';
                    })
                    ->placeholder('—')
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | OSTALE EDUKACIJE
                |--------------------------------------------------------------------------
                */

                ViewColumn::make('certificates')
                    ->label('Ostale edukacije')
                    ->state(
                        fn (Employee $record) => $record->certificates
                    )
                    ->view(
                        'filament.components.certificates-filtered'
                    )
                    ->extraAttributes([
                        'style' => 'max-width:240px; width:240px; overflow:hidden;',
                    ])
                    ->toggleable(),

                /*
                |--------------------------------------------------------------------------
                | PRILOZI
                |--------------------------------------------------------------------------
                */

                TextColumn::make('pdf')
                    ->label('Prilozi')
                    ->alignment(Alignment::Center)
                    ->html()
                    ->extraAttributes([
                        'style' => 'min-width:230px; width:230px;',
                    ])
                    ->state(function (Employee $record): string {
                        if (
                            ! is_array($record->pdf)
                            || count($record->pdf) === 0
                        ) {
                            return '<span style="color:#6b7280;">0</span>';
                        }

                        $files = collect($record->pdf)
                            ->take(10)
                            ->values();

                        $makeLink = function (
                            $file,
                            $index
                        ): string {
                            $url = route('file.preview', [
                                'file' => ltrim($file, '/'),
                            ]);

                            $name = e(basename($file));
                            $number = $index + 1;

                            return '<a href="' . e($url) . '"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="' . $name . '"
                                onclick="event.preventDefault(); event.stopPropagation(); event.stopImmediatePropagation(); window.open(this.href, \'_blank\'); return false;"
                                style="
                                    display:inline-flex;
                                    align-items:center;
                                    justify-content:center;
                                    width:36px;
                                    height:24px;
                                    border-radius:7px;
                                    background:rgba(59,130,246,.15);
                                    border:1px solid rgba(59,130,246,.35);
                                    color:#93c5fd;
                                    font-size:12px;
                                    font-weight:700;
                                    text-decoration:none;
                                    cursor:pointer;
                                    white-space:nowrap;
                                    flex:0 0 36px;
                                "
                            >📎 ' . $number . '</a>';
                        };

                        $row1 = $files
                            ->slice(0, 5)
                            ->values()
                            ->map(
                                fn ($file, $index) => $makeLink(
                                    $file,
                                    $index
                                )
                            )
                            ->implode('');

                        $row2 = $files
                            ->slice(5, 5)
                            ->values()
                            ->map(
                                fn ($file, $index) => $makeLink(
                                    $file,
                                    $index + 5
                                )
                            )
                            ->implode('');

                        return '<div style="
                                    display:flex;
                                    flex-direction:column;
                                    gap:4px;
                                    align-items:center;
                                ">
                                    <div style="
                                        display:flex;
                                        gap:4px;
                                        justify-content:center;
                                        flex-wrap:nowrap;
                                    ">'
                                        . $row1 .
                                    '</div>

                                    <div style="
                                        display:flex;
                                        gap:4px;
                                        justify-content:center;
                                        flex-wrap:nowrap;
                                    ">'
                                        . $row2 .
                                    '</div>
                                </div>';
                    })
                    ->tooltip(function (Employee $record): string {
                        if (
                            ! is_array($record->pdf)
                            || count($record->pdf) === 0
                        ) {
                            return 'Nema priloga';
                        }

                        return collect($record->pdf)
                            ->map(
                                fn ($file, $index): string => ($index + 1)
                                    . '. '
                                    . basename($file)
                            )
                            ->implode("\n");
                    })
                    ->toggleable(),
            ])

            /*
            |--------------------------------------------------------------------------
            | FILTERI
            |--------------------------------------------------------------------------
            */

            ->filters([
                /*
                |--------------------------------------------------------------------------
                | STATUS ZAPISA
                |--------------------------------------------------------------------------
                */

                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder('Aktivni zapisi')
                    ->options([
                        'active' => 'Aktivni zapisi',
                        'trashed' => 'Deaktivirani zapisi',
                        'all' => 'Svi zapisi',
                    ])
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        return match ($data['value'] ?? null) {
                            'trashed' => $query->onlyTrashed(),
                            'all' => $query->withTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    }),

                /*
                |--------------------------------------------------------------------------
                | RADNO MJESTO
                |--------------------------------------------------------------------------
                */

                SelectFilter::make('workplace')
                    ->label('Radno mjesto')
                    ->placeholder('Sva radna mjesta')
                    ->searchable()
                    ->options(function (): array {
                        return self::employeeOptionsQuery()
                            ->whereNotNull('workplace')
                            ->where('workplace', '!=', '')
                            ->distinct()
                            ->orderBy('workplace')
                            ->pluck('workplace', 'workplace')
                            ->toArray();
                    }),

                /*
                |--------------------------------------------------------------------------
                | ORGANIZACIJSKA JEDINICA / LOKACIJA
                |--------------------------------------------------------------------------
                */

                SelectFilter::make('organization_unit')
                    ->label('Organizacijska jedinica / lokacija')
                    ->placeholder('Sve organizacijske jedinice')
                    ->searchable()
                    ->options(function (): array {
                        return self::employeeOptionsQuery()
                            ->whereNotNull('organization_unit')
                            ->where('organization_unit', '!=', '')
                            ->distinct()
                            ->orderBy('organization_unit')
                            ->pluck(
                                'organization_unit',
                                'organization_unit'
                            )
                            ->toArray();
                    }),

                /*
                |--------------------------------------------------------------------------
                | ROKOVI I OSPOSOBLJAVANJA
                |--------------------------------------------------------------------------
                |
                | Jedan objedinjeni izbornik, ali su opcije podijeljene
                | prema kategorijama.
                |
                | Prva pomoć nema opcije "isteklo" i "uskoro istječe".
                |--------------------------------------------------------------------------
                */

                SelectFilter::make('validity_status')
                    ->label('Rokovi i osposobljavanja')
                    ->placeholder('Odaberite kategoriju i status')
                    ->searchable()
                    ->options([
                        'Liječnički pregled' => [
                            'medical_expired' =>
                                'Liječnički – isteklo',

                            'medical_expiring' =>
                                'Liječnički – uskoro istječe',

                            'medical_valid' =>
                                'Liječnički – važeće',

                            'medical_missing' =>
                                'Liječnički – nije evidentirano',
                        ],

                        'Zaštita na radu – ZNR' => [
                            'znr_expired' =>
                                'ZNR – prekoračen rok',

                            'znr_expiring' =>
                                'ZNR – rok uskoro istječe',

                            'znr_recorded' =>
                                'ZNR – evidentirano',

                            'znr_missing' =>
                                'ZNR – nije evidentirano',
                        ],

                        'Toksikologija' => [
                            'toxicology_expired' =>
                                'Toksikologija – isteklo',

                            'toxicology_expiring' =>
                                'Toksikologija – uskoro istječe',

                            'toxicology_valid' =>
                                'Toksikologija – važeće',

                            'toxicology_missing' =>
                                'Toksikologija – nije evidentirano',
                        ],

                        'Prva pomoć' => [
                            'first_aid_recorded' =>
                                'Prva pomoć – evidentirano',

                            'first_aid_missing' =>
                                'Prva pomoć – nije evidentirano',
                        ],

                        'Rukovanje zapaljivim tvarima' => [
                            'flammable_expired' =>
                                'Zapaljive tvari – isteklo',

                            'flammable_expiring' =>
                                'Zapaljive tvari – uskoro istječe',

                            'flammable_valid' =>
                                'Zapaljive tvari – važeće',

                            'flammable_missing' =>
                                'Zapaljive tvari – nije evidentirano',
                        ],

                        'Ovlaštenik poslodavca za ZNR' => [
                            'authorization_expired' =>
                                'Ovlaštenik ZNR – isteklo',

                            'authorization_expiring' =>
                                'Ovlaštenik ZNR – uskoro istječe',

                            'authorization_valid' =>
                                'Ovlaštenik ZNR – važeće',

                            'authorization_missing' =>
                                'Ovlaštenik ZNR – nije evidentirano',
                        ],
                    ])
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        return self::applyValidityFilter(
                            $query,
                            $value
                        );
                    }),

                /*
                |--------------------------------------------------------------------------
                | OSTALE EDUKACIJE I CERTIFIKATI
                |--------------------------------------------------------------------------
                */

                SelectFilter::make('certificate')
                    ->label('Ostala edukacija / certifikat')
                    ->placeholder('Svi certifikati')
                    ->searchable()
                    ->options(function (): array {
                        $query = EmployeeCertificate::query()
                            ->whereNotNull('title')
                            ->where('title', '!=', '');

                        if (! auth()->user()?->isSuperAdmin()) {
                            $query->whereHas(
                                'employee',
                                function (Builder $query): void {
                                    $query->where(
                                        'user_id',
                                        auth()->user()?->ownerId()
                                    );
                                }
                            );
                        }

                        return $query
                            ->orderBy('title')
                            ->pluck('title', 'title')
                            ->unique()
                            ->toArray();
                    })
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        return $query->whereHas(
                            'certificates',
                            fn (Builder $certificateQuery): Builder =>
                                $certificateQuery->where(
                                    'title',
                                    $value
                                )
                        );
                    }),

                /*
                |--------------------------------------------------------------------------
                | ALKOTESTIRANJE
                |--------------------------------------------------------------------------
                */

                SelectFilter::make('alcohol_test_status')
                    ->label('Status alkotestiranja')
                    ->placeholder('Svi zaposlenici')
                    ->options([
                        'done' =>
                            'Provedeno alkotestiranje',

                        'missing' =>
                            'Nije provedeno alkotestiranje',
                    ])
                    ->query(function (
                        Builder $query,
                        array $data
                    ): Builder {
                        return match ($data['value'] ?? null) {
                            'done' => $query->whereHas(
                                'alcoholTests'
                            ),

                            'missing' => $query->whereDoesntHave(
                                'alcoholTests'
                            ),

                            default => $query,
                        };
                    }),
            ])

            /*
            |--------------------------------------------------------------------------
            | POJEDINAČNE AKCIJE
            |--------------------------------------------------------------------------
            */

            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(
                            fn (Employee $record): bool => ! (
                                method_exists($record, 'trashed')
                                && $record->trashed()
                            )
                        ),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(
                            fn (Employee $record): bool => ! (
                                method_exists($record, 'trashed')
                                && $record->trashed()
                            )
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(
                            fn (Employee $record): bool =>
                                method_exists($record, 'trashed')
                                && $record->trashed()
                        ),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(
                            fn (Employee $record): bool =>
                                method_exists($record, 'trashed')
                                && $record->trashed()
                        ),
                ])->label('Akcije'),
            ])

            /*
            |--------------------------------------------------------------------------
            | GRUPNE AKCIJE
            |--------------------------------------------------------------------------
            */

            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Deaktiviraj odabrano')
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->modalSubmitActionLabel('Deaktiviraj')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(
                        fn (HasTable $livewire): bool =>
                            ! self::isOnlyTrashed($livewire)
                    ),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Vrati odabrano')
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti?'
                    )
                    ->modalSubmitActionLabel('Vrati')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(
                        fn (HasTable $livewire): bool =>
                            self::isOnlyTrashed($livewire)
                    ),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Trajno obriši odabrano')
                    ->modalDescription(
                        'Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.'
                    )
                    ->modalSubmitActionLabel('Trajno obriši')
                    ->modalCancelActionLabel('Odustani'),
            ])

            ->paginated([10, 25, 50, 100, 'all']);
    }

    /**
     * Primjenjuje odabrani filter roka ili osposobljavanja.
     */
    private static function applyValidityFilter(
        Builder $query,
        string $value
    ): Builder {
        $today = Carbon::today();
        $soon = Carbon::today()->addDays(30);

        return match ($value) {
            /*
            |--------------------------------------------------------------------------
            | LIJEČNIČKI PREGLED
            |--------------------------------------------------------------------------
            */

            'medical_expired' => $query
                ->whereNotNull(
                    'medical_examination_valid_until'
                )
                ->whereDate(
                    'medical_examination_valid_until',
                    '<',
                    $today
                ),

            'medical_expiring' => $query
                ->whereNotNull(
                    'medical_examination_valid_until'
                )
                ->whereDate(
                    'medical_examination_valid_until',
                    '>=',
                    $today
                )
                ->whereDate(
                    'medical_examination_valid_until',
                    '<=',
                    $soon
                ),

            'medical_valid' => $query
                ->whereNotNull(
                    'medical_examination_valid_until'
                )
                ->whereDate(
                    'medical_examination_valid_until',
                    '>',
                    $soon
                ),

            'medical_missing' => $query
                ->whereNull(
                    'medical_examination_valid_until'
                ),

            /*
            |--------------------------------------------------------------------------
            | ZAŠTITA NA RADU – ZNR
            |--------------------------------------------------------------------------
            */

            'znr_expired' => $query
                ->whereNull(
                    'occupational_safety_valid_from'
                )
                ->whereNotNull('employeed_at')
                ->whereDate(
                    'employeed_at',
                    '<',
                    $today->copy()->subDays(60)
                ),

            'znr_expiring' => $query
                ->whereNull(
                    'occupational_safety_valid_from'
                )
                ->whereNotNull('employeed_at')
                ->whereDate(
                    'employeed_at',
                    '>=',
                    $today->copy()->subDays(60)
                )
                ->whereDate(
                    'employeed_at',
                    '<=',
                    $today->copy()->subDays(30)
                ),

            'znr_recorded' => $query
                ->whereNotNull(
                    'occupational_safety_valid_from'
                ),

            'znr_missing' => $query
                ->whereNull(
                    'occupational_safety_valid_from'
                ),

            /*
            |--------------------------------------------------------------------------
            | TOKSIKOLOGIJA
            |--------------------------------------------------------------------------
            */

            'toxicology_expired' => $query
                ->whereNotNull('toxicology_valid_until')
                ->whereDate(
                    'toxicology_valid_until',
                    '<',
                    $today
                ),

            'toxicology_expiring' => $query
                ->whereNotNull('toxicology_valid_until')
                ->whereDate(
                    'toxicology_valid_until',
                    '>=',
                    $today
                )
                ->whereDate(
                    'toxicology_valid_until',
                    '<=',
                    $soon
                ),

            'toxicology_valid' => $query
                ->whereNotNull('toxicology_valid_until')
                ->whereDate(
                    'toxicology_valid_until',
                    '>',
                    $soon
                ),

            'toxicology_missing' => $query
                ->whereNull('toxicology_valid_until'),

            /*
            |--------------------------------------------------------------------------
            | PRVA POMOĆ – NEMA ROKA VAŽENJA
            |--------------------------------------------------------------------------
            */

            'first_aid_recorded' => $query
                ->whereNotNull('first_aid_valid_from'),

            'first_aid_missing' => $query
                ->whereNull('first_aid_valid_from'),

            /*
            |--------------------------------------------------------------------------
            | RUKOVANJE ZAPALJIVIM TVARIMA
            |--------------------------------------------------------------------------
            */

            'flammable_expired' => $query
                ->whereNotNull(
                    'handling_flammable_materials_valid_until'
                )
                ->whereDate(
                    'handling_flammable_materials_valid_until',
                    '<',
                    $today
                ),

            'flammable_expiring' => $query
                ->whereNotNull(
                    'handling_flammable_materials_valid_until'
                )
                ->whereDate(
                    'handling_flammable_materials_valid_until',
                    '>=',
                    $today
                )
                ->whereDate(
                    'handling_flammable_materials_valid_until',
                    '<=',
                    $soon
                ),

            'flammable_valid' => $query
                ->whereNotNull(
                    'handling_flammable_materials_valid_until'
                )
                ->whereDate(
                    'handling_flammable_materials_valid_until',
                    '>',
                    $soon
                ),

            'flammable_missing' => $query
                ->whereNull(
                    'handling_flammable_materials_valid_until'
                ),

            /*
            |--------------------------------------------------------------------------
            | OVLAŠTENIK POSLODAVCA ZA ZNR
            |--------------------------------------------------------------------------
            */

            'authorization_expired' => $query
                ->whereNotNull(
                    'employers_authorization_valid_until'
                )
                ->whereDate(
                    'employers_authorization_valid_until',
                    '<',
                    $today
                ),

            'authorization_expiring' => $query
                ->whereNotNull(
                    'employers_authorization_valid_until'
                )
                ->whereDate(
                    'employers_authorization_valid_until',
                    '>=',
                    $today
                )
                ->whereDate(
                    'employers_authorization_valid_until',
                    '<=',
                    $soon
                ),

            'authorization_valid' => $query
                ->whereNotNull(
                    'employers_authorization_valid_until'
                )
                ->whereDate(
                    'employers_authorization_valid_until',
                    '>',
                    $soon
                ),

            'authorization_missing' => $query
                ->whereNull(
                    'employers_authorization_valid_until'
                ),

            default => $query,
        };
    }

    /**
     * Opcije radnih mjesta i organizacijskih jedinica
     * ograničene su na trenutačnu organizaciju.
     */
    private static function employeeOptionsQuery(): Builder
    {
        $query = Employee::query()
            ->withoutTrashed();

        if (! auth()->user()?->isSuperAdmin()) {
            $query->where(
                'user_id',
                auth()->user()?->ownerId()
            );
        }

        return $query;
    }

    private static function isOnlyTrashed(
        HasTable $livewire
    ): bool {
        $state = $livewire->getTableFilterState(
            'status'
        );

        $value = data_get($state, 'value');

        return $value === 'trashed';
    }
}
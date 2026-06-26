<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use App\Support\ExpiryBadge;
use App\Models\EmployeeCertificate;
use App\Models\EmployeeAlcoholTest;
use Illuminate\Support\Facades\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;

use Filament\Tables\Contracts\HasTable;
use App\Filament\Resources\Concerns\HasUserTableColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EmployeesTable
{
    use HasUserTableColumn;

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
            ->with('latestAlcoholTest')
            ->orderByDesc(
                EmployeeAlcoholTest::query()
                    ->select('test_date')
                    ->whereColumn('employee_alcohol_tests.employee_id', 'employees.id')
                    ->latest('test_date')
                    ->limit(1)
            )
            ->orderBy('name')
        )
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
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->toggleable(),

                TextColumn::make('workplace')
                    ->label('Radno mjesto')
                    ->searchable()
                    ->sortable()
                    ->wrap()
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

                TextColumn::make('occupational_safety_valid_from')
                    ->label('ZNR')
                    ->state(function (Employee $record): string {

                        if ($record->occupational_safety_valid_from) {
                            return Carbon::parse($record->occupational_safety_valid_from)
                                ->format('d.m.Y.');
                        }

                        if ($record->znrTrainingDueDate()) {
                            return 'Rok: ' . $record->znrTrainingDueLabel();
                        }

                        return '—';
                    })
                    ->badge()

                    ->color(function (Employee $record) {

                        if ($record->occupational_safety_valid_from) {
                            return 'success';
                        }

                        return ExpiryBadge::color($record->znrTrainingDueDate());
                    })

                    ->icon(function (Employee $record) {

                        if ($record->occupational_safety_valid_from) {
                            return 'heroicon-m-check-circle';
                        }

                        return ExpiryBadge::icon($record->znrTrainingDueDate());
                    })

                    ->tooltip(function (Employee $record) {

                        if ($record->occupational_safety_valid_from) {
                            return 'Osposobljavanje evidentirano';
                        }

                        return ExpiryBadge::tooltip($record->znrTrainingDueDate());
                    })

                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('occupational_safety_valid_from', $direction);
                    })

                    ->alignment(Alignment::Center)
                    ->toggleable(),

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

                    TextColumn::make('first_aid_valid_from')
                    ->label('Prva pomoć (od)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->icon(fn ($state) => $state ? 'heroicon-m-check-circle' : 'heroicon-m-minus-circle')
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                    TextColumn::make('handling_flammable_materials_valid_until')
                    ->label('Zapaljive tvari (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                TextColumn::make('employers_authorization_valid_until')
                    ->label('Ovlaštenik ZNR (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                    TextColumn::make('latestAlcoholTest.test_date')
                    ->label('Zadnje alkotestiranje')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'gray')
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                TextColumn::make('latestAlcoholTest.result')
                    ->label('Rezultat promila')
                    ->badge()
                    ->formatStateUsing(fn ($state) => filled($state) ? $state . ' ‰' : '—')
                    ->color(function ($state) {
                        $value = (float) str_replace(',', '.', (string) $state);

                        return filled($state) && $value > 0.5 ? 'danger' : 'success';
                    })
                    ->placeholder('—')
                    ->alignment(Alignment::Center)
                    ->toggleable(),

                ViewColumn::make('certificates')
                    ->label('Ostale edukacije')
                    ->state(fn (Employee $record) => $record->certificates)
                    ->view('filament.components.certificates-filtered')
                    ->extraAttributes([
                        'style' => 'max-width:240px; width:240px; overflow:hidden;',
                    ])
                    ->toggleable(),

                TextColumn::make('pdf')
                    ->label('Prilozi')
                    ->alignment(Alignment::Center)
                    ->html()
                    ->extraAttributes([
                        'style' => 'min-width:230px; width:230px;',
                    ])
                    ->state(function (Employee $record): string {

                        if (! is_array($record->pdf) || count($record->pdf) === 0) {
                            return '<span style="color:#6b7280;">0</span>';
                        }

                        $files = collect($record->pdf)->take(10)->values();

                        $makeLink = function ($file, $index) {

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

                        $row1 = $files->slice(0, 5)
                            ->values()
                            ->map(fn ($file, $index) => $makeLink($file, $index))
                            ->implode('');

                        $row2 = $files->slice(5, 5)
                            ->values()
                            ->map(fn ($file, $index) => $makeLink($file, $index + 5))
                            ->implode('');

                        return '<div style="display:flex; flex-direction:column; gap:4px; align-items:center;">
                            <div style="display:flex; gap:4px; justify-content:center; flex-wrap:nowrap;">' . $row1 . '</div>
                            <div style="display:flex; gap:4px; justify-content:center; flex-wrap:nowrap;">' . $row2 . '</div>
                        </div>';
                    })
                    ->tooltip(function (Employee $record): string {

                        if (! is_array($record->pdf) || count($record->pdf) === 0) {
                            return 'Nema priloga';
                        }

                        return collect($record->pdf)
                            ->map(fn ($file, $index) => ($index + 1) . '. ' . basename($file))
                            ->implode("\n");
                    })
                    ->toggleable(),
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

                SelectFilter::make('certificate')
                    ->label('Certifikat / edukacija')
                    ->placeholder('Svi certifikati')
                    ->searchable()
                    ->options(function (): array {
                        return EmployeeCertificate::query()
                            ->whereNotNull('title')
                            ->where('title', '!=', '')
                            ->orderBy('title')
                            ->pluck('title', 'title')
                            ->unique()
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        return $query->whereHas('certificates', function (Builder $query) use ($value) {
                            $query->where('title', $value);
                        });
                    }),

                     SelectFilter::make('alcohol_test_status')
                        ->label('Status alkotestiranja')
                        ->placeholder('Svi zaposlenici')
                        ->options([
                            'done' => 'Provedeno alkotestiranje',
                            'missing' => 'Nije provedeno alkotestiranje',
                        ])
                        ->query(function (Builder $query, array $data): Builder {
                            return match ($data['value'] ?? null) {
                                'done' => $query->whereHas('alcoholTests'),
                                'missing' => $query->whereDoesntHave('alcoholTests'),
                                default => $query,
                            };
                        }),

                Filter::make('medical_examination_expired')
                ->label('Liječnički (istekao)')
                ->query(function (Builder $query): Builder {
                    return $query
                        ->whereNotNull('medical_examination_valid_until')
                        ->whereDate('medical_examination_valid_until', '<', Carbon::today());
                }),

            Filter::make('medical_examination_expiring')
                ->label('Liječnički (uskoro ističe)')
                ->query(function (Builder $query): Builder {
                    return $query
                        ->whereNotNull('medical_examination_valid_until')
                        ->whereDate('medical_examination_valid_until', '>=', Carbon::today())
                        ->whereDate('medical_examination_valid_until', '<=', Carbon::today()->addDays(30));
                }),

            Filter::make('znr_expired')
                ->label('ZNR nije položen (istekao rok)')
                ->query(function (Builder $query): Builder {
                    return $query
                        ->whereNull('occupational_safety_valid_from')
                        ->whereNotNull('employeed_at')
                        ->whereDate('employeed_at', '<', Carbon::today()->subDays(60));
                }),

            Filter::make('znr_expiring')
                ->label('ZNR nije položen (uskoro ističe)')
                ->query(function (Builder $query): Builder {
                    return $query
                        ->whereNull('occupational_safety_valid_from')
                        ->whereNotNull('employeed_at')
                        ->whereDate('employeed_at', '>=', Carbon::today()->subDays(60))
                        ->whereDate('employeed_at', '<=', Carbon::today()->subDays(30));
                }),
    
            Filter::make('toxicology_expired')
                ->label('Toksikologija (istekla)')
                ->query(function (Builder $query): Builder {
                    return $query
                        ->whereNotNull('toxicology_valid_until')
                        ->whereDate('toxicology_valid_until', '<', Carbon::today());
                }),

            Filter::make('toxicology_expiring')
                ->label('Toksikologija (uskoro ističe)')
                ->query(function (Builder $query): Builder {
                    return $query
                        ->whereNotNull('toxicology_valid_until')
                        ->whereDate('toxicology_valid_until', '>=', Carbon::today())
                        ->whereDate('toxicology_valid_until', '<=', Carbon::today()->addDays(30));
                }),
            ])

            ->actions([
                ActionGroup::make([

                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (Employee $record) =>
                            ! (method_exists($record, 'trashed') && $record->trashed())
                        ),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (Employee $record) =>
                            ! (method_exists($record, 'trashed') && $record->trashed())
                        ),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (Employee $record) =>
                            method_exists($record, 'trashed') && $record->trashed()
                        ),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (Employee $record) =>
                            method_exists($record, 'trashed') && $record->trashed()
                        ),

                ])->label('Akcije'),
            ])

            ->bulkActions([

                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Deaktiviraj odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->modalSubmitActionLabel('Deaktiviraj')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(fn (HasTable $livewire) =>
                        ! self::isOnlyTrashed($livewire)
                    ),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Vrati odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->modalSubmitActionLabel('Vrati')
                    ->modalCancelActionLabel('Odustani')
                    ->visible(fn (HasTable $livewire) =>
                        self::isOnlyTrashed($livewire)
                    ),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Trajno obriši odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti? Ova radnja se ne može poništiti.')
                    ->modalSubmitActionLabel('Trajno obriši')
                    ->modalCancelActionLabel('Odustani'),
            ])

            ->paginated([10, 25, 50, 100, 'all']);
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');

        $value = data_get($state, 'value');

        return $value === 'trashed';
    }
}
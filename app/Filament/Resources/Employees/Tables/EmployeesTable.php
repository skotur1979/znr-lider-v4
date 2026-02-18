<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use App\Support\ExpiryBadge;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Prezime i ime')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('workplace')
                    ->label('Radno mjesto')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('medical_examination_valid_until')
                    ->label('Liječnički (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('article')
                    ->label('Članak 3. točke')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->alignment(Alignment::Center),

                TextColumn::make('occupational_safety_valid_from')
                    ->label('ZNR (od)')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('toxicology_valid_until')
                    ->label('Toksikologija (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('employers_authorization_valid_until')
                    ->label('Ovlaštenik ZNR (do)')
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable()
                    ->alignment(Alignment::Center),

                // ovo je JEDAN view – vrijedi gdje god treba, nije “po modulu”
                ViewColumn::make('certificates')
    ->label('Ostale edukacije')
    ->state(fn (Employee $record) => $record->certificates)
    ->view('filament.components.certificates-filtered'),

                TextColumn::make('pdf')
                    ->label('Prilozi')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->icon(fn (Employee $record) => (is_array($record->pdf) && count($record->pdf)) ? 'heroicon-o-paper-clip' : null)
                    ->color(fn (Employee $record) => (is_array($record->pdf) && count($record->pdf)) ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state, Employee $record) => is_array($record->pdf) ? (string) count($record->pdf) : '0')
                    ->tooltip(fn (Employee $record) => (is_array($record->pdf) && count($record->pdf))
                        ? implode("\n", $record->pdf)
                        : 'Nema priloga'),
            ])
            ->filters([
                TrashedFilter::make(),

                Filter::make('medical_examination_expired')
                    ->label('Liječnički (istekao)')
                    ->query(fn (Builder $q) => $q->whereDate('medical_examination_valid_until', '<', Carbon::today())),

                Filter::make('medical_examination_expiring')
                    ->label('Liječnički (uskoro ističe)')
                    ->query(fn (Builder $q) => $q
                        ->whereDate('medical_examination_valid_until', '>=', Carbon::today())
                        ->whereDate('medical_examination_valid_until', '<=', Carbon::today()->addDays(30))),

                Filter::make('toxicology_expired')
                    ->label('Toksikologija (istekla)')
                    ->query(fn (Builder $q) => $q->whereDate('toxicology_valid_until', '<', Carbon::today())),

                Filter::make('toxicology_expiring')
                    ->label('Toksikologija (uskoro ističe)')
                    ->query(fn (Builder $q) => $q
                        ->whereDate('toxicology_valid_until', '>=', Carbon::today())
                        ->whereDate('toxicology_valid_until', '<=', Carbon::today()->addDays(30))),
            ])
            ->actions([
    ViewAction::make()->label('Prikaži'),
    EditAction::make()->label('Uredi'),
    DeleteAction::make()->label('Deaktiviraj')->requiresConfirmation(),
    RestoreAction::make()->label('Vrati')->requiresConfirmation(),
    ForceDeleteAction::make()->label('Trajno obriši')->requiresConfirmation(),
])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Deaktiviraj označeno')->requiresConfirmation(),
                    RestoreBulkAction::make()->label('Vrati označeno')->requiresConfirmation(),
                    ForceDeleteBulkAction::make()->label('Trajno obriši označeno')->requiresConfirmation(),
                ]),
            ])
            // ako ti "all" radi na Machines, radit će i tu
            ->paginated([10, 25, 50, 'all']);
    }
}



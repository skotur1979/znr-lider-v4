<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Employee;
use App\Support\ExpiryBadge;
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

                Filter::make('medical_examination_expired')
                    ->label('Liječnički (istekao)')
                    ->query(fn (Builder $q) =>
                        $q->whereDate('medical_examination_valid_until', '<', Carbon::today())
                    ),

                Filter::make('medical_examination_expiring')
                    ->label('Liječnički (uskoro ističe)')
                    ->query(fn (Builder $q) =>
                        $q->whereDate('medical_examination_valid_until', '>=', Carbon::today())
                            ->whereDate('medical_examination_valid_until', '<=', Carbon::today()->addDays(30))
                    ),

                Filter::make('toxicology_expired')
                    ->label('Toksikologija (istekla)')
                    ->query(fn (Builder $q) =>
                        $q->whereDate('toxicology_valid_until', '<', Carbon::today())
                    ),

                Filter::make('toxicology_expiring')
                    ->label('Toksikologija (uskoro ističe)')
                    ->query(fn (Builder $q) =>
                        $q->whereDate('toxicology_valid_until', '>=', Carbon::today())
                            ->whereDate('toxicology_valid_until', '<=', Carbon::today()->addDays(30))
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (Employee $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (Employee $record) => ! (method_exists($record, 'trashed') && $record->trashed())),

                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (Employee $record) => method_exists($record, 'trashed') && $record->trashed()),

                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (Employee $record) => method_exists($record, 'trashed') && $record->trashed()),
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
            ->modalCancelActionLabel('Odustani')
            ])
            ->paginated([10, 25, 50, 'all']);
    }

    private static function isOnlyTrashed(HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');
        $value = data_get($state, 'value');

        return $value === 'trashed';
    }
}



<?php

namespace App\Filament\Resources\PpeLogs\PPELogResource\RelationManagers;

use App\Support\ExpiryBadge;
use App\Support\SignatureStorage;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Popis osobne zaštitne opreme';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('equipment_name')
                ->label('Naziv OZO')
                ->required()
                ->datalist([
                    'Zaštitna Kaciga',
                    'Zaštitne naočale prozirne',
                    'Zaštitne Rukavice',
                    'Reflektirajući prsluk',
                    'Zaštitne cipele s kapicom',
                    'Zaštitne gumene čizme',
                    'Radne hlače',
                    'Radna jakna',
                    'Majca s kratkim rukavima',
                    'Majca s dugim rukavima',
                    'Zimska jakna sa rukavima',
                    'Manžeta za zaštitu podlaktice',
                    'Zaštitna polumaska s filterima',
                ]),

            TextInput::make('standard')
                ->label('HRN EN')
                ->maxLength(64),

            TextInput::make('size')
                ->label('Veličina')
                ->maxLength(20),

            TextInput::make('duration_months')
                ->label('Rok uporabe (mjeseci)')
                ->numeric()
                ->minValue(0)
                ->maxValue(120)
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) => self::recalcEndDate($set, $get)),

            DatePicker::make('issue_date')
                ->label('Datum izdavanja')
                ->required()
                ->native(false)
                ->displayFormat('d/m/Y')
                ->format('Y-m-d')
                ->closeOnDateSelection()
                ->live()
                ->afterStateUpdated(fn ($state, $set, $get) => self::recalcEndDate($set, $get)),

            DatePicker::make('end_date')
                ->label('Datum isteka')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->format('Y-m-d')
                ->readOnly()
                ->helperText('Automatski izračun iz “Izdano” + “Rok (mjeseci)”.'),

            ViewField::make('signature')
                ->label('Potpis – preuzeo OZO')
                ->view('filament.components.ozo-signature')
                ->columnSpanFull(),

            DatePicker::make('return_date')
                ->label('Datum vraćanja')
                ->native(false)
                ->displayFormat('d/m/Y')
                ->format('Y-m-d')
                ->closeOnDateSelection(),
        ])->columns(4);
    }

    protected static function recalcEndDate(callable $set, callable $get): void
    {
        $issue = $get('issue_date');
        $months = (int) ($get('duration_months') ?? 0);

        if (blank($issue) || $months <= 0) {
            $set('end_date', null);
            return;
        }

        try {
            $set(
                'end_date',
                Carbon::parse($issue)->addMonths($months)->format('Y-m-d')
            );
        } catch (\Throwable $e) {
            $set('end_date', null);
        }
    }

    protected static function prepareFormData(array $data): array
    {
        if (! empty($data['signature']) && str_starts_with($data['signature'], 'data:image')) {
            $data['signature'] = SignatureStorage::storeDataUrl($data['signature']);
        }

        $issue = $data['issue_date'] ?? null;
        $months = (int) ($data['duration_months'] ?? 0);

        if (! empty($issue) && $months > 0) {
            try {
                $data['end_date'] = Carbon::parse($issue)->addMonths($months)->format('Y-m-d');
            } catch (\Throwable $e) {
                $data['end_date'] = null;
            }
        } else {
            $data['end_date'] = null;
        }

        return $data;
    }

    public function table(Table $table): Table
{
    return $table
        ->emptyStateIcon('heroicon-o-shield-check')
        ->emptyStateHeading('Nema Osobne zaštitne opreme')
        ->emptyStateDescription('Stvori OZO kako bi započeo')

        ->columns([
                TextColumn::make('equipment_name')
                    ->label('Naziv OZO')
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('standard')
                    ->label('HRN EN')
                    ->toggleable()
                    ->wrap(),

                TextColumn::make('size')
                    ->label('Veličina')
                    ->alignCenter(),

                TextColumn::make('duration_months')
                    ->label('Rok (mjeseci)')
                    ->alignCenter(),

                TextColumn::make('issue_date')
                    ->label('Izdano')
                    ->date('d.m.Y.')
                    ->alignCenter(),

                TextColumn::make('end_date')
                    ->label('Istek')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (blank($state)) {
                            return '—';
                        }

                        return Carbon::parse($state)->format('d.m.Y.');
                    })
                    ->color(function ($state) {
                        if (blank($state)) {
                            return 'gray';
                        }

                        return ExpiryBadge::color($state, 30);
                    })
                    ->icon(function ($state) {
                        if (blank($state)) {
                            return null;
                        }

                        return ExpiryBadge::icon($state, 30);
                    })
                    ->tooltip(function ($state) {
                        if (blank($state)) {
                            return null;
                        }

                        return ExpiryBadge::tooltip($state, 30);
                    })
                    ->sortable(),

                TextColumn::make('return_date')
                    ->label('Datum vraćanja')
                    ->date('d.m.Y.')
                    ->alignCenter()
                    ->toggleable(),

                ImageColumn::make('signature')
                    ->label('Potpis')
                    ->disk('public')
                    ->height(40)
                    ->width(100)
                    ->extraImgAttributes([
                        'class' => 'bg-white rounded-md p-0.5 ring-1 ring-gray-300 dark:ring-gray-600',
                        'style' => 'object-fit:contain;',
                    ])
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('isteklo')
                    ->label('Isteklo')
                    ->query(fn (Builder $q) => $q->whereNotNull('end_date')->where('end_date', '<', today())),

                Filter::make('uskoro')
                    ->label('Uskoro ističe (≤30d)')
                    ->query(fn (Builder $q) => $q->whereBetween('end_date', [today(), today()->addDays(30)])),

                Filter::make('vraceno')
                    ->label('Vraćeno')
                    ->query(fn (Builder $q) => $q->whereNotNull('return_date')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj OZO')
                    ->mutateFormDataUsing(fn (array $data) => self::prepareFormData($data)),
            ])
            ->actions([
                EditAction::make()
                    ->mutateFormDataUsing(fn (array $data) => self::prepareFormData($data)),

                Action::make('extend3')
                    ->label('Produži +3 mj')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->duration_months = max(0, (int) $record->duration_months) + 3;

                        if ($record->issue_date && $record->duration_months > 0) {
                            $record->end_date = Carbon::parse($record->issue_date)->addMonths($record->duration_months);
                        } else {
                            $record->end_date = null;
                        }

                        $record->save();
                    }),

                Action::make('returnedToday')
                    ->label('Označi vraćeno danas')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'return_date' => today(),
                    ])),

                DeleteAction::make()
                    ->label('Deaktiviraj'),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
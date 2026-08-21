<?php

namespace App\Filament\Resources\Inspections;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\Inspections\Pages;
use App\Filament\Resources\Inspections\RelationManagers\FindingsRelationManager;
use App\Filament\Resources\Inspections\RelationManagers\ZonesRelationManager;
use App\Models\Inspection;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InspectionResource extends BaseResource
{
    protected static ?string $model =
        Inspection::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup =
        'Upravljanje';

    protected static ?string $navigationLabel =
        'Nadzori';

    protected static ?string $modelLabel =
        'Nadzor';

    protected static ?string $pluralModelLabel =
        'Nadzori';

    protected static bool $hasOwnership =
        true;

    protected static function getModuleKey(): ?string
    {
        return 'inspections';
    }

    public static function form(
        Schema $schema
    ): Schema {
        return $schema->schema([
            Hidden::make('inspection_type')
                ->default(
                    fn () =>
                        request()->query(
                            'inspection_type',
                            'general'
                        )
                )
                ->dehydrated(true),

            Section::make(
                'Osnovni podaci nadzora'
            )
                ->schema([
                    TextInput::make('number')
                        ->label('Broj nadzora')
                        ->required()
                        ->maxLength(50)
                        ->unique(
                            table: 'inspections',
                            column: 'number',
                            ignoreRecord: true,
                            modifyRuleUsing: function (
                                $rule,
                                ?Inspection $record
                            ) {
                                $ownerId =
                                    $record?->user_id
                                    ?? static::ownerId();

                                return $rule->where(
                                    'user_id',
                                    $ownerId
                                );
                            }
                        ),

                    TextInput::make('title')
                        ->label('Naziv nadzora')
                        ->required(),

                    TextInput::make('location')
                        ->label('Lokacija')
                        ->required(),

                    DatePicker::make('performed_at')
                        ->label('Datum nadzora')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb')
                        ->required(),

                    TextInput::make('performed_by')
                        ->label('Proveo nadzor'),

                    Textarea::make('present_persons')
                        ->label('Prisutne osobe')
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('conclusion')
                        ->label('Zaključak')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(
        Table $table
    ): Table {
        return $table
            ->recordUrl(
                fn (
                    Inspection $record
                ): string =>
                    static::getUrl(
                        'edit',
                        [
                            'record' =>
                                $record,
                        ]
                    )
            )
            ->modifyQueryUsing(
                fn (
                    Builder $query
                ) =>
                    $query->withCount([
                        'findings',
                        'zones',
                    ])
            )
            ->columns([
                TextColumn::make('number')
                    ->label('Broj')
                    ->weight('bold')
                    ->toggleable(),

               static::userTableColumn(),

                TextColumn::make(
                    'inspection_type'
                )
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(
                        fn (
                            ?string $state
                        ) => match (
                            $state
                        ) {
                            'five_s' =>
                                '5S nadzor',

                            default =>
                                'Nadzor',
                        }
                    )
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),

                TextColumn::make(
                    'performed_at'
                )
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->alignment(
                        Alignment::Center
                    )
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('title')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('location')
                    ->label('Lokacija')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make(
                    'five_s_score'
                )
                    ->label('5S rezultat')
                    ->state(
                        fn (
                            Inspection $record
                        ) =>
                            $record
                                ->calculateFiveSScore()
                    )
                    ->formatStateUsing(
                        fn (
                            $state
                        ) =>
                            filled($state)
                                ? $state . '%'
                                : '-'
                    )
                    ->badge()
                    ->color(
                        fn (
                            $state
                        ) => match (
                            true
                        ) {
                            blank($state) =>
                                'gray',

                            $state < 40 =>
                                'danger',

                            $state < 60 =>
                                'warning',

                            default =>
                                'success',
                        }
                    )
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(
                        isToggledHiddenByDefault:
                            true
                    ),

                TextColumn::make(
                    'findings_count'
                )
                    ->label('Nalaza')
                    ->alignment(
                        Alignment::Center
                    )
                    ->toggleable(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label(
                            'Prikaz'
                        ),

                    EditAction::make()
                        ->label(
                            'Uredi'
                        ),

                    DeleteAction::make()
                        ->label(
                            'Obriši'
                        )
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            FindingsRelationManager::class,
            ZonesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListInspections::route('/'),

            'create' =>
                Pages\CreateInspection::route(
                    '/create'
                ),

            'zone-results-report' =>
                Pages\ZoneResultsReport::route(
                    '/zone-results-report'
                ),

            'edit' =>
                Pages\EditInspection::route(
                    '/{record}/edit'
                ),
        ];
    }
}
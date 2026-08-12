<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeAlcoholTestsRelationManager extends RelationManager
{
    protected static string $relationship =
        'alcoholTests';

    protected static ?string $title =
        'Alkotestiranja';

    protected static ?string $modelLabel =
        'alkotestiranje';

    protected static ?string $pluralModelLabel =
        'alkotestiranja';

    /**
     * Vlasnik alkotestiranja uvijek
     * mora biti vlasnik Employee zapisa.
     */
    protected function ownerId(): int
    {
        $ownerId =
            (int) (
                $this
                    ->getOwnerRecord()
                    ->user_id
                ?? 0
            );

        if ($ownerId <= 0) {
            abort(403);
        }

        return $ownerId;
    }

    public function form(
        Schema $schema
    ): Schema {
        return $schema
            ->schema([
                DatePicker::make(
                    'test_date'
                )
                    ->label(
                        'Datum kontrole'
                    )
                    ->required()
                    ->displayFormat(
                        'd.m.Y.'
                    )
                    ->weekStartsOnMonday()
                    ->timezone(
                        'Europe/Zagreb'
                    ),

                TextInput::make(
                    'result'
                )
                    ->label(
                        'Rezultat'
                    )
                    ->placeholder(
                        'npr. 0,0'
                    )
                    ->maxLength(50),

                TextInput::make(
                    'tested_by'
                )
                    ->label(
                        'Kontrolu proveo'
                    )
                    ->maxLength(255),

                Textarea::make(
                    'note'
                )
                    ->label(
                        'Napomena'
                    )
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(
        Table $table
    ): Table {
        return $table
            ->defaultSort(
                'test_date',
                'desc'
            )
            ->columns([
                TextColumn::make(
                    'test_date'
                )
                    ->label(
                        'Datum kontrole'
                    )
                    ->date(
                        'd.m.Y.'
                    )
                    ->sortable()
                    ->alignment(
                        Alignment::Center
                    ),

                TextColumn::make(
                    'result'
                )
                    ->label(
                        'Rezultat'
                    )
                    ->badge()
                    ->color(
                        function ($state): string {
                            $value = (float) str_replace(
                                ',',
                                '.',
                                (string) $state
                            );

                            return filled($state)
                                && $value > 0.5
                                    ? 'danger'
                                    : 'success'
                            ;
                        }
                    )
                    ->alignment(
                        Alignment::Center
                    ),

                TextColumn::make(
                    'tested_by'
                )
                    ->label(
                        'Kontrolu proveo'
                    )
                    ->searchable()
                    ->toggleable(),

                TextColumn::make(
                    'note'
                )
                    ->label(
                        'Napomena'
                    )
                    ->wrap()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(
                        'Dodaj alkotestiranje'
                    )
                    ->before(
                        EmployeeResource::beforeModulePermission(
                            'update'
                        )
                    )

                    /*
                     * user_id se ne uzima
                     * iz forme nego iz
                     * Employee zapisa.
                     */
                    ->mutateDataUsing(
                        function (
                            array $data
                        ): array {
                            $data['user_id'] =
                                $this->ownerId();

                            return $data;
                        }
                    ),
            ])
            ->actions([
                EditAction::make()
                    ->label('Uredi')
                    ->before(
                        EmployeeResource::beforeModulePermission(
                            'update'
                        )
                    )
                    ->mutateDataUsing(
                        function (
                            array $data
                        ): array {
                            /*
                             * Ownership se pri
                             * uređivanju ponovno
                             * prisilno vraća na
                             * vlasnika zaposlenika.
                             */
                            $data['user_id'] =
                                $this->ownerId();

                            return $data;
                        }
                    ),

                DeleteAction::make()
                    ->label('Obriši')
                    ->requiresConfirmation()
                    ->before(
                        EmployeeResource::beforeModulePermission(
                            'delete'
                        )
                    ),
            ]);
    }
}
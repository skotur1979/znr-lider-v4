<?php

namespace App\Filament\Resources\FirstAidKits;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\FirstAidKits\Pages;
use App\Models\FirstAidKit;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use UnitEnum;

class FirstAidKitResource extends BaseResource
{
    protected static ?string $model =
        FirstAidKit::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-briefcase';

    protected static string|UnitEnum|null $navigationGroup =
        'Ispitivanja';

    protected static ?string $pluralModelLabel =
        'Prva pomoć';

    protected static ?string $navigationLabel =
        'Prva pomoć - ormarići';

    protected static ?int $navigationSort = 3;

    protected static bool $hasOwnership = true;

    protected static function getModuleKey(): ?string
    {
        return 'first_aid';
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Prva pomoć')
                    ->columnSpanFull()
                    ->columns(1)
                    ->schema([
                        Section::make(
                            'Podaci o ormariću'
                        )
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                TextInput::make(
                                    'location'
                                )
                                    ->label(
                                        'Lokacija ormarića PP'
                                    )
                                    ->required()
                                    ->maxLength(255),

                                DatePicker::make(
                                    'inspected_at'
                                )
                                    ->label(
                                        'Pregled obavljen dana'
                                    )
                                    ->required()
                                    ->displayFormat(
                                        'd.m.Y.'
                                    )
                                    ->native(false),

                                Textarea::make(
                                    'note'
                                )
                                    ->label(
                                        'Napomena'
                                    )
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Section::make(
                            'Sadržaj ormarića prve pomoći'
                        )
                            ->columnSpanFull()
                            ->schema([
                                Repeater::make(
                                    'items'
                                )
                                    ->relationship()
                                    ->label(
                                        'Sanitetski materijal'
                                    )
                                    ->addActionLabel(
                                        'Dodaj stavku'
                                    )
                                    ->defaultItems(1)
                                    ->collapsible()
                                    ->itemLabel(
                                        function (
                                            array $state
                                        ): string {
                                            $material =
                                                $state[
                                                    'material_type'
                                                ]
                                                ?? null;

                                            $purpose =
                                                $state[
                                                    'purpose'
                                                ]
                                                ?? null;

                                            if (
                                                filled(
                                                    $material
                                                )
                                                && filled(
                                                    $purpose
                                                )
                                            ) {
                                                return
                                                    $material
                                                    . ' — '
                                                    . $purpose;
                                            }

                                            if (
                                                filled(
                                                    $material
                                                )
                                            ) {
                                                return $material;
                                            }

                                            return 'Nova stavka';
                                        }
                                    )
                                    ->schema([
                                        Section::make(
                                            'Stavka sanitetskog materijala'
                                        )
                                            ->columns(3)
                                            ->schema([
                                                TextInput::make(
                                                    'material_type'
                                                )
                                                    ->label(
                                                        'Vrsta sanitetskog materijala'
                                                    )
                                                    ->required()
                                                    ->maxLength(
                                                        255
                                                    ),

                                                TextInput::make(
                                                    'purpose'
                                                )
                                                    ->label(
                                                        'Namjena'
                                                    )
                                                    ->required()
                                                    ->maxLength(
                                                        255
                                                    ),

                                                DatePicker::make(
                                                    'valid_until'
                                                )
                                                    ->label(
                                                        'Vrijedi do'
                                                    )
                                                    ->displayFormat(
                                                        'd.m.Y.'
                                                    )
                                                    ->native(
                                                        false
                                                    ),
                                            ]),
                                    ]),
                            ]),
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
                100,
                'all',
            ])
            ->modifyQueryUsing(
                fn (Builder $query) =>
                    $query->with('items')
            )
            ->columns([
                TextColumn::make('location')
                    ->label(
                        'Lokacija ormarića'
                    )
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                static::userTableColumn(),

                TextColumn::make(
                    'inspected_at'
                )
                    ->label(
                        'Pregled obavljen'
                    )
                    ->date('d.m.Y.')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make(
                    'items_count'
                )
                    ->label(
                        'Ukupan broj stavki'
                    )
                    ->counts('items')
                    ->alignCenter()
                    ->toggleable(),

                ViewColumn::make(
                    'items_summary'
                )
                    ->label(
                        'Rok ističe/istekao'
                    )
                    ->alignCenter()
                    ->view(
                        'filament.resources.first-aid-kits.items-summary'
                    )
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make(
                    'expired_items'
                )
                    ->label('Stavke')
                    ->placeholder('Sve')
                    ->options([
                        'expired' =>
                            'Samo istekle stavke',
                    ])
                    ->query(
                        function (
                            Builder $query,
                            array $data
                        ): Builder {
                            return match (
                                $data['value']
                                ?? null
                            ) {
                                'expired' =>
                                    $query->whereHas(
                                        'items',
                                        function (
                                            Builder $subQuery
                                        ) {
                                            $subQuery
                                                ->whereNotNull(
                                                    'valid_until'
                                                )
                                                ->whereDate(
                                                    'valid_until',
                                                    '<',
                                                    now()
                                                        ->startOfDay()
                                                );
                                        }
                                    ),

                                default =>
                                    $query,
                            };
                        }
                    ),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Prikaz'),

                    Action::make('qrCode')
                        ->label('QR kod')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->url(
                            fn (
                                FirstAidKit $record
                            ): string =>
                                route(
                                    'first-aid-kit.qr.admin',
                                    [
                                        'firstAidKit' =>
                                            $record,
                                    ]
                                )
                        )
                        ->openUrlInNewTab(),

                    EditAction::make()
                        ->label('Uredi'),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->modalHeading(
                            'Obriši Prvu pomoć'
                        )
                        ->modalDescription(
                            'Jeste li sigurni da želite obrisati ovu Prvu pomoć?'
                        )
                        ->successNotificationTitle(
                            'Prva pomoć je obrisana.'
                        ),
                ]),
            ])
            ->bulkActions([
                BulkAction::make(
                    'copyAndCreateNew'
                )
                    ->label(
                        'Kopiraj i napravi novi'
                    )
                    ->icon(
                        'heroicon-o-document-duplicate'
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        'Kopiraj zapis prve pomoći'
                    )
                    ->modalDescription(
                        'Kopirat će se odabrani ormarić prve pomoći zajedno sa svim stavkama i otvoriti novi zapis za uređivanje.'
                    )
                    ->modalSubmitActionLabel(
                        'Kopiraj i otvori'
                    )
                    ->modalCancelActionLabel(
                        'Odustani'
                    )
                    ->action(
                        function (
                            EloquentCollection $records
                        ) {
                            if (! static::canCreate()) {
                                static::notifyMissingModulePermission();

                                return;
                            }

                            if (
                                $records->count() !== 1
                            ) {
                                Notification::make()
                                    ->title(
                                        'Odaberi samo jedan zapis'
                                    )
                                    ->body(
                                        'Za kopiranje može biti označen samo jedan ormarić prve pomoći.'
                                    )
                                    ->danger()
                                    ->send();

                                return;
                            }

                            /** @var FirstAidKit $record */
                            $record =
                                $records->first();

                            $record->loadMissing(
                                'items'
                            );

                            $newRecord =
                                $record->replicate([
                                    'items_count',
                                    'created_at',
                                    'updated_at',
                                ]);

                            /*
                             * Kopirani zapis ostaje
                             * unutar iste organizacije.
                             */
                            $newRecord->user_id =
                                $record->user_id;

                            $newRecord->save();

                            foreach (
                                $record->items
                                as $item
                            ) {
                                $newItem =
                                    $item->replicate([
                                        'first_aid_kit_id',
                                        'created_at',
                                        'updated_at',
                                    ]);

                                $newItem
                                    ->first_aid_kit_id =
                                    $newRecord->id;

                                $newItem->save();
                            }

                            Notification::make()
                                ->title(
                                    'Zapis je kopiran'
                                )
                                ->body(
                                    'Otvara se novi kopirani zapis prve pomoći za uređivanje.'
                                )
                                ->success()
                                ->send();

                            return redirect(
                                static::getUrl(
                                    'edit',
                                    [
                                        'record' =>
                                            $newRecord,
                                    ]
                                )
                            );
                        }
                    ),

                DeleteBulkAction::make()
                    ->label(
                        'Obriši označeno'
                    )
                    ->modalHeading(
                        'Obriši Prve pomoći'
                    )
                    ->modalDescription(
                        'Jeste li sigurni da želite obrisati odabrane zapise?'
                    )
                    ->successNotificationTitle(
                        'Prve pomoći su obrisane.'
                    ),
            ])
            ->defaultSort(
                'inspected_at',
                'desc'
            );
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                Pages\ListFirstAidKits::route(
                    '/'
                ),

            'create' =>
                Pages\CreateFirstAidKit::route(
                    '/create'
                ),

            'edit' =>
                Pages\EditFirstAidKit::route(
                    '/{record}/edit'
                ),

            'view' =>
                Pages\ViewFirstAidKit::route(
                    '/{record}'
                ),
        ];
    }
}

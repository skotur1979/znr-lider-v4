<?php

namespace App\Filament\Resources\WorkPermits;

use App\Filament\Resources\WorkPermits\Pages;
use App\Models\WorkPermit;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Str;
use UnitEnum;

class WorkPermitResource extends Resource
{
    protected static ?string $model = WorkPermit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-check';
    protected static string|UnitEnum|null $navigationGroup = 'Upravljanje';
    protected static ?string $navigationLabel = 'Dozvole za rad';
    protected static ?string $pluralModelLabel = 'Dozvole za rad';
    protected static ?string $modelLabel = 'Dozvola za rad';
    protected static ?int $navigationSort = 8;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    private static function isAdminUser($user): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $user->id === 1) {
            return true;
        }

        try {
            if (method_exists($user, 'getRoleNames')) {
                $roles = $user->getRoleNames()->toArray();

                foreach ($roles as $role) {
                    $name = trim((string) $role);

                    if (
                        Str::contains(Str::lower($name), 'admin') ||
                        in_array(Str::lower($name), ['administrator', 'super-admin', 'super admin', 'owner', 'root'])
                    ) {
                        return true;
                    }
                }
            }

            if (method_exists($user, 'hasAnyRole')) {
                if ($user->hasAnyRole([
                    'admin', 'Admin', 'administrator', 'Administrator',
                    'super-admin', 'Super Admin', 'owner', 'Owner', 'root', 'Root',
                ])) {
                    return true;
                }
            }

            if (method_exists($user, 'hasRole')) {
                foreach (['admin', 'Admin', 'administrator', 'Administrator', 'super-admin', 'Super Admin', 'owner', 'Owner', 'root', 'Root'] as $role) {
                    if ($user->hasRole($role)) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        if (isset($user->is_admin) && (bool) $user->is_admin) {
            return true;
        }

        try {
            if (method_exists($user, 'can') && $user->can('viewAny', \App\Models\WorkPermit::class)) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        return false;
    }

    public static function generateNextPermitNumber(): string
    {
        $year = now()->year;

        $last = WorkPermit::query()
            ->withTrashed()
            ->whereYear('created_at', $year)
            ->count();

        $next = $last + 1;

        return str_pad((string) $next, 2, '0', STR_PAD_LEFT) . '/' . $year;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Osnovni podaci')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('permit_number')
                        ->label('Broj')
                        ->default(fn () => self::generateNextPermitNumber())
                        ->required(),

                    DatePicker::make('issue_date')
                        ->label('Datum')
                        ->displayFormat('d.m.Y.')
                        ->default(now())
                        ->required(),

                    DateTimePicker::make('valid_from')
                        ->label('Vrijedi od')
                        ->seconds(false)
                        ->displayFormat('d.m.Y. H:i')
                        ->native(false),

                    DateTimePicker::make('valid_until')
                        ->label('Vrijedi do')
                        ->seconds(false)
                        ->displayFormat('d.m.Y. H:i')
                        ->native(false),
                ]),

            Section::make('Za poslove')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('work_types')
                        ->label('Vrsta poslova')
                        ->options(WorkPermit::workTypeOptions())
                        ->columns(5),

                    TextInput::make('other_work_type')
                    ->label('Ostalo')
                    ->maxLength(50)
                    ->rule('max:50')
                    ->extraAttributes(['maxlength' => 50])
                    ->live(onBlur: true)
                    ->helperText(fn ($state) => mb_strlen((string) $state) . '/50'),

                    Textarea::make('request_or_regulation')
                    ->label('Zahtjev / propis')
                    ->rows(2)
                    ->maxLength(150)
                    ->rule('max:150')
                    ->extraAttributes(['maxlength' => 150])
                    ->live(onBlur: true)
                    ->helperText(fn ($state) => mb_strlen((string) $state) . '/150'),
                ]),

            Section::make('Radove izvode')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('executor_types')
                        ->label('Izvođači')
                        ->options(WorkPermit::executorTypeOptions())
                        ->columns(2),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('worker_1')->label('Radnik 1'),
                            TextInput::make('worker_2')->label('Radnik 2'),
                            TextInput::make('worker_3')->label('Radnik 3'),
                            TextInput::make('worker_4')->label('Radnik 4'),
                            TextInput::make('worker_5')->label('Radnik 5'),
                            TextInput::make('worker_6')->label('Radnik 6'),
                            TextInput::make('worker_7')->label('Radnik 7'),
                            TextInput::make('worker_8')->label('Radnik 8'),
                            TextInput::make('worker_9')->label('Radnik 9'),
                        ]),

                    Textarea::make('work_description')
    ->label('Opis poslova - radova')
    ->rows(3)
    ->maxLength(300)
    ->rule('max:300')
    ->extraAttributes(['maxlength' => 300])
    ->live(onBlur: true)
    ->helperText(fn ($state) => mb_strlen((string) $state) . '/300'),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('contact_person')
                            ->label('Kontakt osoba')
                            ->maxLength(50)
                            ->rule('max:50')
                            ->extraAttributes(['maxlength' => 50])
                            ->live(onBlur: true)
                            ->helperText(fn ($state) => mb_strlen((string) $state) . '/50'),
                            TextInput::make('phone')->label('Telefonski broj'),
                        ]),
                ]),

            Section::make('Mjere')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('required_measures')
                        ->label('Mjere koje je potrebno poduzeti')
                        ->options(WorkPermit::requiredMeasuresOptions())
                        ->columns(2),

                    Textarea::make('additional_measures')
                    ->label('Dodatne mjere')
                    ->rows(2)
                    ->maxLength(200)
                    ->rule('max:200')
                    ->extraAttributes(['maxlength' => 200])
                    ->live(onBlur: true)
                    ->helperText(fn ($state) => mb_strlen((string) $state) . '/200'),

                    Textarea::make('required_equipment')
                        ->label('Potrebna oprema')
                        ->rows(2),
                ]),

            Section::make('Opasnosti rada')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('work_hazards')
                        ->label('Opasnosti')
                        ->options(WorkPermit::hazardOptions())
                        ->columns(3),

                    TextInput::make('other_hazard')
                    ->label('Ostalo')
                    ->maxLength(30)
                    ->rule('max:30')
                    ->extraAttributes(['maxlength' => 30])
                    ->live(onBlur: true)
                    ->helperText(fn ($state) => mb_strlen((string) $state) . '/30'),
                ]),

            Section::make('Osobna zaštitna oprema')
                ->columnSpanFull()
                ->schema([
                    CheckboxList::make('required_ppe')
                        ->label('OZO')
                        ->options(WorkPermit::ppeOptions())
                        ->columns(4),
                ]),

            Section::make('Odobrenje')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('requester_name')->label('Osoba koja zahtjeva dozvolu - ime i prezime'),
                    TextInput::make('requester_signature')->label('Osoba koja zahtjeva dozvolu - potpis'),
                    TextInput::make('approver_name')->label('Osoba koja odobrava dozvolu - ime i prezime'),
                    TextInput::make('approver_signature')->label('Osoba koja odobrava dozvolu - potpis'),
                ]),

            Section::make('Produženje valjanosti')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    DateTimePicker::make('extension_valid_from')
                        ->label('Vrijedi od')
                        ->seconds(false)
                        ->displayFormat('d.m.Y. H:i')
                        ->native(false),

                    DateTimePicker::make('extension_valid_until')
                        ->label('Vrijedi do')
                        ->seconds(false)
                        ->displayFormat('d.m.Y. H:i')
                        ->native(false),

                    TextInput::make('extension_approver_name')->label('Osoba koja odobrava produženje - ime i prezime'),
                    TextInput::make('extension_approver_signature')->label('Osoba koja odobrava produženje - potpis'),
                ]),

            Section::make('Provjera izvršenih radova')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Radio::make('works_finished')
                        ->label('Radovi su završeni')
                        ->boolean('DA', 'NE')
                        ->inline(),

                    Radio::make('checked_after')
                        ->label('Provjera provedena nakon')
                        ->options([
                            '1h' => '1 sata',
                            '3h' => '3 sata',
                        ])
                        ->inline(),

                    Textarea::make('unfinished_reason')
                    ->label('Ako nisu završeni navesti razlog')
                    ->rows(3)
                    ->maxLength(150)
                    ->rule('max:150')
                    ->extraAttributes(['maxlength' => 150])
                    ->live(onBlur: true)
                    ->helperText(fn ($state) => mb_strlen((string) $state) . '/150')
                    ->columnSpanFull(),

                    TextInput::make('verification_name')->label('Ime i prezime'),
                    TextInput::make('verification_signature')->label('Potpis'),
                    DatePicker::make('verification_date')->label('Datum')->displayFormat('d.m.Y.'),
                    TextInput::make('verification_time')->label('Vrijeme')->placeholder('14:30'),
                ]),
        ]);
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
{
    return $schema->components([]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('permit_number')
                    ->label('Broj')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->alignment(Alignment::Center),

                TextColumn::make('issue_date')
                    ->label('Datum')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('work_types')
                    ->label('Vrsta poslova')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('valid_from')
                    ->label('Vrijedi od')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable(),

                TextColumn::make('valid_until')
                    ->label('Vrijedi do')
                    ->dateTime('d.m.Y. H:i')
                    ->sortable(),

                TextColumn::make('work_types')
                    ->label('Vrsta poslova')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('works_finished')
                    ->label('Završeno')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        true => 'DA',
                        false => 'NE',
                        default => '-',
                    })
                    ->color(fn ($state) => match ($state) {
                        true => 'success',
                        false => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('issue_date', 'desc')
            ->recordUrl(fn (WorkPermit $record): string => static::getUrl('view', ['record' => $record]))
            ->filters([
                SelectFilter::make('status')
                    ->label('Status zapisa')
                    ->placeholder('Odaberi status')
                    ->options([
                        'active' => 'Aktivni zapisi',
                        'trashed' => 'Deaktivirani zapisi',
                        'all' => 'Svi zapisi',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'trashed' => $query->onlyTrashed(),
                            'all' => $query->withTrashed(),
                            default => $query->withoutTrashed(),
                        };
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),
                    EditAction::make()
                        ->label('Uredi')
                        ->visible(fn (WorkPermit $record) => ! (method_exists($record, 'trashed') && $record->trashed())),
                    DeleteAction::make()
                        ->label('Deaktiviraj')
                        ->requiresConfirmation()
                        ->visible(fn (WorkPermit $record) => ! (method_exists($record, 'trashed') && $record->trashed())),
                    RestoreAction::make()
                        ->label('Vrati')
                        ->requiresConfirmation()
                        ->visible(fn (WorkPermit $record) => method_exists($record, 'trashed') && $record->trashed()),
                    ForceDeleteAction::make()
                        ->label('Trajno obriši')
                        ->requiresConfirmation()
                        ->visible(fn (WorkPermit $record) => method_exists($record, 'trashed') && $record->trashed()),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->label(''),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Deaktiviraj označeno')
                    ->requiresConfirmation()
                    ->visible(fn (\Filament\Tables\Contracts\HasTable $livewire) => ! self::isOnlyTrashed($livewire)),

                RestoreBulkAction::make()
                    ->label('Vrati označeno')
                    ->requiresConfirmation()
                    ->visible(fn (\Filament\Tables\Contracts\HasTable $livewire) => self::isOnlyTrashed($livewire)),

                ForceDeleteBulkAction::make()
                    ->label('Trajno obriši označeno')
                    ->requiresConfirmation(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkPermits::route('/'),
            'create' => Pages\CreateWorkPermit::route('/create'),
            'edit' => Pages\EditWorkPermit::route('/{record}/edit'),
            'view' => Pages\ViewWorkPermit::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1=0');
        }

        return self::isAdminUser($user)
            ? $query
            : $query->where('user_id', $user->id);
    }

    public static function getNavigationBadge(): ?string
    {
        try {
            if (! DbSchema::hasTable('work_permits')) {
                return null;
            }

            $user = auth()->user();

            if (! $user) {
                return '0';
            }

            $query = static::getModel()::query();

            if (! self::isAdminUser($user)) {
                $query->where('user_id', $user->id);
            }

            return (string) $query->count();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    private static function isOnlyTrashed(\Filament\Tables\Contracts\HasTable $livewire): bool
    {
        $state = $livewire->getTableFilterState('status');
        $value = data_get($state, 'value');

        return $value === 'trashed';
    }
}
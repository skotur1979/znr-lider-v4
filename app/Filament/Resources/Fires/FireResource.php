<?php

namespace App\Filament\Resources\Fires;

use App\Filament\Resources\Fires\Pages;
use App\Models\Fire;
use App\Support\ExpiryBadge;

use Filament\Resources\Resource;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;

use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;

use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class FireResource extends Resource
{
    protected static ?string $model = Fire::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?string $navigationLabel = 'Vatrogasni aparati';
    protected static ?string $modelLabel = 'Vatrogasni aparat';
    protected static ?string $pluralModelLabel = 'Vatrogasni aparati';

    protected static \UnitEnum|string|null $navigationGroup = 'Ispitivanja';
    protected static ?int $navigationSort = 2;

    /** ✅ Create/Edit (Schema API kao Machine) */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->dehydrated(),

            Section::make('Podatci o vatrogasnom aparatu')
                ->schema([
                    TextInput::make('place')
                        ->label('Mjesto gdje se aparat nalazi (obavezno)')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('type')
                        ->label('Tip aparata')
                        ->maxLength(255),

                    TextInput::make('factory_number_year_of_production')
    ->label('Tvornički broj/Godina proizvodnje')
    ->maxLength(255)
    // ✅ kad se učitava Edit forma, povuci iz stvarnog DB stupca
    ->formatStateUsing(fn ($record) => $record?->getAttribute('factory_number/year_of_production'))
    // ✅ kad se sprema, upiši u stvarni DB stupac
    ->dehydrateStateUsing(fn ($state) => $state)
    ->saveRelationshipsUsing(null), // nije relacija, čisto da ne pokušava gluposti

                    TextInput::make('serial_label_number')
                        ->label('Serijski broj evidencijske naljepnice')
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Ispitivanje vatrogasnog aparata')
                ->schema([
                    DatePicker::make('examination_valid_from')
                        ->label('Datum periodičkog servisa (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->native(false),

                    DatePicker::make('examination_valid_until')
                        ->label('Vrijedi do (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->native(false),

                    TextInput::make('service')
                        ->label('Naziv servisera koji je servisirao aparat')
                        ->maxLength(255),

                    DatePicker::make('regular_examination_valid_from')
                        ->label('Datum redovnog pregleda (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->native(false),
                ])
                ->columns(2),

            Section::make('Ostalo')
                ->schema([
                    TextInput::make('visible')
                        ->label('Uočljivost i dostupnost aparata')
                        ->maxLength(255),

                    TextInput::make('remark')
                        ->label('Uočeni nedostatci')
                        ->maxLength(255),

                    TextInput::make('action')
                        ->label('Postupci otklanjanja')
                        ->maxLength(255),
                ])
                ->columns(2),

            Section::make('Prilozi')
                ->schema([
                    FileUpload::make('pdf')
                        ->label('Dodaj Prilog (max. 10)')
                        ->disk('public')
                        ->directory('fires')
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(30720)
                        ->preserveFilenames()
                        ->enableOpen()
                        ->enableDownload()
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'application/zip',
                            'application/x-rar-compressed',
                        ]),
                ]),
        ]);
    }

    /** ✅ View (Pregled) – Schema-based infolist */
public static function infolist(Schema $schema): Schema
{
    return $schema->components([
        Section::make('Podatci o vatrogasnom aparatu')
            ->components([
                TextEntry::make('place')->label('Mjesto gdje se aparat nalazi'),
                TextEntry::make('type')->label('Tip aparata'),
                TextEntry::make('factory_number_year_of_production')->label('Tvornički broj/Godina proizvodnje'),
                TextEntry::make('serial_label_number')->label('Serijski broj evidencijske naljepnice'),
            ])
            ->columns(2),

        Section::make('Ispitivanje vatrogasnog aparata')
            ->components([
                TextEntry::make('examination_valid_from')->label('Datum periodičkog servisa')->date('d.m.Y.'),
                TextEntry::make('examination_valid_until')->label('Vrijedi do')->date('d.m.Y.'),
                TextEntry::make('service')->label('Naziv servisera'),
                TextEntry::make('regular_examination_valid_from')->label('Datum redovnog pregleda')->date('d.m.Y.'),
            ])
            ->columns(2),

        Section::make('Ostalo')
            ->components([
                TextEntry::make('visible')->label('Uočljivost i dostupnost aparata'),
                TextEntry::make('remark')->label('Uočeni nedostatci'),
                TextEntry::make('action')->label('Postupci otklanjanja'),
            ])
            ->columns(2),
    ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('place')
                    ->label('Mjesto gdje se aparat nalazi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Tip aparata')
                    ->alignment(Alignment::Center)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('factory_number_year_of_production')
                    ->label('Tvor.broj/Godina proizv.')
                    ->alignment(Alignment::Center)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('examination_valid_from')
                    ->label('Datum ispitivanja')
                    ->alignment(Alignment::Center)
                    ->date('d.m.Y.')
                    ->sortable(),

                TextColumn::make('examination_valid_until')
                    ->label('Ispitivanje vrijedi do')
                    ->alignment(Alignment::Center)
                    ->date('d.m.Y.')
                    ->badge()
                    ->color(fn ($state) => ExpiryBadge::color($state))
                    ->icon(fn ($state) => ExpiryBadge::icon($state))
                    ->tooltip(fn ($state) => ExpiryBadge::tooltip($state))
                    ->sortable(),

                TextColumn::make('regular_examination_valid_from')
                    ->label('Datum redovnog pregleda')
                    ->alignment(Alignment::Center)
                    ->date('d.m.Y.')
                    ->sortable(),
    
            ])
            ->filters([
                TrashedFilter::make(),

                Filter::make('examination_validity_expired')
                    ->label('Ispitivanje (isteklo)')
                    ->query(fn (Builder $query) => $query->whereDate('examination_valid_until', '<', Carbon::today())),

                Filter::make('examination_validity_expiring')
                    ->label('Ispitivanje (uskoro ističe)')
                    ->query(fn (Builder $query) => $query
                        ->whereDate('examination_valid_until', '>=', Carbon::today())
                        ->whereDate('examination_valid_until', '<=', Carbon::today()->addDays(30))
                    ),
                   ])
        ->paginated([10, 25, 50, 'all']) // ✅ dodano "all" 
            
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()->requiresConfirmation(),
                    RestoreAction::make()->requiresConfirmation(),
                    ForceDeleteAction::make()->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        if (Auth::user()?->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::id());
    }

    public static function getNavigationBadge(): ?string
    {
        $q = static::getModel()::query();

        if (! Auth::user()?->isAdmin()) {
            $q->where('user_id', Auth::id());
        }

        return (string) $q->count();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFires::route('/'),
            'create' => Pages\CreateFire::route('/create'),
            'view'   => Pages\ViewFire::route('/{record}'),
            'edit'   => Pages\EditFire::route('/{record}/edit'),
        ];
    }
}
<?php

namespace App\Filament\Resources\RiskAssessments;

use App\Filament\Resources\RiskAssessments\Pages;
use App\Filament\Resources\RiskAssessments\Schemas\RiskAssessmentInfolist;
use App\Models\RiskAssessment;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

use Filament\Resources\Resource;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RiskAssessmentResource extends Resource
{
    protected static ?string $model = RiskAssessment::class;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedClipboard;

    protected static ?string $navigationLabel = 'Procjene rizika';
    protected static ?string $modelLabel = 'Procjena rizika';
    protected static ?string $pluralModelLabel = 'Procjene rizika';

    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'tvrtka';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([

            Select::make('user_id')
                ->label('Korisnik')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn () => Auth::user()?->isAdmin())
                ->dehydrated(fn () => Auth::user()?->isAdmin()),

            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->visible(fn () => ! Auth::user()?->isAdmin())
                ->dehydrated(fn () => ! Auth::user()?->isAdmin()),

            // 🔷 PODACI O PROCJENI
            Section::make('Podaci o procjeni rizika')
                ->columns(3)
                ->collapsible()
                ->schema([
                    TextInput::make('tvrtka')
                        ->label('Tvrtka')
                        ->required(),

                    TextInput::make('oib_tvrtke')
                        ->label('OIB tvrtke')
                        ->required()
                        ->maxLength(11)
                        ->rule('digits:11')
                        ->validationMessages([
                            'required' => 'OIB tvrtke je obavezan.',
                            'digits' => 'OIB mora imati točno 11 znamenki.',
                        ]),

                    TextInput::make('adresa_tvrtke')
                        ->label('Adresa tvrtke'),

                    TextInput::make('broj_procjene')
                        ->label('Broj procjene')
                        ->required(),

                    DatePicker::make('datum_izrade')
                        ->label('Datum izrade')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    TextInput::make('vrsta_procjene')
                        ->label('Vrsta procjene rizika')
                        ->required(),
                ]),

            // 👷 SUDIONICI
            Section::make('Sudionici izrade')
                ->collapsible()
                ->schema([
                    Repeater::make('participants')
                        ->relationship('participants')
                        ->label('Sudionici izrade')
                        ->columns(3)
                        ->schema([
                            TextInput::make('ime_prezime')
                                ->label('Ime i prezime'),

                            TextInput::make('uloga')
                                ->label('Uloga'),

                            Textarea::make('napomena')
                                ->label('Napomena')
                                ->rows(1),
                        ])
                        ->collapsible(),
                ]),

            // 🔁 REVIZIJE
            Section::make('Revizije Procjene Rizika')
                ->collapsible()
                ->schema([
                    Repeater::make('revisions')
                        ->relationship('revisions')
                        ->label('Revizije')
                        ->columns(2)
                        ->schema([
                            TextInput::make('revizija_broj')
                                ->label('Revizija broj'),

                            DatePicker::make('datum_izrade')
                                ->label('Datum izrade')
                                ->displayFormat('d.m.Y.')
                                ->weekStartsOnMonday()
                                ->timezone('Europe/Zagreb'),
                        ])
                        ->collapsible(),
                ]),

            // 📎 PRILOZI
            Section::make('Prilozi')
                ->collapsible()
                ->schema([
                    Repeater::make('attachments')
                        ->relationship('attachments')
                        ->label('Prilozi')
                        ->columns(2)
                        ->schema([
                            TextInput::make('naziv')
                                ->label('Naziv dokumenta')
                                ->required(),

                            FileUpload::make('file_path')
                                ->label('Dokument')
                                ->disk('public')
                                ->directory('risk-assessments/attachments')
                                ->visibility('public')
                                ->preserveFilenames()
                                ->openable()
                                ->downloadable()
                                ->maxSize(30720)
                                ->required(),
                        ])
                        ->collapsible(),
                ]),
        ]);
    }

    /**
     * ✅ VIEW (Pregled) - koristi Schemas/RiskAssessmentInfolist.php
     */
    public static function infolist(Schema $schema): Schema
    {
        return RiskAssessmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tvrtka')
                    ->label('Tvrtka')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('broj_procjene')
                    ->label('Broj procjene')
                    ->alignment(Alignment::Center)
                    ->sortable(),

                TextColumn::make('datum_izrade')
                    ->label('Datum izrade')
                    ->date('d.m.Y.')
                    ->alignment(Alignment::Center)
                    ->sortable(),

                TextColumn::make('vrsta_procjene')
                    ->label('Vrsta procjene')
                    ->alignment(Alignment::Center)
                    ->searchable(),

                TextColumn::make('revisions_count')
                    ->label('Broj revizija')
                    ->alignment(Alignment::Center)
                    ->counts('revisions'),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),
                    EditAction::make()->label('Uredi'),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->requiresConfirmation()
                        ->modalHeading('Obriši Procjenu rizika')
                        ->modalDescription('Jeste li sigurni da želite obrisati ovu Procjenu rizika?')
                        ->modalSubmitActionLabel('Obriši')
                        ->modalCancelActionLabel('Odustani'),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->label(''),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->requiresConfirmation()
                    ->modalHeading('Obriši odabrano')
                    ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
                    ->modalSubmitActionLabel('Obriši')
                    ->modalCancelActionLabel('Odustani'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['participants', 'revisions', 'attachments']);

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
            'index'  => Pages\ListRiskAssessments::route('/'),
            'create' => Pages\CreateRiskAssessment::route('/create'),
            'edit'   => Pages\EditRiskAssessment::route('/{record}/edit'),
            'view'   => Pages\ViewRiskAssessment::route('/{record}'),
        ];
    }
}
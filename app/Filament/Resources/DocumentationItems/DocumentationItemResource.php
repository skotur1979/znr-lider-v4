<?php

namespace App\Filament\Resources\DocumentationItems;

use App\Filament\Resources\DocumentationItems\Pages;
use App\Models\DocumentationItem;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

use Filament\Actions\DeleteBulkAction;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentationItemResource extends Resource
{
    protected static ?string $model = DocumentationItem::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Dokumentacija';
    protected static ?string $modelLabel = 'Dokumentacija';
    protected static ?string $pluralModelLabel = 'Dokumentacija';

    protected static \UnitEnum|string|null $navigationGroup = 'Upravljanje';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'naziv';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            // ADMIN može birati usera
            Select::make('user_id')
                ->label('Korisnik')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->visible(fn () => Auth::user()?->isAdmin())
                ->dehydrated(fn () => Auth::user()?->isAdmin()),

            // OSTALI dobiju svoj user_id automatski
            Hidden::make('user_id')
                ->default(fn () => Auth::id())
                ->visible(fn () => ! Auth::user()?->isAdmin())
                ->dehydrated(fn () => ! Auth::user()?->isAdmin()),

            Section::make('Dokument')
                ->columns(2)
                ->schema([
                    TextInput::make('naziv')
                        ->label('Naziv dokumenta')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('tvrtka')
                        ->label('Tvrtka')
                        ->maxLength(255),

                    DatePicker::make('datum_izrade')
                        ->label('Datum izrade')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    TextInput::make('status_napomena')
                        ->label('Status/Napomena')
                        ->maxLength(255),
                ]),

            FileUpload::make('prilozi')
                ->label('Dodaj priloge (max. 5, do 30 MB po datoteci)')
                ->disk('public')
                ->directory('pdfs')
                ->multiple()
                ->maxFiles(5)
                ->maxSize(30720)
                ->preserveFilenames()
                ->openable()
                ->downloadable()
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('naziv')
                    ->label('Naziv')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                TextColumn::make('tvrtka')
                    ->label('Tvrtka')
                    ->sortable()
                    ->searchable()
                    ->alignment(Alignment::Center),

                TextColumn::make('datum_izrade')
                    ->label('Datum izrade')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('status_napomena')
                    ->label('Status/Napomena')
                    ->wrap()
                    ->alignment(Alignment::Center),

                TextColumn::make('prilozi')
                    ->label('Prilozi')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->icon(fn (DocumentationItem $record) => is_array($record->prilozi) && count($record->prilozi) ? Heroicon::PaperClip : null)
                    ->color(fn (DocumentationItem $record) => is_array($record->prilozi) && count($record->prilozi) ? 'info' : 'gray')
                    ->formatStateUsing(fn ($state, DocumentationItem $record) => is_array($record->prilozi) ? (string) count($record->prilozi) : '0')
                    ->tooltip(fn (DocumentationItem $record) => is_array($record->prilozi) && count($record->prilozi)
                        ? implode("\n", $record->prilozi)
                        : 'Nema priloga'),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Prikaži'),

                    EditAction::make()->label('Uredi'),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->requiresConfirmation()
                        ->modalHeading('Obriši dokumentaciju')
                        ->modalDescription('Jesi li siguran/a da želiš to učiniti?')
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
        $query = parent::getEloquentQuery();

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
            'index'  => Pages\ListDocumentationItems::route('/'),
            'create' => Pages\CreateDocumentationItem::route('/create'),
            'edit'   => Pages\EditDocumentationItem::route('/{record}/edit'),
            'view'   => Pages\ViewDocumentationItem::route('/{record}'),
        ];
    }
}
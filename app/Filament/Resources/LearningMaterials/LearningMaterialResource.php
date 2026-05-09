<?php

namespace App\Filament\Resources\LearningMaterials;

use App\Filament\Resources\BaseResource;
use App\Filament\Resources\LearningMaterials\Pages;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload as FormFileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class LearningMaterialResource extends BaseResource
{
    protected static ?string $model = LearningMaterial::class;

    protected static bool $hasOwnership = false;

    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static \UnitEnum|string|null $navigationGroup = 'Edukacija';

    protected static ?string $navigationLabel = 'Edukacijski centar';
    protected static ?string $modelLabel = 'Edukacijski materijal';
    protected static ?string $pluralModelLabel = 'Edukacijski centar';

    protected static ?int $navigationSort = 2;

    protected static function getModuleKey(): ?string
    {
        return null;
    }

    public static function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('user_id')
                    ->label('Korisnik')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn (string $operation): bool => static::isSuperAdmin() && $operation === 'create')
                    ->dehydrated(fn (string $operation): bool => static::isSuperAdmin() && $operation === 'create'),

                Hidden::make('user_id')
                    ->default(fn () => static::ownerId())
                    ->visible(fn (string $operation): bool => ! static::isSuperAdmin() && $operation === 'create')
                    ->dehydrated(fn (string $operation): bool => ! static::isSuperAdmin() && $operation === 'create'),

                Section::make('Edukacijski materijal')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Section::make('Osnovni podaci')
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Naziv materijala')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('learning_category_id')
                                    ->label('Kategorija')
                                    ->options(fn () => static::categoryOptions())
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                CheckboxList::make('content_types')
                                    ->label('Sadržaj uključuje')
                                    ->options([
                                        'document' => '📄 Dokument',
                                        'video' => '🎥 Video',
                                        'website' => '🌐 Stručni link / web stranica',
                                        'instruction' => '📘 Uputa',
                                        'other' => '📚 Ostalo',
                                    ])
                                    ->columns(3)
                                    ->bulkToggleable()
                                    ->required()
                                    ->helperText('Možeš označiti više opcija. Npr. ako materijal ima Napo video i PDF dokument, označi Video i Dokument.')
                                    ->columnSpanFull(),

                                Textarea::make('description')
                                    ->label('Kratki opis')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Section::make('Postavke prikaza')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('sort_order')
                                            ->label('Redoslijed')
                                            ->numeric()
                                            ->default(0),

                                        Toggle::make('is_global')
                                            ->label('Globalni materijal')
                                            ->helperText('Globalne materijale vide svi korisnici.')
                                            ->visible(fn () => static::isSuperAdmin()),

                                        Toggle::make('is_active')
                                            ->label('Aktivno')
                                            ->default(true),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Linkovi i dokumenti')
                            ->columns(1)
                            ->schema([
                                Placeholder::make('info')
                                    ->label('Napomena')
                                    ->content('Možeš dodati više linkova i više dokumenata. Video se ne uploada već ide kao link, npr. YouTube, Napo i sl.'),

                                Repeater::make('links')
                                    ->label('Linkovi')
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Naziv linka')
                                            ->placeholder('npr. Napo film, EU-OSHA, interna stranica...')
                                            ->maxLength(255),

                                        TextInput::make('url')
                                            ->label('Link')
                                            ->url()
                                            ->placeholder('https://...')
                                            ->maxLength(2048),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->addActionLabel('Dodaj link')
                                    ->collapsible()
                                    ->columnSpanFull(),

                                FormFileUpload::make('files')
                                    ->label('Dokumenti / materijali')
                                    ->disk('public')
                                    ->directory('learning-materials')
                                    ->visibility('public')
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->maxSize(30720)
                                    ->preserveFilenames()
                                    ->openable()
                                    ->downloadable()
                                    ->previewable()
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/vnd.ms-powerpoint',
                                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                        'image/jpeg',
                                        'image/png',
                                        'image/gif',
                                        'image/webp',
                                        'application/zip',
                                        'application/x-rar-compressed',
                                    ])
                                    ->helperText('Možeš dodati najviše 10 dokumenata. Svaki dokument može biti maksimalno 30 MB. Video datoteke se ne uploadaju.')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')
                    ->label('Materijal')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->wrap()
                    ->description(fn (LearningMaterial $record) => $record->description ? str($record->description)->limit(90) : null),

                TextColumn::make('category.name')
                    ->label('Kategorija')
                    ->badge()
                    ->sortable()
                    ->alignment(Alignment::Center),

                TextColumn::make('content_types')
                    ->label('Sadržaj')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(function ($state, LearningMaterial $record) {
                        $types = is_array($record->content_types) && count($record->content_types)
                            ? $record->content_types
                            : [$record->type ?: 'document'];

                        return collect($types)
                            ->map(fn ($type) => match ($type) {
                                'document' => 'Dokument',
                                'video' => 'Video',
                                'website' => 'Stručna stranica',
                                'instruction' => 'Uputa',
                                'other' => 'Ostalo',
                                default => 'Materijal',
                            })
                            ->implode(', ');
                    })
                    ->color('info'),

                TextColumn::make('sources')
                    ->label('Materijali')
                    ->html()
                    ->alignment(Alignment::Center)
                    ->state(function (LearningMaterial $record): string {
                        $linksCount = collect($record->links ?? [])
                            ->filter(fn ($item) => ! blank($item['url'] ?? null))
                            ->count();

                        if (! blank($record->url)) {
                            $linksCount++;
                        }

                        $filesCount = collect($record->files ?? [])
                            ->filter()
                            ->count();

                        if (! blank($record->file_path)) {
                            $filesCount++;
                        }

                        $html = '';

                        if ($linksCount > 0) {
                            $html .= '<span style="
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                                min-width:28px;
                                height:24px;
                                padding:0 8px;
                                margin:1px 2px;
                                border-radius:7px;
                                background:rgba(245,158,11,.18);
                                border:1px solid rgba(245,158,11,.38);
                                color:#fbbf24;
                                font-size:12px;
                                font-weight:700;
                            ">🔗 ' . $linksCount . '</span>';
                        }

                        if ($filesCount > 0) {
                            $html .= '<span style="
                                display:inline-flex;
                                align-items:center;
                                justify-content:center;
                                min-width:28px;
                                height:24px;
                                padding:0 8px;
                                margin:1px 2px;
                                border-radius:7px;
                                background:rgba(16,185,129,.18);
                                border:1px solid rgba(16,185,129,.38);
                                color:#6ee7b7;
                                font-size:12px;
                                font-weight:700;
                            ">📄 ' . $filesCount . '</span>';
                        }

                        return $html ?: '<span style="color:#6b7280;">0</span>';
                    }),

                IconColumn::make('is_global')
                    ->label('Globalno')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Aktivno')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dodano')
                    ->date('d.m.Y.')
                    ->sortable()
                    ->alignment(Alignment::Center),
            ])
            ->filters([
                SelectFilter::make('learning_category_id')
                    ->label('Kategorija')
                    ->options(fn () => static::categoryOptions()),

                SelectFilter::make('content_type')
                    ->label('Vrsta')
                    ->options([
                        'document' => 'Dokument',
                        'video' => 'Video',
                        'website' => 'Stručna stranica',
                        'instruction' => 'Uputa',
                        'other' => 'Ostalo',
                    ])
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($value) {
                            $q->whereJsonContains('content_types', $value)
                                ->orWhere('type', $value);
                        });
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('open_first_link')
                        ->label('Otvori prvi link')
                        ->icon(Heroicon::ArrowTopRightOnSquare)
                        ->color('info')
                        ->url(fn (LearningMaterial $record) => static::firstLinkUrl($record))
                        ->openUrlInNewTab()
                        ->visible(fn (LearningMaterial $record) => filled(static::firstLinkUrl($record))),

                    Action::make('open_first_file')
                        ->label('Otvori prvi dokument')
                        ->icon(Heroicon::DocumentArrowDown)
                        ->color('success')
                        ->url(fn (LearningMaterial $record) => static::firstFileUrl($record))
                        ->openUrlInNewTab()
                        ->visible(fn (LearningMaterial $record) => filled(static::firstFileUrl($record))),

                    ViewAction::make()
                        ->label('Prikaži'),

                    EditAction::make()
                        ->label('Uredi'),

                    DeleteAction::make()
                        ->label('Obriši')
                        ->requiresConfirmation(),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->label(''),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Obriši označeno')
                    ->requiresConfirmation(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getModel()::query()
            ->with(['category', 'user']);

        if (static::isSuperAdmin()) {
            return $query;
        }

        $ownerId = static::ownerId();

        return $query->where(function (Builder $q) use ($ownerId) {
            $q->where('is_global', true)
                ->orWhere('user_id', $ownerId);
        });
    }

    protected static function categoryOptions(): array
    {
        $ownerId = static::ownerId();

        return LearningCategory::query()
            ->where('is_active', true)
            ->where(function (Builder $q) use ($ownerId) {
                if (static::isSuperAdmin()) {
                    return;
                }

                $q->where('is_global', true)
                    ->orWhere('user_id', $ownerId);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    protected static function firstLinkUrl(LearningMaterial $record): ?string
    {
        if (! blank($record->url)) {
            return $record->url;
        }

        $first = collect($record->links ?? [])
            ->first(fn ($item) => ! blank($item['url'] ?? null));

        return $first['url'] ?? null;
    }

    protected static function firstFileUrl(LearningMaterial $record): ?string
    {
        if (! blank($record->file_path)) {
            return Storage::disk('public')->url($record->file_path);
        }

        $first = collect($record->files ?? [])
            ->filter()
            ->first();

        return $first ? Storage::disk('public')->url($first) : null;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! static::isSuperAdmin()) {
            $data['user_id'] = static::ownerId();
            $data['is_global'] = false;
        }

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (! static::isSuperAdmin()) {
            $data['user_id'] = static::ownerId();
            $data['is_global'] = false;
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLearningMaterials::route('/'),
            'create' => Pages\CreateLearningMaterial::route('/create'),
            'view' => Pages\ViewLearningMaterial::route('/{record}'),
            'edit' => Pages\EditLearningMaterial::route('/{record}/edit'),
        ];
    }
}
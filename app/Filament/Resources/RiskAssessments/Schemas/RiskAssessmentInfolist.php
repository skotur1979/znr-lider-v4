<?php

namespace App\Filament\Resources\RiskAssessments\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class RiskAssessmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Procjena rizika')
                ->tabs([
                    Tab::make('Osnovno')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Section::make('Podaci o procjeni rizika')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('tvrtka')
                                                ->label('Tvrtka')
                                                ->weight('bold'),

                                            TextEntry::make('oib_tvrtke')
                                                ->label('OIB tvrtke')
                                                ->copyable(),

                                            TextEntry::make('adresa_tvrtke')
                                                ->label('Adresa tvrtke')
                                                ->columnSpanFull(),

                                            TextEntry::make('broj_procjene')
                                                ->label('Broj procjene')
                                                ->badge()
                                                ->color('warning'),

                                            TextEntry::make('datum_izrade')
                                                ->label('Datum izrade')
                                                ->date('d.m.Y.'),

                                            TextEntry::make('vrsta_procjene')
                                                ->label('Vrsta procjene rizika')
                                                ->badge()
                                                ->color('gray'),
                                        ]),

                                    Section::make('Sažetak')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('participants_count')
                                                ->label('Sudionika')
                                                ->state(fn ($record) => $record->participants?->count() ?? 0)
                                                ->badge()
                                                ->color('info'),

                                            TextEntry::make('revisions_count')
                                                ->label('Revizija')
                                                ->state(fn ($record) => $record->revisions?->count() ?? 0)
                                                ->badge()
                                                ->color('warning'),

                                            TextEntry::make('attachments_count')
                                                ->label('Priloga')
                                                ->state(fn ($record) => $record->attachments?->count() ?? 0)
                                                ->badge()
                                                ->color('success'),

                                            TextEntry::make('status_prikaza')
                                                ->label('Status')
                                                ->state('Aktivno')
                                                ->badge()
                                                ->color('success'),
                                        ]),
                                ]),
                        ]),

                    Tab::make('Sudionici izrade')
                        ->schema([
                            Section::make('Sudionici izrade')
                                ->schema([
                                    RepeatableEntry::make('participants')
                                        ->label('')
                                        ->columns(3)
                                        ->schema([
                                            TextEntry::make('ime_prezime')
                                                ->label('Ime i prezime')
                                                ->weight('bold')
                                                ->placeholder('—'),

                                            TextEntry::make('uloga')
                                                ->label('Uloga')
                                                ->badge()
                                                ->color('gray')
                                                ->placeholder('—'),

                                            TextEntry::make('napomena')
                                                ->label('Napomena')
                                                ->placeholder('—'),
                                        ]),
                                ]),
                        ]),

                    Tab::make('Revizije')
                        ->schema([
                            Section::make('Revizije procjene rizika')
                                ->schema([
                                    RepeatableEntry::make('revisions')
                                        ->label('')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('revizija_broj')
                                                ->label('Revizija broj')
                                                ->badge()
                                                ->color('warning')
                                                ->placeholder('—'),

                                            TextEntry::make('datum_izrade')
                                                ->label('Datum izrade')
                                                ->date('d.m.Y.')
                                                ->placeholder('—'),
                                        ]),
                                ]),
                        ]),

                    Tab::make('Prilozi')
                        ->schema([
                            Section::make('Prilozi')
                                ->schema([
                                    RepeatableEntry::make('attachments')
                                        ->label('')
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('naziv')
                                                ->label('Naziv dokumenta')
                                                ->weight('bold')
                                                ->placeholder('—'),

                                            TextEntry::make('file_path')
                                                ->label('Dokument')
                                                ->formatStateUsing(fn (?string $state) => $state ? basename($state) : '—')
                                                ->url(fn (?string $state) => $state ? asset('storage/' . ltrim($state, '/')) : null, true)
                                                ->openUrlInNewTab()
                                                ->badge()
                                                ->color('info'),
                                        ]),
                                ]),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
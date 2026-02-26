<?php

namespace App\Filament\Resources\RiskAssessments\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section; // ✅ OVO je ispravno (nije Infolists Section)
use Filament\Schemas\Schema;

class RiskAssessmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // GORE: 2 stupca (lijevo podaci, desno sudionici)
            Grid::make(2)->schema([
                Section::make('Podaci o procjeni rizika')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('tvrtka')->label('Tvrtka'),
                        TextEntry::make('oib_tvrtke')->label('OIB tvrtke'),
                        TextEntry::make('adresa_tvrtke')->label('Adresa tvrtke'),

                        TextEntry::make('broj_procjene')->label('Broj procjene'),
                        TextEntry::make('datum_izrade')->label('Datum izrade')->date('d.m.Y.'),
                        TextEntry::make('vrsta_procjene')->label('Vrsta procjene rizika'),
                    ]),

                Section::make('Sudionici izrade')
                    ->schema([
                        RepeatableEntry::make('participants')
                            ->label('')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('ime_prezime')->label('Ime i prezime'),
                                TextEntry::make('uloga')->label('Uloga'),
                                TextEntry::make('napomena')->label('Napomena'),
                            ]),
                    ]),
            ]),

            // ISPOD: 2 stupca (lijevo revizije, desno prilozi)
            Grid::make(2)->schema([
                Section::make('Revizije Procjene Rizika')
                    ->schema([
                        RepeatableEntry::make('revisions')
                            ->label('')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('revizija_broj')->label('Revizija broj'),
                                TextEntry::make('datum_izrade')->label('Datum izrade')->date('d.m.Y.'),
                            ]),
                    ]),

                Section::make('Prilozi')
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->label('')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('naziv')->label('Naziv dokumenta'),

                                TextEntry::make('file_path')
                                    ->label('Dokument')
                                    ->formatStateUsing(fn (?string $state) => $state ? basename($state) : '—')
                                    ->url(fn (?string $state) => $state ? asset('storage/' . ltrim($state, '/')) : null, true)
                                    ->openUrlInNewTab()
                                    ->badge(),
                            ]),
                    ]),
            ]),
        ]);
    }
}

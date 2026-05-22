<?php

namespace App\Filament\Resources\LegalAcceptances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LegalAcceptanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Korisnik')
                ->columns(2)
                ->schema([
                    TextEntry::make('user_name')
                        ->label('Ime i prezime'),

                    TextEntry::make('user_email')
                        ->label('E-mail'),

                    TextEntry::make('organization_name')
                        ->label('Organizacija'),

                    TextEntry::make('accepted_at')
                        ->label('Datum prihvaćanja')
                        ->dateTime('d.m.Y. H:i'),
                ]),

            Section::make('Prihvaćeni dokumenti')
                ->columns(2)
                ->schema([
                    TextEntry::make('terms_version')
                        ->label('Verzija uvjeta korištenja'),

                    TextEntry::make('privacy_version')
                        ->label('Verzija pravila privatnosti'),

                    TextEntry::make('newsletter_opt_in')
                        ->label('Newsletter')
                        ->formatStateUsing(fn ($state): string => $state ? 'Da' : 'Ne'),
                ]),

            Section::make('Tehnički podaci')
                ->columns(1)
                ->schema([
                    TextEntry::make('ip_address')
                        ->label('IP adresa'),

                    TextEntry::make('user_agent')
                        ->label('Preglednik / uređaj')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
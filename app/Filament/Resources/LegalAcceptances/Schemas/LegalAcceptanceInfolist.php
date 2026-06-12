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
                        ->label('Organizacija')
                        ->placeholder('-'),

                    TextEntry::make('accepted_at')
                        ->label('Datum prihvaćanja')
                        ->dateTime('d.m.Y. H:i'),
                ]),

            Section::make('Prihvaćeni pravni dokumenti')
                ->columns(2)
                ->schema([
                    TextEntry::make('terms_version')
                        ->label('Uvjeti korištenja'),

                    TextEntry::make('privacy_version')
                        ->label('Pravila privatnosti'),

                    TextEntry::make('cookies_version')
                        ->label('Politika kolačića')
                        ->placeholder('-'),

                    TextEntry::make('dpa_version')
                        ->label('Ugovor o obradi podataka / DPA')
                        ->placeholder('-'),

                    TextEntry::make('security_version')
                        ->label('Politika sigurnosti')
                        ->placeholder('-'),

                    TextEntry::make('retention_version')
                        ->label('Politika zadržavanja i brisanja podataka')
                        ->placeholder('-'),

                    TextEntry::make('newsletter_opt_in')
                        ->label('Newsletter')
                        ->formatStateUsing(fn ($state): string => $state ? 'Da' : 'Ne'),
                ]),

            Section::make('Tehnički podaci')
                ->columns(1)
                ->schema([
                    TextEntry::make('ip_address')
                        ->label('IP adresa')
                        ->placeholder('-'),

                    TextEntry::make('user_agent')
                        ->label('Preglednik / uređaj')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
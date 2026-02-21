<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('ViewTabs')
                ->columnSpanFull()
                ->tabs([
                    // ======================
                    // OSNOVNO (v2 layout)
                    // ======================
                    Tab::make('Osnovno')->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Section::make('Osobni podatci')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('name')->label('Prezime i ime')->weight('bold'),
                                        TextEntry::make('gender')->label('Spol'),

                                        TextEntry::make('OIB')->label('OIB'),
                                        TextEntry::make('phone')->label('Telefon/Mobitel'),

                                        TextEntry::make('email')->label('Email')->copyable(),
                                        TextEntry::make('job_title')->label('Zanimanje'),

                                        TextEntry::make('education')->label('Školska sprema'),
                                        TextEntry::make('place_of_birth')->label('Datum i mjesto rođenja'),

                                        TextEntry::make('name_of_parents')->label('Ime oca – majke'),
                                        TextEntry::make('address')->label('Adresa'),

                                        TextEntry::make('workplace')->label('Radno mjesto'),
                                        TextEntry::make('organization_unit')->label('Organizacijska jedinica'),

                                        TextEntry::make('contract_type')->label('Vrsta ugovora'),
                                        TextEntry::make('employeed_at')->label('Datum zaposlenja')->date('d.m.Y.'),

                                        TextEntry::make('contract_ended_at')->label('Datum prekida ugovora')->date('d.m.Y.'),
                                    ]),

                                Section::make('Liječnički pregled')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('medical_examination_valid_from')
                                            ->label('Vrijedi od')
                                            ->date('d.m.Y.'),

                                        TextEntry::make('medical_examination_valid_until')
                                            ->label('Vrijedi do')
                                            ->date('d.m.Y.')
                                            ->badge()
                                            ->color(fn ($state) => self::rokColor($state)),

                                        TextEntry::make('article')
                                            ->label('Članak 3. točke')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),

                    // ======================
                    // ROKOVI (v2 layout + boje)
                    // ======================
                    Tab::make('Rokovi')->schema([
                        Grid::make()
                            ->columns(2)
                            ->schema([
                                Section::make('Zaštita na radu')
                                    ->columns(1)
                                    ->schema([
                                        TextEntry::make('occupational_safety_valid_from')
                                            ->label('Vrijedi od')
                                            ->date('d.m.Y.'),
                                    ]),

                                Section::make('ZOP / Evakuacija')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('fire_protection_valid_from')
                                            ->label('ZOP – Vrijedi od')
                                            ->date('d.m.Y.'),

                                        TextEntry::make('fire_protection_statement_at')
                                            ->label('ZOP Izjava od')
                                            ->date('d.m.Y.'),

                                        TextEntry::make('evacuation_valid_from')
                                            ->label('Voditelj evakuacije vrijedi od')
                                            ->date('d.m.Y.')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Prva pomoć')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('first_aid_valid_from')
                                            ->label('Vrijedi od')
                                            ->date('d.m.Y.'),

                                        TextEntry::make('first_aid_valid_until')
                                            ->label('Vrijedi do')
                                            ->date('d.m.Y.')
                                            ->badge()
                                            ->color(fn ($state) => self::rokColor($state)),
                                    ]),

                                Section::make('Toksikologija')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('toxicology_valid_from')
                                            ->label('Vrijedi od')
                                            ->date('d.m.Y.'),

                                        TextEntry::make('toxicology_valid_until')
                                            ->label('Vrijedi do')
                                            ->date('d.m.Y.')
                                            ->badge()
                                            ->color(fn ($state) => self::rokColor($state)),
                                    ]),

                                Section::make('Ovlaštenik poslodavca za ZNR')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('employers_authorization_valid_from')
                                            ->label('Vrijedi od')
                                            ->date('d.m.Y.'),

                                        TextEntry::make('employers_authorization_valid_until')
                                            ->label('Vrijedi do')
                                            ->date('d.m.Y.')
                                            ->badge()
                                            ->color(fn ($state) => self::rokColor($state)),
                                    ]),
                            ]),
                    ]),

                    // ======================
                    // EDUKACIJE (ostavi kako je)
                    // ======================
                    Tab::make('Edukacije')->schema([
                        Section::make('Ostale edukacije i ovlaštenja')
                            ->description('Popis edukacija (certificates) za ovog zaposlenika.')
                            ->schema([
                                RepeatableEntry::make('certificates')
                                    ->label('')
                                    ->contained(false)
                                    ->columns(12)
                                    ->schema([
                                        TextEntry::make('title')
                                            ->label('Naziv')
                                            ->columnSpan(6),

                                        TextEntry::make('valid_from')
                                            ->label('Vrijedi od')
                                            ->date('d.m.Y.')
                                            ->columnSpan(3),

                                        TextEntry::make('valid_until')
                                            ->label('Vrijedi do')
                                            ->date('d.m.Y.')
                                            ->badge()
                                            ->color(fn ($state) => self::rokColor($state))
                                            ->columnSpan(3),
                                    ]),
                            ]),
                    ]),
                ]),
        ]);
    }

    /**
     * Boje rokova:
     * - danger = isteklo (crveno)
     * - warning = ističe <= 30 dana (žuto)
     * - success = ok (zeleno)
     * - gray = nema datuma
     */
    private static function rokColor($state): string
    {
        if (! $state) {
            return 'gray';
        }

        $today = Carbon::today();
        $soon  = $today->copy()->addDays(30);

        $d = Carbon::parse($state);

        if ($d->lt($today)) {
            return 'danger';
        }

        if ($d->lte($soon)) {
            return 'warning';
        }

        return 'success';
    }
}
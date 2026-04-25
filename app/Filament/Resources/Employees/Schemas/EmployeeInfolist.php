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

                    Tab::make('Rokovi i osposobljavanja')->schema([
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

                    Tab::make('Ostale edukacije')->schema([
                        Section::make('Ostale edukacije i ovlaštenja')
                            ->description('Popis edukacija za ovog zaposlenika.')
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

                    Tab::make('Prilozi')->schema([
                        Section::make('Prilozi')
                            ->schema([
                                TextEntry::make('pdf')
                                    ->label('')
                                    ->html()
                                    ->state(function ($record): string {
                                        if (! is_array($record->pdf) || count($record->pdf) === 0) {
                                            return '<span style="color:#6b7280;">Nema priloga</span>';
                                        }

                                        return collect($record->pdf)
                                            ->map(function ($file, $index) {
                                                $url = route('file.preview', [
                                                    'file' => ltrim($file, '/'),
                                                ]);

                                                $name = e(basename($file));
                                                $number = $index + 1;

                                                return '<a href="' . e($url) . '"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    title="' . $name . '"
                                                    style="
                                                        display:inline-flex;
                                                        align-items:center;
                                                        justify-content:center;
                                                        min-width:110px;
                                                        height:34px;
                                                        padding:0 12px;
                                                        margin:4px 6px 4px 0;
                                                        border-radius:8px;
                                                        background:rgba(59,130,246,.15);
                                                        border:1px solid rgba(59,130,246,.35);
                                                        color:#93c5fd;
                                                        font-size:13px;
                                                        font-weight:700;
                                                        text-decoration:none;
                                                        cursor:pointer;
                                                    "
                                                >📎 Prilog ' . $number . '</a>';
                                            })
                                            ->implode('');
                                    }),
                            ]),
                    ]),
                ]),
        ]);
    }

    private static function rokColor($state): string
    {
        if (! $state) {
            return 'gray';
        }

        $today = Carbon::today();
        $soon = $today->copy()->addDays(30);

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
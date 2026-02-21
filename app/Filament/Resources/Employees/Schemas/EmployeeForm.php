<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        $date = fn (string $name, string $label, bool $required = false) => DatePicker::make($name)
            ->label($label)
            ->required($required)
            ->displayFormat('d.m.Y.')
            ->weekStartsOnMonday()
            ->timezone('Europe/Zagreb');

        return $schema->schema([
            // ✅ user_id (admin bira, user automatski)
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

            Tabs::make('EmployeeTabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Osnovno')
                        ->schema([
                            // ✅ 2 stupca kao v2: lijevo osobni, desno liječnički
                            Section::make('Osobni podatci')
                                ->columns(2)
                                ->columnSpan(1)
                                ->schema([
                                    TextInput::make('name')->label('Prezime i ime (obavezno)')->required()->maxLength(255)->columnSpanFull(),

                                    Select::make('gender')
                                        ->label('Spol')
                                        ->options(['M' => 'M', 'Ž' => 'Ž'])
                                        ->native(false),

                                    TextInput::make('OIB')->label('OIB')->maxLength(32),
                                    TextInput::make('phone')->label('Telefon/Mobitel')->maxLength(50),
                                    TextInput::make('email')->label('Email')->email()->maxLength(255),

                                    TextInput::make('job_title')->label('Zanimanje')->maxLength(255),
                                    TextInput::make('education')->label('Školska sprema')->maxLength(255),

                                    TextInput::make('place_of_birth')->label('Datum i mjesto rođenja')->maxLength(255)->columnSpanFull(),
                                    TextInput::make('name_of_parents')->label('Ime oca – majke')->maxLength(255)->columnSpanFull(),
                                    TextInput::make('address')->label('Adresa')->maxLength(255)->columnSpanFull(),

                                    TextInput::make('workplace')->label('Radno mjesto')->maxLength(255)->columnSpanFull(),
                                    TextInput::make('organization_unit')->label('Organizacijska jedinica')->maxLength(255),
                                    TextInput::make('contract_type')->label('Vrsta ugovora')->maxLength(255),

                                    $date('employeed_at', 'Datum zaposlenja (obavezno)', true),
                                    $date('contract_ended_at', 'Datum prekida ugovora'),
                                ]),

                            Section::make('Liječnički pregled')
                                ->columns(2)
                                ->columnSpan(1)
                                ->schema([
                                    $date('medical_examination_valid_from', 'Vrijedi od'),
                                    $date('medical_examination_valid_until', 'Vrijedi do'),

                                    Textarea::make('article')
                                        ->label('Članak 3. točke')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columns(2),

                    Tab::make('Rokovi i osposobljavanja')
                        ->schema([
                            // ✅ 2 stupca “kartice” kao u v2 (pregledno, kompaktno)
                            Section::make('Zaštita na radu – Rad na siguran način')
                                ->columns(2)
                                ->columnSpan(1)
                                ->schema([
                                    $date('occupational_safety_valid_from', 'Vrijedi od'),
                                ]),

                            Section::make('Zaštita od požara – ZOP')
                                ->columns(2)
                                ->columnSpan(1)
                                ->schema([
                                    $date('fire_protection_valid_from', 'ZOP – Vrijedi od'),
                                    $date('fire_protection_statement_at', 'ZOP izjava od'),
                                    $date('evacuation_valid_from', 'Voditelj evakuacije vrijedi od')->columnSpanFull(),
                                ]),

                            Section::make('Prva pomoć')
                                ->columns(2)
                                ->columnSpan(1)
                                ->schema([
                                    $date('first_aid_valid_from', 'Vrijedi od'),
                                    $date('first_aid_valid_until', 'Vrijedi do'),
                                ]),

                            Section::make('Toksikologija – Rad s opasnim kemikalijama')
                                ->columns(2)
                                ->columnSpan(1)
                                ->schema([
                                    $date('toxicology_valid_from', 'Vrijedi od'),
                                    $date('toxicology_valid_until', 'Vrijedi do'),
                                ]),

                            Section::make('Ovlaštenik poslodavca za ZNR')
                                ->columns(2)
                                ->columnSpan(1)
                                ->schema([
                                    $date('employers_authorization_valid_from', 'Vrijedi od'),
                                    $date('employers_authorization_valid_until', 'Vrijedi do'),
                                ]),
                        ])
                        ->columns(2),

                    Tab::make('Ostale edukacije')
                        ->schema([
                            Section::make('Ostale edukacije i ovlaštenja')
                                ->columnSpanFull()
                                ->schema([
                                    Repeater::make('certificates')
                                        ->label('Popis edukacija / ovlaštenja')
                                        ->relationship()
                                        ->createItemButtonLabel('Dodaj novi zapis')
                                        ->columns(3)
                                        ->collapsible()
                                        ->itemLabel(fn ($state) => $state['title'] ?? 'Nova stavka')
                                        ->schema([
                                            TextInput::make('title')->label('Naziv')->required(),

                                            $date('valid_from', 'Vrijedi od', true),
                                            $date('valid_until', 'Vrijedi do'),
                                        ]),
                                ]),
                        ]),

                    Tab::make('Prilozi')
                        ->schema([
                            FileUpload::make('pdf')
                                ->label('Dodaj priloge (max. 10, do 30 MB po datoteci)')
                                ->disk('public')
                                ->visibility('public')
                                ->directory('employees/attachments')
                                ->multiple()
                                ->maxFiles(10)
                                ->maxSize(30720)
                                ->preserveFilenames()
                                ->openable()
                                ->downloadable(),
                        ]),
                ]),
        ])
        ->columns(1); // root: sve ide kroz tabove (nema uske kolone)
    }
}
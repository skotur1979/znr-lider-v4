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
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
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

            Section::make('Osobni podatci')
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label('Prezime i ime (obavezno)')->required()->maxLength(255),

                    TextInput::make('job_title')->label('Zanimanje')->maxLength(255),
                    TextInput::make('education')->label('Školska sprema')->maxLength(255),
                    TextInput::make('place_of_birth')->label('Datum i mjesto rođenja')->maxLength(255),

                    TextInput::make('name_of_parents')->label('Ime oca – majke')->maxLength(255),
                    TextInput::make('address')->label('Adresa')->maxLength(255),
                    TextInput::make('gender')->label('Spol')->maxLength(50),

                    TextInput::make('OIB')->label('OIB')->maxLength(32),
                    TextInput::make('phone')->label('Telefon/Mobitel')->maxLength(50),
                    TextInput::make('email')->label('Email')->email()->maxLength(255),

                    TextInput::make('workplace')->label('Radno mjesto')->maxLength(255),
                    TextInput::make('organization_unit')->label('Organizacijska jedinica')->maxLength(255),
                    TextInput::make('contract_type')->label('Vrsta ugovora')->maxLength(255),

                    DatePicker::make('employeed_at')
                        ->label('Datum zaposlenja (obavezno)')
                        ->required()
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('contract_ended_at')
                        ->label('Datum prekida ugovora')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),
                ]),

            Section::make('Liječnički pregled')
                ->columns(3)
                ->schema([
                    DatePicker::make('medical_examination_valid_from')
                        ->label('Vrijedi od')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('medical_examination_valid_until')
                        ->label('Vrijedi do')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    Textarea::make('article')->rows(1)->label('Članak 3. točke'),
                ]),

            Section::make('Zaštita na radu - Rad na siguran način')
                ->columns(2)
                ->schema([
                    DatePicker::make('occupational_safety_valid_from')
                        ->label('Vrijedi od')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),
                ]),

            Section::make('Zaštita od požara - ZOP')
                ->columns(3)
                ->schema([
                    DatePicker::make('fire_protection_valid_from')
                        ->label('ZOP - Vrijedi od')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('fire_protection_statement_at')
                        ->label('ZOP Izjava od')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('evacuation_valid_from')
                        ->label('Voditelj evakuacije vrijedi od')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),
                ]),

            Section::make('Prva pomoć')
                ->columns(2)
                ->schema([
                    DatePicker::make('first_aid_valid_from')
                        ->label('Vrijedi od')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('first_aid_valid_until')
                        ->label('Vrijedi do')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),
                ]),

            Section::make('Toksikologija - Rad s opasnim kemikalijama')
                ->columns(2)
                ->schema([
                    DatePicker::make('toxicology_valid_from')
                        ->label('Vrijedi od')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('toxicology_valid_until')
                        ->label('Vrijedi do')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),
                ]),

            Section::make('Ovlaštenik poslodavca za ZNR')
                ->columns(2)
                ->schema([
                    DatePicker::make('employers_authorization_valid_from')
                        ->label('Vrijedi od')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),

                    DatePicker::make('employers_authorization_valid_until')
                        ->label('Vrijedi do')
                        ->displayFormat('d.m.Y.')
                        ->weekStartsOnMonday()
                        ->timezone('Europe/Zagreb'),
                ]),

            Section::make('Ostale edukacije i ovlaštenja')
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

                            DatePicker::make('valid_from')
                                ->label('Vrijedi od')
                                ->required()
                                ->displayFormat('d.m.Y.')
                                ->weekStartsOnMonday()
                                ->timezone('Europe/Zagreb'),

                            DatePicker::make('valid_until')
                                ->label('Vrijedi do')
                                ->displayFormat('d.m.Y.')
                                ->weekStartsOnMonday()
                                ->timezone('Europe/Zagreb'),
                        ]),
                ]),

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
        ]);
    }
}

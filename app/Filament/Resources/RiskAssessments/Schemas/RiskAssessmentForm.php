<?php

namespace App\Filament\Resources\RiskAssessments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class RiskAssessmentForm
{
    public static function schema(): array
    {
        return [
            Section::make('Podaci o procjeni rizika')
                ->schema([
                    TextInput::make('tvrtka')->required()->label('Tvrtka'),
                    TextInput::make('oib_tvrtke')->nullable()->label('OIB tvrtke'),
                    TextInput::make('adresa_tvrtke')->label('Adresa tvrtke'),
                    TextInput::make('broj_procjene')->required()->label('Broj procjene'),
                    DatePicker::make('datum_izrade')->required()->label('Datum izrade'),
                    TextInput::make('vrsta_procjene')->required()->label('Vrsta procjene rizika'),
                ])
                ->columns(3)
                ->collapsible(),

            Section::make('Sudionici izrade')
                ->schema([
                    Repeater::make('participants')
                        ->label('Sudionici izrade')
                        ->relationship('participants')
                        ->schema([
                            TextInput::make('ime_prezime')->nullable()->label('Ime i prezime'),
                            TextInput::make('uloga')->nullable()->label('Uloga'),
                            Textarea::make('napomena')->label('Napomena')->rows(1),
                        ])
                        ->columns(3)
                        ->collapsible(),
                ])
                ->collapsible(),

            Section::make('Revizije Procjene Rizika')
                ->schema([
                    Repeater::make('revisions')
                        ->label('Revizije')
                        ->relationship('revisions')
                        ->schema([
                            TextInput::make('revizija_broj')->nullable()->label('Revizija broj'),
                            DatePicker::make('datum_izrade')->nullable()->label('Datum izrade'),
                        ])
                        ->columns(2)
                        ->collapsible(),
                ])
                ->collapsible(),

            Section::make('Prilozi')
                ->schema([
                    Repeater::make('attachments')
                        ->label('Prilozi')
                        ->relationship('attachments')
                        ->schema([
                            TextInput::make('naziv')
                                ->label('Naziv dokumenta')
                                ->required(),

                            FileUpload::make('file_path')
                                ->label('Dokument')
                                ->disk('public')
                                ->visibility('public')
                                ->directory('risk-assessments/attachments')
                                ->acceptedFileTypes([
                                    'application/pdf',
                                    'application/x-pdf',
                                    'application/acrobat',
                                    'text/pdf', 'text/x-pdf',
                                    'application/msword',
                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'image/jpeg','image/png','image/gif','image/webp',
                                    'application/zip','application/x-rar-compressed',
                                ])
                                ->maxSize(30720)
                                ->preserveFilenames()
                                ->openable()
                                ->downloadable()
                                ->required()
                                ->helperText('Do 30 MB po datoteci.'),
                        ])
                        ->columns(2)
                        ->collapsible(),
                ])
                ->collapsible(),
        ];
    }
}
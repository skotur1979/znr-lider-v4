<?php

namespace App\Filament\Resources\Incidents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class IncidentForm
{
    public static function schema(): array
    {
        return [
            TextInput::make('location')->label('Lokacija')->required(),

            Select::make('type_of_incident')
                ->label('Vrsta incidenta')
                ->options(\App\Filament\Resources\Incidents\IncidentResource::$INCIDENT_TYPES)
                ->required(),

            Select::make('permanent_or_temporary')
                ->label('Vrsta zaposlenja')
                ->options(['Permanent' => 'Stalni', 'Temporary' => 'Privremeni'])
                ->required(),

            DatePicker::make('date_occurred')
                ->label('Datum nastanka')
                ->required()
                ->reactive(),

            DatePicker::make('date_of_return')
                ->label('Datum povratka na posao')
                ->reactive()
                ->after('date_occurred')
                ->afterStateUpdated(function ($state, $context, $set, $get) {
                    $start = $get('date_occurred');
                    $end = $state;

                    if ($start && $end) {
                        $startDate = \Carbon\Carbon::parse($start);
                        $endDate = \Carbon\Carbon::parse($end);

                        // Isključujemo dan nezgode
                        $daysLost = $startDate->diffInWeekdays($endDate) - 1;
                        $set('working_days_lost', max($daysLost, 0));
                    }
                }),

            TextInput::make('working_days_lost')
                ->label('Izgubljeni radni dani')
                ->numeric(),

            Textarea::make('causes_of_injury')->label('Uzrok ozljede')->rows(2),
            Textarea::make('accident_injury_type')->label('Tip ozljede')->rows(2),
            TextInput::make('injured_body_part')->label('Ozlijeđeni dio tijela'),

            FileUpload::make('image_path')
                ->label('Slika')
                ->image()
                ->directory('pdfs')
                ->placeholder('Povucite i ispustite datoteke ili pretražite'),

            TextInput::make('other')->label('Napomena - Podaci o ozlijeđenom radniku'),

            FileUpload::make('investigation_report')
                ->label('Dodaj prilog - Izvještaj o istrazi')
                ->disk('public')
                ->directory('pdfs')
                ->placeholder('Povucite i ispustite datoteke ili pretražite')
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'application/zip',
                    'application/x-rar-compressed',
                ])
                ->maxSize(30720)
                ->multiple()
                ->maxFiles(5)
                ->preserveFilenames()
                ->enableOpen()
                ->enableDownload()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $maxTotalMB = 150;
                    $totalBytes = 0;

                    if (is_array($state)) {
                        foreach ($state as $file) {
                            if ($file instanceof \Illuminate\Http\UploadedFile) {
                                $totalBytes += $file->getSize();
                            }
                        }
                    }

                    if ($totalBytes > $maxTotalMB * 1024 * 1024) {
                        $set('investigation_report', []);
                        \Filament\Notifications\Notification::make()
                            ->title("Ukupna veličina svih datoteka ne smije biti veća od {$maxTotalMB} MB.")
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
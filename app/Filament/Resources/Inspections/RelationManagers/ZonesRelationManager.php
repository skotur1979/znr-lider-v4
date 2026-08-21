<?php

namespace App\Filament\Resources\Inspections\RelationManagers;

use App\Filament\Resources\InspectionZones\InspectionZoneResource;
use App\Filament\Resources\Inspections\InspectionResource;
use Filament\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\InspectionZones5sExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZonesRelationManager extends RelationManager
{
    protected static string $relationship = 'zones';

    protected static ?string $title = '5S zone';

    protected function getInspectionViewUrl(): string
    {
        $inspection = $this->getOwnerRecord();

        return InspectionResource::getUrl(
            'edit',
            [
                'record' => $inspection,
            ]
        ) . '?relation=1';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label('Zona')
                ->required(),

            TextInput::make('sort_order')
                ->label('Redoslijed')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Nema 5S zona')
            ->emptyStateDescription(
                'Stvori 5S zonu kako bi započeo.'
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Zona')
                    ->searchable(),

                TextColumn::make('total_points')
                    ->label('Bodovi')
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(
                        fn ($state) =>
                            filled($state)
                                ? $state
                                : '-'
                    )
                    ->summarize(
                        Summarizer::make()
                            ->label('Ukupno bodova')
                            ->using(
                                function (): int {
                                    $zones = $this
                                        ->getOwnerRecord()
                                        ->zones()
                                        ->get();

                                    return (int) $zones->sum(
                                        'total_points'
                                                );
                                }
                            )
                    ),

                TextColumn::make('max_points')
                    ->label('Max')
                    ->alignment(Alignment::Center)
                    ->formatStateUsing(
                        fn ($state) =>
                            filled($state)
                                ? $state
                                : '-'
                    )
                    ->summarize(
                        Summarizer::make()
                            ->label('Ukupno max')
                            ->using(
                                function (): int {
                                    $zones = $this
                                        ->getOwnerRecord()
                                        ->zones()
                                        ->get();

                                    return (int) $zones->sum(
                                        'max_points'
                                    );
                                }
                            )
                    ),

                TextColumn::make('percentage')
                    ->label('Rezultat')
                    ->alignment(Alignment::Center)
                    ->html()
                    ->state(function ($record) {
                        $percentage =
                            (float) ($record->percentage ?? 0);

                        $styles = match (true) {
                            $percentage < 40 =>
                                'background:#991b1b;color:#ffffff;',

                            $percentage < 60 =>
                                'background:#f59e0b;color:#111827;',

                            $percentage < 80 =>
                                'background:#fde047;color:#111827;',

                            default =>
                                'background:#16a34a;color:#ffffff;',
                        };

                        return '<div style="
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            min-width:76px;
                            height:36px;
                            padding:0 12px;
                            border-radius:10px;
                            font-weight:800;
                            font-size:16px;
                            line-height:1;
                            box-shadow:0 0 0 1px rgba(255,255,255,0.08) inset;
                            ' . $styles . '
                        ">'
                            . e(
                                number_format(
                                    $percentage,
                                    0
                                )
                            )
                            . '%</div>';
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Ukupni 5S rezultat')
                            ->using(
                                function (): string {
                                    $inspection =
                                        $this->getOwnerRecord();

                                    $zones =
                                        $inspection
                                            ->zones()
                                            ->get();

                                    $totalPoints =
                                        (float) $zones->sum(
                                            'total_points'
                                        );

                                    $maxPoints =
                                        (float) $zones->sum(
                                            'max_points'
                                        );

                                    $percentage =
                                        $maxPoints > 0
                                            ? ($totalPoints / $maxPoints) * 100
                                            : 0;

                                    $styles = match (true) {
                                        $percentage < 40 =>
                                            'background:#991b1b;color:#ffffff;',

                                        $percentage < 60 =>
                                            'background:#f59e0b;color:#111827;',

                                        $percentage < 80 =>
                                            'background:#fde047;color:#111827;',

                                        default =>
                                            'background:#16a34a;color:#ffffff;',
                                    };

                                    return '<div style="
                                        display:inline-flex;
                                        align-items:center;
                                        justify-content:center;
                                        min-width:76px;
                                        height:36px;
                                        padding:0 12px;
                                        border-radius:10px;
                                        font-weight:800;
                                        font-size:16px;
                                        line-height:1;
                                        box-shadow:0 0 0 1px rgba(255,255,255,0.08) inset;
                                        ' . $styles . '
                                    ">'
                                        . e(
                                            number_format(
                                                $percentage,
                                                0
                                            )
                                        )
                                        . '%</div>';
                                }
                            )
                            ->html()
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj zonu'),

                Action::make('zoneResultsReport')
                    ->label('Izvještaj rezultata zona')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('info')
                    ->url(function (): string {
                        $inspection = $this->getOwnerRecord();

                        return InspectionResource::getUrl(
                            'zone-results-report',
                            [
                                'inspection' => $inspection->getKey(),
                            ]
                        );
                    }),

                Action::make('export_5s_pdf')
                    ->label('Izvoz u PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->action(function () {
                        $inspection =
                            $this->getOwnerRecord();

                        $inspection->load([
                            'zones.questions',
                            'zones.answers.question',
                        ]);

                        $pdf = Pdf::loadView(
                            'pdf.inspection-zones-5s',
                            [
                                'inspection' =>
                                    $inspection,

                                'zones' =>
                                    $inspection->zones,
                            ]
                        )
                            ->setPaper(
                                'a4',
                                'landscape'
                            )
                            ->setOptions([
                                'isHtml5ParserEnabled' => true,
                                'isRemoteEnabled' => true,
                                'isPhpEnabled' => true,
                                'dpi' => 96,
                                'defaultFont' =>
                                    'DejaVu Sans',
                            ]);

                        return response()
                            ->streamDownload(
                                fn () =>
                                    print(
                                        $pdf->output()
                                    ),

                                '5s-nadzor-'
                                . str(
                                    $inspection->number
                                        ?? $inspection->id
                                )->slug()
                                . '-'
                                . now()->format('Y-m-d')
                                . '.pdf'
                            );
                    }),

                Action::make('export_5s_excel')
                    ->label('Izvoz u Excel')
                    ->icon(
                        'heroicon-o-document-arrow-down'
                    )
                    ->color('success')
                    ->action(function () {
                        $inspection =
                            $this->getOwnerRecord();

                        return Excel::download(
                            new InspectionZones5sExport(
                                $inspection
                            ),
                            '5s-nadzor-'
                            . str(
                                $inspection->number
                                    ?? $inspection->id
                            )->slug()
                            . '-'
                            . now()->format('Y-m-d')
                            . '.xlsx'
                        );
                    }),
            ])
            ->actions([
                Action::make('ocijeni')
                    ->label('Ocijeni zonu')
                    ->icon(
                        'heroicon-o-clipboard-document-check'
                    )
                    ->color('success')
                    ->url(
                        fn ($record) =>
                            InspectionZoneResource::getUrl(
                                'edit',
                                [
                                    'record' => $record,
                                    'return_url' =>
                                        $this->getInspectionViewUrl(),
                                ]
                            )
                    ),

                EditAction::make()
                    ->label('Uredi zonu'),

                DeleteAction::make()
                    ->label('Obriši zonu'),
            ]);
    }
}
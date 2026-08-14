<?php

namespace App\Exports;

use App\Models\LegalAcceptance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LegalAcceptancesExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithColumnFormatting,
    ShouldAutoSize,
    WithEvents
{
    protected Collection $records;

    public function __construct(Collection $records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Datum prihvaćanja',
            'Korisnik',
            'E-mail',
            'Organizacija',
            'Verzija uvjeta korištenja',
            'Verzija pravila privatnosti',
            'Verzija politike kolačića',
            'Verzija DPA',
            'Verzija politike sigurnosti',
            'Verzija politike zadržavanja',
            'Newsletter',
            'IP adresa',
            'Preglednik / uređaj',
            'Paket prihvaćenih dokumenata',
        ];
    }

    public function map($record): array
    {
        /** @var LegalAcceptance $record */

        return [
            $record->accepted_at
                ? ExcelDate::dateTimeToExcel(
                    Carbon::parse($record->accepted_at)
                )
                : null,

            $record->user_name ?: '',
            $record->user_email ?: '',
            $record->organization_name ?: '',

            $record->terms_version ?: '',
            $record->privacy_version ?: '',
            $record->cookies_version ?: '',
            $record->dpa_version ?: '',
            $record->security_version ?: '',
            $record->retention_version ?: '',

            $record->newsletter_opt_in
                ? 'Da'
                : 'Ne',

            $record->ip_address ?: '',
            $record->user_agent ?: '',

            $this->acceptedDocumentsText(
                $record->accepted_documents
            ),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => 'dd.mm.yyyy hh:mm',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class =>
                function (AfterSheet $event): void {
                    $sheet =
                        $event->sheet->getDelegate();

                    $lastRow =
                        $this->records->count() + 1;

                    $lastCol = 'N';

                    $sheet
                        ->getStyle(
                            "A1:{$lastCol}{$lastRow}"
                        )
                        ->getFont()
                        ->setName('DejaVu Sans')
                        ->setSize(10);

                    $sheet
                        ->getStyle(
                            "A1:{$lastCol}1"
                        )
                        ->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => [
                                    'rgb' => 'FFFFFF',
                                ],
                                'name' =>
                                    'DejaVu Sans',
                                'size' => 10,
                            ],

                            'fill' => [
                                'fillType' =>
                                    'solid',
                                'startColor' => [
                                    'rgb' =>
                                        '1F2937',
                                ],
                            ],

                            'alignment' => [
                                'horizontal' =>
                                    Alignment::HORIZONTAL_CENTER,
                                'vertical' =>
                                    Alignment::VERTICAL_CENTER,
                                'wrapText' =>
                                    true,
                            ],
                        ]);

                    if ($lastRow >= 2) {
                        $sheet
                            ->getStyle(
                                "A2:{$lastCol}{$lastRow}"
                            )
                            ->getAlignment()
                            ->setVertical(
                                Alignment::VERTICAL_CENTER
                            )
                            ->setWrapText(true);

                        /*
                         * Datum, verzije, newsletter
                         * i IP preglednije su centrirani.
                         */
                        $sheet
                            ->getStyle(
                                "A2:A{$lastRow}"
                            )
                            ->getAlignment()
                            ->setHorizontal(
                                Alignment::HORIZONTAL_CENTER
                            );

                        $sheet
                            ->getStyle(
                                "E2:L{$lastRow}"
                            )
                            ->getAlignment()
                            ->setHorizontal(
                                Alignment::HORIZONTAL_CENTER
                            );
                    }

                    $widths = [
                        'A' => 20,
                        'B' => 24,
                        'C' => 32,
                        'D' => 28,
                        'E' => 18,
                        'F' => 18,
                        'G' => 18,
                        'H' => 18,
                        'I' => 18,
                        'J' => 20,
                        'K' => 14,
                        'L' => 20,
                        'M' => 55,
                        'N' => 55,
                    ];

                    foreach (
                        $widths
                        as $column => $width
                    ) {
                        $sheet
                            ->getColumnDimension(
                                $column
                            )
                            ->setWidth($width);
                    }

                    $sheet
                        ->getRowDimension(1)
                        ->setRowHeight(32);

                    for (
                        $row = 2;
                        $row <= $lastRow;
                        $row++
                    ) {
                        $sheet
                            ->getRowDimension($row)
                            ->setRowHeight(42);
                    }

                    $sheet->freezePane('A2');

                    $sheet->setAutoFilter(
                        "A1:{$lastCol}{$lastRow}"
                    );
                },
        ];
    }

    private function acceptedDocumentsText(
        mixed $documents
    ): string {
        if (blank($documents)) {
            return '';
        }

        if (is_string($documents)) {
            $decoded =
                json_decode(
                    $documents,
                    true
                );

            if (is_array($decoded)) {
                $documents = $decoded;
            } else {
                return trim($documents);
            }
        }

        if (! is_array($documents)) {
            return '';
        }

        return collect($documents)
            ->flatten()
            ->filter(
                fn ($value): bool =>
                    is_scalar($value)
                    && filled($value)
            )
            ->map(
                fn ($value): string =>
                    trim((string) $value)
            )
            ->unique()
            ->implode(', ');
    }
}
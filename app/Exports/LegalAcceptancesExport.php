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

class LegalAcceptancesExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
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
            'Newsletter',
            'IP adresa',
            'Preglednik / uređaj',
        ];
    }

    public function map($record): array
    {
        /** @var LegalAcceptance $record */

        return [
            $record->accepted_at ? ExcelDate::dateTimeToExcel(Carbon::parse($record->accepted_at)) : null,
            $record->user_name,
            $record->user_email,
            $record->organization_name,
            $record->terms_version,
            $record->privacy_version,
            $record->newsletter_opt_in ? 'Da' : 'Ne',
            $record->ip_address,
            $record->user_agent,
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
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->records->count() + 1;
                $lastCol = 'I';

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => ['rgb' => '1F2937'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getRowDimension(1)->setRowHeight(30);

                foreach (range(2, $lastRow) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(30);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }
}
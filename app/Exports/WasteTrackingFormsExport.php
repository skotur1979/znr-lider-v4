<?php

namespace App\Exports;

use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Models\WasteTrackingForm;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class WasteTrackingFormsExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    protected $records;

    public function __construct()
    {
        $this->records = WasteTrackingFormResource::getEloquentQuery()
            ->with([
                'user',
                'ontoRecord.organizationLocation',
                'ontoRecord.wasteType',
            ])
            ->orderByDesc('handover_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Broj PL-O',
            'Korisnik',
            'Datum',
            'Lokacija',
            'K.B.',
            'Naziv otpada',
            'Količina (kg)',
            'Status',
        ];
    }

    public function map($record): array
    {
        /** @var WasteTrackingForm $record */

        $date = $record->handover_date ? Carbon::parse($record->handover_date) : null;

        return [
            $record->document_number,
            $record->user?->name ?? '',
            $date ? ExcelDate::dateTimeToExcel($date) : null,
            $record->ontoRecord?->organizationLocation?->display_name
                ?? $record->ontoRecord?->organizationLocation?->name
                ?? $record->ontoRecord?->organizationLocation?->location_name
                ?? '-',
            $this->formatWasteCode($record->ontoRecord?->wasteType?->waste_code),
            $record->ontoRecord?->wasteType?->name ?? '',
            (float) $record->quantity_kg,
            $record->status === 'locked' ? 'Zaključen' : 'Nacrt',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => 'dd.mm.yyyy',
            'G' => '#,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->records->count() + 1;

                $sheet->getStyle("A1:H{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle('A1:H1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'name' => 'DejaVu Sans',
                        'size' => 10,
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

                $sheet->getStyle("A2:H{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:H{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                $sheet->getStyle("C2:C{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("E2:E{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("G2:H{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $widths = [
                    'A' => 34,
                    'B' => 22,
                    'C' => 16,
                    'D' => 28,
                    'E' => 14,
                    'F' => 36,
                    'G' => 16,
                    'H' => 16,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(28);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(34);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:H{$lastRow}");
            },
        ];
    }

    private function formatWasteCode(?string $code): string
    {
        if (! $code) {
            return '-';
        }

        $hasStar = str_contains($code, '*');

        $code = str_replace('*', '', $code);
        $code = preg_replace('/\D/', '', $code);
        $code = trim(chunk_split($code, 2, ' '));

        return $hasStar ? $code . '*' : $code;
    }
}
<?php

namespace App\Exports;

use App\Filament\Resources\Fires\FireResource;
use App\Models\Fire;
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
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FiresExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    /** @var \Illuminate\Support\Collection<int, \App\Models\Fire> */
    protected $fires;

    public function __construct()
    {
        // ✅ user scope + soft delete scope maknut u Resource::getEloquentQuery()
        $this->fires = FireResource::getEloquentQuery()
            ->orderBy('place')
            ->get();
    }

    public function collection()
    {
        return $this->fires;
    }

    public function headings(): array
    {
        return [
            'Mjesto',
            'Tip',
            'Tvor. broj / god. proizv.',
            'Serijski broj',
            'Datum periodičkog servisa',
            'Vrijedi do',
            'Serviser',
            'Datum redovnog pregleda',
            'Uočljivost',
            'Uočeni nedostatci',
            'Postupci otklanjanja',
        ];
    }

    public function map($fire): array
    {
        /** @var Fire $fire */

        $serviceFrom = $fire->examination_valid_from ? Carbon::parse($fire->examination_valid_from) : null;
        $validUntil  = $fire->examination_valid_until ? Carbon::parse($fire->examination_valid_until) : null;
        $regularFrom = $fire->regular_examination_valid_from ? Carbon::parse($fire->regular_examination_valid_from) : null;

        return [
            $fire->place,
            $fire->type,
            $fire->factory_number_year_of_production, // ✅ alias koji mapira na DB "factory_number/year_of_production"
            $fire->serial_label_number,
            $serviceFrom ? ExcelDate::dateTimeToExcel($serviceFrom) : null,
            $validUntil  ? ExcelDate::dateTimeToExcel($validUntil)  : null,
            $fire->service,
            $regularFrom ? ExcelDate::dateTimeToExcel($regularFrom) : null,
            $fire->visible,
            $fire->remark,
            $fire->action,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => 'dd.mm.yyyy', // Datum periodičkog servisa
            'F' => 'dd.mm.yyyy', // Vrijedi do (boji se ovaj stupac)
            'H' => 'dd.mm.yyyy', // Datum redovnog pregleda
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ✅ header stil
                $sheet->getStyle('A1:K1')->getFont()->setBold(true);
                $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $today = Carbon::today();

                foreach ($this->fires as $i => $fire) {
                    $row = $i + 2;

                    $until = $fire->examination_valid_until
                        ? Carbon::parse($fire->examination_valid_until)
                        : null;

                    if (! $until) {
                        continue;
                    }

                    // 🔴 isteklo
                    if ($until->lt($today)) {
                        $this->fillCell($sheet, "F{$row}", 'FFFF0000');
                        continue;
                    }

                    // 🟡 ističe unutar 30 dana
                    if ($until->lte($today->copy()->addDays(30))) {
                        $this->fillCell($sheet, "F{$row}", 'FFFFFF00');
                        continue;
                    }
                }
            },
        ];
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle('A:K')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle('A:K')->getAlignment()->setWrapText(true);
    }
}
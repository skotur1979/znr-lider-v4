<?php

namespace App\Exports;

use App\Filament\Resources\Machines\MachineResource;
use App\Models\Machine;
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

class MachinesExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    /** @var \Illuminate\Support\Collection<int, \App\Models\Machine> */
    protected $machines;

    public function __construct()
    {
        // ✅ sve po user_id (preko MachineResource::getEloquentQuery()), bez obzira na filtere
        $this->machines = MachineResource::getEloquentQuery()
            ->orderBy('name')
            ->get();
    }

    public function collection()
    {
        return $this->machines;
    }

    public function headings(): array
    {
        return [
            'Naziv',
            'Proizvođač',
            'Tvornički broj',
            'Inventarni broj',
            'Vrijedi od',
            'Vrijedi do',
            'Ispitao',
            'Broj izvještaja',
            'Lokacija',
            'Napomena',
        ];
    }

    public function map($machine): array
    {
        /** @var Machine $machine */

        $from = $machine->examination_valid_from
            ? Carbon::parse($machine->examination_valid_from)
            : null;

        $until = $machine->examination_valid_until
            ? Carbon::parse($machine->examination_valid_until)
            : null;

        return [
            $machine->name,
            $machine->manufacturer,
            $machine->factory_number,
            $machine->inventory_number,
            $from ? ExcelDate::dateTimeToExcel($from) : null,
            $until ? ExcelDate::dateTimeToExcel($until) : null,
            $machine->examined_by,
            $machine->report_number,
            $machine->location,
            $machine->remark,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => 'dd.mm.yyyy',
            'F' => 'dd.mm.yyyy',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // header malo jači
                $sheet->getStyle('A1:J1')->getFont()->setBold(true);
                $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $today = Carbon::today();

                foreach ($this->machines as $i => $machine) {
                    $row = $i + 2; // header je 1

                    $until = $machine->examination_valid_until
                        ? Carbon::parse($machine->examination_valid_until)
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

                    // ✅ >30 dana: bez boje
                }
            },
        ];
    }

    private function fillCell($sheet, string $cell, string $argb): void
    {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($cell)->getFill()->getStartColor()->setARGB($argb);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }
}

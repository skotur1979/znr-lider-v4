<?php

namespace App\Exports;

use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Models\Chemical;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ChemicalsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithDrawings
{
    /** @var \Illuminate\Support\Collection<int, \App\Models\Chemical> */
    protected $chemicals;

    public function __construct()
    {
        $this->chemicals = ChemicalResource::getEloquentQuery()
            ->orderBy('product_name')
            ->get();
    }

    public function collection()
    {
        return $this->chemicals;
    }

    public function headings(): array
    {
        return [
            'Ime proizvoda',
            'CAS',
            'UFI',
            'Piktogrami',
            'H oznake',
            'P oznake',
            'Mjesto upotrebe',
            'Količina',
            'GVI / KGVI',
            'VOC',
            'STL – HZJZ',
        ];
    }

    public function map($chemical): array
    {
        /** @var Chemical $chemical */

        $h = $this->toList($chemical->h_statements);
        $p = $this->toList($chemical->p_statements);

        return [
            $chemical->product_name,
            $chemical->cas_number,
            $chemical->ufi_number,
            '', // ✅ slike se crtaju preko drawings()
            implode(', ', $h),
            implode(', ', $p),
            $chemical->usage_location,
            $chemical->annual_quantity,
            $chemical->gvi_kgvi,
            $chemical->voc,
            $chemical->stl_hzjz ? $chemical->stl_hzjz->format('d.m.Y.') : '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // header bold + centriran
                $sheet->getStyle('A1:K1')->getFont()->setBold(true);
                $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Piktogrami stupac malo širi
                $sheet->getColumnDimension('D')->setWidth(18);

                // Visina redaka da stanu 1-2 reda piktograma
                // (ako netko ima puno piktograma)
                foreach ($this->chemicals as $i => $chemical) {
                    $row = $i + 2;

                    $count = count($this->normalizePictos($chemical->hazard_pictograms));

                    // 0-3 -> jedan red slika, 4+ -> dva reda
                    $sheet->getRowDimension($row)->setRowHeight($count > 3 ? 45 : 28);
                }
            },
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        foreach ($this->chemicals as $i => $chemical) {
            $row = $i + 2;
            $codes = $this->normalizePictos($chemical->hazard_pictograms);

            // max koliko god imaš, složimo 3 po redu
            foreach ($codes as $idx => $code) {
                $path = $this->findPictogramPath($code);

                // Excel drawings: najbolje png/jpg; ako nema – preskoči
                if (! $path) {
                    continue;
                }

                $col = 'D';
                $cell = $col . $row;

                // 3 po redu
                $colIndex = $idx % 3;          // 0,1,2
                $rowIndex = intdiv($idx, 3);   // 0,1,2...

                $drawing = new Drawing();
                $drawing->setName("picto_{$row}_{$idx}");
                $drawing->setDescription($code);
                $drawing->setPath($path);
                $drawing->setHeight(18); // veličina ikone

                $drawing->setCoordinates($cell);

                // offseti unutar ćelije:
                // X: 0, 22, 44 (razmak)
                // Y: 2 ili 22 (drugi red)
                $drawing->setOffsetX(2 + ($colIndex * 22));
                $drawing->setOffsetY(2 + ($rowIndex * 20));

                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    private function normalizePictos($value): array
    {
        $list = $this->toList($value);

        return collect($list)
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function toList($value): array
    {
        if ($value === null || $value === '') return [];

        if (is_array($value)) {
            return collect($value)->map(fn ($v) => trim((string) $v))->filter()->values()->all();
        }

        $value = (string) $value;

        // glavno: split po zarezu
        $parts = explode(',', $value);

        // ako netko ima "GHS01 GHS02" bez zareza, dodatno split po whitespace
        if (count($parts) === 1 && preg_match('/\s+/', $value)) {
            $parts = preg_split('/\s+/', $value) ?: [];
        }

        return collect($parts)->map(fn ($v) => trim($v))->filter()->values()->all();
    }

    private function findPictogramPath(string $code): ?string
    {
        $code = strtoupper(trim($code));

        // Excel: prvo png/jpg, pa gif
        $candidates = [
            public_path("images/ghs/{$code}.png"),
            public_path("images/ghs/{$code}.jpg"),
            public_path("images/ghs/{$code}.jpeg"),
            public_path("piktogrami/{$code}.png"),
            public_path("piktogrami/{$code}.jpg"),
            public_path("piktogrami/{$code}.jpeg"),
            public_path("images/ghs/{$code}.gif"),
            public_path("piktogrami/{$code}.gif"),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
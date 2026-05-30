<?php

namespace App\Exports;

use App\Filament\Resources\Chemicals\ChemicalResource;
use App\Models\Chemical;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ChemicalsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents, WithDrawings
{
    protected $chemicals;

    protected bool $showUserColumn = false;

    public function __construct()
    {
        $user = auth()->user();

        $this->showUserColumn =
            (bool) $user?->isSuperAdmin()
            || (bool) $user?->canCreateSubusers();

        $this->chemicals = ChemicalResource::getEloquentQuery()
            ->with('user')
            ->orderBy('product_name')
            ->get();
    }

    public function collection()
    {
        return $this->chemicals;
    }

    public function headings(): array
    {
        $headings = [
            'Ime proizvoda',
        ];

        if ($this->showUserColumn) {
            $headings[] = 'Korisnik';
        }

        return array_merge($headings, [
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
        ]);
    }

    public function map($chemical): array
    {
        /** @var Chemical $chemical */

        $row = [
            $chemical->product_name,
        ];

        if ($this->showUserColumn) {
            $row[] = $chemical->user?->name ?? '';
        }

        return array_merge($row, [
        $chemical->cas_number,
        $chemical->ufi_number,
        implode('; ', $this->normalizePictos($chemical->hazard_pictograms)),
        implode(', ', $this->toList($chemical->h_statements)),
        implode(', ', $this->toList($chemical->p_statements)),
        $chemical->usage_location,
        $chemical->annual_quantity,
        $chemical->gvi_kgvi,
        $chemical->voc,
        $chemical->stl_hzjz ? $chemical->stl_hzjz->format('d.m.Y.') : '',
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->chemicals->count() + 1;
                $lastCol = $this->showUserColumn ? 'L' : 'K';

                $sheet->getStyle("A1:{$lastCol}{$lastRow}")
                    ->getFont()
                    ->setName('DejaVu Sans')
                    ->setSize(10);

                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
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

                $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                $sheet->getStyle("A2:{$lastCol}{$lastRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                if ($this->showUserColumn) {
                    $sheet->getStyle("I2:K{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 28,
                        'B' => 18,
                        'C' => 20,
                        'D' => 20,
                        'E' => 20,
                        'F' => 28,
                        'G' => 42,
                        'H' => 24,
                        'I' => 12,
                        'J' => 12,
                        'K' => 10,
                        'L' => 16,
                    ];
                } else {
                    $sheet->getStyle("H2:J{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $widths = [
                        'A' => 28,
                        'B' => 20,
                        'C' => 20,
                        'D' => 20,
                        'E' => 28,
                        'F' => 42,
                        'G' => 24,
                        'H' => 12,
                        'I' => 12,
                        'J' => 10,
                        'K' => 16,
                    ];
                }

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getRowDimension(1)->setRowHeight(26);

                foreach ($this->chemicals as $i => $chemical) {
                    $row = $i + 2;
                    $count = count($this->normalizePictos($chemical->hazard_pictograms));

                    $sheet->getRowDimension($row)->setRowHeight($count > 3 ? 48 : 32);
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");
            },
        ];
    }

    public function drawings(): array
    {
        $drawings = [];

        $pictogramColumn = $this->showUserColumn ? 'E' : 'D';

        foreach ($this->chemicals as $i => $chemical) {
            $row = $i + 2;
            $codes = $this->normalizePictos($chemical->hazard_pictograms);

            foreach ($codes as $idx => $code) {
                $path = $this->findPictogramPath($code);

                if (! $path) {
                    continue;
                }

                $drawing = new Drawing();
                $drawing->setName("picto_{$row}_{$idx}");
                $drawing->setDescription($code);
                $drawing->setPath($path);
                $drawing->setHeight(18);
                $drawing->setCoordinates($pictogramColumn . $row);

                $drawing->setOffsetX(4 + (($idx % 3) * 24));
                $drawing->setOffsetY(4 + (intdiv($idx, 3) * 21));

                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    private function normalizePictos($value): array
{
    return collect($this->toList($value))
        ->map(function ($v) {
            $v = strtoupper(trim((string) $v));
            $v = pathinfo($v, PATHINFO_FILENAME);
            $v = str_replace([' ', '_', '-'], '', $v);

            if (preg_match('/GHS0?([1-9])/', $v, $m)) {
                return 'GHS0' . $m[1];
            }

            return $v;
        })
        ->filter()
        ->unique()
        ->values()
        ->all();
}

    private function toList($value): array
{
    if ($value === null || $value === '') {
        return [];
    }

    if (is_array($value)) {
        return collect($value)
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values()
            ->all();
    }

    return collect(preg_split('/[,\n\r;:]+/', (string) $value) ?: [])
        ->map(fn ($v) => trim((string) $v))
        ->filter()
        ->values()
        ->all();
}

    private function findPictogramPath(string $code): ?string
    {
        $code = strtoupper(trim($code));

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
<?php

namespace App\Exports;

use App\Models\WasteType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WasteTypesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $filters;
    protected int $rowNumber = 0;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = WasteType::query();

        // Soft delete filter (ako koristiš)
        $status = data_get($this->filters, 'status.value');
        $query = match ($status) {
            'trashed' => $query->onlyTrashed(),
            'all'     => $query->withTrashed(),
            default   => $query->withoutTrashed(),
        };

        return $query
            ->orderBy('waste_code')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Redni broj',
            'Ključni broj otpada',
            'Naziv otpada',
            'Opasan otpad',
            'Datum unosa',
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row->waste_code,
            $row->name,
            $row->is_hazardous ? 'DA' : 'NE',
            optional($row->created_at)?->format('d.m.Y. H:i'),
        ];
    }
}
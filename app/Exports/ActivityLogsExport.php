<?php

namespace App\Exports;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ActivityLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected Collection $logs;

    public function __construct()
    {
        $this->logs = ActivityLogResource::getEloquentQuery()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function headings(): array
    {
        return [
            'Vrijeme',
            'Korisnik',
            'Modul',
            'Radnja',
            'Opis aktivnosti',
            'Detalji',
            'IP adresa',
        ];
    }

    public function map($log): array
    {
        return [
            optional($log->created_at)->format('d.m.Y. H:i'),
            $log->user?->name ?? '-',
            $log->module ?? '-',
            match ($log->action) {
                'created' => 'Kreirano',
                'updated' => 'Uređeno',
                'deleted' => 'Obrisano',
                'import' => 'Import',
                'export' => 'Export',
                'status' => 'Status',
                default => $log->action ?? '-',
            },
            $log->title ?? '-',
            $log->description ?? '-',
            $log->ip_address ?? '-',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:G1')->getFont()->setBold(true);

                $sheet->getStyle('A:G')
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_TOP)
                    ->setWrapText(true);

                $sheet->freezePane('A2');
            },
        ];
    }
}
<?php

namespace App\Filament\Resources\Fires\Pages;

use App\Filament\Resources\Fires\FireResource;
use App\Imports\FiresImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use App\Exports\FiresExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ListFires extends ListRecords
{
    protected static string $resource = FireResource::class;

    public function getMaxContentWidth(): ?string
    {
        return 'screen-2xl';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Dodaj Vatrogasni aparat')
                ->icon('heroicon-o-plus'),

            Actions\Action::make('export_pdf')
    ->label('Izvoz u PDF')
    ->icon('heroicon-o-arrow-down-tray')
    ->color('warning')
    ->action(function () {
        $fires = $this->getFilteredSortedTableQuery()->get();

        $pdf = Pdf::loadView('pdf.fires', [
                'fires' => $fires,
            ])
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
                'dpi' => 96,
                'defaultFont' => 'DejaVu Sans',
            ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'vatrogasni-aparati-' . now()->format('Y-m-d') . '.pdf');
    }),

            Actions\Action::make('export_excel')
    ->label('Izvoz u Excel')
    ->icon('heroicon-o-document-arrow-down')
    ->color('success')
    ->action(function () {

        $fireIds = $this->getFilteredSortedTableQuery()
            ->pluck('fires.id')
            ->toArray();

        return Excel::download(
            new FiresExport($fireIds),
            'vatrogasni-aparati-' . now()->format('Y-m-d') . '.xlsx'
        );
    }),

            Actions\Action::make('import_excel')
    ->label('Uvoz iz Excela')
    ->icon('heroicon-o-document-arrow-up')
    ->color('warning')
    ->form([
        FileUpload::make('excel_file')
            ->label('Excel datoteka')
            ->disk('local')
            ->directory('imports')
            ->preserveFilenames()
            ->acceptedFileTypes([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
            ])
            ->required(),
    ])
    ->action(function (array $data): void {
        $path = $data['excel_file'];

        if (is_array($path)) {
            $path = collect($path)->first();
        }

        if ($path instanceof TemporaryUploadedFile) {
            $path = $path->store('imports', 'local');
        }

        if (! Storage::disk('local')->exists($path)) {
            Notification::make()
                ->title('Excel datoteka nije pronađena')
                ->danger()
                ->send();

            return;
        }

        $fullPath = Storage::disk('local')->path($path);

        $import = new FiresImport();

        Excel::import($import, $fullPath);

        $total = $import->created + $import->updated + $import->unchanged + $import->skipped;

        Notification::make()
            ->title('Uvoz vatrogasnih aparata je završen')
            ->body(
                "Ukupno obrađeno: {$total}\n" .
                "Novi zapisi: {$import->created}\n" .
                "Ažurirani zapisi: {$import->updated}\n" .
                "Bez promjene: {$import->unchanged}\n" .
                "Preskočeni redovi: {$import->skipped}"
            )
            ->success()
            ->send();

        $this->resetTable();
    }),
        ];
    }
    protected function getTableQuery(): Builder
{
    $query = parent::getTableQuery();
    $pregled = request()->query('pregled');

    return match ($pregled) {
        'uskoro' => $query->where(function (Builder $q) {
            $q->where(function (Builder $qq) {
                $qq->whereDate('examination_valid_until', '>=', Carbon::today())
                    ->whereDate('examination_valid_until', '<=', Carbon::today()->addDays(30));
            })
            ->orWhere(function (Builder $qq) {
                $qq->whereNotNull('regular_examination_valid_from')
                    ->whereDate(
                        \DB::raw('DATE_ADD(regular_examination_valid_from, INTERVAL 3 MONTH)'),
                        '>=',
                        Carbon::today()
                    )
                    ->whereDate(
                        \DB::raw('DATE_ADD(regular_examination_valid_from, INTERVAL 3 MONTH)'),
                        '<=',
                        Carbon::today()->addDays(30)
                    );
            });
        }),

        'isteklo' => $query->where(function (Builder $q) {
            $q->whereDate('examination_valid_until', '<', Carbon::today())
                ->orWhere(function (Builder $qq) {
                    $qq->whereNotNull('regular_examination_valid_from')
                        ->whereDate(
                            \DB::raw('DATE_ADD(regular_examination_valid_from, INTERVAL 3 MONTH)'),
                            '<',
                            Carbon::today()
                        );
                });
        }),

        default => $query,
    };
}
}
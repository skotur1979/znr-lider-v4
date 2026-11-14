<?php

namespace App\Filament\Resources\PPEEquipment\Pages;

use App\Exports\PPEEquipmentExport;
use App\Filament\Resources\PPEEquipment\PPEEquipmentResource;
use App\Imports\PPEEquipmentImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ListPPEEquipment extends ListRecords
{
    protected static string $resource =
        PPEEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova OZO oprema')
                ->icon('heroicon-o-plus')
                ->color('warning')
                ->visible(
                    fn (): bool =>
                        PPEEquipmentResource::canCreate()
                )
                ->url(
                    PPEEquipmentResource::getUrl(
                        'create'
                    )
                ),

            Action::make('export_excel')
                ->label('Izvoz u Excel')
                ->icon(
                    'heroicon-o-document-arrow-down'
                )
                ->color('success')
                ->visible(
                    fn (): bool =>
                        PPEEquipmentResource::canViewAny()
                )
                ->action(
                    function () {
                        abort_unless(
                            PPEEquipmentResource::canViewAny(),
                            403
                        );

                        $ids =
                            $this
                                ->getFilteredSortedTableQuery()
                                ->pluck(
                                    'ppe_equipments.id'
                                )
                                ->toArray();

                        return Excel::download(
                            new PPEEquipmentExport(
                                $ids
                            ),
                            'registar-ozo-'
                            . now()->format(
                                'Y-m-d'
                            )
                            . '.xlsx'
                        );
                    }
                ),

            Action::make('import_excel')
                ->label('Uvoz iz Excela')
                ->icon(
                    'heroicon-o-document-arrow-up'
                )
                ->color('warning')
                ->visible(
                    fn (): bool =>
                        PPEEquipmentResource::canCreate()
                        && PPEEquipmentResource::allowsModulePermission(
                            'update'
                        )
                )
                ->form([
                    FileUpload::make('excel_file')
                        ->label('Excel datoteka')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required(),
                ])
                ->action(
                    function (
                        array $data
                    ): void {
                        abort_unless(
                            PPEEquipmentResource::canCreate()
                            && PPEEquipmentResource::allowsModulePermission(
                                'update'
                            ),
                            403
                        );

                        $user =
                            auth()->user();

                        abort_unless(
                            $user
                            && ! $user->isSuperAdmin(),
                            403
                        );

                        $ownerId =
                            (int) $user->ownerId();

                        abort_unless(
                            $ownerId > 0,
                            403
                        );

                        $file =
                            $data['excel_file'];

                        if (is_array($file)) {
                            $file =
                                collect($file)
                                    ->first();
                        }

                        if (
                            $file instanceof
                                TemporaryUploadedFile
                        ) {
                            $path =
                                $file->store(
                                    'imports',
                                    'local'
                                );
                        } else {
                            $path =
                                ltrim(
                                    (string) $file,
                                    '/'
                                );
                        }

                        if (
                            blank($path)
                            || ! Storage::disk('local')
                                ->exists($path)
                        ) {
                            Notification::make()
                                ->title(
                                    'Excel datoteka nije pronađena'
                                )
                                ->danger()
                                ->send();

                            return;
                        }

                        $import =
                            new PPEEquipmentImport(
                                $ownerId
                            );

                        try {
                            Excel::import(
                                $import,
                                Storage::disk('local')
                                    ->path($path)
                            );

                            $total =
                                $import->created
                                + $import->updated
                                + $import->unchanged
                                + $import->skipped;

                            Notification::make()
                                ->title(
                                    'Uvoz Registra OZO je završen'
                                )
                                ->body(
                                    "Ukupno obrađeno: {$total}\n"
                                    . "Novi zapisi: {$import->created}\n"
                                    . "Ažurirani zapisi: {$import->updated}\n"
                                    . "Bez promjene: {$import->unchanged}\n"
                                    . "Preskočeni redovi: {$import->skipped}"
                                )
                                ->success()
                                ->send();

                            $this->resetTable();
                        } catch (\Throwable $e) {
                            report($e);

                            Notification::make()
                                ->title(
                                    'Uvoz Registra OZO nije uspio'
                                )
                                ->body(
                                    'Provjeri Excel datoteku i pokušaj ponovno.'
                                )
                                ->danger()
                                ->send();
                        } finally {
                            if (
                                filled($path)
                                && Storage::disk('local')
                                    ->exists($path)
                            ) {
                                Storage::disk('local')
                                    ->delete($path);
                            }
                        }
                    }
                ),
        ];
    }
}
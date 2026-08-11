<?php

namespace App\Filament\Resources\WorkTasks\Pages;

use App\Exports\WorkTasksExport;
use App\Filament\Resources\WorkTasks\WorkTaskResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ListWorkTasks extends ListRecords
{
    protected static string $resource = WorkTaskResource::class;

    public function mount(): void
    {
        parent::mount();

        $status = request()->query('status');

        if ($status === 'open') {
            $this->tableFilters['status']['value'] = 'open';

            return;
        }

        if ($status === 'closed') {
            $this->tableFilters['status']['value'] = 'closed';

            return;
        }

        unset($this->tableFilters['status']);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novi radni zadatak')
                ->icon('heroicon-o-plus')
                ->visible(
                    fn (): bool =>
                        Auth::user() !== null
                        && ! Auth::user()->isSuperAdmin()
                ),

           Action::make('exportExcel')
            ->label('Izvoz u Excel')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->action(function () {
                $taskIds = $this
                    ->getFilteredSortedTableQuery()
                    ->pluck('work_tasks.id')
                    ->toArray();

                return Excel::download(
                    new WorkTasksExport($taskIds),
                    'radni-zadaci-' . now()->format('Y-m-d') . '.xlsx'
                );
            }),
        ];
    }
}
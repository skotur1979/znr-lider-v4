<?php

namespace App\Filament\Resources\Inspections\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Inspections\InspectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInspection extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        InspectionResource::class;

    public function mount(
        int|string $record
    ): void {
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function beforeSave(): void
    {
        $this->haltIfMissingModulePermission(
            'update'
        );
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        /*
         * Ownership nadzora nikada se ne mijenja
         * kroz edit formu.
         */
        unset($data['user_id']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->before(
                    InspectionResource::beforeModulePermission(
                        'delete'
                    )
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}
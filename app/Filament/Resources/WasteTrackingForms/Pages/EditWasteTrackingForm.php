<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Models\OntoRecord;
use App\Services\FormVersionService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWasteTrackingForm extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        WasteTrackingFormResource::class;

    public function mount(
        int|string $record
    ): void {
        parent::mount($record);

        if ($this->record->isLocked()) {
            $this->redirect(
                WasteTrackingFormResource::getUrl(
                    'view',
                    [
                        'record' =>
                            $this->record,
                    ]
                ),
                navigate: true
            );

            return;
        }

        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        ! $this->record->trashed()
                        && WasteTrackingFormResource::canDelete(
                            $this->record
                        )
                )
                ->before(
                    WasteTrackingFormResource::beforeModulePermission(
                        'delete'
                    )
                ),

            RestoreAction::make()
                ->label('Vrati')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this->record->trashed()
                        && WasteTrackingFormResource::canRestore(
                            $this->record
                        )
                )
                ->before(
                    WasteTrackingFormResource::beforeModulePermission(
                        'delete'
                    )
                ),

            ForceDeleteAction::make()
                ->label('Trajno izbriši')
                ->requiresConfirmation()
                ->visible(
                    fn (): bool =>
                        $this->record->trashed()
                        && WasteTrackingFormResource::canForceDelete(
                            $this->record
                        )
                )
                ->before(
                    WasteTrackingFormResource::beforeModulePermission(
                        'delete'
                    )
                ),
        ];
    }

    protected function beforeSave(): void
    {
        $this->haltIfMissingModulePermission(
            'update'
        );

        if ($this->record->isLocked()) {
            $this->halt();
        }
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $ownerId =
            (int) $this->record->user_id;

        if ($ownerId <= 0) {
            abort(403);
        }

        if (
            ! $user->isSuperAdmin()
            && (int) $user->ownerId()
                !== $ownerId
        ) {
            abort(403);
        }

        $data['user_id'] = $ownerId;

        $ontoRecordId =
            (int) (
                $data['onto_record_id']
                ?? 0
            );

        if ($ontoRecordId <= 0) {
            abort(
                403,
                'ONTO obrazac nije ispravno odabran.'
            );
        }

        $validOntoRecord =
            OntoRecord::query()
                ->whereKey(
                    $ontoRecordId
                )
                ->where(
                    'user_id',
                    $ownerId
                )
                ->exists();

        abort_unless(
            $validOntoRecord,
            403,
            'Odabrani ONTO obrazac ne pripada organizaciji ovog pratećeg lista.'
        );

        /*
        * Poštujemo ručni odabir verzije.
        *
        * Ako verzija iz nekog razloga nije poslana
        * iz forme, zadržavamo postojeću.
        */
        $data['form_version'] =
            $data['form_version']
            ?? $this->record->form_version
            ?? FormVersionService::ploForDate(
                $data['handover_date']
                ?? $this->record->handover_date
            );

        if (
            FormVersionService::isCurrentPlo(
                $data['form_version']
            )
        ) {
    /*
     * Nova verzija obrasca više nema
     * ovo polje.
     */
    $data['report_choice'] = null;
}

        /*
         * Novi obrazac više nema
         * report_choice.
         */
        if (
            FormVersionService::isCurrentPlo(
                $data['form_version']
            )
        ) {
            $data['report_choice'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this
            ->getResource()::getUrl(
                'index'
            );
    }
}
<?php

namespace App\Filament\Resources\WasteTrackingForms\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\WasteTrackingForms\WasteTrackingFormResource;
use App\Models\OntoRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditWasteTrackingForm extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        WasteTrackingFormResource::class;

    public function mount(int|string $record): void
    {
        /*
         * Filament prvo učitava stvarni model kroz
         * tenant-scoped Resource query.
         */
        parent::mount($record);

        /*
         * Zaključani PL-O više se ne smije uređivati.
         */
        if ($this->record->isLocked()) {
            $this->redirect(
                WasteTrackingFormResource::getUrl(
                    'view',
                    [
                        'record' => $this->record,
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

        /*
         * Zaključani zapis ne smije biti spremljen
         * ni ako je forma već bila otvorena prije
         * zaključavanja.
         */
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

        /*
        * Ownership postojećeg PL-O zapisa
        * uvijek ostaje nepromijenjen.
        */
        $ownerId = (int) $this->record->user_id;

        if ($ownerId <= 0) {
            abort(403);
        }

        /*
        * Organizacijski korisnik smije uređivati
        * samo zapis svoje organizacije.
        *
        * Superadmin preskače ovu tenant provjeru jer
        * smije administrirati postojeće zapise.
        */
        if (
            ! $user->isSuperAdmin()
            && (int) $user->ownerId() !== $ownerId
        ) {
            abort(403);
        }

        $data['user_id'] = $ownerId;

        /*
        * Ako se mijenja ONTO obrazac,
        * on mora pripadati ISTOJ organizaciji
        * kao postojeći PL-O.
        */
        $ontoRecordId =
            (int) ($data['onto_record_id'] ?? 0);

        if ($ontoRecordId <= 0) {
            abort(
                403,
                'ONTO obrazac nije ispravno odabran.'
            );
        }

        $validOntoRecord = OntoRecord::query()
            ->whereKey($ontoRecordId)
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

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl(
            'index'
        );
    }
}
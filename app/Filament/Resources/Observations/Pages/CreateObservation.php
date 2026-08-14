<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Inspections\InspectionResource;
use App\Filament\Resources\Observations\ObservationResource;
use App\Mail\ObservationNotificationMail;
use App\Models\InspectionFinding;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateObservation extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource = ObservationResource::class;

    protected ?string $returnInspectionEditUrl = null;

    protected ?int $validatedInspectionFindingId = null;

    protected function getFormContentGrid(): ?array
    {
        return [
            'default' => 1,
            'lg' => 1,
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public function mount(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Dozvola za kreiranje
        |--------------------------------------------------------------------------
        */

        if (
            $this->redirectIfMissingModulePermission(
                'create'
            )
        ) {
            return;
        }

        parent::mount();

        /*
        |--------------------------------------------------------------------------
        | Provjera nalaza nadzora
        |--------------------------------------------------------------------------
        |
        | inspection_finding_id dolazi iz URL-a i zato ga ne smijemo
        | prihvatiti bez provjere pripada li nadzor organizaciji
        | trenutnog korisnika.
        |
        */

        $findingId = request()->integer(
            'inspection_finding_id'
        );

        if ($findingId) {
            $finding = InspectionFinding::query()
                ->with('inspection')
                ->whereKey($findingId)
                ->first();

            if (
                $finding?->inspection
                && InspectionResource::getEloquentQuery()
                    ->whereKey($finding->inspection->getKey())
                    ->exists()
            ) {
                $this->validatedInspectionFindingId =
                    (int) $finding->id;

                $this->returnInspectionEditUrl =
                    InspectionResource::getUrl(
                        'edit',
                        [
                            'record' => $finding->inspection,
                        ]
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Početno popunjavanje forme
        |--------------------------------------------------------------------------
        */

        $this->form->fill([
            /*
             * user_id iz URL-a namjerno se ne koristi.
             * Ownership će se postaviti preko fillOwnershipData().
             */
            'incident_date' => request()->query(
                'incident_date'
            ),

            'observation_type' => request()->query(
                'observation_type'
            ),

            'priority' => request()->query(
                'priority'
            ) ?? 'medium',

            'location' => request()->query(
                'location'
            ),

            'item' => request()->query(
                'item'
            ),

            'potential_incident_type' => request()->query(
                'potential_incident_type'
            ),

            'picture_path' => request()->query(
                'picture_path'
            ),

            'action' => request()->query(
                'action'
            ),

            'responsible' => request()->query(
                'responsible'
            ),

            'notification_emails' => request()->query(
                'notification_emails'
            ),

            'target_date' => request()->query(
                'target_date'
            ),

            'status' => request()->query(
                'status'
            ) ?? 'Not started',

            'comments' => request()->query(
                'comments'
            ),
        ]);
    }

    protected function beforeCreate(): void
    {
        /*
         * Dodatna serverska provjera neposredno
         * prije spremanja.
         */
        $this->haltIfMissingModulePermission(
            'create'
        );
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Ownership
        |--------------------------------------------------------------------------
        |
        | Glavni korisnik i podkorisnik spremaju zapis
        | na ownerId organizacije.
        |
        */

        $data = ObservationResource::fillOwnershipData(
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | Zadane vrijednosti
        |--------------------------------------------------------------------------
        */

        if (blank($data['priority'] ?? null)) {
            $data['priority'] = 'medium';
        }

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'Not started';
        }

        /*
        |--------------------------------------------------------------------------
        | E-mail adrese
        |--------------------------------------------------------------------------
        */

        $data['notification_emails'] = collect(
            $data['notification_emails'] ?? []
        )
            ->map(
                fn ($email): string =>
                    trim((string) $email)
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $data;
    }

    protected function afterCreate(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Povezivanje s nalazom nadzora
        |--------------------------------------------------------------------------
        |
        | Koristi se samo ID koji je prethodno prošao
        | tenant provjeru u mount().
        |
        */

        if ($this->validatedInspectionFindingId) {
            $finding = InspectionFinding::query()
                ->whereKey(
                    $this->validatedInspectionFindingId
                )
                ->first();

            if (
                $finding
                && $finding->inspection
                && InspectionResource::getEloquentQuery()
                    ->whereKey($finding->inspection_id)
                    ->exists()
            ) {
                $finding->update([
                    'observation_id' =>
                        $this->record?->id,

                    'workflow_status' =>
                        'converted_to_observation',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Automatsko slanje e-maila
        |--------------------------------------------------------------------------
        */

        $emails = collect(
            $this->record->notification_emails ?? []
        )
            ->map(
                fn ($email): string =>
                    trim((string) $email)
            )
            ->filter()
            ->unique()
            ->values()
            ->all();

        /*
         * Ako nije upisana nijedna adresa,
         * ništa se ne šalje.
         */
        if (empty($emails)) {
            return;
        }

        foreach ($emails as $email) {
            Mail::to($email)->send(
                new ObservationNotificationMail(
                    $this->record
                )
            );
        }

        $this->record->updateQuietly([
            'notification_emails' => $emails,
            'sent_at' => now(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        if (filled($this->returnInspectionEditUrl)) {
            return $this->returnInspectionEditUrl;
        }

        return $this->getResource()::getUrl(
            'index'
        );
    }
}
<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Inspections\InspectionResource;
use App\Filament\Resources\Observations\ObservationResource;
use App\Mail\ObservationNotificationMail;
use App\Models\InspectionFinding;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CreateObservation extends CreateRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource = ObservationResource::class;

    protected ?string $returnInspectionEditUrl = null;

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
         * Kod CreateRecord stranice provjera ide prije
         * parent::mount(), jer još nema zapisa koji Filament
         * treba pretvoriti u Eloquent model.
         */
        if (
            $this->redirectIfMissingModulePermission(
                'create'
            )
        ) {
            return;
        }

        parent::mount();

        $findingId = request()->query(
            'inspection_finding_id'
        );

        if ($findingId) {
            $finding = InspectionFinding::with(
                'inspection'
            )->find($findingId);

            if ($finding?->inspection) {
                $this->returnInspectionEditUrl =
                    InspectionResource::getUrl(
                        'edit',
                        [
                            'record' => $finding->inspection,
                        ]
                    );
            }
        }

        $this->form->fill([
            'user_id' => request()->query('user_id'),
            'incident_date' => request()->query(
                'incident_date'
            ),
            'observation_type' => request()->query(
                'observation_type'
            ),
            'priority' => request()->query(
                'priority'
            ) ?? 'medium',
            'location' => request()->query('location'),
            'item' => request()->query('item'),
            'potential_incident_type' => request()->query(
                'potential_incident_type'
            ),
            'picture_path' => request()->query(
                'picture_path'
            ),
            'action' => request()->query('action'),
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
            'comments' => request()->query('comments'),
        ]);
    }

    protected function beforeCreate(): void
    {
        /*
         * Dodatna serverska provjera neposredno prije
         * spremanja zapisa.
         */
        $this->haltIfMissingModulePermission(
            'create'
        );
    }

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        /*
         * Podkorisnik i glavni korisnik zapise spremaju
         * na ownerId organizacije.
         */
        $data = ObservationResource::fillOwnershipData(
            $data
        );

        if (blank($data['priority'] ?? null)) {
            $data['priority'] = 'medium';
        }

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'Not started';
        }

        /*
         * Očisti prazne i ponovljene e-mail adrese.
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
        $findingId = request()->query(
            'inspection_finding_id'
        );

        if ($findingId) {
            $finding = InspectionFinding::find(
                $findingId
            );

            if ($finding) {
                $finding->update([
                    'observation_id' =>
                        $this->record?->id,
                    'workflow_status' =>
                        'converted_to_observation',
                ]);
            }
        }

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
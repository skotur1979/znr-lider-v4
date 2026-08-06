<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Resources\Concerns\InteractsWithModulePagePermissions;
use App\Filament\Resources\Observations\ObservationResource;
use App\Mail\ObservationNotificationMail;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditObservation extends EditRecord
{
    use InteractsWithModulePagePermissions;

    protected static string $resource =
        ObservationResource::class;

    protected array $oldData = [];

    protected array $oldNotificationEmails = [];

    public function mount(int|string $record): void
    {
        /*
         * Kod EditRecord stranice parent::mount() mora
         * biti prvi kako bi Filament ID iz URL-a pretvorio
         * u Observation model.
         */
        parent::mount($record);

        $this->redirectIfMissingModulePermission(
            'update'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Prikaži'),

            DeleteAction::make()
                ->label('Deaktiviraj')
                ->requiresConfirmation()
                ->before(
                    ObservationResource::beforeModulePermission(
                        'delete'
                    )
                ),
        ];
    }

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

    protected function beforeSave(): void
    {
        $this->haltIfMissingModulePermission(
            'update'
        );
    }

    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        $this->oldData = $this->record->only([
            'incident_date',
            'observation_type',
            'priority',
            'location',
            'item',
            'potential_incident_type',
            'picture_path',
            'action',
            'responsible',
            'notification_emails',
            'target_date',
            'status',
            'comments',
        ]);

        $this->oldNotificationEmails = collect(
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

        if (blank($data['priority'] ?? null)) {
            $data['priority'] = 'medium';
        }

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'Not started';
        }

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

    protected function afterSave(): void
    {
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
         * Ako nema primatelja, ništa se ne šalje.
         */
        if (empty($emails)) {
            return;
        }

        foreach ($emails as $email) {
            Mail::to($email)->send(
                new ObservationNotificationMail(
                    observation: $this->record,
                    mode: 'updated',
                    oldData: $this->oldData,
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
        return $this->previousUrl
            ?? static::getResource()::getUrl(
                'index'
            );
    }
}
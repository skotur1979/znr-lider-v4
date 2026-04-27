<?php

namespace App\Filament\Resources\Observations\Pages;

use App\Filament\Resources\Observations\ObservationResource;
use App\Mail\ObservationNotificationMail;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditObservation extends EditRecord
{
    protected static string $resource = ObservationResource::class;

    protected array $oldData = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

        if (blank($data['priority'] ?? null)) {
            $data['priority'] = 'medium';
        }

        if (blank($data['status'] ?? null)) {
            $data['status'] = 'Not started';
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $emails = collect($this->record->notification_emails ?? [])
            ->push('prvostupnik@gmail.com')
            ->filter()
            ->unique()
            ->values()
            ->all();

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
}
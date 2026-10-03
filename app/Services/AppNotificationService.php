<?php

namespace App\Services;

use App\Filament\Resources\Observations\ObservationResource;
use App\Models\Observation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AppNotificationService
{
    /*
    |--------------------------------------------------------------------------
    | Kreirano zapažanje
    |--------------------------------------------------------------------------
    */

    public function handleObservationCreated(
        Observation $observation
    ): void {
        if (! $observation->responsible_user_id) {
            return;
        }

        if ($observation->status === 'Complete') {
            return;
        }

        $this->sendObservationAssigned(
            $observation
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Izmijenjeno zapažanje
    |--------------------------------------------------------------------------
    */

    public function handleObservationUpdated(
        Observation $observation
    ): void {
        if ($observation->status === 'Complete') {
            return;
        }

        /*
         * Promijenjen korisnik.
         */
        if (
            $observation->wasChanged(
                'responsible_user_id'
            )
        ) {
            if ($observation->responsible_user_id) {
                $this->sendObservationAssigned(
                    $observation
                );
            }

            return;
        }

        /*
         * Promijenjen rok.
         */
        if (
            $observation->wasChanged('target_date')
            && $observation->responsible_user_id
        ) {
            $this->sendObservationDeadlineChanged(
                $observation
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Nova dodjela
    |--------------------------------------------------------------------------
    */

    public function sendObservationAssigned(
        Observation $observation
    ): bool {
        $recipient = $this->observationRecipient(
            $observation
        );

        if (! $recipient) {
            return false;
        }

        $deadline = $observation->target_date
            ? $observation->target_date->format(
                'd.m.Y.'
            )
            : 'nije zadan';

        Notification::make()
            ->title(
                'Dodijeljena vam je nova radnja'
            )
            ->body(
                $this->observationSummary(
                    $observation
                )
                . "\nRok: {$deadline}"
            )
            ->icon(
                'heroicon-o-clipboard-document-check'
            )
            ->info()
            ->actions([
                $this->openObservationAction(
                    $observation
                ),
            ])
            ->sendToDatabase(
                $recipient
            );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Promjena roka
    |--------------------------------------------------------------------------
    */

    public function sendObservationDeadlineChanged(
        Observation $observation
    ): bool {
        $recipient = $this->observationRecipient(
            $observation
        );

        if (! $recipient) {
            return false;
        }

        if ($observation->target_date) {
            $title =
                'Promijenjen je rok dodijeljene radnje';

            $deadlineText =
                'Novi rok: '
                . $observation->target_date->format(
                    'd.m.Y.'
                );
        } else {
            $title =
                'Uklonjen je rok dodijeljene radnje';

            $deadlineText =
                'Radnja trenutačno nema zadan rok za provedbu.';
        }

        Notification::make()
            ->title($title)
            ->body(
                $this->observationSummary(
                    $observation
                )
                . "\n{$deadlineText}"
            )
            ->icon(
                'heroicon-o-calendar-days'
            )
            ->warning()
            ->actions([
                $this->openObservationAction(
                    $observation
                ),
            ])
            ->sendToDatabase(
                $recipient
            );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Rok za 7 dana
    |--------------------------------------------------------------------------
    */

    public function sendObservationDueSoon(
        Observation $observation
    ): bool {
        $recipient = $this->observationRecipient(
            $observation
        );

        if (
            ! $recipient
            || ! $observation->target_date
        ) {
            return false;
        }

        Notification::make()
            ->title(
                'Približava se rok dodijeljene radnje'
            )
            ->body(
                $this->observationSummary(
                    $observation
                )
                . "\nRok: "
                . $observation->target_date->format(
                    'd.m.Y.'
                )
                . "\nPreostalo: 7 dana"
            )
            ->icon('heroicon-o-clock')
            ->warning()
            ->actions([
                $this->openObservationAction(
                    $observation
                ),
            ])
            ->sendToDatabase(
                $recipient
            );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Rok istekao
    |--------------------------------------------------------------------------
    */

    public function sendObservationOverdue(
        Observation $observation
    ): bool {
        $recipient = $this->observationRecipient(
            $observation
        );

        if (
            ! $recipient
            || ! $observation->target_date
        ) {
            return false;
        }

        $daysOverdue = (int)
            $observation->target_date
                ->copy()
                ->startOfDay()
                ->diffInDays(
                    Carbon::today(
                        'Europe/Zagreb'
                    )
                );

        $daysText = match ($daysOverdue) {
            1 => '1 dan',
            default =>
                $daysOverdue . ' dana',
        };

        Notification::make()
            ->title(
                'Rok dodijeljene radnje je istekao'
            )
            ->body(
                $this->observationSummary(
                    $observation
                )
                . "\nRok: "
                . $observation->target_date->format(
                    'd.m.Y.'
                )
                . "\nIsteklo prije: {$daysText}"
            )
            ->icon(
                'heroicon-o-exclamation-triangle'
            )
            ->danger()
            ->actions([
                $this->openObservationAction(
                    $observation
                ),
            ])
            ->sendToDatabase(
                $recipient
            );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Sistemska obavijest
    |--------------------------------------------------------------------------
    */

    public function sendSystemNotification(
        iterable $recipients,
        string $title,
        string $body
    ): int {
        $sent = 0;

        foreach ($recipients as $recipient) {
            if (
                ! $recipient instanceof User
            ) {
                continue;
            }

            if (
                ! $recipient->is_active
                || $recipient->trashed()
            ) {
                continue;
            }

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon(
                    'heroicon-o-bell-alert'
                )
                ->info()
                ->sendToDatabase(
                    $recipient
                );

            $sent++;
        }

        return $sent;
    }

    /*
    |--------------------------------------------------------------------------
    | Primatelj zapažanja
    |--------------------------------------------------------------------------
    */

    private function observationRecipient(
        Observation $observation
    ): ?User {
        $recipient =
            $observation->responsibleUser;

        if (! $recipient) {
            return null;
        }

        if (
            ! $recipient->is_active
            || $recipient->trashed()
        ) {
            return null;
        }

        /*
         * Superadmin ne prima dodijeljene
         * organizacijske radnje.
         */
        if ($recipient->isSuperAdmin()) {
            return null;
        }

        $observationOwnerId =
            $observation->user?->ownerId()
            ?? (int) $observation->user_id;

        /*
         * Multi-tenant zaštita.
         */
        if (
            (int) $recipient->ownerId()
            !== (int) $observationOwnerId
        ) {
            return null;
        }

        /*
         * Korisnik mora imati pregled
         * Zapažanja.
         */
        if (
            ! $recipient
                ->canViewModuleRecords(
                    'observations'
                )
        ) {
            return null;
        }

        return $recipient;
    }

    /*
    |--------------------------------------------------------------------------
    | Tekst zapažanja
    |--------------------------------------------------------------------------
    */

    private function observationSummary(
        Observation $observation
    ): string {
        $description = trim(
            strip_tags(
                (string) $observation->item
            )
        );

        $description =
            $description !== ''
                ? Str::limit(
                    $description,
                    120
                )
                : 'Zapažanje #'
                    . $observation->getKey();

        $location = trim(
            (string) $observation->location
        );

        if ($location !== '') {
            return
                $description
                . "\nLokacija: "
                . Str::limit(
                    $location,
                    80
                );
        }

        return $description;
    }

    /*
    |--------------------------------------------------------------------------
    | Otvori zapažanje
    |--------------------------------------------------------------------------
    */

    private function openObservationAction(
        Observation $observation
    ): Action {
        return Action::make(
            'open_observation'
        )
            ->label(
                'Otvori zapažanje'
            )
            ->button()
            ->url(
                ObservationResource::getUrl(
                    'view',
                    [
                        'record' =>
                            $observation,
                    ]
                )
            )
            ->markAsRead();
    }
}
<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\AppNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Observation extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule = 'Zapažanja';

    protected $table = 'observations';

    protected $fillable = [
        'user_id',

        'source',
        'source_qr_code_id',

        'incident_date',
        'observation_type',
        'priority',
        'location',
        'item',
        'potential_incident_type',
        'picture_path',
        'action',
        'responsible',
        'responsible_user_id',
        'notification_emails',
        'sent_at',
        'target_date',
        'status',
        'comments',
        'reporter_contact',
        'voice_note',
        'completed_at',
        'due_soon_notified_for',
        'overdue_notified_for',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'target_date' => 'date',
        'notification_emails' => 'array',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_soon_notified_for' => 'date',
        'overdue_notified_for' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'responsible_user_id'
        );
    }

    public function sourceQrCode(): BelongsTo
    {
        return $this->belongsTo(
            QrCode::class,
            'source_qr_code_id'
        );
    }

    public function isPublicQrReport(): bool
    {
        return $this->source === 'qr_public';
    }

    protected static function booted(): void
    {
        static::saving(
            function (Observation $observation): void {

                /*
                |--------------------------------------------------------------------------
                | Datum zatvaranja
                |--------------------------------------------------------------------------
                */

                if (
                    $observation->status !== 'Complete'
                ) {
                    $observation->completed_at = null;
                }

                /*
                |--------------------------------------------------------------------------
                | Sigurnosna provjera dodijeljenog korisnika
                |--------------------------------------------------------------------------
                |
                | Korisnik kojem se dodjeljuje zapažanje mora:
                |
                | - biti aktivan
                | - pripadati istoj organizaciji
                | - ne smije biti superadmin
                | - imati pravo pregleda modula Zapažanja
                |
                */

                if (
                    $observation->isDirty('responsible_user_id')
                    && $observation->responsible_user_id
                ) {
                    $recipient = User::query()->find(
                        $observation->responsible_user_id
                    );

                    $owner = User::query()->find(
                        $observation->user_id
                    );

                    $ownerId = $owner?->ownerId()
                        ?? (int) $observation->user_id;

                    $validRecipient =
                        $recipient
                        && ! $recipient->isSuperAdmin()
                        && $recipient->is_active
                        && (int) $recipient->ownerId()
                            === (int) $ownerId
                        && $recipient->canViewModuleRecords(
                            'observations'
                        );

                    if (! $validRecipient) {
                        $observation->responsible_user_id = null;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Reset oznaka podsjetnika
                |--------------------------------------------------------------------------
                |
                | Ako promijenimo korisnika ili rok, novi rok ponovno
                | mora moći generirati odgovarajuću obavijest.
                |
                */

                if (
                    $observation->isDirty('responsible_user_id')
                    || $observation->isDirty('target_date')
                ) {
                    $observation->due_soon_notified_for = null;
                    $observation->overdue_notified_for = null;
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Novo zapažanje
        |--------------------------------------------------------------------------
        */

        static::created(
            function (Observation $observation): void {
                app(AppNotificationService::class)
                    ->handleObservationCreated(
                        $observation
                    );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Izmjena zapažanja
        |--------------------------------------------------------------------------
        */

        static::updated(
            function (Observation $observation): void {
                app(AppNotificationService::class)
                    ->handleObservationUpdated(
                        $observation
                    );
            }
        );
    }
}
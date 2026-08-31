<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Observation extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static string $activityModule =
        'Zapažanja';

    protected $table =
        'observations';

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
        'notification_emails',
        'sent_at',
        'target_date',
        'status',
        'comments',
        'reporter_contact',
        'voice_note',
        'completed_at',
    ];

    protected $casts = [
        'incident_date' =>
            'date',

        'target_date' =>
            'date',

        'notification_emails' =>
            'array',

        'sent_at' =>
            'datetime',

        'completed_at' =>
            'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
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
        return $this->source
            === 'qr_public';
    }

    protected static function booted(): void
    {
        static::saving(
            function (
                Observation $observation
            ): void {

                if (
                    $observation->status
                    !== 'Complete'
                ) {
                    $observation->completed_at =
                        null;
                }
            }
        );
    }
}
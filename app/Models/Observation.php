<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Observation extends Model
{
    use SoftDeletes;

    protected $table = 'observations';

    protected $fillable = [
        'user_id',
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
        'voice_note',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'target_date' => 'date',
        'notification_emails' => 'array',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Incident extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'location',
        'type_of_incident',
        'permanent_or_temporary',
        'date_occurred',
        'date_of_return',
        'working_days_lost',
        'causes_of_injury',
        'accident_injury_type',
        'injured_body_part',
        'image_path',
        'other',
        'investigation_report',
    ];

    protected $casts = [
        'date_occurred' => 'date',
        'date_of_return' => 'date',
        'investigation_report' => 'array',
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
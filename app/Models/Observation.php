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
        'location',
        'item',
        'potential_incident_type',
        'picture_path',
        'action',
        'responsible',
        'target_date',
        'status',
        'comments',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'target_date'   => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

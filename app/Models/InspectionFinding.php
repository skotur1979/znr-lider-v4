<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionFinding extends Model
{
    protected $fillable = [
        'inspection_id',
        'category',
        'title',
        'description',
        'finding_status',
        'workflow_status',
        'action_required',
        'responsible_person',
        'due_date',
        'photo_path',
        'observation_id',
        'resolved_at',
        'resolution_note',
    ];

    protected $casts = [
        'action_required' => 'boolean',
        'due_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
    }
}

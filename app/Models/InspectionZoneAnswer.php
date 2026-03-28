<?php

namespace App\Models;

use App\Services\FiveSScoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionZoneAnswer extends Model
{
    protected $table = 'inspection_answers';

    protected $fillable = [
        'inspection_id',
        'inspection_zone_id',
        'inspection_question_id',
        'score',
        'note',
        'photo_path',
        'action_required',
        'responsible_person',
        'due_date',
        'finding_status',
        'observation_id',
        'resolved_at',
    ];

    protected $attributes = [
        'score' => 0,
    ];

    protected $casts = [
        'score' => 'integer',
        'action_required' => 'boolean',
        'due_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
{
    static::saved(function (InspectionZoneAnswer $answer) {
        if ($answer->zone) {
            app(FiveSScoreService::class)->recalculateZone($answer->zone);
            $answer->zone->inspection?->refreshFiveSScore();
        }
    });

    static::deleted(function (InspectionZoneAnswer $answer) {
        if ($answer->zone) {
            app(FiveSScoreService::class)->recalculateZone($answer->zone);
            $answer->zone->inspection?->refreshFiveSScore();
        }
    });
}

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(InspectionZone::class, 'inspection_zone_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(InspectionQuestion::class, 'inspection_question_id');
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(Observation::class);
    }
}
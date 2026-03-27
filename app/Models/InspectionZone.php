<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionZone extends Model
{
    protected $fillable = [
        'name',
        'note',
    ];

    protected $appends = [
        'total_points',
        'max_points',
        'percentage',
    ];

    protected static function booted(): void
    {
        static::retrieved(function (InspectionZone $zone) {
            $zone->ensureAnswersExist();
        });
    }

    public function questions(): HasMany
    {
        return $this->hasMany(InspectionQuestion::class, 'inspection_zone_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(InspectionZoneAnswer::class, 'inspection_zone_id');
    }

    public function ensureAnswersExist(): void
    {
        $questionIds = $this->questions()
            ->pluck('id')
            ->toArray();

        if (empty($questionIds)) {
            return;
        }

        $existingQuestionIds = $this->answers()
            ->pluck('inspection_question_id')
            ->toArray();

        foreach ($questionIds as $questionId) {
            if (! in_array($questionId, $existingQuestionIds, true)) {
                $this->answers()->create([
                    'inspection_question_id' => $questionId,
                    'score' => 0,
                ]);
            }
        }
    }

    public function getTotalPointsAttribute(): int
    {
        return (int) $this->answers()->sum('score');
    }

    public function getMaxPointsAttribute(): int
    {
        return $this->questions()->count() * 5;
    }

    public function getPercentageAttribute(): float
    {
        $max = $this->max_points;

        if ($max === 0) {
            return 0;
        }

        return round(($this->total_points / $max) * 100, 2);
    }
}
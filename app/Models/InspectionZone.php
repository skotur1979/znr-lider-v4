<?php

namespace App\Models;

use App\Services\InspectionZoneTemplateService;
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
        static::created(function (InspectionZone $zone) {
            app(InspectionZoneTemplateService::class)->syncQuestionsAndAnswers($zone);
        });

        static::retrieved(function (InspectionZone $zone) {
            app(InspectionZoneTemplateService::class)->syncQuestionsAndAnswers($zone);
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
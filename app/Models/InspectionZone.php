<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\InspectionZoneTemplateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionZone extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Zone nadzora';

    protected $fillable = [
        'inspection_id',
        'name',
        'sort_order',
        'total_points',
        'max_points',
        'percentage',
        'note',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'total_points' => 'float',
        'max_points' => 'float',
        'percentage' => 'float',
    ];

    protected $appends = [
        'total_points',
        'max_points',
        'percentage',
    ];

    protected static function booted(): void
    {
        /*
         * Pitanja i odgovore sinkroniziramo kada se
         * kreira nova zona.
         *
         * Namjerno NE koristimo retrieved(),
         * jer samo otvaranje/pregled zapisa ne smije
         * uzrokovati upise u bazu.
         */
        static::created(function (InspectionZone $zone): void {
            app(InspectionZoneTemplateService::class)
                ->syncQuestionsAndAnswers($zone);
        });
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(
            InspectionQuestion::class,
            'inspection_zone_id'
        );
    }

    public function answers(): HasMany
    {
        return $this->hasMany(
            InspectionZoneAnswer::class,
            'inspection_zone_id'
        );
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

        return round(
            ($this->total_points / $max) * 100,
            2
        );
    }
}
<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\FiveSScoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionZoneAnswer extends Model
{
    use LogsActivity;

    protected static string $activityModule =
        '5S odgovori';

    protected $table =
        'inspection_answers';

    protected $fillable = [
        'inspection_zone_id',
        'inspection_question_id',
        'score',
    ];

    protected $attributes = [
        'score' => 0,
    ];

    protected $casts = [
        'score' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(
            function (
                InspectionZoneAnswer $answer
            ): void {
                $zone =
                    $answer->zone;

                if (! $zone) {
                    return;
                }

                app(
                    FiveSScoreService::class
                )->recalculateZone(
                    $zone
                );

                $zone
                    ->inspection
                    ?->refreshFiveSScore();
            }
        );

        static::deleted(
            function (
                InspectionZoneAnswer $answer
            ): void {
                $zone =
                    $answer->zone;

                if (! $zone) {
                    return;
                }

                app(
                    FiveSScoreService::class
                )->recalculateZone(
                    $zone
                );

                $zone
                    ->inspection
                    ?->refreshFiveSScore();
            }
        );
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(
            InspectionZone::class,
            'inspection_zone_id'
        );
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(
            InspectionQuestion::class,
            'inspection_question_id'
        );
    }
}
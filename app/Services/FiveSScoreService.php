<?php

namespace App\Services;

use App\Models\InspectionZone;

class FiveSScoreService
{
    public function recalculateZone(?InspectionZone $zone): void
    {
        if (! $zone) {
            return;
        }

        $zone->loadMissing([
            'questions',
            'answers',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Ako u bazi NEMAŠ kolone total_points, max_points, percentage,
        | onda nemoj ništa spremati u bazu.
        | Ovaj servis samo "osvježi" model u memoriji.
        |--------------------------------------------------------------------------
        */

        $zone->setAttribute('total_points', (int) $zone->answers->sum('score'));
        $zone->setAttribute('max_points', (int) $zone->questions->count() * 5);

        $maxPoints = (int) $zone->getAttribute('max_points');

        $zone->setAttribute(
            'percentage',
            $maxPoints > 0
                ? round(((int) $zone->getAttribute('total_points') / $maxPoints) * 100, 2)
                : 0
        );
    }
}
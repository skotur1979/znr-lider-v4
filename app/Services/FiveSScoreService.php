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
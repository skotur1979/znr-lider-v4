<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionAnswer extends Model
{
    use LogsActivity;

    protected static string $activityModule = '5S odgovori';

    protected $fillable = [
        'inspection_zone_id',
        'inspection_question_id',
        'score',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(InspectionQuestion::class, 'inspection_question_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(InspectionZone::class, 'inspection_zone_id');
    }
}
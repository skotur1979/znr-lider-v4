<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionAnswer extends Model
{
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

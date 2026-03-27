<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InspectionQuestion extends Model
{
    protected $fillable = [
        'inspection_zone_id',
        'section',
        'question',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(InspectionZone::class, 'inspection_zone_id');
    }

    public function answer(): HasOne
    {
        return $this->hasOne(InspectionZoneAnswer::class, 'inspection_question_id');
    }

    public function getSectionLabelAttribute(): string
    {
        return $this->section ?: '-';
    }
}
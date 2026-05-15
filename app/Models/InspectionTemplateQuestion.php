<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectionTemplateQuestion extends Model
{
    use LogsActivity;

    protected static string $activityModule = '5S predlošci';

    protected $fillable = [
        'section',
        'code',
        'question',
        'max_score',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_score' => 'integer',
        'sort_order' => 'integer',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(InspectionZoneAnswer::class);
    }

    public function getSectionLabelAttribute(): string
    {
        return match ($this->section) {
            'sortiranje' => 'Sortiranje',
            'slaganje' => 'Slaganje',
            'sjaj' => 'Sjaj',
            'standardiziranje' => 'Standardiziranje',
            'samoodrzavanje' => 'Samoodržavanje',
            default => ucfirst((string) $this->section),
        };
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiValue extends Model
{
    protected $fillable = [
        'kpi_id',
        'month',
        'year',
        'value',
        'auto_generated',
        'source_label',
        'note',
    ];

    protected $casts = [
        'value' => 'float',
        'auto_generated' => 'boolean',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function getMonthYearLabelAttribute(): string
    {
        return sprintf('%02d/%s', $this->month, $this->year);
    }
}

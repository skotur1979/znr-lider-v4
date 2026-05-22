<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiValue extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'KPI vrijednosti';

    protected $fillable = [
        'kpi_id',
        'user_id',
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
    public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
}
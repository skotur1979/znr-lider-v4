<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTargetOverride extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'KPI ciljevi';

    protected $fillable = [
        'kpi_id',
        'user_id',
        'target_value',
        'warning_offset',
    ];

    protected $casts = [
        'target_value' => 'float',
        'warning_offset' => 'float',
    ];

    public function kpi(): BelongsTo
    {
        return $this->belongsTo(Kpi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
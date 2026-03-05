<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PPEItem extends Model
{
    protected $table = 'personal_protective_equipment_items';

    protected $fillable = [
        'personal_protective_equipment_log_id',
        'equipment_name',
        'standard',
        'size',
        'duration_months',
        'issue_date',
        'end_date',
        'return_date',
        'signature',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'end_date' => 'date',
        'return_date' => 'date',
        'duration_months' => 'integer',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(PPELog::class, 'personal_protective_equipment_log_id', 'id');
    }
}
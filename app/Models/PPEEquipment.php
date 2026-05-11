<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PPEEquipment extends Model
{
    protected $table = 'ppe_equipments';

    protected $fillable = [
        'user_id',
        'name',
        'standard',
        'duration_months',
        'is_active',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

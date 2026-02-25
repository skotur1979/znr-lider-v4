<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirstAidItem extends Model
{
    protected $fillable = [
        'first_aid_kit_id',
        'material_type',
        'purpose',
        'valid_until',
    ];

    protected $casts = [
        'valid_until' => 'date',
    ];

    public function kit(): BelongsTo
    {
        return $this->belongsTo(FirstAidKit::class, 'first_aid_kit_id');
    }
    public function items(): HasMany
{
    return $this->hasMany(FirstAidItem::class, 'first_aid_kit_id');
}
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FirstAidKit extends Model
{
    protected $fillable = [
        'user_id',
        'location',
        'inspected_at',
        'note',
    ];

    protected $casts = [
        'inspected_at' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FirstAidItem::class);
    }
}
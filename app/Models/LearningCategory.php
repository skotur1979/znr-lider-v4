<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningCategory extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Kategorije edukacija';

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'is_global',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LearningMaterial::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
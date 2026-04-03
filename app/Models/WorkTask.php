<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class WorkTask extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'due_date',
        'is_done',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_done' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeMine(Builder $query): Builder
    {
        if (Auth::user()?->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', Auth::id());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_done', false);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('is_done', true);
    }
}

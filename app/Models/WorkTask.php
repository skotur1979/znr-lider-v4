<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class WorkTask extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Radni zadaci';

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

    /**
     * Radni zadaci su organizacijski zapisi.
     *
     * Superadmin vidi sve.
     *
     * Glavni korisnik i podkorisnici vide
     * zadatke svoje organizacije.
     */
    public function scopeMine(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        $ownerId = $user->ownerId();

        if (! $ownerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            'user_id',
            $ownerId
        );
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(
            'is_done',
            false
        );
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where(
            'is_done',
            true
        );
    }
}
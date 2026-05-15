<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Budžet';

    protected $fillable = [
        'user_id',
        'godina',
        'ukupni_budget',
    ];

    protected $casts = [
        'godina' => 'integer',
        'ukupni_budget' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // ✅ suma kao u v2 (realizirano=true i iznos)
    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->expenses()
            ->where('realizirano', true)
            ->sum('iznos');
    }

    public function getRemainingAttribute(): float
    {
        return (float) $this->ukupni_budget - (float) $this->total_expenses;
    }

    public function getFilamentTitleAttribute(): string
    {
        return (string) $this->godina;
    }
}
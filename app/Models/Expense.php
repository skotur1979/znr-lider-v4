<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Troškovi';

    protected $fillable = [
        'user_id',
        'budget_id',
        'category_id',
        'mjesec',
        'naziv_troska',
        'iznos',
        'dobavljac',
        'realizirano',
    ];

    protected $casts = [
        'iznos'       => 'decimal:2',
        'realizirano' => 'boolean',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Test extends Model
{
    use LogsActivity;

    protected static string $activityModule = 'Testovi';

    protected $fillable = [
        'user_id',
        'naziv',
        'sifra',
        'opis',
        'minimalni_prolaz',
    ];

    protected $casts = [
        'minimalni_prolaz' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }
}

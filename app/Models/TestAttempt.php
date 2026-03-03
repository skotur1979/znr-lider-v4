<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestAttempt extends Model
{
    protected $fillable = [
        'test_id',
        'user_id',
        'ime_prezime',
        'radno_mjesto',
        'datum_rodjenja',
        'bodovi_osvojeni',
        'rezultat',
        'prolaz',
    ];

    protected $casts = [
        'datum_rodjenja' => 'date',
        'bodovi_osvojeni' => 'integer',
        'rezultat' => 'decimal:2',
        'prolaz' => 'boolean',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function odgovori(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class, 'test_attempt_id');
    }
}
